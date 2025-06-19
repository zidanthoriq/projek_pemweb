<?php
require_once '../config/database.php';
requireAdmin();

$database = new Database();
$db = $database->getConnection();

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $registration_id = isset($_POST['registration_id']) ? (int)$_POST['registration_id'] : 0;
    $action = isset($_POST['action']) ? $_POST['action'] : '';
    
    if ($registration_id <= 0 || empty($action)) {
        $_SESSION['error'] = "Data tidak valid.";
        header('Location: dashboard_admin.php');
        exit();
    }
    
    try {
        // Get registration details
        $registration_query = "SELECT r.*, u.full_name, e.title as event_title, e.max_participants 
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
            // Check if already approved
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
            // Check if already processed
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
            
        } else {
            $_SESSION['error'] = "Aksi tidak valid.";
        }
        
    } catch(PDOException $exception) {
        $_SESSION['error'] = "Error database: " . $exception->getMessage();
    }
    
} else {
    $_SESSION['error'] = "Metode request tidak valid.";
}

header('Location: dashboard_admin.php');
exit();
?>
