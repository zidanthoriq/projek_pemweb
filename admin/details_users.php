<?php
require_once '../config/database.php';
requireAdmin();

$database = new Database();
$db = $database->getConnection();

$user_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

if ($user_id <= 0) {
    header("Location: kelola_users.php");
    exit();
}

try {
    // Get user details
    $user_query = "SELECT * FROM users WHERE id = :user_id";
    $user_stmt = $db->prepare($user_query);
    $user_stmt->bindParam(':user_id', $user_id);
    $user_stmt->execute();
    
    if ($user_stmt->rowCount() == 0) {
        header("Location: kelola_users.php");
        exit();
    }
    
    $user = $user_stmt->fetch(PDO::FETCH_ASSOC);

    // Get user registration history
    $registrations_query = "SELECT r.*, e.title as event_title, e.event_date, e.start_time, e.end_time, e.location, e.organizer,
                           a.full_name as approved_by_name
                           FROM registrations r
                           JOIN events e ON r.event_id = e.id
                           LEFT JOIN users a ON r.approved_by = a.id
                           WHERE r.user_id = :user_id
                           ORDER BY r.registration_date DESC";
    $registrations_stmt = $db->prepare($registrations_query);
    $registrations_stmt->bindParam(':user_id', $user_id);
    $registrations_stmt->execute();
    $registrations = $registrations_stmt->fetchAll(PDO::FETCH_ASSOC);

    // Get user statistics
    $stats_query = "SELECT 
                    COUNT(*) as total_registrations,
                    SUM(CASE WHEN status = 'approved' THEN 1 ELSE 0 END) as approved_registrations,
                    SUM(CASE WHEN status = 'pending' THEN 1 ELSE 0 END) as pending_registrations,
                    SUM(CASE WHEN status = 'rejected' THEN 1 ELSE 0 END) as rejected_registrations,
                    MIN(registration_date) as first_registration,
                    MAX(registration_date) as last_registration
                    FROM registrations WHERE user_id = :user_id";
    $stats_stmt = $db->prepare($stats_query);
    $stats_stmt->bindParam(':user_id', $user_id);
    $stats_stmt->execute();
    $stats = $stats_stmt->fetch(PDO::FETCH_ASSOC);

} catch(PDOException $exception) {
    $error = "Error: " . $exception->getMessage();
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Detail Volunteer - VolunteerHub Admin</title>
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

        .sidebar-footer {
            padding: 1rem;
            border-top: 1px solid #e5e7eb;
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

        .profile-img {
            font-size: 2rem;
            margin-right: 0.5rem;
            color: #6b7280;
        }

        /* Profile Header */
        .profile-header {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            border-radius: 0.75rem;
            padding: 2rem;
            margin-bottom: 2rem;
        }

        .profile-avatar {
            width: 100px;
            height: 100px;
            background-color: rgba(255, 255, 255, 0.2);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 2.5rem;
            margin-bottom: 1rem;
        }

        /* Status Badges */
        .status-badge {
            display: inline-flex;
            align-items: center;
            padding: 0.25rem 0.75rem;
            border-radius: 9999px;
            font-size: 0.75rem;
            font-weight: 600;
        }

        .status-badge.approved {
            background-color: #d1fae5;
            color: #065f46;
        }

        .status-badge.pending {
            background-color: #fef3c7;
            color: #92400e;
        }

        .status-badge.rejected {
            background-color: #fee2e2;
            color: #991b1b;
        }

        /* Role Badges */
        .role-badge {
            display: inline-flex;
            align-items: center;
            padding: 0.25rem 0.75rem;
            border-radius: 9999px;
            font-size: 0.75rem;
            font-weight: 600;
        }

        .role-badge.admin {
            background-color: #fef3c7;
            color: #92400e;
        }

        .role-badge.user {
            background-color: #dbeafe;
            color: #1e40af;
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

        .info-card {
            background: white;
            border-radius: 0.75rem;
            padding: 2rem;
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
            margin-bottom: 2rem;
        }

        .info-item {
            display: flex;
            align-items: center;
            padding: 0.75rem 0;
            border-bottom: 1px solid #f3f4f6;
        }

        .info-item:last-child {
            border-bottom: none;
        }

        .info-icon {
            width: 2.5rem;
            height: 2.5rem;
            background-color: #f3f4f6;
            border-radius: 0.5rem;
            display: flex;
            align-items: center;
            justify-content: center;
            margin-right: 1rem;
            flex-shrink: 0;
        }

        .info-label {
            font-weight: 500;
            color: #6b7280;
            margin-bottom: 0.25rem;
        }

        .info-value {
            font-weight: 600;
            color: #1f2937;
        }

        /* Responsive adjustments */
        @media (max-width: 767px) {
            .sidebar {
                transform: translateX(-100%);
                transition: transform 0.3s ease;
            }
            
            .sidebar.show {
                transform: translateX(0);
            }
            
            .main-content {
                margin-left: 0;
            }
            
            .top-header {
                padding: 1rem;
            }
        }
    </style>
</head>
<body>
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
                    <nav aria-label="breadcrumb">
                        <ol class="breadcrumb mb-1">
                            <li class="breadcrumb-item"><a href="dashboard_admin.php" class="text-decoration-none">Dashboard</a></li>
                            <li class="breadcrumb-item"><a href="kelola_users.php" class="text-decoration-none">Kelola Volunteer</a></li>
                            <li class="breadcrumb-item active" aria-current="page">Detail Volunteer</li>
                        </ol>
                    </nav>
                    <h5 class="mb-0 text-muted fw-medium">Detail Volunteer</h5>
                </div>
                <a href="kelola_users.php" class="btn btn-outline-secondary">
                    <i class="bi bi-arrow-left me-2"></i>
                    Kembali
                </a>
            </div>
        </header>

        <!-- Main Content -->
        <main class="px-4 pb-5">
            <!-- Profile Header -->
            <div class="profile-header">
                <div class="row align-items-center">
                    <div class="col-lg-8">
                        <div class="d-flex align-items-center">
                            <div class="profile-avatar">
                                <i class="bi bi-person"></i>
                            </div>
                            <div>
                                <h1 class="h2 fw-bold mb-2"><?php echo htmlspecialchars($user['full_name']); ?></h1>
                                <div class="d-flex align-items-center gap-3 mb-2">
                                    <span class="role-badge <?php echo $user['role']; ?>">
                                        <?php echo ucfirst($user['role']); ?>
                                    </span>
                                    <span class="opacity-75">
                                        <i class="bi bi-envelope me-1"></i>
                                        <?php echo htmlspecialchars($user['email']); ?>
                                    </span>
                                </div>
                                <p class="mb-0 opacity-75">Member sejak <?php echo formatDate($user['created_at']); ?></p>
                            </div>
                        </div>
                    </div>
                    <div class="col-lg-4 text-lg-end">
                        <div class="text-center">
                            <h3 class="h2 fw-bold mb-1"><?php echo $stats['approved_registrations']; ?></h3>
                            <p class="mb-0 opacity-75">Event Diikuti</p>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Statistics Cards -->
            <div class="row mb-4">
                <div class="col-md-3 mb-3">
                    <div class="stat-card">
                        <div class="stat-number text-primary"><?php echo $stats['total_registrations']; ?></div>
                        <div class="stat-label">Total Pendaftaran</div>
                    </div>
                </div>
                <div class="col-md-3 mb-3">
                    <div class="stat-card">
                        <div class="stat-number text-success"><?php echo $stats['approved_registrations']; ?></div>
                        <div class="stat-label">Disetujui</div>
                    </div>
                </div>
                <div class="col-md-3 mb-3">
                    <div class="stat-card">
                        <div class="stat-number text-warning"><?php echo $stats['pending_registrations']; ?></div>
                        <div class="stat-label">Menunggu</div>
                    </div>
                </div>
                <div class="col-md-3 mb-3">
                    <div class="stat-card">
                        <div class="stat-number text-danger"><?php echo $stats['rejected_registrations']; ?></div>
                        <div class="stat-label">Ditolak</div>
                    </div>
                </div>
            </div>

            <div class="row">
                <!-- Left Column - User Information -->
                <div class="col-lg-4">
                    <div class="info-card">
                        <h3 class="h5 fw-semibold mb-3">Informasi Personal</h3>
                        
                        <div class="info-item">
                            <div class="info-icon">
                                <i class="bi bi-person text-primary"></i>
                            </div>
                            <div class="flex-grow-1">
                                <div class="info-label">Nama Lengkap</div>
                                <div class="info-value"><?php echo htmlspecialchars($user['full_name']); ?></div>
                            </div>
                        </div>

                        <div class="info-item">
                            <div class="info-icon">
                                <i class="bi bi-envelope text-primary"></i>
                            </div>
                            <div class="flex-grow-1">
                                <div class="info-label">Email</div>
                                <div class="info-value"><?php echo htmlspecialchars($user['email']); ?></div>
                            </div>
                        </div>

                        <div class="info-item">
                            <div class="info-icon">
                                <i class="bi bi-telephone text-primary"></i>
                            </div>
                            <div class="flex-grow-1">
                                <div class="info-label">Nomor Telepon</div>
                                <div class="info-value"><?php echo htmlspecialchars($user['phone'] ?: 'Tidak ada'); ?></div>
                            </div>
                        </div>

                        <div class="info-item">
                            <div class="info-icon">
                                <i class="bi bi-geo-alt text-primary"></i>
                            </div>
                            <div class="flex-grow-1">
                                <div class="info-label">Alamat</div>
                                <div class="info-value"><?php echo htmlspecialchars($user['address'] ?: 'Tidak ada'); ?></div>
                            </div>
                        </div>

                        <div class="info-item">
                            <div class="info-icon">
                                <i class="bi bi-calendar text-primary"></i>
                            </div>
                            <div class="flex-grow-1">
                                <div class="info-label">Bergabung</div>
                                <div class="info-value"><?php echo formatDate($user['created_at']); ?></div>
                            </div>
                        </div>

                        <?php if ($stats['first_registration']): ?>
                        <div class="info-item">
                            <div class="info-icon">
                                <i class="bi bi-clock text-success"></i>
                            </div>
                            <div class="flex-grow-1">
                                <div class="info-label">Aktivitas Pertama</div>
                                <div class="info-value"><?php echo formatDate($stats['first_registration']); ?></div>
                            </div>
                        </div>

                        <div class="info-item">
                            <div class="info-icon">
                                <i class="bi bi-clock-history text-info"></i>
                            </div>
                            <div class="flex-grow-1">
                                <div class="info-label">Aktivitas Terakhir</div>
                                <div class="info-value"><?php echo formatDate($stats['last_registration']); ?></div>
                            </div>
                        </div>
                        <?php endif; ?>
                    </div>
                </div>

                <!-- Right Column - Registration History -->
                <div class="col-lg-8">
                    <div class="card border-0 shadow-sm">
                        <div class="card-header bg-light border-0 py-3">
                            <h5 class="mb-0 fw-semibold">Riwayat Pendaftaran (<?php echo count($registrations); ?>)</h5>
                        </div>
                        <div class="card-body p-0">
                            <?php if (count($registrations) > 0): ?>
                                <div class="table-responsive">
                                    <table class="table table-hover mb-0">
                                        <thead>
                                            <tr>
                                                <th class="fw-semibold text-muted small py-3 px-3">EVENT</th>
                                                <th class="fw-semibold text-muted small py-3 px-3">TANGGAL EVENT</th>
                                                <th class="fw-semibold text-muted small py-3 px-3">TANGGAL DAFTAR</th>
                                                <th class="fw-semibold text-muted small py-3 px-3">STATUS</th>
                                                <th class="fw-semibold text-muted small py-3 px-3">DISETUJUI OLEH</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php foreach ($registrations as $registration): ?>
                                            <tr>
                                                <td class="py-3 px-3">
                                                    <div>
                                                        <p class="fw-medium mb-1"><?php echo htmlspecialchars($registration['event_title']); ?></p>
                                                        <p class="text-muted small mb-0">
                                                            <i class="bi bi-geo-alt me-1"></i>
                                                            <?php echo htmlspecialchars($registration['location']); ?>
                                                        </p>
                                                    </div>
                                                </td>
                                                <td class="py-3 px-3">
                                                    <div>
                                                        <p class="fw-medium mb-1"><?php echo formatDate($registration['event_date']); ?></p>
                                                        <p class="text-muted small mb-0">
                                                            <?php echo formatTime($registration['start_time']); ?> - <?php echo formatTime($registration['end_time']); ?>
                                                        </p>
                                                    </div>
                                                </td>
                                                <td class="py-3 px-3 fw-medium"><?php echo formatDate($registration['registration_date']); ?></td>
                                                <td class="py-3 px-3">
                                                    <span class="status-badge <?php echo $registration['status']; ?>">
                                                        <?php 
                                                        switch($registration['status']) {
                                                            case 'pending': echo 'Menunggu'; break;
                                                            case 'approved': echo 'Disetujui'; break;
                                                            case 'rejected': echo 'Ditolak'; break;
                                                        }
                                                        ?>
                                                    </span>
                                                </td>
                                                <td class="py-3 px-3">
                                                    <?php if ($registration['approved_by_name']): ?>
                                                        <span class="fw-medium"><?php echo htmlspecialchars($registration['approved_by_name']); ?></span>
                                                        <br><small class="text-muted"><?php echo formatDateTime($registration['approved_at']); ?></small>
                                                    <?php else: ?>
                                                        <span class="text-muted">-</span>
                                                    <?php endif; ?>
                                                </td>
                                            </tr>
                                            <?php endforeach; ?>
                                        </tbody>
                                    </table>
                                </div>
                            <?php else: ?>
                                <div class="text-center py-5">
                                    <i class="bi bi-clipboard text-muted" style="font-size: 4rem;"></i>
                                    <h3 class="text-muted mt-3">Belum ada riwayat pendaftaran</h3>
                                    <p class="text-muted">Volunteer ini belum pernah mendaftar ke event manapun.</p>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </div>
        </main>


    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>