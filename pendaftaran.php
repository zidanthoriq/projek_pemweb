<?php
require_once '../config/database.php';
requireLogin();

$database = new Database();
$db = $database->getConnection();

$user_id = $_SESSION['user_id'];
$status_filter = isset($_GET['status']) ? sanitizeInput($_GET['status']) : '';
$search = isset($_GET['search']) ? sanitizeInput($_GET['search']) : '';

// Build query
$where_conditions = ["r.user_id = :user_id"];
$params = [':user_id' => $user_id];

if (!empty($status_filter)) {
    $where_conditions[] = "r.status = :status";
    $params[':status'] = $status_filter;
}

if (!empty($search)) {
    $where_conditions[] = "e.title LIKE :search";
    $params[':search'] = '%' . $search . '%';
}

$where_clause = implode(' AND ', $where_conditions);

try {
    // Get registrations with event details
    $registrations_query = "SELECT r.*, e.title, e.description, e.location, e.event_date, e.start_time, e.end_time, e.organizer, e.image_path
                           FROM registrations r
                           JOIN events e ON r.event_id = e.id
                           WHERE $where_clause
                           ORDER BY r.registration_date DESC";
    
    $registrations_stmt = $db->prepare($registrations_query);
    foreach ($params as $key => $value) {
        $registrations_stmt->bindValue($key, $value);
    }
    $registrations_stmt->execute();
    $registrations = $registrations_stmt->fetchAll(PDO::FETCH_ASSOC);

} catch(PDOException $exception) {
    $error = "Error: " . $exception->getMessage();
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Pendaftaran Saya - VolunteerHub</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
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

        .filter-card {
            background: white;
            border-radius: 0.75rem;
            padding: 1.5rem;
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
            margin-bottom: 2rem;
        }

        .event-card {
            background: white;
            border-radius: 0.75rem;
            overflow: hidden;
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
            transition: transform 0.2s, box-shadow 0.2s;
            height: 100%;
            display: flex;
            flex-direction: column;
        }

        .event-card:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 8px rgba(0, 0, 0, 0.15);
        }

        .event-image {
            height: 200px;
            object-fit: cover;
            flex-shrink: 0;
        }

        .event-card-body {
            padding: 1.5rem;
            display: flex;
            flex-direction: column;
            flex-grow: 1;
        }

        .event-card-title {
            font-size: 1.125rem;
            font-weight: 600;
            color: #1f2937;
            margin-bottom: 0.75rem;
        }

        .event-card-desc {
            font-size: 0.875rem;
            color: #6b7280;
            margin-bottom: 1rem;
            flex-grow: 1;
        }

        .event-card-info {
            display: flex;
            align-items: center;
            font-size: 0.875rem;
            color: #6b7280;
            margin-bottom: 0.5rem;
        }

        .event-card-info i {
            font-size: 1rem;
            margin-right: 0.5rem;
            color: #6b7280;
            width: 1rem;
            flex-shrink: 0;
        }

        .status-badge {
            display: inline-flex;
            align-items: center;
            padding: 0.5rem 0.75rem;
            border-radius: 9999px;
            font-size: 0.875rem;
            font-weight: 600;
            margin-top: auto;
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
                        <a class="nav-link" href="dashboard_user.php">Dashboard</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="search.php">Cari Event</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link active" href="pendaftaran.php">Pendaftaran Saya</a>
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

    <!-- Main Content -->
    <div class="container py-5">
        <!-- Page Header -->
        <div class="mb-4">
            <h1 class="h3 fw-semibold text-dark mb-2">Pendaftaran Saya</h1>
            <p class="text-muted">Lihat semua pendaftaran event volunteer Anda</p>
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
                <div class="col-md-6">
                    <label for="search" class="form-label fw-medium">Cari Event</label>
                    <input type="text" class="form-control" id="search" name="search" 
                           placeholder="Cari nama event..." value="<?php echo htmlspecialchars($search); ?>">
                </div>
                <div class="col-md-2 d-flex align-items-end">
                    <button type="submit" class="btn btn-primary w-100">
                        <i class="bi bi-search me-1"></i>
                        Filter
                    </button>
                </div>
            </form>
        </div>

        <!-- Results -->
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h2 class="h5 fw-semibold mb-0">
                <?php if (!empty($status_filter) || !empty($search)): ?>
                    Hasil Filter (<?php echo count($registrations); ?> pendaftaran)
                <?php else: ?>
                    Semua Pendaftaran (<?php echo count($registrations); ?> pendaftaran)
                <?php endif; ?>
            </h2>
            <?php if (!empty($status_filter) || !empty($search)): ?>
                <a href="pendaftaran.php" class="btn btn-outline-secondary">
                    <i class="bi bi-x-circle me-1"></i>
                    Reset Filter
                </a>
            <?php endif; ?>
        </div>

        <!-- Registrations Grid -->
        <?php if (count($registrations) > 0): ?>
            <div class="row">
                <?php foreach ($registrations as $registration): ?>
                <div class="col-lg-4 col-md-6 mb-4">
                    <div class="card event-card">
                        <?php if (!empty($registration['image_path']) && file_exists('../' . $registration['image_path'])): ?>
                            <img src="../<?php echo htmlspecialchars($registration['image_path']); ?>" 
                                 alt="<?php echo htmlspecialchars($registration['title']); ?>" 
                                 class="event-image">
                        <?php else: ?>
                            <img src="/placeholder.svg?height=200&width=400&text=<?php echo urlencode($registration['title']); ?>" 
                                 alt="<?php echo htmlspecialchars($registration['title']); ?>" 
                                 class="event-image">
                        <?php endif; ?>
                        <div class="event-card-body">
                            <h5 class="event-card-title"><?php echo htmlspecialchars($registration['title']); ?></h5>
                            <p class="event-card-desc">
                                <?php echo htmlspecialchars(substr($registration['description'], 0, 100)) . '...'; ?>
                            </p>
                            
                            <div class="event-card-info">
                                <i class="bi bi-calendar"></i>
                                <span><?php echo formatDate($registration['event_date']); ?></span>
                            </div>
                            <div class="event-card-info">
                                <i class="bi bi-clock"></i>
                                <span><?php echo formatTime($registration['start_time']); ?> - <?php echo formatTime($registration['end_time']); ?></span>
                            </div>
                            <div class="event-card-info">
                                <i class="bi bi-geo-alt"></i>
                                <span><?php echo htmlspecialchars($registration['location']); ?></span>
                            </div>
                            <div class="event-card-info">
                                <i class="bi bi-building"></i>
                                <span><?php echo htmlspecialchars($registration['organizer']); ?></span>
                            </div>
                            <div class="event-card-info">
                                <i class="bi bi-calendar-plus"></i>
                                <span>Daftar: <?php echo formatDate($registration['registration_date']); ?></span>
                            </div>
                            
                            <div class="mt-3">
                                <span class="status-badge <?php echo $registration['status']; ?>">
                                    <i class="bi bi-<?php 
                                        switch($registration['status']) {
                                            case 'pending': echo 'clock'; break;
                                            case 'approved': echo 'check-circle'; break;
                                            case 'rejected': echo 'x-circle'; break;
                                            case 'cancelled': echo 'dash-circle'; break;
                                        }
                                    ?> me-1"></i>
                                    <?php 
                                    switch($registration['status']) {
                                        case 'pending': echo 'Menunggu Persetujuan'; break;
                                        case 'approved': echo 'Disetujui'; break;
                                        case 'rejected': echo 'Ditolak'; break;
                                        case 'cancelled': echo 'Dibatalkan'; break;
                                        default: echo 'Status Tidak Diketahui';
                                    }
                                    ?>
                                </span>
                            </div>
                            
                            <div class="mt-3">
                                <a href="event_details.php?id=<?php echo $registration['event_id']; ?>" class="btn btn-outline-primary w-100">
                                    Lihat Detail Event
                                </a>
                            </div>
                        </div>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
        <?php else: ?>
            <div class="text-center py-5">
                <i class="bi bi-clipboard-x text-muted" style="font-size: 4rem;"></i>
                <h3 class="text-muted mt-3">Tidak ada pendaftaran ditemukan</h3>
                <p class="text-muted">
                    <?php if (!empty($status_filter) || !empty($search)): ?>
                        Coba ubah filter atau reset pencarian.
                    <?php else: ?>
                        Anda belum mendaftar di event manapun. Mulai cari event yang menarik!
                    <?php endif; ?>
                </p>
                <a href="search.php" class="btn btn-primary">
                    <i class="bi bi-search me-1"></i>
                    Cari Event
                </a>
            </div>
        <?php endif; ?>
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

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
