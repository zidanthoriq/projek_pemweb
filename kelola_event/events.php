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

// Handle event deletion
if ($_POST && isset($_POST['delete_event'])) {
    $event_id = (int)$_POST['event_id'];
    
    try {
        // Get event image path and registration count before deletion
        $event_query = "SELECT image_path, 
                       (SELECT COUNT(*) FROM registrations WHERE event_id = :event_id) as registration_count
                       FROM events WHERE id = :event_id";
        $event_stmt = $db->prepare($event_query);
        $event_stmt->bindParam(':event_id', $event_id);
        $event_stmt->execute();
        $event_data = $event_stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($event_data) {
            // Delete all registrations for this event first
            $delete_registrations_query = "DELETE FROM registrations WHERE event_id = :event_id";
            $delete_registrations_stmt = $db->prepare($delete_registrations_query);
            $delete_registrations_stmt->bindParam(':event_id', $event_id);
            $delete_registrations_stmt->execute();
            
            // Delete event
            $delete_query = "DELETE FROM events WHERE id = :event_id";
            $delete_stmt = $db->prepare($delete_query);
            $delete_stmt->bindParam(':event_id', $event_id);
            
            if ($delete_stmt->execute()) {
                // Delete image file if exists
                if ($event_data['image_path'] && file_exists('../' . $event_data['image_path'])) {
                    unlink('../' . $event_data['image_path']);
                }
                
                if ($event_data['registration_count'] > 0) {
                    $success_message = "Event berhasil dihapus beserta {$event_data['registration_count']} pendaftaran volunteer.";
                } else {
                    $success_message = "Event berhasil dihapus.";
                }
            } else {
                $error_message = "Gagal menghapus event.";
            }
        } else {
            $error_message = "Event tidak ditemukan.";
        }
    } catch(PDOException $exception) {
        $error_message = "Error: " . $exception->getMessage();
    }
}

// Get filter parameters
$status_filter = isset($_GET['status']) ? sanitizeInput($_GET['status']) : '';
$search = isset($_GET['search']) ? sanitizeInput($_GET['search']) : '';

// Build query
$where_conditions = ["1=1"];
$params = [];

if (!empty($status_filter)) {
    $where_conditions[] = "e.status = :status";
    $params[':status'] = $status_filter;
}

if (!empty($search)) {
    $where_conditions[] = "(e.title LIKE :search OR e.organizer LIKE :search OR e.location LIKE :search)";
    $params[':search'] = '%' . $search . '%';
}

$where_clause = implode(' AND ', $where_conditions);

try {
    // Get events with registration count
    $events_query = "SELECT e.*, COUNT(r.id) as registration_count,
                     SUM(CASE WHEN r.status = 'approved' THEN 1 ELSE 0 END) as approved_count,
                     SUM(CASE WHEN r.status = 'pending' THEN 1 ELSE 0 END) as pending_count
                     FROM events e
                     LEFT JOIN registrations r ON e.id = r.event_id
                     WHERE $where_clause
                     GROUP BY e.id
                     ORDER BY e.created_at DESC";
    
    $events_stmt = $db->prepare($events_query);
    foreach ($params as $key => $value) {
        $events_stmt->bindValue($key, $value);
    }
    $events_stmt->execute();
    $events = $events_stmt->fetchAll(PDO::FETCH_ASSOC);

    // Get statistics
    $stats_query = "SELECT 
                    COUNT(*) as total,
                    SUM(CASE WHEN status = 'active' THEN 1 ELSE 0 END) as active,
                    SUM(CASE WHEN status = 'inactive' THEN 1 ELSE 0 END) as inactive,
                    SUM(CASE WHEN event_date < CURDATE() THEN 1 ELSE 0 END) as completed,
                    SUM(CASE WHEN event_date >= CURDATE() THEN 1 ELSE 0 END) as upcoming
                    FROM events";
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
    <title>Kelola Event - VolunteerHub Admin</title>
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
            padding: 0.25rem 0.75rem;
            border-radius: 9999px;
            font-size: 0.75rem;
            font-weight: 600;
        }

        .status-badge.active {
            background-color: #d1fae5;
            color: #065f46;
        }

        .status-badge.inactive {
            background-color: #fee2e2;
            color: #991b1b;
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

        .event-image-thumb {
            width: 60px;
            height: 40px;
            object-fit: cover;
            border-radius: 0.375rem;
        }

        .registration-info {
            font-size: 0.875rem;
        }

        .registration-info .badge {
            font-size: 0.75rem;
            margin-left: 0.25rem;
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
                    <a class="nav-link active" href="events.php">
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
                    <h1 class="h3 fw-semibold text-dark mb-1">Kelola Event</h1>
                    <p class="text-muted mb-0">Kelola semua event volunteer di platform</p>
                </div>
                <a href="add_event.php" class="btn btn-primary">
                    <i class="bi bi-plus-circle me-1"></i>
                    Tambah Event
                </a>
            </div>
        </header>

        <!-- Main Content -->
        <main class="px-4 pb-5">
            <?php if (isset($success_message)): ?>
                <div class="alert alert-success alert-dismissible fade show" role="alert">
                    <?php echo $success_message; ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
            <?php endif; ?>

            <?php if (isset($error_message)): ?>
                <div class="alert alert-danger alert-dismissible fade show" role="alert">
                    <?php echo $error_message; ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
            <?php endif; ?>

            <!-- Statistics Cards -->
            <div class="row mb-4">
                <div class="col-md-2 mb-3">
                    <div class="stat-card">
                        <div class="stat-number text-primary"><?php echo $stats['total']; ?></div>
                        <div class="stat-label">Total Event</div>
                    </div>
                </div>
                <div class="col-md-2 mb-3">
                    <div class="stat-card">
                        <div class="stat-number text-success"><?php echo $stats['active']; ?></div>
                        <div class="stat-label">Aktif</div>
                    </div>
                </div>
                <div class="col-md-2 mb-3">
                    <div class="stat-card">
                        <div class="stat-number text-danger"><?php echo $stats['inactive']; ?></div>
                        <div class="stat-label">Tidak Aktif</div>
                    </div>
                </div>
                <div class="col-md-2 mb-3">
                    <div class="stat-card">
                        <div class="stat-number text-info"><?php echo $stats['upcoming']; ?></div>
                        <div class="stat-label">Mendatang</div>
                    </div>
                </div>
                <div class="col-md-2 mb-3">
                    <div class="stat-card">
                        <div class="stat-number text-secondary"><?php echo $stats['completed']; ?></div>
                        <div class="stat-label">Selesai</div>
                    </div>
                </div>
            </div>

            <!-- Filter -->
            <div class="filter-card">
                <form method="GET" class="row g-3">
                    <div class="col-md-4">
                        <label for="search" class="form-label fw-medium">Cari Event</label>
                        <input type="text" class="form-control" id="search" name="search" 
                               placeholder="Cari judul, organizer, lokasi..." value="<?php echo htmlspecialchars($search); ?>">
                    </div>
                    <div class="col-md-4">
                        <label for="status" class="form-label fw-medium">Status</label>
                        <select class="form-select" id="status" name="status">
                            <option value="">Semua Status</option>
                            <option value="active" <?php echo ($status_filter == 'active') ? 'selected' : ''; ?>>Aktif</option>
                            <option value="inactive" <?php echo ($status_filter == 'inactive') ? 'selected' : ''; ?>>Tidak Aktif</option>
                        </select>
                    </div>
                    <div class="col-md-4 d-flex align-items-end">
                        <button type="submit" class="btn btn-primary me-2">
                            <i class="bi bi-search me-1"></i>
                            Filter
                        </button>
                        <a href="events.php" class="btn btn-outline-secondary">
                            <i class="bi bi-x-circle me-1"></i>
                            Reset
                        </a>
                    </div>
                </form>
            </div>

            <!-- Events Table -->
            <div class="card border-0 shadow-sm">
                <div class="card-header bg-light border-0 py-3">
                    <h5 class="mb-0 fw-semibold">
                        Daftar Event 
                        <?php if (!empty($search) || !empty($status_filter)): ?>
                            (<?php echo count($events); ?> hasil)
                        <?php endif; ?>
                    </h5>
                </div>
                <div class="card-body p-0">
                    <?php if (count($events) > 0): ?>
                        <div class="table-responsive">
                            <table class="table table-hover mb-0">
                                <thead>
                                    <tr>
                                        <th class="fw-semibold text-muted small py-3 px-3">EVENT</th>
                                        <th class="fw-semibold text-muted small py-3 px-3">ORGANIZER</th>
                                        <th class="fw-semibold text-muted small py-3 px-3">TANGGAL</th>
                                        <th class="fw-semibold text-muted small py-3 px-3">LOKASI</th>
                                        <th class="fw-semibold text-muted small py-3 px-3">PENDAFTARAN</th>
                                        <th class="fw-semibold text-muted small py-3 px-3">STATUS</th>
                                        <th class="fw-semibold text-muted small py-3 px-3">AKSI</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($events as $event): ?>
                                    <tr>
                                        <td class="py-3 px-3">
                                            <div class="d-flex align-items-center">
                                                <?php if ($event['image_path'] && file_exists('../' . $event['image_path'])): ?>
                                                    <img src="../<?php echo htmlspecialchars($event['image_path']); ?>" 
                                                         alt="Event Image" class="event-image-thumb me-3">
                                                <?php else: ?>
                                                    <div class="event-image-thumb me-3 bg-light d-flex align-items-center justify-content-center">
                                                        <i class="bi bi-image text-muted"></i>
                                                    </div>
                                                <?php endif; ?>
                                                <div>
                                                    <p class="fw-medium mb-1"><?php echo htmlspecialchars($event['title']); ?></p>
                                                    <p class="text-muted small mb-0">
                                                        <?php echo htmlspecialchars(substr($event['description'], 0, 50)) . '...'; ?>
                                                    </p>
                                                </div>
                                            </div>
                                        </td>
                                        <td class="py-3 px-3 fw-medium"><?php echo htmlspecialchars($event['organizer']); ?></td>
                                        <td class="py-3 px-3">
                                            <p class="fw-medium mb-1"><?php echo formatDate($event['event_date']); ?></p>
                                            <p class="text-muted small mb-0">
                                                <?php echo formatTime($event['start_time']); ?> - <?php echo formatTime($event['end_time']); ?>
                                            </p>
                                        </td>
                                        <td class="py-3 px-3"><?php echo htmlspecialchars($event['location']); ?></td>
                                        <td class="py-3 px-3">
                                            <div class="registration-info">
                                                <p class="fw-medium mb-1">
                                                    <?php echo $event['approved_count']; ?>/<?php echo $event['max_participants']; ?> peserta
                                                </p>
                                                <div>
                                                    <?php if ($event['pending_count'] > 0): ?>
                                                        <span class="badge bg-warning"><?php echo $event['pending_count']; ?> menunggu</span>
                                                    <?php endif; ?>
                                                    <?php if ($event['registration_count'] > 0): ?>
                                                        <span class="badge bg-info"><?php echo $event['registration_count']; ?> total</span>
                                                    <?php endif; ?>
                                                </div>
                                            </div>
                                        </td>
                                        <td class="py-3 px-3">
                                            <span class="status-badge <?php echo $event['status']; ?>">
                                                <?php echo ucfirst($event['status']); ?>
                                            </span>
                                        </td>
                                        <td class="py-3 px-3">
                                            <!-- Tombol Lihat Detail mengarah ke kelola pendaftaran untuk event ini -->
                                            <a href="kelola_pendaftaran.php?event=<?php echo $event['id']; ?>" 
                                               class="btn btn-action btn-outline-primary" title="Lihat Pendaftaran">
                                                <i class="bi bi-eye"></i>
                                            </a>
                                            <a href="edit_event.php?id=<?php echo $event['id']; ?>" 
                                               class="btn btn-action btn-outline-warning" title="Edit">
                                                <i class="bi bi-pencil"></i>
                                            </a>
                                            <?php if (true): // Selalu tampilkan tombol hapus ?>
                                                <form method="POST" class="d-inline" onsubmit="return confirmDelete(<?php echo $event['registration_count']; ?>, '<?php echo htmlspecialchars($event['title'], ENT_QUOTES); ?>')">
                                                    <input type="hidden" name="event_id" value="<?php echo $event['id']; ?>">
                                                    <button type="submit" name="delete_event" class="btn btn-action btn-outline-danger" title="Hapus Event">
                                                        <i class="bi bi-trash"></i>
                                                    </button>
                                                </form>
                                            <?php endif; ?>
                                        </td>
                                    </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    <?php else: ?>
                        <div class="text-center py-5">
                            <i class="bi bi-calendar-x text-muted" style="font-size: 4rem;"></i>
                            <h3 class="text-muted mt-3">Tidak ada event ditemukan</h3>
                            <p class="text-muted">
                                <?php if (!empty($search) || !empty($status_filter)): ?>
                                    Coba ubah filter atau reset pencarian.
                                <?php else: ?>
                                    Belum ada event yang dibuat. Mulai dengan menambah event baru.
                                <?php endif; ?>
                            </p>
                            <a href="add_event.php" class="btn btn-primary">
                                <i class="bi bi-plus-circle me-1"></i>
                                Tambah Event Pertama
                            </a>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </main>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
function confirmDelete(registrationCount, eventTitle) {
    let message = `Apakah Anda yakin ingin menghapus event "${eventTitle}"?`;
    
    if (registrationCount > 0) {
        message += `\n\nPeringatan: Event ini memiliki ${registrationCount} pendaftaran volunteer yang akan ikut terhapus.`;
        message += `\n\nTindakan ini tidak dapat dibatalkan!`;
    }
    
    return confirm(message);
}
</script>
</body>
</html>
