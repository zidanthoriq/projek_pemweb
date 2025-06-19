<?php
require_once '../config/database.php';
requireAdmin();

$database = new Database();
$db = $database->getConnection();

// Get statistics
try {
    // Total events
    $total_events_query = "SELECT COUNT(*) as total FROM events";
    $total_events_stmt = $db->prepare($total_events_query);
    $total_events_stmt->execute();
    $total_events = $total_events_stmt->fetch(PDO::FETCH_ASSOC)['total'];

    // Active events
    $active_events_query = "SELECT COUNT(*) as total FROM events WHERE status = 'active' AND event_date >= CURDATE()";
    $active_events_stmt = $db->prepare($active_events_query);
    $active_events_stmt->execute();
    $active_events = $active_events_stmt->fetch(PDO::FETCH_ASSOC)['total'];

    // Total volunteers
    $total_volunteers_query = "SELECT COUNT(*) as total FROM users WHERE role = 'user'";
    $total_volunteers_stmt = $db->prepare($total_volunteers_query);
    $total_volunteers_stmt->execute();
    $total_volunteers = $total_volunteers_stmt->fetch(PDO::FETCH_ASSOC)['total'];

    // Pending registrations
    $pending_registrations_query = "SELECT COUNT(*) as total FROM registrations WHERE status = 'pending'";
    $pending_registrations_stmt = $db->prepare($pending_registrations_query);
    $pending_registrations_stmt->execute();
    $pending_registrations = $pending_registrations_stmt->fetch(PDO::FETCH_ASSOC)['total'];

    // Completed events
    $completed_events_query = "SELECT COUNT(*) as total FROM events WHERE status = 'completed' OR event_date < CURDATE()";
    $completed_events_stmt = $db->prepare($completed_events_query);
    $completed_events_stmt->execute();
    $completed_events = $completed_events_stmt->fetch(PDO::FETCH_ASSOC)['total'];

    // Recent events
    $recent_events_query = "SELECT e.*, COUNT(CASE WHEN r.status = 'approved' THEN 1 END) as registration_count
                           FROM events e 
                           LEFT JOIN registrations r ON e.id = r.event_id 
                           GROUP BY e.id 
                           ORDER BY e.created_at DESC 
                           LIMIT 4";
    $recent_events_stmt = $db->prepare($recent_events_query);
    $recent_events_stmt->execute();
    $recent_events = $recent_events_stmt->fetchAll(PDO::FETCH_ASSOC);

    // Pending registrations list
    $pending_list_query = "SELECT r.*, u.full_name, u.email, e.title as event_title 
                          FROM registrations r 
                          JOIN users u ON r.user_id = u.id 
                          JOIN events e ON r.event_id = e.id 
                          WHERE r.status = 'pending' 
                          ORDER BY r.registration_date DESC 
                          LIMIT 5";
    $pending_list_stmt = $db->prepare($pending_list_query);
    $pending_list_stmt->execute();
    $pending_list = $pending_list_stmt->fetchAll(PDO::FETCH_ASSOC);

} catch(PDOException $exception) {
    $error = "Error: " . $exception->getMessage();
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Dashboard - VolunteerHub</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <style>
        body {
            font-family: 'Inter', sans-serif;
            background-color: #f8f9fa;
        }

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

        .stat-icon {
            display: flex;
            align-items: center;
            justify-content: center;
            width: 3rem;
            height: 3rem;
            border-radius: 0.5rem;
            margin-right: 1rem;
            flex-shrink: 0;
        }

        .stat-icon i {
            font-size: 1.5rem;
        }

        .stat-icon.blue { background-color: #3b82f6; }
        .stat-icon.green { background-color: #10b981; }
        .stat-icon.yellow { background-color: #f59e0b; }
        .stat-icon.purple { background-color: #8b5cf6; }
        .stat-icon.indigo { background-color: #6366f1; }

        .status-badge {
            display: inline-flex;
            align-items: center;
            padding: 0.25rem 0.75rem;
            border-radius: 9999px;
            font-size: 0.75rem;
            font-weight: 600;
        }

        .status-badge.active {
            background-color: #d1fae5;
            color: #065f46;
        }

        .status-badge.pending {
            background-color: #fef3c7;
            color: #92400e;
        }

        .status-badge.completed {
            background-color: #dbeafe;
            color: #1e40af;
        }

        .action-buttons {
            display: flex;
            gap: 0.25rem;
        }

        .btn-action {
            padding: 0.25rem 0.5rem;
            border: none;
            border-radius: 0.375rem;
            font-size: 0.875rem;
            cursor: pointer;
            transition: all 0.2s;
        }

        .btn-approve {
            background-color: #10b981;
            color: white;
        }

        .btn-approve:hover {
            background-color: #059669;
        }

        .btn-reject {
            background-color: #ef4444;
            color: white;
        }

        .btn-reject:hover {
            background-color: #dc2626;
        }

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
    <!-- Sidebar Navigation -->
    <div class="sidebar">
        <div class="sidebar-header">
            <h3 class="sidebar-brand">VolunteerHub Admin</h3>
        </div>
         <nav class="sidebar-nav">
            <ul class="nav flex-column">
                <li class="nav-item">
                    <a class="nav-link active" href="dashboard_admin.php">
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
                    <a class="nav-link" href="kelola_users.php">
                        <i class="bi bi-people"></i>
                        Kelola Users
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link" href="kelola_pendaftaran.php">
                        <i class="bi bi-clipboard-check"></i>
                        Kelola Pendaftaran
                    </a>
                </li>
            </ul>
        </nav>
        <div class="sidebar-footer">
            <div class="dropdown">
                <button class="btn btn-link dropdown-toggle d-flex align-items-center text-decoration-none w-100" type="button" data-bs-toggle="dropdown" aria-expanded="false">
                    <i class="bi bi-person-circle me-2" style="font-size: 2rem; color: #6b7280;"></i>
                    <span class="text-dark fw-medium"><?php echo $_SESSION['full_name']; ?></span>
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
                <h5 class="mb-0 text-muted fw-medium">Panel Administrasi</h5>
            </div>
        </header>

        <!-- Main Content -->
        <main class="px-4 pb-5">
            <!-- Success/Error Messages -->
            <?php if (isset($_SESSION['success'])): ?>
                <div class="alert alert-success alert-dismissible fade show" role="alert">
                    <i class="bi bi-check-circle me-2"></i>
                    <?php echo $_SESSION['success']; unset($_SESSION['success']); ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
            <?php endif; ?>

            <?php if (isset($_SESSION['error'])): ?>
                <div class="alert alert-danger alert-dismissible fade show" role="alert">
                    <i class="bi bi-exclamation-triangle me-2"></i>
                    <?php echo $_SESSION['error']; unset($_SESSION['error']); ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
            <?php endif; ?>

            <!-- Dashboard Header -->
            <div class="mb-4">
                <h1 class="h2 fw-semibold text-dark mb-2">Dashboard Admin</h1>
                <p class="text-muted mb-0">Selamat datang di panel admin VolunteerHub. Kelola event dan volunteer dengan mudah.</p>
            </div>
            
            <!-- Stats Cards -->
            <div class="row g-3 mb-4">
                <!-- Total Events -->
                <div class="col-xl-2 col-lg-4 col-md-6">
                    <div class="card h-100 border-0 shadow-sm">
                        <div class="card-body p-3">
                            <div class="d-flex align-items-center">
                                <div class="stat-icon blue">
                                    <i class="bi bi-calendar-event text-white"></i>
                                </div>
                                <div class="flex-grow-1">
                                    <p class="text-muted small mb-1 fw-medium">Total Event</p>
                                    <h4 class="fw-semibold mb-0"><?php echo $total_events; ?></h4>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Active Events -->
                <div class="col-xl-2 col-lg-4 col-md-6">
                    <div class="card h-100 border-0 shadow-sm">
                        <div class="card-body p-3">
                            <div class="d-flex align-items-center">
                                <div class="stat-icon green">
                                    <i class="bi bi-play-circle text-white"></i>
                                </div>
                                <div class="flex-grow-1">
                                    <p class="text-muted small mb-1 fw-medium">Event Aktif</p>
                                    <h4 class="fw-semibold mb-0"><?php echo $active_events; ?></h4>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Total Volunteers -->
                <div class="col-xl-2 col-lg-4 col-md-6">
                    <div class="card h-100 border-0 shadow-sm">
                        <div class="card-body p-3">
                            <div class="d-flex align-items-center">
                                <div class="stat-icon purple">
                                    <i class="bi bi-people text-white"></i>
                                </div>
                                <div class="flex-grow-1">
                                    <p class="text-muted small mb-1 fw-medium">Total Volunteer</p>
                                    <h4 class="fw-semibold mb-0"><?php echo $total_volunteers; ?></h4>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Pending Applications -->
                <div class="col-xl-2 col-lg-4 col-md-6">
                    <div class="card h-100 border-0 shadow-sm">
                        <div class="card-body p-3">
                            <div class="d-flex align-items-center">
                                <div class="stat-icon yellow">
                                    <i class="bi bi-clock text-white"></i>
                                </div>
                                <div class="flex-grow-1">
                                    <p class="text-muted small mb-1 fw-medium">Menunggu Persetujuan</p>
                                    <h4 class="fw-semibold mb-0"><?php echo $pending_registrations; ?></h4>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Completed Events -->
                <div class="col-xl-2 col-lg-4 col-md-6">
                    <div class="card h-100 border-0 shadow-sm">
                        <div class="card-body p-3">
                            <div class="d-flex align-items-center">
                                <div class="stat-icon indigo">
                                    <i class="bi bi-check-circle text-white"></i>
                                </div>
                                <div class="flex-grow-1">
                                    <p class="text-muted small mb-1 fw-medium">Event Selesai</p>
                                    <h4 class="fw-semibold mb-0"><?php echo $completed_events; ?></h4>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Content Grid -->
            <div class="row g-3">
                <!-- Left Column - Recent Events & Applications -->
                <div class="col-lg-8">
                    <!-- Recent Events -->
                    <div class="card border-0 shadow-sm mb-3">
                        <div class="card-header bg-light border-0 py-3">
                            <div class="d-flex justify-content-between align-items-center">
                                <h5 class="mb-0 fw-semibold">Event Terbaru</h5>
                                <a href="events.php" class="btn btn-sm btn-outline-primary fw-medium">Lihat Semua</a>
                            </div>
                        </div>
                        <div class="card-body p-0">
                            <div class="table-responsive">
                                <table class="table table-hover mb-0">
                                    <thead>
                                        <tr>
                                            <th class="fw-semibold text-muted small py-3 px-3">NAMA EVENT</th>
                                            <th class="fw-semibold text-muted small py-3 px-3">TANGGAL</th>
                                            <th class="fw-semibold text-muted small py-3 px-3">LOKASI</th>
                                            <th class="fw-semibold text-muted small py-3 px-3">PESERTA</th>
                                            <th class="fw-semibold text-muted small py-3 px-3">STATUS</th>
                                            <th class="fw-semibold text-muted small py-3 px-3">AKSI</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($recent_events as $event): ?>
                                        <tr>
                                            <td class="py-3 px-3">
                                                <div>
                                                    <p class="fw-medium mb-1"><?php echo htmlspecialchars($event['title']); ?></p>
                                                    <p class="text-muted small mb-0"><?php echo htmlspecialchars($event['organizer']); ?></p>
                                                </div>
                                            </td>
                                            <td class="py-3 px-3 fw-medium"><?php echo formatDate($event['event_date']); ?></td>
                                            <td class="py-3 px-3"><?php echo htmlspecialchars($event['location']); ?></td>
                                            <td class="py-3 px-3 fw-medium"><?php echo $event['registration_count']; ?>/<?php echo $event['max_participants']; ?></td>
                                            <td class="py-3 px-3">
                                                <?php
                                                $status_class = 'active';
                                                $status_text = 'Aktif';
                                                if ($event['status'] == 'completed' || $event['event_date'] < date('Y-m-d')) {
                                                    $status_class = 'completed';
                                                    $status_text = 'Selesai';
                                                } elseif ($event['status'] == 'cancelled') {
                                                    $status_class = 'pending';
                                                    $status_text = 'Dibatalkan';
                                                }
                                                ?>
                                                <span class="status-badge <?php echo $status_class; ?>"><?php echo $status_text; ?></span>
                                            </td>
                                            <td class="py-3 px-3">
                                                <a href="kelola_pendaftaran.php?event=<?php echo $event['id']; ?>" class="btn btn-sm btn-outline-primary">
                                                    <i class="bi bi-eye"></i>
                                                </a>
                                                <a href="edit_event.php?id=<?php echo $event['id']; ?>" class="btn btn-sm btn-outline-warning">
                                                    <i class="bi bi-pencil"></i>
                                                </a>
                                            </td>
                                        </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>

                    <!-- Pending Applications -->
                    <div class="card border-0 shadow-sm">
                        <div class="card-header bg-light border-0 py-3">
                            <div class="d-flex justify-content-between align-items-center">
                                <h5 class="mb-0 fw-semibold">Pendaftaran Menunggu Persetujuan</h5>
                                <div class="d-flex align-items-center gap-2">
                                    <span class="badge bg-warning fw-medium"><?php echo $pending_registrations; ?> Pending</span>
                                    <a href="kelola_pendaftaran.php" class="btn btn-sm btn-outline-primary fw-medium">Lihat Semua</a>
                                </div>
                            </div>
                        </div>
                        <div class="card-body p-0">
                            <?php if (count($pending_list) > 0): ?>
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
                                        <?php foreach ($pending_list as $registration): ?>
                                        <tr>
                                            <td class="py-3 px-3">
                                                <div class="d-flex align-items-center">
                                                    <i class="bi bi-person-circle me-2" style="font-size: 2rem; color: #6b7280;"></i>
                                                    <div>
                                                        <p class="fw-medium mb-1"><?php echo htmlspecialchars($registration['full_name']); ?></p>
                                                        <p class="text-muted small mb-0"><?php echo htmlspecialchars($registration['email']); ?></p>
                                                    </div>
                                                </div>
                                            </td>
                                            <td class="py-3 px-3 fw-medium"><?php echo htmlspecialchars($registration['event_title']); ?></td>
                                            <td class="py-3 px-3 fw-medium"><?php echo formatDate($registration['registration_date']); ?></td>
                                            <td class="py-3 px-3"><span class="status-badge pending">Menunggu</span></td>
                                            <td class="py-3 px-3">
                                                <div class="action-buttons">
                                                    <form method="POST" action="process_registration.php" class="d-inline">
                                                        <input type="hidden" name="registration_id" value="<?php echo $registration['id']; ?>">
                                                        <input type="hidden" name="action" value="approve">
                                                        <button type="submit" class="btn-action btn-approve" title="Setujui" onclick="return confirm('Apakah Anda yakin ingin menyetujui pendaftaran ini?')">
                                                            <i class="bi bi-check"></i>
                                                        </button>
                                                    </form>
                                                    <form method="POST" action="process_registration.php" class="d-inline">
                                                        <input type="hidden" name="registration_id" value="<?php echo $registration['id']; ?>">
                                                        <input type="hidden" name="action" value="reject">
                                                        <button type="submit" class="btn-action btn-reject" title="Tolak" onclick="return confirm('Apakah Anda yakin ingin menolak pendaftaran ini?')">
                                                            <i class="bi bi-x"></i>
                                                        </button>
                                                    </form>
                                                </div>
                                            </td>
                                        </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>
                            <?php else: ?>
                            <div class="text-center py-5">
                                <i class="bi bi-clipboard-check text-muted" style="font-size: 3rem;"></i>
                                <p class="text-muted mt-3 mb-0">Tidak ada pendaftaran yang menunggu persetujuan</p>
                            </div>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>

                <!-- Right Column - Quick Actions -->
                <div class="col-lg-4">
                    <!-- Quick Actions -->
                    <div class="card border-0 shadow-sm">
                        <div class="card-header bg-light border-0 py-3">
                            <h5 class="mb-0 fw-semibold">Aksi Cepat</h5>
                        </div>
                        <div class="card-body p-3">
                            <div class="d-grid gap-3">
                                <a href="add_event.php" class="btn btn-light d-flex align-items-center text-start p-3 border text-decoration-none">
                                    <i class="bi bi-plus-circle me-3 text-primary" style="font-size: 1.5rem;"></i>
                                    <span class="fw-medium text-dark">Buat Event Baru</span>
                                </a>
                                <a href="kelola_users.php" class="btn btn-light d-flex align-items-center text-start p-3 border text-decoration-none">
                                    <i class="bi bi-people me-3 text-success" style="font-size: 1.5rem;"></i>
                                    <span class="fw-medium text-dark">Kelola Volunteer</span>
                                </a>
                                <a href="kelola_pendaftaran.php" class="btn btn-light d-flex align-items-center text-start p-3 border text-decoration-none">
                                    <i class="bi bi-clipboard-check me-3 text-info" style="font-size: 1.5rem;"></i>
                                    <span class="fw-medium text-dark">Kelola Pendaftaran</span>
                                </a>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </main>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
