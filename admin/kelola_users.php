<?php
require_once '../config/database.php';
requireAdmin();

$database = new Database();
$db = $database->getConnection();

$search = isset($_GET['search']) ? sanitizeInput($_GET['search']) : '';
$role_filter = isset($_GET['role']) ? sanitizeInput($_GET['role']) : '';

// Build query
$where_conditions = [];
$params = [];

if (!empty($search)) {
    $where_conditions[] = "(u.full_name LIKE :search OR u.email LIKE :search OR u.phone LIKE :search)";
    $params[':search'] = '%' . $search . '%';
}

if (!empty($role_filter)) {
    $where_conditions[] = "u.role = :role";
    $params[':role'] = $role_filter;
}

$where_clause = !empty($where_conditions) ? 'WHERE ' . implode(' AND ', $where_conditions) : '';

try {
    // Get users with registration statistics
    $users_query = "SELECT u.*, 
                    COUNT(r.id) as total_registrations,
                    SUM(CASE WHEN r.status = 'approved' THEN 1 ELSE 0 END) as approved_registrations,
                    SUM(CASE WHEN r.status = 'pending' THEN 1 ELSE 0 END) as pending_registrations,
                    MAX(r.registration_date) as last_activity
                    FROM users u 
                    LEFT JOIN registrations r ON u.id = r.user_id 
                    $where_clause
                    GROUP BY u.id 
                    ORDER BY u.created_at DESC";
    
    $users_stmt = $db->prepare($users_query);
    foreach ($params as $key => $value) {
        $users_stmt->bindValue($key, $value);
    }
    $users_stmt->execute();
    $users = $users_stmt->fetchAll(PDO::FETCH_ASSOC);

    // Get statistics
    $stats_query = "SELECT 
                    COUNT(*) as total_users,
                    SUM(CASE WHEN role = 'user' THEN 1 ELSE 0 END) as volunteers,
                    SUM(CASE WHEN role = 'admin' THEN 1 ELSE 0 END) as admins,
                    SUM(CASE WHEN created_at >= DATE_SUB(NOW(), INTERVAL 30 DAY) THEN 1 ELSE 0 END) as new_users
                    FROM users";
    $stats_stmt = $db->prepare($stats_query);
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
    <title>Kelola Volunteer - VolunteerHub Admin</title>
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

        /* Activity Indicators */
        .activity-indicator {
            display: inline-flex;
            align-items: center;
            padding: 0.25rem 0.5rem;
            border-radius: 0.375rem;
            font-size: 0.75rem;
            font-weight: 500;
        }

        .activity-indicator.active {
            background-color: #d1fae5;
            color: #065f46;
        }

        .activity-indicator.inactive {
            background-color: #f3f4f6;
            color: #6b7280;
        }

        .search-filter-card {
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

        /* Action Buttons */
        .btn-action {
            padding: 0.375rem 0.5rem;
            font-size: 0.875rem;
            border-radius: 0.375rem;
            margin: 0 0.125rem;
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
                    <a class="nav-link active" href="kelola_users.php">
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
                    <i class="bi bi-person-circle profile-img"></i>
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
                <h5 class="mb-0 text-muted fw-medium">Kelola Volunteer</h5>
            </div>
        </header>

        <!-- Main Content -->
        <main class="px-4 pb-5">
            <!-- Statistics Cards -->
            <div class="row mb-4">
                <div class="col-md-3 mb-3">
                    <div class="stat-card">
                        <div class="stat-number text-primary"><?php echo $stats['total_users']; ?></div>
                        <div class="stat-label">Total User</div>
                    </div>
                </div>
                <div class="col-md-3 mb-3">
                    <div class="stat-card">
                        <div class="stat-number text-success"><?php echo $stats['volunteers']; ?></div>
                        <div class="stat-label">Volunteer</div>
                    </div>
                </div>
                <div class="col-md-3 mb-3">
                    <div class="stat-card">
                        <div class="stat-number text-warning"><?php echo $stats['admins']; ?></div>
                        <div class="stat-label">Admin</div>
                    </div>
                </div>
                <div class="col-md-3 mb-3">
                    <div class="stat-card">
                        <div class="stat-number text-info"><?php echo $stats['new_users']; ?></div>
                        <div class="stat-label">User Baru (30 hari)</div>
                    </div>
                </div>
            </div>

            <!-- Search and Filter -->
            <div class="search-filter-card">
                <form method="GET" class="row g-3">
                    <div class="col-md-8">
                        <label for="search" class="form-label fw-medium">Cari Volunteer</label>
                        <input type="text" class="form-control" id="search" name="search" 
                               placeholder="Cari nama, email, atau nomor telepon..." 
                               value="<?php echo htmlspecialchars($search); ?>">
                    </div>
                    <div class="col-md-3">
                        <label for="role" class="form-label fw-medium">Role</label>
                        <select class="form-select" id="role" name="role">
                            <option value="">Semua Role</option>
                            <option value="user" <?php echo ($role_filter == 'user') ? 'selected' : ''; ?>>Volunteer</option>
                        </select>
                    </div>
                    <div class="col-md-1 d-flex align-items-end">
                        <button type="submit" class="btn btn-primary w-100">
                            <i class="bi bi-search"></i>
                        </button>
                    </div>
                </form>
            </div>

            <!-- Users Table -->
            <div class="card border-0 shadow-sm">
                <div class="card-header bg-light border-0 py-3">
                    <div class="d-flex justify-content-between align-items-center">
                        <h5 class="mb-0 fw-semibold">Daftar Volunteer (<?php echo count($users); ?>)</h5>
                        <?php if (!empty($search) || !empty($role_filter)): ?>
                            <a href="kelola_users.php" class="btn btn-outline-secondary btn-sm">
                                <i class="bi bi-x-circle me-1"></i>
                                Reset Filter
                            </a>
                        <?php endif; ?>
                    </div>
                </div>
                <div class="card-body p-0">
                    <?php if (count($users) > 0): ?>
                        <div class="table-responsive">
                            <table class="table table-hover mb-0">
                                <thead>
                                    <tr>
                                        <th class="fw-semibold text-muted small py-3 px-3">USER</th>
                                        <th class="fw-semibold text-muted small py-3 px-3">KONTAK</th>
                                        <th class="fw-semibold text-muted small py-3 px-3">ROLE</th>
                                        <th class="fw-semibold text-muted small py-3 px-3">AKTIVITAS</th>
                                        <th class="fw-semibold text-muted small py-3 px-3">STATISTIK</th>
                                        <th class="fw-semibold text-muted small py-3 px-3">BERGABUNG</th>
                                        <th class="fw-semibold text-muted small py-3 px-3">AKSI</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($users as $user): ?>
                                    <tr>
                                        <td class="py-3 px-3">
                                            <div class="d-flex align-items-center">
                                                <i class="bi bi-person-circle me-2" style="font-size: 2.5rem; color: #6b7280;"></i>
                                                <div>
                                                    <p class="fw-medium mb-1"><?php echo htmlspecialchars($user['full_name']); ?></p>
                                                    <p class="text-muted small mb-0"><?php echo htmlspecialchars($user['email']); ?></p>
                                                </div>
                                            </div>
                                        </td>
                                        <td class="py-3 px-3">
                                            <div>
                                                <p class="fw-medium mb-1"><?php echo htmlspecialchars($user['phone'] ?: 'Tidak ada'); ?></p>
                                                <p class="text-muted small mb-0"><?php echo htmlspecialchars(substr($user['address'] ?: 'Tidak ada', 0, 30)) . (strlen($user['address'] ?: '') > 30 ? '...' : ''); ?></p>
                                            </div>
                                        </td>
                                        <td class="py-3 px-3">
                                            <span class="role-badge <?php echo $user['role']; ?>">
                                                <?php echo ucfirst($user['role']); ?>
                                            </span>
                                        </td>
                                        <td class="py-3 px-3">
                                            <?php if ($user['last_activity']): ?>
                                                <span class="activity-indicator active">
                                                    <i class="bi bi-circle-fill me-1" style="font-size: 0.5rem;"></i>
                                                    Aktif
                                                </span>
                                                <br><small class="text-muted">Terakhir: <?php echo formatDate($user['last_activity']); ?></small>
                                            <?php else: ?>
                                                <span class="activity-indicator inactive">
                                                    <i class="bi bi-circle me-1" style="font-size: 0.5rem;"></i>
                                                    Belum aktif
                                                </span>
                                            <?php endif; ?>
                                        </td>
                                        <td class="py-3 px-3">
                                            <div class="small">
                                                <div class="d-flex justify-content-between">
                                                    <span>Total:</span>
                                                    <span class="fw-medium"><?php echo $user['total_registrations']; ?></span>
                                                </div>
                                                <div class="d-flex justify-content-between">
                                                    <span>Disetujui:</span>
                                                    <span class="fw-medium text-success"><?php echo $user['approved_registrations']; ?></span>
                                                </div>
                                                <div class="d-flex justify-content-between">
                                                    <span>Pending:</span>
                                                    <span class="fw-medium text-warning"><?php echo $user['pending_registrations']; ?></span>
                                                </div>
                                            </div>
                                        </td>
                                        <td class="py-3 px-3 fw-medium"><?php echo formatDate($user['created_at']); ?></td>
                                        <td class="py-3 px-3">
                                            <a href="details_users.php?id=<?php echo $user['id']; ?>" class="btn btn-action btn-outline-primary" title="Lihat Detail">
                                                <i class="bi bi-eye"></i>
                                            </a>
                                            <?php if ($user['role'] == 'user' && $user['pending_registrations'] > 0): ?>
                                                <a href="kelola_pendaftaran.php?user_id=<?php echo $user['id']; ?>" class="btn btn-action btn-outline-info" title="Kelola Pendaftaran">
                                                    <i class="bi bi-clipboard-check"></i>
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
                            <i class="bi bi-people text-muted" style="font-size: 4rem;"></i>
                            <h3 class="text-muted mt-3">Tidak ada volunteer ditemukan</h3>
                            <p class="text-muted">
                                <?php if (!empty($search) || !empty($role_filter)): ?>
                                    Coba ubah filter pencarian untuk melihat hasil yang berbeda.
                                <?php else: ?>
                                    Volunteer akan muncul di sini ketika mereka mendaftar ke sistem.
                                <?php endif; ?>
                            </p>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </main>


    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>