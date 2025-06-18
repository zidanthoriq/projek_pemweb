<?php
require_once '../config/database.php';
requireLogin();

$database = new Database();
$db = $database->getConnection();

$event_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
$user_id = $_SESSION['user_id'];

if ($event_id <= 0) {
    header('Location: search.php');
    exit();
}

try {
    // Get event details
    $event_query = "SELECT e.*, COUNT(r.id) as current_participants 
                   FROM events e 
                   LEFT JOIN registrations r ON e.id = r.event_id AND r.status = 'approved'
                   WHERE e.id = :event_id 
                   GROUP BY e.id";
    $event_stmt = $db->prepare($event_query);
    $event_stmt->bindParam(':event_id', $event_id);
    $event_stmt->execute();
    
    if ($event_stmt->rowCount() == 0) {
        header('Location: search.php');
        exit();
    }
    
    $event = $event_stmt->fetch(PDO::FETCH_ASSOC);
    
    // Check if user already registered
    $registration_query = "SELECT status FROM registrations WHERE user_id = :user_id AND event_id = :event_id";
    $registration_stmt = $db->prepare($registration_query);
    $registration_stmt->bindParam(':user_id', $user_id);
    $registration_stmt->bindParam(':event_id', $event_id);
    $registration_stmt->execute();
    
    $user_registration = $registration_stmt->fetch(PDO::FETCH_ASSOC);
    $is_registered = $user_registration ? true : false;
    $registration_status = $user_registration ? $user_registration['status'] : null;
    
    // Calculate progress
    $progress_percentage = ($event['current_participants'] / $event['max_participants']) * 100;
    $remaining_slots = $event['max_participants'] - $event['current_participants'];
    
    // Check if registration is still open
    $registration_deadline = date('Y-m-d H:i:s', strtotime($event['event_date'] . ' -2 days'));
    $is_registration_open = (date('Y-m-d H:i:s') < $registration_deadline) && ($event['status'] == 'active') && ($remaining_slots > 0);
    
} catch(PDOException $exception) {
    $error = "Error: " . $exception->getMessage();
    header('Location: search.php');
    exit();
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Detail Event - <?php echo htmlspecialchars($event['title']); ?> | VolunteerHub</title>
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

        .card-custom {
            border: none;
            border-radius: 0.5rem;
            box-shadow: 0 1px 3px 0 rgba(0, 0, 0, 0.1);
            margin-bottom: 1.5rem;
        }

        .event-image {
            height: 16rem;
            object-fit: cover;
            border-radius: 0.5rem 0.5rem 0 0;
        }

        @media (min-width: 768px) {
            .event-image {
                height: 20rem;
            }
        }

        .status-badge {
            display: inline-flex;
            align-items: center;
            padding: 0.25rem 0.75rem;
            border-radius: 9999px;
            font-size: 0.875rem;
            font-weight: 500;
        }

        .status-badge.open {
            background-color: #dcfce7;
            color: #166534;
        }

        .status-badge.closed {
            background-color: #fee2e2;
            color: #991b1b;
        }

        .status-badge.full {
            background-color: #fef3c7;
            color: #92400e;
        }

        .status-badge i {
            font-size: 1rem;
            margin-right: 0.25rem;
        }

        .interest-count {
            display: flex;
            align-items: center;
            font-size: 0.875rem;
            color: #6b7280;
        }

        .interest-count i {
            font-size: 1rem;
            margin-right: 0.25rem;
        }

        .benefit-item {
            display: flex;
            align-items: flex-start;
            margin-bottom: 1rem;
        }

        .benefit-icon {
            display: flex;
            align-items: center;
            justify-content: center;
            width: 2rem;
            height: 2rem;
            border-radius: 0.375rem;
            color: white;
            margin-right: 0.75rem;
            flex-shrink: 0;
        }

        .benefit-icon i {
            font-size: 1.25rem;
        }

        .benefit-icon.blue { background-color: #3b82f6; }
        .benefit-icon.green { background-color: #10b981; }
        .benefit-icon.purple { background-color: #8b5cf6; }
        .benefit-icon.yellow { background-color: #f59e0b; }

        .registration-card {
            position: sticky;
            top: 1.5rem;
        }

        .info-item {
            display: flex;
            align-items: center;
            margin-bottom: 1rem;
        }

        .info-item i {
            font-size: 1.25rem;
            margin-right: 0.5rem;
            flex-shrink: 0;
            color: #6b7280;
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
            background-color: #3b82f6;
        }

        .warning-box {
            background-color: #fefce8;
            border: 1px solid #fde047;
            border-radius: 0.5rem;
            padding: 1rem;
            margin-bottom: 1.5rem;
        }

        .warning-box i {
            font-size: 1.25rem;
            margin-right: 0.5rem;
            flex-shrink: 0;
            color: #92400e;
        }

        .warning-title {
            font-size: 0.875rem;
            font-weight: 500;
            color: #92400e;
            margin: 0;
        }

        .warning-text {
            font-size: 0.875rem;
            color: #a16207;
            margin: 0;
        }

        .btn-primary-custom {
            background-color: #2563eb;
            border-color: #2563eb;
            color: white;  
            font-weight: 500;
            padding: 0.75rem 1rem;
            border-radius: 0.5rem;
            transition: all 0.2s;
        }

        .btn-primary-custom:hover {
            background-color: #1d4ed8;
            border-color: #1d4ed8;
        }

        .btn-primary-custom:disabled {
            background-color: #9ca3af;
            border-color: #9ca3af;
        }

        .btn-outline-custom {
            border: 1px solid #d1d5db;
            color: #374151;
            font-weight: 500;
            padding: 0.5rem 1rem;
            border-radius: 0.5rem;
            background-color: white;
            transition: all 0.2s;
        }

        .btn-outline-custom:hover {
            background-color: #f9fafb;
            color: #374151;
            border-color: #d1d5db;
        }

        .btn-icon {
            font-size: 1rem;
            margin-right: 0.5rem;
        }

        .contact-item {
            display: flex;
            align-items: center;
            font-size: 0.875rem;
            color: #4b5563;
            margin-bottom: 0.5rem;
        }

        .contact-item i {
            font-size: 1rem;
            margin-right: 0.5rem;
            margin-left: 1rem;
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

    <!-- Body -->
    <main class="py-4">
        <?php if (isset($_SESSION['success'])): ?>
            <div class="container">
                <div class="alert alert-success alert-dismissible fade show" role="alert">
                    <i class="bi bi-check-circle-fill me-2"></i>
                    <?php echo $_SESSION['success']; unset($_SESSION['success']); ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
            </div>
        <?php endif; ?>

        <?php if (isset($_SESSION['error'])): ?>
            <div class="container">
                <div class="alert alert-danger alert-dismissible fade show" role="alert">
                    <i class="bi bi-exclamation-triangle-fill me-2"></i>
                    <?php echo $_SESSION['error']; unset($_SESSION['error']); ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
            </div>
        <?php endif; ?>
        <div class="container">
            <div class="row">
                <div class="col-lg-8">
                    <!-- Gambar Event -->
                    <div class="card card-custom">
                        <?php if ($event['image_path'] && file_exists('../' . $event['image_path'])): ?>
                            <img src="../<?php echo htmlspecialchars($event['image_path']); ?>" alt="<?php echo htmlspecialchars($event['title']); ?>" class="event-image">
                        <?php else: ?>
                            <img src="/placeholder.svg?height=320&width=800&text=<?php echo urlencode($event['title']); ?>" alt="<?php echo htmlspecialchars($event['title']); ?>" class="event-image">
                        <?php endif; ?>
                    </div>

                    <!-- Deskripsi Event -->
                    <div class="card card-custom">
                        <div class="card-body">
                            <div class="d-flex justify-content-between align-items-center mb-4">
                                <?php if ($is_registration_open): ?>
                                    <span class="status-badge open">
                                        <i class="bi bi-check-circle-fill"></i>
                                        Pendaftaran Terbuka
                                    </span>
                                <?php elseif ($remaining_slots <= 0): ?>
                                    <span class="status-badge full">
                                        <i class="bi bi-people-fill"></i>
                                        Kuota Penuh
                                    </span>
                                <?php else: ?>
                                    <span class="status-badge closed">
                                        <i class="bi bi-x-circle-fill"></i>
                                        Pendaftaran Ditutup
                                    </span>
                                <?php endif; ?>
                                
                                <div class="interest-count">
                                    <i class="bi bi-heart-fill"></i>
                                    <?php echo $event['current_participants']; ?> orang terdaftar
                                </div>
                            </div>

                            <h1 class="display-6 fw-bold text-dark mb-4"><?php echo htmlspecialchars($event['title']); ?></h1>
                            
                            <div>
                                <p class="text-muted fs-5 lh-base mb-4">
                                    <?php echo htmlspecialchars($event['description']); ?>
                                </p>
                                
                                <h3 class="fs-4 fw-semibold text-dark mb-3">Deskripsi Event</h3>
                                <p class="text-muted mb-4">
                                    Event volunteer ini merupakan kesempatan bagi Anda untuk berkontribusi positif kepada masyarakat. 
                                    Bergabunglah dengan komunitas volunteer yang peduli dan berkomitmen untuk membuat perubahan nyata.
                                </p>
                                
                                <h3 class="fs-4 fw-semibold text-dark mb-3">Kegiatan yang Akan Dilakukan</h3>
                                <ul class="text-muted mb-4">
                                    <li class="mb-2">Partisipasi aktif dalam kegiatan volunteer</li>
                                    <li class="mb-2">Bekerja sama dalam tim untuk mencapai tujuan</li>
                                    <li class="mb-2">Berbagi pengalaman dengan volunteer lainnya</li>
                                    <li class="mb-2">Dokumentasi kegiatan untuk laporan</li>
                                    <li class="mb-2">Evaluasi dan refleksi kegiatan</li>
                                </ul>
                                
                                <h3 class="fs-4 fw-semibold text-dark mb-3">Yang Perlu Dibawa</h3>
                                <ul class="text-muted mb-0">
                                    <li class="mb-2">Pakaian yang nyaman dan sesuai kegiatan</li>
                                    <li class="mb-2">Sepatu yang cocok untuk aktivitas</li>
                                    <li class="mb-2">Botol minum pribadi</li>
                                    <li class="mb-2">Semangat untuk berkontribusi</li>
                                    <li class="mb-2">Attitude positif dan kerjasama tim</li>
                                </ul>
                            </div>
                        </div>
                    </div>

                    <!-- Benefit  -->
                    <div class="card card-custom">
                        <div class="card-body">
                            <h3 class="fs-4 fw-semibold text-dark mb-4">Manfaat yang Didapat</h3>
                            <div class="row">
                                <div class="col-md-6">
                                    <div class="benefit-item">
                                        <div class="benefit-icon blue">
                                            <i class="bi bi-award text-white"></i>
                                        </div>
                                        <div>
                                            <h4 class="fw-medium mb-1">Sertifikat Volunteer</h4>
                                            <p class="text-muted small mb-0">Mendapat sertifikat resmi sebagai volunteer</p>
                                        </div>
                                    </div>
                                </div>
                                
                                <div class="col-md-6">
                                    <div class="benefit-item">
                                        <div class="benefit-icon green">
                                            <i class="bi bi-people-fill text-white"></i>
                                        </div>
                                        <div>
                                            <h4 class="fw-medium mb-1">Networking</h4>
                                            <p class="text-muted small mb-0">Bertemu dengan volunteer lain yang berpikiran sama</p>
                                        </div>
                                    </div>
                                </div>
                                
                                <div class="col-md-6">
                                    <div class="benefit-item">
                                        <div class="benefit-icon purple">
                                            <i class="bi bi-heart-fill text-white"></i>
                                        </div>
                                        <div>
                                            <h4 class="fw-medium mb-1">Kontribusi Positif</h4>
                                            <p class="text-muted small mb-0">Berkontribusi langsung untuk masyarakat</p>
                                        </div>
                                    </div>
                                </div>
                                
                                <div class="col-md-6">
                                    <div class="benefit-item">
                                        <div class="benefit-icon yellow">
                                            <i class="bi bi-star-fill text-white"></i>
                                        </div>
                                        <div>
                                            <h4 class="fw-medium mb-1">Pengalaman Berharga</h4>
                                            <p class="text-muted small mb-0">Mendapat pengalaman volunteer yang bermakna</p>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Container Register -->
                <div class="col-lg-4">
                    <div class="card card-custom registration-card">
                        <div class="card-body">
                            <div class="mb-4">
                                <div class="info-item">
                                    <i class="bi bi-calendar-event"></i>
                                    <div>
                                        <p class="fw-medium mb-0"><?php echo formatDate($event['event_date']); ?></p>
                                        <p class="text-muted small mb-0"><?php echo formatTime($event['start_time']); ?> - <?php echo formatTime($event['end_time']); ?> WIB</p>
                                    </div>
                                </div>
                                
                                <div class="info-item">
                                    <i class="bi bi-geo-alt-fill"></i>
                                    <div>
                                        <p class="fw-medium mb-0"><?php echo htmlspecialchars($event['location']); ?></p>
                                        <p class="text-muted small mb-0">Lokasi Event</p>
                                    </div>
                                </div>
                                
                                <div class="info-item">
                                    <i class="bi bi-building"></i>
                                    <div>
                                        <p class="fw-medium mb-0">Organizer</p>
                                        <p class="text-muted small mb-0"><?php echo htmlspecialchars($event['organizer']); ?></p>
                                    </div>
                                </div>
                            </div>

                            <!-- Bar progress -->
                            <div class="mb-4">
                                <div class="d-flex justify-content-between align-items-center mb-2">
                                    <span class="fw-medium">Kuota Peserta</span>
                                    <span class="fw-medium"><?php echo $event['current_participants']; ?>/<?php echo $event['max_participants']; ?></span>
                                </div>
                                <div class="progress-custom">
                                    <div class="progress-bar-custom" style="width: <?php echo $progress_percentage; ?>%"></div>
                                </div>
                                <div class="d-flex justify-content-between small text-muted">
                                    <span><?php echo round($progress_percentage); ?>% terisi</span>
                                    <span><?php echo $remaining_slots; ?> slot tersisa</span>
                                </div>
                            </div>

                            <?php if ($is_registration_open): ?>
                                <div class="warning-box">
                                    <div class="d-flex">
                                        <i class="bi bi-exclamation-triangle-fill"></i>
                                        <div>
                                            <h4 class="warning-title">Batas Pendaftaran</h4>
                                            <p class="warning-text"><?php echo formatDateTime($registration_deadline); ?></p>
                                        </div>
                                    </div>
                                </div>
                            <?php endif; ?>

                            <?php if ($is_registered): ?>
                                <?php if ($registration_status == 'pending'): ?>
                                    <button class="btn btn-warning w-100 mb-3" disabled>
                                        <i class="bi bi-clock me-2"></i>
                                        Menunggu Persetujuan
                                    </button>
                                <?php elseif ($registration_status == 'approved'): ?>
                                    <button class="btn btn-success w-100 mb-3" disabled>
                                        <i class="bi bi-check-circle me-2"></i>
                                        Terdaftar
                                    </button>
                                <?php else: ?>
                                    <button class="btn btn-danger w-100 mb-3" disabled>
                                        <i class="bi bi-x-circle me-2"></i>
                                        Ditolak
                                    </button>
                                <?php endif; ?>
                            <?php elseif ($is_registration_open): ?>
                                <form method="POST" action="../admin/process_registration.php">
                                    <input type="hidden" name="event_id" value="<?php echo $event_id; ?>">
                                    <button type="submit" class="btn btn-primary-custom w-100 mb-3">
                                        <i class="bi bi-person-plus btn-icon"></i>
                                        Daftar Sekarang
                                    </button>
                                </form>
                            <?php else: ?>
                                <button class="btn btn-primary-custom w-100 mb-3" disabled>
                                    <?php echo ($remaining_slots <= 0) ? 'Kuota Penuh' : 'Pendaftaran Ditutup'; ?>
                                </button>
                            <?php endif; ?>
                        
                            </div>

                            <!-- Organizer Info -->
                            <div class="mt-6 pt-2 border-top">
                                <h4 class="fw-medium mb-4 ms-3">Kontak Organizer</h4>
                                <div class="contact-item">
                                    <i class="bi bi-envelope"></i>
                                    info@volunteerhub.org
                                </div>
                                <div class="contact-item">
                                    <i class="bi bi-telephone"></i>
                                    +62 812-3456-7890
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </main>

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
