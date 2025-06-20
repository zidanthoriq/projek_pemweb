<?php
require_once '../config/database.php';
requireLogin();

$database = new Database();
$db = $database->getConnection();

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    if (isset($_POST['event_id'])) {
        $event_id = (int)$_POST['event_id'];
        $user_id = $_SESSION['user_id'];
        
        if ($event_id <= 0) {
            $_SESSION['error'] = "Event tidak valid.";
            header('Location: ../user/search_events.php');
            exit();
        }
        
        try {
            // Check if event exists and is active
            $event_query = "SELECT id, title, max_participants, event_date, status FROM events WHERE id = :event_id AND status = 'active'";
            $event_stmt = $db->prepare($event_query);
            $event_stmt->bindParam(':event_id', $event_id);
            $event_stmt->execute();
            
            if ($event_stmt->rowCount() == 0) {
                $_SESSION['error'] = "Event tidak ditemukan atau tidak aktif.";
                header('Location: ../user/search_events.php');
                exit();
            }
            
            $event = $event_stmt->fetch(PDO::FETCH_ASSOC);
            
            // Check if user already registered for this event
            $existing_query = "SELECT id, status FROM registrations WHERE user_id = :user_id AND event_id = :event_id";
            $existing_stmt = $db->prepare($existing_query);
            $existing_stmt->bindParam(':user_id', $user_id);
            $existing_stmt->bindParam(':event_id', $event_id);
            $existing_stmt->execute();
            
            if ($existing_stmt->rowCount() > 0) {
                $existing = $existing_stmt->fetch(PDO::FETCH_ASSOC);
                $status_text = '';
                switch($existing['status']) {
                    case 'pending':
                        $status_text = 'menunggu persetujuan';
                        break;
                    case 'approved':
                        $status_text = 'sudah disetujui';
                        break;
                    case 'rejected':
                        $status_text = 'ditolak';
                        break;
                    case 'cancelled':
                        $status_text = 'dibatalkan';
                        break;
                }
                $_SESSION['error'] = "Anda sudah terdaftar untuk event ini dengan status: " . $status_text;
                header('Location: ../user/event_details.php?id=' . $event_id);
                exit();
            }
            
            // Check if event is full
            $participants_query = "SELECT COUNT(*) as current_participants FROM registrations WHERE event_id = :event_id AND status = 'approved'";
            $participants_stmt = $db->prepare($participants_query);
            $participants_stmt->bindParam(':event_id', $event_id);
            $participants_stmt->execute();
            $participants_result = $participants_stmt->fetch(PDO::FETCH_ASSOC);
            
            if ($participants_result['current_participants'] >= $event['max_participants']) {
                $_SESSION['error'] = "Maaf, kuota untuk event ini sudah penuh.";
                header('Location: ../user/event_details.php?id=' . $event_id);
                exit();
            }
            
            // Check registration deadline (2 days before event)
            $registration_deadline = date('Y-m-d H:i:s', strtotime($event['event_date'] . ' -2 days'));
            if (date('Y-m-d H:i:s') >= $registration_deadline) {
                $_SESSION['error'] = "Maaf, pendaftaran untuk event ini sudah ditutup.";
                header('Location: ../user/event_details.php?id=' . $event_id);
                exit();
            }
            
            // Insert new registration
            $insert_query = "INSERT INTO registrations (user_id, event_id, registration_date, status) VALUES (:user_id, :event_id, NOW(), 'pending')";
            $insert_stmt = $db->prepare($insert_query);
            $insert_stmt->bindParam(':user_id', $user_id);
            $insert_stmt->bindParam(':event_id', $event_id);
            
            if ($insert_stmt->execute()) {
                $_SESSION['success'] = "Pendaftaran berhasil! Status pendaftaran Anda sedang menunggu persetujuan admin.";
                header('Location: ../user/event_details.php?id=' . $event_id);
            } else {
                $_SESSION['error'] = "Terjadi kesalahan saat mendaftar. Silakan coba lagi.";
                header('Location: ../user/event_details.php?id=' . $event_id);
            }
            
        } catch(PDOException $exception) {
            $_SESSION['error'] = "Error database: " . $exception->getMessage();
            header('Location: ../user/event_details.php?id=' . $event_id);
        }
        
    } elseif (isset($_POST['registration_id']) && isset($_POST['action'])) {
        // Check if user is admin
        if ($_SESSION['role'] !== 'admin') {
            $_SESSION['error'] = "Akses ditolak. Hanya admin yang dapat melakukan aksi ini.";
            header('Location: ../user/dashboard_user.php');
            exit();
        }
        
        $registration_id = (int)$_POST['registration_id'];
        $action = $_POST['action'];
        
        if ($registration_id <= 0 || !in_array($action, ['approve', 'reject'])) {
            $_SESSION['error'] = "Data tidak valid.";
            header('Location: dashboard_admin.php');
            exit();
        }
        
        try {
            // Get registration details
            $registration_query = "SELECT r.*, u.full_name, u.email, e.title as event_title, e.max_participants 
                                  FROM registrations r 
                                  JOIN users u ON r.user_id = u.id 
                                  JOIN events e ON r.event_id = e.id 
                                  WHERE r.id = :registration_id";
            $registration_stmt = $db->prepare($registration_query);
            $registration_stmt->bindParam(':registration_id', $registration_id);
            $registration_stmt->execute();
            
            if ($registration_stmt->rowCount() == 0) {
                $_SESSION['error'] = "Pendaftaran tidak ditemukan.";
                header('Location: dashboard_admin.php');
                exit();
            }
            
            $registration = $registration_stmt->fetch(PDO::FETCH_ASSOC);
            
            if ($action == 'approve') {
                if ($registration['status'] == 'approved') {
                    $_SESSION['error'] = "Pendaftaran sudah disetujui sebelumnya.";
                    header('Location: dashboard_admin.php');
                    exit();
                }
                
                // Check event capacity
                $capacity_query = "SELECT COUNT(*) as approved_count FROM registrations WHERE event_id = :event_id AND status = 'approved'";
                $capacity_stmt = $db->prepare($capacity_query);
                $capacity_stmt->bindParam(':event_id', $registration['event_id']);
                $capacity_stmt->execute();
                $capacity_result = $capacity_stmt->fetch(PDO::FETCH_ASSOC);
                
                if ($capacity_result['approved_count'] >= $registration['max_participants']) {
                    $_SESSION['error'] = "Kuota event sudah penuh. Tidak dapat menyetujui pendaftaran ini.";
                    header('Location: dashboard_admin.php');
                    exit();
                }
                
                // Approve registration
                $update_query = "UPDATE registrations SET status = 'approved' WHERE id = :registration_id";
                $update_stmt = $db->prepare($update_query);
                $update_stmt->bindParam(':registration_id', $registration_id);
                
                if ($update_stmt->execute()) {
                    $_SESSION['success'] = "Pendaftaran " . htmlspecialchars($registration['full_name']) . " untuk event '" . htmlspecialchars($registration['event_title']) . "' berhasil disetujui.";
                } else {
                    $_SESSION['error'] = "Gagal menyetujui pendaftaran.";
                }
                
            } elseif ($action == 'reject') {
                if ($registration['status'] != 'pending') {
                    $_SESSION['error'] = "Pendaftaran sudah diproses sebelumnya.";
                    header('Location: dashboard_admin.php');
                    exit();
                }
                
                // Reject registration
                $update_query = "UPDATE registrations SET status = 'rejected' WHERE id = :registration_id";
                $update_stmt = $db->prepare($update_query);
                $update_stmt->bindParam(':registration_id', $registration_id);
                
                if ($update_stmt->execute()) {
                    $_SESSION['success'] = "Pendaftaran " . htmlspecialchars($registration['full_name']) . " untuk event '" . htmlspecialchars($registration['event_title']) . "' berhasil ditolak.";
                } else {
                    $_SESSION['error'] = "Gagal menolak pendaftaran.";
                }
            }
            
        } catch(PDOException $exception) {
            $_SESSION['error'] = "Error database: " . $exception->getMessage();
        }
        
        header('Location: dashboard_admin.php');
        exit();
        
    } else {
        $_SESSION['error'] = "Data tidak valid.";
        header('Location: ../user/search_events.php');
        exit();
    }
    
} else {

    header('Location: ../user/search_events.php');
    exit();
}
?>
