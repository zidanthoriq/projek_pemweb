<?php
require_once '../config/database.php';
requireLogin();

$database = new Database();
$db = $database->getConnection();

$search = isset($_GET['search']) ? sanitizeInput($_GET['search']) : '';
$location = isset($_GET['location']) ? sanitizeInput($_GET['location']) : '';
$date_from = isset($_GET['date_from']) ? $_GET['date_from'] : '';
$date_to = isset($_GET['date_to']) ? $_GET['date_to'] : '';

// Build query
$where_conditions = ["e.status = 'active'", "e.event_date >= CURDATE()"];
$params = [];

if (!empty($search)) {
    $where_conditions[] = "(e.title LIKE :search OR e.description LIKE :search OR e.organizer LIKE :search)";
    $params[':search'] = '%' . $search . '%';
}

if (!empty($location)) {
    $where_conditions[] = "e.location LIKE :location";
    $params[':location'] = '%' . $location . '%';
}

if (!empty($date_from)) {
    $where_conditions[] = "e.event_date >= :date_from";
    $params[':date_from'] = $date_from;
}

if (!empty($date_to)) {
    $where_conditions[] = "e.event_date <= :date_to";
    $params[':date_to'] = $date_to;
}

$where_clause = implode(' AND ', $where_conditions);

try {
    $events_query = "SELECT e.*, COUNT(r.id) as current_participants 
                    FROM events e 
                    LEFT JOIN registrations r ON e.id = r.event_id AND r.status = 'approved'
                    WHERE $where_clause
                    GROUP BY e.id 
                    ORDER BY e.event_date ASC";
    
    $events_stmt = $db->prepare($events_query);
    foreach ($params as $key => $value) {
        $events_stmt->bindValue($key, $value);
    }
    $events_stmt->execute();
    $events = $events_stmt->fetchAll(PDO::FETCH_ASSOC);

    // Get unique locations for filter
    $locations_query = "SELECT DISTINCT location FROM events WHERE status = 'active' ORDER BY location";
    $locations_stmt = $db->prepare($locations_query);
    $locations_stmt->execute();
    $locations = $locations_stmt->fetchAll(PDO::FETCH_COLUMN);

} catch(PDOException $exception) {
    $error = "Error: " . $exception->getMessage();
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Cari Event - VolunteerHub</title>
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

        /* Event Cards - Updated for consistency */
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
            font-size: 1rem;
            margin-right: 0.5rem;
            color: #6b7280;
            width: 1rem;
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

        .progress-bar-red {
            background-color: #ef4444;
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

        .search-container form {
            width: 100%;
        }

        .search-container .btn-link:hover {
            color: #374151 !important;
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
                    <a class="nav-link active" href="search.php">Cari Event</a>
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
                       value="<?php echo htmlspecialchars($search); ?>">
                <!-- Preserve other filter values -->
                <?php if (!empty($location)): ?>
                    <input type="hidden" name="location" value="<?php echo htmlspecialchars($location); ?>">
                <?php endif; ?>
                <?php if (!empty($date_from)): ?>
                    <input type="hidden" name="date_from" value="<?php echo htmlspecialchars($date_from); ?>">
                <?php endif; ?>
                <?php if (!empty($date_to)): ?>
                    <input type="hidden" name="date_to" value="<?php echo htmlspecialchars($date_to); ?>">
                <?php endif; ?>
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
        <!-- Search Filters -->
        <div class="filter-card">
            <form method="GET" class="row g-3">
                <div class="col-md-4">
                    <label for="search" class="form-label fw-medium">Kata Kunci</label>
                    <input type="text" class="form-control" id="search" name="search" 
                           placeholder="Cari nama event, organizer..." value="<?php echo htmlspecialchars($search); ?>">
                </div>
                <div class="col-md-3">
                    <label for="location" class="form-label fw-medium">Lokasi</label>
                    <select class="form-select" id="location" name="location">
                        <option value="">Semua Lokasi</option>
                        <?php foreach ($locations as $loc): ?>
                            <option value="<?php echo htmlspecialchars($loc); ?>" 
                                    <?php echo ($location == $loc) ? 'selected' : ''; ?>>
                                <?php echo htmlspecialchars($loc); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-md-2">
                    <label for="date_from" class="form-label fw-medium">Dari Tanggal</label>
                    <input type="date" class="form-control" id="date_from" name="date_from" value="<?php echo $date_from; ?>">
                </div>
                <div class="col-md-2">
                    <label for="date_to" class="form-label fw-medium">Sampai Tanggal</label>
                    <input type="date" class="form-control" id="date_to" name="date_to" value="<?php echo $date_to; ?>">
                </div>
                <div class="col-md-1 d-flex align-items-end">
                    <button type="submit" class="btn btn-primary w-100">
                        <i class="bi bi-search"></i>
                    </button>
                </div>
            </form>
        </div>

        <!-- Results -->
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h2 class="h4 fw-semibold mb-0">
                <?php if (!empty($search) || !empty($location) || !empty($date_from) || !empty($date_to)): ?>
                    Hasil Pencarian (<?php echo count($events); ?> event)
                <?php else: ?>
                    Semua Event Tersedia (<?php echo count($events); ?> event)
                <?php endif; ?>
            </h2>
            <?php if (!empty($search) || !empty($location) || !empty($date_from) || !empty($date_to)): ?>
                <a href="search.php" class="btn btn-outline-secondary">
                    <i class="bi bi-x-circle me-1"></i>
                    Reset Filter
                </a>
            <?php endif; ?>
        </div>

        <!-- Events Grid -->
        <?php if (count($events) > 0): ?>
            <div class="row">
                <?php foreach ($events as $event): ?>
                <div class="col-lg-4 col-md-6 mb-4">
                    <div class="card event-card">
                        <?php if (!empty($event['image_path']) && file_exists('../' . $event['image_path'])): ?>
                            <img src="../<?php echo htmlspecialchars($event['image_path']); ?>" 
                                 alt="<?php echo htmlspecialchars($event['title']); ?>" 
                                 class="event-image">
                        <?php else: ?>
                            <img src="/placeholder.svg?height=200&width=400&text=<?php echo urlencode($event['title']); ?>" 
                                 alt="<?php echo htmlspecialchars($event['title']); ?>" 
                                 class="event-image">
                        <?php endif; ?>
                        <div class="event-card-body">
                            <h5 class="event-card-title"><?php echo htmlspecialchars($event['title']); ?></h5>
                            <p class="event-card-desc">
                                <?php echo htmlspecialchars(substr($event['description'], 0, 100)) . '...'; ?>
                            </p>
                            
                            <div class="event-card-info">
                                <i class="bi bi-calendar"></i>
                                <span><?php echo formatDate($event['event_date']); ?></span>
                            </div>
                            <div class="event-card-info">
                                <i class="bi bi-clock"></i>
                                <span><?php echo formatTime($event['start_time']); ?> - <?php echo formatTime($event['end_time']); ?></span>
                            </div>
                            <div class="event-card-info">
                                <i class="bi bi-geo-alt"></i>
                                <span><?php echo htmlspecialchars($event['location']); ?></span>
                            </div>
                            
                            <!-- Quota Progress Bar -->
                            <div class="event-card-quota">
                                <?php 
                                $percentage = ($event['current_participants'] / $event['max_participants']) * 100;
                                $remaining = $event['max_participants'] - $event['current_participants'];
                                
                                $progress_class = 'progress-bar-blue';
                                if ($percentage >= 80) $progress_class = 'progress-bar-red';
                                elseif ($percentage >= 60) $progress_class = 'progress-bar-orange';
                                elseif ($percentage >= 40) $progress_class = 'progress-bar-green';
                                ?>
                                <div class="d-flex justify-content-between align-items-center">
                                    <span class="small fw-medium text-muted">Kuota Tersisa</span>
                                    <span class="small fw-medium text-dark"><?php echo number_format($percentage, 0); ?>%</span>
                                </div>
                                <div class="progress-custom">
                                    <div class="progress-bar-custom <?php echo $progress_class; ?>" style="width: <?php echo $percentage; ?>%"></div>
                                </div>
                                <div class="d-flex justify-content-between">
                                    <span class="small text-muted"><?php echo $event['current_participants']; ?>/<?php echo $event['max_participants']; ?> peserta</span>
                                    <span class="small text-muted">Tersisa <?php echo $remaining; ?> slot</span>
                                </div>
                            </div>
                            
                            <div class="event-card-button">
                                <a href="event_details.php?id=<?php echo $event['id']; ?>" class="btn btn-primary w-100">
                                    Lihat Detail
                                </a>
                            </div>
                        </div>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
        <?php else: ?>
            <div class="text-center py-5">
                <i class="bi bi-search text-muted" style="font-size: 4rem;"></i>
                <h3 class="text-muted mt-3">Tidak ada event ditemukan</h3>
                <p class="text-muted">Coba ubah filter pencarian atau periksa kembali nanti.</p>
                <a href="search.php" class="btn btn-primary">Reset Pencarian</a>
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
