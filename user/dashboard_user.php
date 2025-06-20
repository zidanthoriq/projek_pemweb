<?php
session_start();
if (!isset($_SESSION['user_id'])) {
    header('Location: ../auth/login.php');
    exit();
}

require_once '../config/database.php';

$database = new Database();
$db = $database->getConnection();

// Fetch user data
$user_id = $_SESSION['user_id'];
$sql = "SELECT full_name, email FROM users WHERE id = ?";
$stmt = $db->prepare($sql);
$stmt->bindParam(1, $user_id);
$stmt->execute();

if ($stmt->rowCount() > 0) {
    $user = $stmt->fetch(PDO::FETCH_ASSOC);
    $_SESSION['full_name'] = $user['full_name'];
    $_SESSION['email'] = $user['email'];
} else {
    echo "User not found.";
    exit();
}

// Fetch stats
$sql = "SELECT 
            COUNT(CASE WHEN status != 'pending' THEN 1 END) as total_registrations,
            COUNT(CASE WHEN status = 'approved' THEN 1 END) as approved_registrations,
            COUNT(CASE WHEN status = 'pending' THEN 1 END) as pending_registrations
        FROM registrations WHERE user_id = ?";
$stmt = $db->prepare($sql);
$stmt->bindParam(1, $user_id);
$stmt->execute();
$stats = $stmt->fetch(PDO::FETCH_ASSOC);

// Fetch upcoming events - GANTI QUERY INI
$sql = "SELECT r.id, e.title, e.location, e.event_date, e.start_time, e.end_time, r.status 
        FROM registrations r
        JOIN events e ON r.event_id = e.id
        WHERE r.user_id = ? AND e.event_date >= CURDATE() AND r.status IN ('approved', 'pending')
        ORDER BY e.event_date ASC LIMIT 5";
$stmt = $db->prepare($sql);
$stmt->bindParam(1, $user_id);
$stmt->execute();
$upcoming_events = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Fetch recommended events (events not registered by user)
$sql = "SELECT e.id, e.title, e.description, e.location, e.event_date, e.start_time, e.end_time, e.image_path,
               COUNT(r.user_id) AS current_participants, e.max_participants
        FROM events e
        LEFT JOIN registrations r ON e.id = r.event_id
        WHERE e.id NOT IN (SELECT event_id FROM registrations WHERE user_id = ?) 
        AND e.event_date >= CURDATE() AND e.status = 'active'
        GROUP BY e.id
        ORDER BY e.event_date ASC LIMIT 3";
$stmt = $db->prepare($sql);
$stmt->bindParam(1, $user_id);
$stmt->execute();
$recommended_events = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard - VolunteerHub</title>
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Bootstrap Icons -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <!-- Inter Font -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <!-- Custom CSS -->
    <style>
        body {
            font-family: 'Inter', sans-serif;
            background-color: #f8f9fa;
        }

        /* Navbar Styles */
        .navbar-custom {
            background-color: white;
            box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1);
            padding: 0.75rem 0;
        }

        .navbar-brand {
            font-size: 1.25rem;
            font-weight: 700;
            color: #1f2937 !important;
        }

        .navbar-nav .nav-link {
            color: #6b7280 !important;
            font-size: 0.875rem;
            font-weight: 500;
            padding: 0.25rem 0.5rem;
            margin: 0 0.5rem;
            border-bottom: 2px solid transparent;
            transition: all 0.2s;
        }

        .navbar-nav .nav-link:hover {
            color: #374151 !important;
        }

        .navbar-nav .nav-link.active {
            color: #2563eb !important;
            border-bottom-color: #2563eb;
        }

        /* Search Bar */
        .search-container {
            position: relative;
            width: 100%;
            max-width: 32rem;
        }

        .search-icon {
            position: absolute;
            left: 0.75rem;
            top: 50%;
            transform: translateY(-50%);
            font-size: 1.25rem;
            color: #6b7280;
            pointer-events: none;
        }

        .search-input {
            padding-left: 2.5rem;
            border: 1px solid #d1d5db;
            border-radius: 0.375rem;
            font-size: 0.875rem;
        }

        .search-input:focus {
            border-color: #3b82f6;
            box-shadow: 0 0 0 0.2rem rgba(59, 130, 246, 0.25);
        }

        /* Cards */
        .card-custom {
            border: none;
            border-radius: 0.5rem;
            box-shadow: 0 1px 3px 0 rgba(0, 0, 0, 0.1);
            margin-bottom: 1.5rem;
            overflow: hidden;
        }

        .card-header-custom {
            background-color: #f9fafb;
            border-bottom: 1px solid #e5e7eb;
            padding: 1rem;
        }

        .card-footer-custom {
            background-color: #f9fafb;
            border-top: 1px solid #e5e7eb;
            padding: 1rem;
        }

        /* Stats Cards */
        .stat-card {
            display: flex;
            align-items: center;
        }

        .stat-icon {
            display: flex;
            align-items: center;
            justify-content: center;
            width: 3rem;
            height: 3rem;
            border-radius: 0.375rem;
            margin-right: 1.25rem;
            flex-shrink: 0;
        }

        .stat-icon i {
            font-size: 1.5rem;
        }

        .stat-icon.blue { background-color: #3b82f6; }
        .stat-icon.green { background-color: #10b981; }
        .stat-icon.yellow { background-color: #f59e0b; }

        .stat-title {
            font-size: 0.875rem;
            font-weight: 500;
            color: #6b7280;
            margin-bottom: 0.25rem;
        }

        .stat-value {
            font-size: 1.5rem;
            font-weight: 600;
            color: #1f2937;
            margin-bottom: 0;
        }

        /* Event List */
        .event-list {
            list-style: none;
            padding: 0;
            margin: 0;
        }

        .event-item {
            border-bottom: 1px solid #e5e7eb;
        }

        .event-item:last-child {
            border-bottom: none;
        }

        .event-link {
            display: block;
            padding: 1rem;
            color: inherit;
            text-decoration: none;
            transition: background-color 0.2s;
        }

        .event-link:hover {
            background-color: #f9fafb;
        }

        .event-title {
            font-size: 0.875rem;
            font-weight: 500;
            color: #2563eb;
            margin-bottom: 0.5rem;
        }

        .event-badge {
            display: inline-flex;
            align-items: center;
            padding: 0.25rem 0.5rem;
            border-radius: 9999px;
            font-size: 0.75rem;
            font-weight: 600;
        }

        .event-badge.green {
            background-color: #d1fae5;
            color: #065f46;
        }

        .event-badge.yellow {
            background-color: #fef3c7;
            color: #92400e;
        }

        .event-badge.red {
            background-color: #fee2e2;
            color: #991b1b;
        }

        .event-info {
            display: flex;
            flex-wrap: wrap;
            font-size: 0.875rem;
            color: #6b7280;
        }

        .event-info-item {
            display: flex;
            align-items: center;
            margin-right: 1.5rem;
            margin-bottom: 0.5rem;
        }

        .event-info-item i {
            font-size: 1.25rem;
            margin-right: 0.375rem;
            color: #6b7280;
        }

        /* Quick Actions */
        .quick-action {
            display: flex;
            align-items: center;
            padding: 0.75rem;
            border-radius: 0.5rem;
            margin-bottom: 0.75rem;
            transition: background-color 0.2s;
            text-decoration: none;
        }

        .quick-action i {
            font-size: 1.5rem;
            margin-right: 0.75rem;
        }

        .quick-action span {
            font-size: 0.875rem;
            font-weight: 500;
            color: #1f2937;
        }

        .quick-action.blue {
            background-color: #eff6ff;
        }

        .quick-action.blue:hover {
            background-color: #dbeafe;
        }

        .quick-action.green {
            background-color: #ecfdf5;
        }

        .quick-action.green:hover {
            background-color: #d1fae5;
        }

        .quick-action.purple {
            background-color: #f5f3ff;
        }

        .quick-action.purple:hover {
            background-color: #ede9fe;
        }

        /* Event Cards - Updated for consistency */
        .event-card-image {
            height: 12rem;
            object-fit: cover;
            flex-shrink: 0;
        }

        .card-custom.h-100 {
            display: flex;
            flex-direction: column;
        }

        .card-body {
            display: flex;
            flex-direction: column;
            flex-grow: 1;
        }

        .event-card-title {
            font-size: 1.125rem;
            font-weight: 500;
            color: #1f2937;
            margin-bottom: 0.75rem;
            height: 3rem;
            display: -webkit-box;
            -webkit-line-clamp: 2;
            -webkit-box-orient: vertical;
            overflow: hidden;
            line-height: 1.5;
        }

        .event-card-desc {
            font-size: 0.875rem;
            color: #6b7280;
            margin-bottom: 1rem;
            height: 2.5rem;
            display: -webkit-box;
            -webkit-line-clamp: 2;
            -webkit-box-orient: vertical;
            overflow: hidden;
            line-height: 1.25;
        }

        .event-card-info {
            display: flex;
            align-items: center;
            font-size: 0.875rem;
            color: #6b7280;
            margin-bottom: 0.5rem;
            height: 1.25rem;
        }

        .event-card-info i {
            font-size: 1.25rem;
            margin-right: 0.375rem;
            color: #6b7280;
            width: 1.25rem;
            flex-shrink: 0;
        }

        .event-card-info span {
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
        }

        .event-card-quota {
            margin-top: auto;
            padding-top: 1rem;
        }

        .event-card-button {
            margin-top: 1rem;
        }

        /* Progress Bar */
        .progress-custom {
            height: 0.5rem;
            border-radius: 9999px;
            background-color: #e5e7eb;
            margin: 0.5rem 0;
        }

        .progress-bar-custom {
            height: 100%;
            border-radius: 9999px;
        }

        .progress-bar-blue {
            background-color: #3b82f6;
        }

        .progress-bar-green {
            background-color: #10b981;
        }

        .progress-bar-orange {
            background-color: #f59e0b;
        }

        .progress-label {
            display: flex;
            justify-content: space-between;
            font-size: 0.75rem;
            color: #6b7280;
        }

        /* Footer */
        .footer-custom {
            background-color: white;
            border-top: 1px solid #e5e7eb;
            margin-top: 3rem;
            padding: 3rem 0;
        }

        .footer-brand {
            font-size: 1.5rem;
            font-weight: 700;
            color: #1f2937;
            margin-bottom: 1rem;
        }

        .footer-desc {
            color: #6b7280;
            line-height: 1.5;
            margin-bottom: 2rem;
        }

        .footer-nav {
            display: flex;
            flex-wrap: wrap;
            justify-content: center;
            gap: 2rem;
            margin-bottom: 2rem;
        }

        .footer-nav a {
            color: #6b7280;
            text-decoration: none;
            font-size: 0.875rem;
            font-weight: 500;
            transition: color 0.2s;
        }

        .footer-nav a:hover {
            color: #2563eb;
        }

        .footer-copyright {
            font-size: 0.875rem;
            color: #9ca3af;
            text-align: center;
        }

        /* Responsive adjustments */
        @media (max-width: 767px) {
            .navbar-nav {
                display: none;
            }
            
            .search-container {
                margin: 0 1rem;
            }
            
            .event-info {
                flex-direction: column;
            }
            
            .event-info-item {
                margin-bottom: 0.5rem;
            }
        }
</style>
</head>
<body>

<!-- Top Navbar -->
<nav class="navbar navbar-expand-lg navbar-custom">
    <div class="container">
        <div class="d-flex align-items-center">
            <!-- Logo and Brand -->
            <a class="navbar-brand" href="#">VolunteerHub</a>
            
            <!-- Navigation Menu -->
            <ul class="navbar-nav d-none d-md-flex">
                <li class="nav-item">
                    <a class="nav-link active" href="dashboard_user.php">Dashboard</a>
                </li>
                <li class="nav-item">
                    <a class="nav-link" href="search.php">Cari Event</a>
                </li>
                <li class="nav-item">
                    <a class="nav-link" href="pendaftaran.php">Pendaftaran Saya</a>
                </li>
            </ul>
        </div>

        <!-- Search Bar -->
        <div class="search-container mx-auto">
            <form action="search.php" method="GET" class="position-relative">
                <i class="bi bi-search search-icon"></i>
                <input type="search" 
                       name="search" 
                       class="form-control search-input" 
                       placeholder="Cari event..."
                       value="<?php echo isset($_GET['search']) ? htmlspecialchars($_GET['search']) : ''; ?>">
                <button type="submit" class="btn btn-link position-absolute" 
                        style="right: 0.5rem; top: 50%; transform: translateY(-50%); border: none; background: none; color: #6b7280;">
                    <i class="bi bi-arrow-right"></i>
                </button>
            </form>
        </div>


        <!-- User Profile Dropdown -->
        <div class="dropdown">
            <button class="btn btn-link dropdown-toggle d-flex align-items-center text-decoration-none" type="button" data-bs-toggle="dropdown">
                <i class="bi bi-person-circle me-2" style="font-size: 2rem; color: #6b7280;"></i>
                <span class="text-dark fw-medium"><?php echo $_SESSION['full_name']; ?></span>
            </button>
            <ul class="dropdown-menu dropdown-menu-end">
                <li class="px-3 py-2 border-bottom">
                    <p class="mb-0 fw-medium"><?php echo $_SESSION['full_name']; ?></p>
                    <p class="mb-0 text-muted small"><?php echo $_SESSION['email']; ?></p>
                </li>
                <li><a class="dropdown-item d-flex align-items-center" href="pendaftaran.php">
                    <i class="bi bi-clipboard-check me-2"></i>
                    Riwayat Pendaftaran
                </a></li>
                <li><a class="dropdown-item d-flex align-items-center" href="profile.php">
                    <i class="bi bi-person me-2"></i>
                    Profil Saya
                </a></li>
                <li><hr class="dropdown-divider"></li>
                <li><a class="dropdown-item d-flex align-items-center" href="../auth/logout.php">
                    <i class="bi bi-box-arrow-right me-2"></i>
                    Logout
                </a></li>
            </ul>
        </div>
    </div>
</nav>

    <div class="container mt-5">
        <!-- Stats Cards -->
<div class="row mb-4">
    <!-- Stat card 1 -->
    <div class="col-md-4 mb-3">
        <div class="card card-custom h-100">
            <div class="card-body p-4">
                <div class="stat-card">
                    <div class="stat-icon blue">
                        <i class="bi bi-people-fill text-white"></i>
                    </div>
                    <div>
                        <p class="stat-title">Total Event Diikuti</p>
                        <p class="stat-value"><?php echo $stats['total_registrations']; ?></p>
                    </div>
                </div>
            </div>
            <div class="card-footer-custom">
                <a href="pendaftaran.php" class="text-decoration-none fw-medium text-primary">Lihat semua</a>
            </div>
        </div>
    </div>

    <!-- Stat card 2 -->
    <div class="col-md-4 mb-3">
        <div class="card card-custom h-100">
            <div class="card-body p-4">
                <div class="stat-card">
                    <div class="stat-icon green">
                        <i class="bi bi-check-circle-fill text-white"></i>
                    </div>
                    <div>
                        <p class="stat-title">Pendaftaran Diterima</p>
                        <p class="stat-value"><?php echo $stats['approved_registrations']; ?></p>
                    </div>
                </div>
            </div>
            <div class="card-footer-custom">
                <a href="pendaftaran.php?status=approved" class="text-decoration-none fw-medium text-primary">Lihat detail</a>
            </div>
        </div>
    </div>

    <!-- Stat card 3 -->
    <div class="col-md-4 mb-3">
        <div class="card card-custom h-100">
            <div class="card-body p-4">
                <div class="stat-card">
                    <div class="stat-icon yellow">
                        <i class="bi bi-clock-fill text-white"></i>
                    </div>
                    <div>
                        <p class="stat-title">Pendaftaran Menunggu</p>
                        <p class="stat-value"><?php echo $stats['pending_registrations']; ?></p>
                    </div>
                </div>
            </div>
            <div class="card-footer-custom">
                <a href="pendaftaran.php?status=pending" class="text-decoration-none fw-medium text-primary">Lihat detail</a>
            </div>
        </div>
    </div>
</div>

        <div class="row">
            <!-- Left Column - Upcoming Events -->
<div class="col-lg-9 mb-4">
    <h2 class="h5 fw-medium text-dark mb-3">Event Mendatang</h2>
    <div class="card card-custom">
        <?php if (count($upcoming_events) > 0): ?>
            <ul class="event-list">
                <?php foreach ($upcoming_events as $event): ?>
                <li class="event-item">
                    <a href="event_details.php?id=<?php echo $event['id']; ?>" class="event-link">
                        <div class="d-flex justify-content-between mb-2">
                            <p class="event-title"><?php echo htmlspecialchars($event['title']); ?></p>
                            <span class="event-badge <?php 
    switch($event['status']) {
        case 'approved': echo 'green'; break;
        case 'pending': echo 'yellow'; break;
        case 'rejected': echo 'red'; break;
        case 'cancelled': echo 'red'; break;
        default: echo 'yellow';
    }
?>">
    <?php 
    switch($event['status']) {
        case 'approved': echo 'Diterima'; break;
        case 'pending': echo 'Menunggu'; break;
        case 'rejected': echo 'Ditolak'; break;
        case 'cancelled': echo 'Dibatalkan'; break;
        default: echo 'Menunggu';
    }
    ?>
</span>
                        </div>
                        <div class="event-info">
                            <div class="event-info-item">
                                <i class="bi bi-geo-alt-fill"></i>
                                <?php echo htmlspecialchars($event['location']); ?>
                            </div>
                            <div class="event-info-item">
                                <i class="bi bi-calendar-event"></i>
                                <?php echo formatDate($event['event_date']); ?>
                            </div>
                            <div class="event-info-item">
                                <i class="bi bi-clock"></i>
                                <?php echo formatTime($event['start_time']); ?> - <?php echo formatTime($event['end_time']); ?> WIB
                            </div>
                        </div>
                    </a>
                </li>
                <?php endforeach; ?>
            </ul>
        <?php else: ?>
            <div class="text-center py-5">
                <i class="bi bi-calendar-x text-muted" style="font-size: 4rem;"></i>
                <h3 class="text-muted mt-3">Tidak ada event mendatang</h3>
                <p class="text-muted">Anda belum terdaftar di event yang akan datang.</p>
                <a href="search.php" class="btn btn-primary">Cari Event</a>
            </div>
        <?php endif; ?>
    </div>
</div>

            <!-- Right Column - Quick Actions -->
<div class="col-lg-3 mb-4">
    <h3 class="h5 fw-medium text-dark mb-3">Aksi Cepat</h3>
    <div class="card card-custom">
        <div class="card-body p-3">
            <a href="search.php" class="quick-action blue">
                <i class="bi bi-search"></i>
                <span>Cari Event Baru</span>
            </a>
            <a href="pendaftaran.php" class="quick-action green">
                <i class="bi bi-clipboard-data"></i>
                <span>Lihat Riwayat</span>
            </a>
            <a href="profile.php" class="quick-action purple">
                <i class="bi bi-person-gear"></i>
                <span>Edit Profil</span>
            </a>
        </div>
    </div>
</div>
        </div>

        <!-- Recommended Events -->
        <h2 class="mt-4 mb-3">Event yang Direkomendasikan</h2>
        <div class="row">
            <?php if (count($recommended_events) > 0): ?>
                <?php foreach ($recommended_events as $event): ?>
<div class="col-md-4 mb-4">
    <div class="card card-custom h-100">
        <?php if (!empty($event['image_path']) && file_exists('../' . $event['image_path'])): ?>
            <img src="../<?php echo htmlspecialchars($event['image_path']); ?>" 
                 alt="<?php echo htmlspecialchars($event['title']); ?>" 
                 class="event-card-image">
        <?php else: ?>
            <img src="/placeholder.svg?height=192&width=400&text=<?php echo urlencode($event['title']); ?>" 
                 alt="<?php echo htmlspecialchars($event['title']); ?>" 
                 class="event-card-image">
        <?php endif; ?>
        <div class="card-body p-4">
            <h3 class="event-card-title"><?php echo htmlspecialchars($event['title']); ?></h3>
            <p class="event-card-desc"><?php echo htmlspecialchars(substr($event['description'], 0, 100)) . '...'; ?></p>
            
            <div class="event-card-info">
                <i class="bi bi-calendar-event"></i>
                <span><?php echo formatDate($event['event_date']); ?></span>
            </div>
            <div class="event-card-info">
                <i class="bi bi-geo-alt-fill"></i>
                <span><?php echo htmlspecialchars($event['location']); ?></span>
            </div>
            
            <!-- Quota Progress Bar -->
            <div class="event-card-quota">
                <?php 
                $percentage = ($event['current_participants'] / $event['max_participants']) * 100;
                $remaining = $event['max_participants'] - $event['current_participants'];
                ?>
                <div class="d-flex justify-content-between align-items-center">
                    <span class="small fw-medium text-muted">Kuota Tersisa</span>
                    <span class="small fw-medium text-dark"><?php echo round($percentage); ?>%</span>
                </div>
                <div class="progress-custom">
                    <div class="progress-bar-custom progress-bar-blue" style="width: <?php echo $percentage; ?>%"></div>
                </div>
                <div class="progress-label">
                    <span><?php echo $event['current_participants']; ?>/<?php echo $event['max_participants']; ?> peserta</span>
                    <span>Tersisa <?php echo $remaining; ?> slot</span>
                </div>
            </div>
            
            <div class="event-card-button">
                <a href="event_details.php?id=<?php echo $event['id']; ?>" class="btn btn-primary w-100">Daftar Sekarang</a>
            </div>
        </div>
    </div>
</div>
<?php endforeach; ?>
            <?php else: ?>
                <div class="col-12 text-center">
                    <p class="text-muted">Tidak ada event yang direkomendasikan untuk saat ini.</p>
                </div>
            <?php endif; ?>
        </div>
    </div>

    <!-- Footer -->
<footer class="footer-custom">
    <div class="container">
        <div class="text-center">
            <!-- Company Info -->
            <div class="mb-4">
                <h2 class="footer-brand">VolunteerHub</h2>
                <p class="footer-desc mx-auto" style="max-width: 48rem;">
                    Platform terpercaya untuk menghubungkan volunteer dengan berbagai kegiatan sosial dan lingkungan yang bermakna. Bergabunglah dengan komunitas yang peduli untuk membuat perubahan positif bagi masyarakat dan lingkungan sekitar kita.
                </p>
            </div>

            <!-- Navigation Links -->
            <div class="footer-nav">
                <a href="dashboard_user.php">Dashboard</a>
                <a href="search.php">Cari Event</a>
                <a href="pendaftaran.php">Pendaftaran Saya</a>
                <a href="profile.php">Profil Saya</a>
            </div>

            <!-- Copyright -->
            <div class="border-top pt-4 mt-4">
                <p class="footer-copyright">
                    © 2025 VolunteerHub. Semua hak dilindungi undang-undang.
                </p>
            </div>
        </div>
    </div>
</footer>

    <!-- Bootstrap Bundle with Popper -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
