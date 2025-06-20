<?php
require_once '../config/database.php';
requireLogin();

// Check if user is admin
if ($_SESSION['role'] !== 'admin') {
    header('Location: ../user/dashboard_user.php');
    exit();
}

$database = new Database();
$db = $database->getConnection();

// Handle registration status update
if ($_POST && isset($_POST['action'])) {
    $registration_id = (int)$_POST['registration_id'];
    $action = $_POST['action'];
    
    try {
        if ($action === 'approve') {
            $update_query = "UPDATE registrations SET status = 'approved' WHERE id = :id";
        } elseif ($action === 'reject') {
            $update_query = "UPDATE registrations SET status = 'rejected' WHERE id = :id";
        } elseif ($action === 'cancel') {
            // Use 'rejected' instead of 'cancelled' if column is too small
            // Or make sure database column can handle 'cancelled'
            $update_query = "UPDATE registrations SET status = 'cancelled' WHERE id = :id";
        }
        
        if (isset($update_query)) {
            $update_stmt = $db->prepare($update_query);
            $update_stmt->bindParam(':id', $registration_id);
            
            if ($update_stmt->execute()) {
                $success_message = "Status pendaftaran berhasil diperbarui.";
            } else {
                $error_message = "Gagal memperbarui status pendaftaran.";
            }
        }
    } catch(PDOException $exception) {
        $error_message = "Error: " . $exception->getMessage();
        
        // If the error is about data truncation, provide more specific message
        if (strpos($exception->getMessage(), 'Data truncated') !== false) {
            $error_message = "Error: Kolom status di database perlu diperbaiki. Silakan jalankan script perbaikan database.";
        }
    }
}

// Get filter parameters
$status_filter = isset($_GET['status']) ? sanitizeInput($_GET['status']) : '';
$event_filter = isset($_GET['event']) ? (int)$_GET['event'] : 0;

// Build query
$where_conditions = ["1=1"];
$params = [];

if (!empty($status_filter)) {
    $where_conditions[] = "r.status = :status";
    $params[':status'] = $status_filter;
}

if ($event_filter > 0) {
    $where_conditions[] = "r.event_id = :event_id";
    $params[':event_id'] = $event_filter;
}

$where_clause = implode(' AND ', $where_conditions);

try {
    // Get registrations with user and event details
    $registrations_query = "SELECT r.*, u.full_name, u.email, u.phone, e.title as event_title, e.event_date
                           FROM registrations r
                           JOIN users u ON r.user_id = u.id
                           JOIN events e ON r.event_id = e.id
                           WHERE $where_clause
                           ORDER BY r.registration_date DESC";
    
    $registrations_stmt = $db->prepare($registrations_query);
    foreach ($params as $key => $value) {
        $registrations_stmt->bindValue($key, $value);
    }
    $registrations_stmt->execute();
    $registrations = $registrations_stmt->fetchAll(PDO::FETCH_ASSOC);

    // Get events for filter dropdown
    $events_query = "SELECT id, title FROM events ORDER BY title";
    $events_stmt = $db->prepare($events_query);
    $events_stmt->execute();
    $events = $events_stmt->fetchAll(PDO::FETCH_ASSOC);

    // Get statistics
    $stats_query = "SELECT 
                    COUNT(*) as total,
                    SUM(CASE WHEN status = 'pending' THEN 1 ELSE 0 END) as pending,
                    SUM(CASE WHEN status = 'approved' THEN 1 ELSE 0 END) as approved,
                    SUM(CASE WHEN status = 'rejected' THEN 1 ELSE 0 END) as rejected,
                    SUM(CASE WHEN status = 'cancelled' THEN 1 ELSE 0 END) as cancelled
                    FROM registrations";
    $stats_stmt = $db->prepare($stats_query);
    $stats_stmt->execute();
    $stats = $stats_stmt->fetch(PDO::FETCH_ASSOC);

} catch(PDOException $exception) {
    $error_message = "Error: " . $exception->getMessage();
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Kelola Pendaftaran - VolunteerHub Admin</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <style>
        body {
            font-family: 'Inter', sans-serif;
            background-color: #f8f9fa;
        }

        /* Sidebar Styles */
        .sidebar {
            position: fixed;
            top: 0;
            left: 0;
            height: 100vh;
            width: 250px;
            background-color: white;
            border-right: 1px solid #e5e7eb;
            box-shadow: 2px 0 4px rgba(0, 0, 0, 0.1);
            z-index: 1000;
            display: flex;
            flex-direction: column;
        }

        .sidebar-header {
            padding: 1.5rem 1rem;
            border-bottom: 1px solid #e5e7eb;
        }

        .sidebar-brand {
            font-size: 1.25rem;
            font-weight: 600;
            color: #1f2937;
            margin: 0;
        }

        .sidebar-nav {
            flex: 1;
            padding: 1rem 0;
        }

        .sidebar-nav .nav-link {
            display: flex;
            align-items: center;
            padding: 0.75rem 1rem;
            color: #6b7280;
            text-decoration: none;
            transition: all 0.2s;
            border-left: 3px solid transparent;
            font-weight: 500;
        }

        .sidebar-nav .nav-link:hover {
            background-color: #f9fafb;
            color: #374151;
        }

        .sidebar-nav .nav-link.active {
            background-color: #eff6ff;
            color: #2563eb;
            border-left-color: #2563eb;
        }

        .sidebar-nav .nav-link i {
            margin-right: 0.75rem;
            font-size: 1.125rem;
        }

        .main-content {
            margin-left: 250px;
            min-height: 100vh;
            background-color: #f8f9fa;
        }

        .top-header {
            background-color: white;
            padding: 1.5rem 2rem;
            border-bottom: 1px solid #e5e7eb;
            margin-bottom: 2rem;
        }

        .status-badge {
            display: inline-flex;
            align-items: center;
            padding: 0.5rem 0.75rem;
            border-radius: 9999px;
            font-size: 0.75rem;
            font-weight: 600;
        }

        .status-badge.pending {
            background-color: #fef3c7;
            color: #92400e;
        }

        .status-badge.approved {
            background-color: #d1fae5;
            color: #065f46;
        }

        .status-badge.rejected {
            background-color: #fee2e2;
            color: #991b1b;
        }

        .status-badge.cancelled {
            background-color: #f3f4f6;
            color: #374151;
        }

        .btn-action {
            padding: 0.375rem 0.5rem;
            font-size: 0.875rem;
            border-radius: 0.375rem;
            margin: 0 0.125rem;
        }

        .filter-card {
            background: white;
            border-radius: 0.75rem;
            padding: 1.5rem;
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
            margin-bottom: 2rem;
        }

        .stat-card {
            background: white;
            border-radius: 0.75rem;
            padding: 1.5rem;
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
            text-align: center;
        }

        .stat-number {
            font-size: 2rem;
            font-weight: 700;
            margin-bottom: 0.5rem;
        }

        .stat-label {
            font-size: 0.875rem;
            color: #6b7280;
            font-weight: 500;
        }

        .alert-database {
            background-color: #fef3c7;
            border-color: #f59e0b;
            color: #92400e;
        }
    </style>
</head>
<body>
    <!-- Sidebar Navigation -->
    <div class="sidebar">
        <div class="sidebar-header">
            <h3 class="sidebar-brand">VolunteerHub Admin</h3>
        </div>
         <nav class="sidebar-nav">
            <ul class="nav flex-column">
                <li class="nav-item">
                    <a class="nav-link " href="dashboard_admin.php">
                        <i class="bi bi-speedometer2"></i>
                        Dashboard
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link" href="events.php">
                        <i class="bi bi-calendar-event"></i>
                        Kelola Event
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link" href="events.php">
                        <i class="bi bi-people"></i>
                        Kelola Users
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link active" href="kelola_pendaftaran.php">
                        <i class="bi bi-clipboard-check"></i>
                        Kelola Pendaftaran
                    </a>
                </li>
            </ul>
        </nav>
        <div class="sidebar-footer p-3">
            <div class="dropdown">
                <button class="btn btn-link dropdown-toggle d-flex align-items-center text-decoration-none w-100" type="button" data-bs-toggle="dropdown">
                    <i class="bi bi-person-circle me-2" style="font-size: 2rem; color: #6b7280;"></i>
                    <span class="text-dark fw-medium">Admin</span>
                </button>
                <ul class="dropdown-menu dropdown-menu-end">
                    <li><a class="dropdown-item" href="../auth/logout.php">
                        <i class="bi bi-box-arrow-right me-2"></i>
                        Logout
                    </a></li>
                </ul>
            </div>
        </div>
    </div>

    <!-- Main Content Area -->
    <div class="main-content">
        <!-- Top Header -->
        <header class="top-header">
            <div class="d-flex justify-content-between align-items-center">
                <div>
                    <h1 class="h3 fw-semibold text-dark mb-1">Kelola Pendaftaran</h1>
                    <p class="text-muted mb-0">Kelola pendaftaran volunteer untuk semua event</p>
                </div>
            </div>
        </header>

        <!-- Main Content -->
        <main class="px-4 pb-5">
            <?php if (isset($success_message)): ?>
                <div class="alert alert-success alert-dismissible fade show" role="alert">
                    <i class="bi bi-check-circle me-2"></i>
                    <?php echo $success_message; ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
            <?php endif; ?>

            <?php if (isset($error_message)): ?>
                <div class="alert alert-danger alert-dismissible fade show" role="alert">
                    <i class="bi bi-exclamation-triangle me-2"></i>
                    <?php echo $error_message; ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                    
                    <?php if (strpos($error_message, 'Data truncated') !== false || strpos($error_message, 'kolom status') !== false): ?>
                        <hr>
                        <div class="mt-3">
                            <h6><i class="bi bi-tools me-2"></i>Cara Memperbaiki:</h6>
                            <ol class="mb-0">
                                <li>Buka phpMyAdmin atau tool database lainnya</li>
                                <li>Jalankan query SQL berikut:</li>
                                <code class="d-block bg-light p-2 mt-2 mb-2">
                                    ALTER TABLE registrations MODIFY COLUMN status ENUM('pending', 'approved', 'rejected', 'cancelled') NOT NULL DEFAULT 'pending';
                                </code>
                                <li>Atau jalankan file <code>database/fix_registration_status.sql</code></li>
                            </ol>
                        </div>
                    <?php endif; ?>
                </div>
            <?php endif; ?>

            <!-- Statistics Cards -->
            <div class="row mb-4">
                <div class="col-md-2 mb-3">
                    <div class="stat-card">
                        <div class="stat-number text-primary"><?php echo $stats['total']; ?></div>
                        <div class="stat-label">Total</div>
                    </div>
                </div>
                <div class="col-md-2 mb-3">
                    <div class="stat-card">
                        <div class="stat-number text-warning"><?php echo $stats['pending']; ?></div>
                        <div class="stat-label">Menunggu</div>
                    </div>
                </div>
                <div class="col-md-2 mb-3">
                    <div class="stat-card">
                        <div class="stat-number text-success"><?php echo $stats['approved']; ?></div>
                        <div class="stat-label">Disetujui</div>
                    </div>
                </div>
                <div class="col-md-2 mb-3">
                    <div class="stat-card">
                        <div class="stat-number text-danger"><?php echo $stats['rejected']; ?></div>
                        <div class="stat-label">Ditolak</div>
                    </div>
                </div>
                <div class="col-md-2 mb-3">
                    <div class="stat-card">
                        <div class="stat-number text-secondary"><?php echo $stats['cancelled']; ?></div>
                        <div class="stat-label">Dibatalkan</div>
                    </div>
                </div>
            </div>

            <!-- Filter -->
            <div class="filter-card">
                <form method="GET" class="row g-3">
                    <div class="col-md-4">
                        <label for="status" class="form-label fw-medium">Status</label>
                        <select class="form-select" id="status" name="status">
                            <option value="">Semua Status</option>
                            <option value="pending" <?php echo ($status_filter == 'pending') ? 'selected' : ''; ?>>Menunggu</option>
                            <option value="approved" <?php echo ($status_filter == 'approved') ? 'selected' : ''; ?>>Disetujui</option>
                            <option value="rejected" <?php echo ($status_filter == 'rejected') ? 'selected' : ''; ?>>Ditolak</option>
                            <option value="cancelled" <?php echo ($status_filter == 'cancelled') ? 'selected' : ''; ?>>Dibatalkan</option>
                        </select>
                    </div>
                    <div class="col-md-4">
                        <label for="event" class="form-label fw-medium">Event</label>
                        <select class="form-select" id="event" name="event">
                            <option value="">Semua Event</option>
                            <?php foreach ($events as $event): ?>
                                <option value="<?php echo $event['id']; ?>" 
                                        <?php echo ($event_filter == $event['id']) ? 'selected' : ''; ?>>
                                    <?php echo htmlspecialchars($event['title']); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="col-md-4 d-flex align-items-end">
                        <button type="submit" class="btn btn-primary me-2">
                            <i class="bi bi-search me-1"></i>
                            Filter
                        </button>
                        <a href="kelola_pendaftaran.php" class="btn btn-outline-secondary">
                            <i class="bi bi-x-circle me-1"></i>
                            Reset
                        </a>
                    </div>
                </form>
            </div>

            <!-- Registrations Table -->
            <div class="card border-0 shadow-sm">
                <div class="card-header bg-light border-0 py-3">
                    <h5 class="mb-0 fw-semibold">
                        Daftar Pendaftaran 
                        <?php if (!empty($status_filter) || $event_filter > 0): ?>
                            (<?php echo count($registrations); ?> hasil)
                        <?php endif; ?>
                    </h5>
                </div>
                <div class="card-body p-0">
                    <?php if (count($registrations) > 0): ?>
                        <div class="table-responsive">
                            <table class="table table-hover mb-0">
                                <thead>
                                    <tr>
                                        <th class="fw-semibold text-muted small py-3 px-3">VOLUNTEER</th>
                                        <th class="fw-semibold text-muted small py-3 px-3">EVENT</th>
                                        <th class="fw-semibold text-muted small py-3 px-3">TANGGAL DAFTAR</th>
                                        <th class="fw-semibold text-muted small py-3 px-3">STATUS</th>
                                        <th class="fw-semibold text-muted small py-3 px-3">AKSI</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($registrations as $registration): ?>
                                    <tr>
                                        <td class="py-3 px-3">
                                            <div class="d-flex align-items-center">
                                                <i class="bi bi-person-circle me-2" style="font-size: 2rem; color: #6b7280;"></i>
                                                <div>
                                                    <p class="fw-medium mb-1"><?php echo htmlspecialchars($registration['full_name']); ?></p>
                                                    <p class="text-muted small mb-0"><?php echo htmlspecialchars($registration['email']); ?></p>
                                                    <?php if ($registration['phone']): ?>
                                                        <p class="text-muted small mb-0"><?php echo htmlspecialchars($registration['phone']); ?></p>
                                                    <?php endif; ?>
                                                </div>
                                            </div>
                                        </td>
                                        <td class="py-3 px-3">
                                            <p class="fw-medium mb-1"><?php echo htmlspecialchars($registration['event_title']); ?></p>
                                            <p class="text-muted small mb-0"><?php echo formatDate($registration['event_date']); ?></p>
                                        </td>
                                        <td class="py-3 px-3 fw-medium"><?php echo formatDateTime($registration['registration_date']); ?></td>
                                        <td class="py-3 px-3">
                                            <span class="status-badge <?php echo $registration['status']; ?>">
                                                <?php 
                                                switch($registration['status']) {
                                                    case 'pending': echo 'Menunggu'; break;
                                                    case 'approved': echo 'Disetujui'; break;
                                                    case 'rejected': echo 'Ditolak'; break;
                                                    case 'cancelled': echo 'Dibatalkan'; break;
                                                }
                                                ?>
                                            </span>
                                        </td>
                                        <td class="py-3 px-3">
                                            <?php if ($registration['status'] == 'pending'): ?>
                                                <form method="POST" class="d-inline">
                                                    <input type="hidden" name="registration_id" value="<?php echo $registration['id']; ?>">
                                                    <button type="submit" name="action" value="approve" class="btn btn-action btn-success" title="Setujui">
                                                        <i class="bi bi-check"></i>
                                                    </button>
                                                    <button type="submit" name="action" value="reject" class="btn btn-action btn-danger" title="Tolak">
                                                        <i class="bi bi-x"></i>
                                                    </button>
                                                </form>
                                            <?php elseif ($registration['status'] == 'approved'): ?>
                                                <form method="POST" class="d-inline" onsubmit="return confirm('Apakah Anda yakin ingin membatalkan pendaftaran ini?')">
                                                    <input type="hidden" name="registration_id" value="<?php echo $registration['id']; ?>">
                                                    <button type="submit" name="action" value="cancel" class="btn btn-action btn-warning" title="Batalkan">
                                                        <i class="bi bi-x-circle"></i>
                                                    </button>
                                                </form>
                                                <a href="details_users.php?id=<?php echo $registration['user_id']; ?>" class="btn btn-action btn-outline-primary" title="Lihat Detail">
                                                    <i class="bi bi-eye"></i>
                                                </a>
                                            <?php else: ?>
                                                <a href="details_users.php?id=<?php echo $registration['user_id']; ?>" class="btn btn-action btn-outline-primary" title="Lihat Detail">
                                                    <i class="bi bi-eye"></i>
                                                </a>
                                            <?php endif; ?>
                                        </td>
                                    </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    <?php else: ?>  
                        <div class="text-center py-5">
                            <i class="bi bi-clipboard-x text-muted" style="font-size: 4rem;"></i>
                            <h3 class="text-muted mt-3">Tidak ada pendaftaran ditemukan</h3>
                            <p class="text-muted">
                                <?php if (!empty($status_filter) || $event_filter > 0): ?>
                                    Coba ubah filter atau reset pencarian.
                                <?php else: ?>
                                    Belum ada pendaftaran volunteer.
                                <?php endif; ?>
                            </p>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </main>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
