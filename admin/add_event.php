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

if ($_POST) {
    $title = sanitizeInput($_POST['title']);
    $description = sanitizeInput($_POST['description']);
    $organizer = sanitizeInput($_POST['organizer']);
    $location = sanitizeInput($_POST['location']);
    $event_date = $_POST['event_date'];
    $start_time = $_POST['start_time'];
    $end_time = $_POST['end_time'];
    $max_participants = (int)$_POST['max_participants'];
    $status = sanitizeInput($_POST['status']);
    
    // Handle image upload
    $image_path = null;
    if (isset($_FILES['event_image']) && $_FILES['event_image']['error'] == 0) {
        $upload_dir = '../uploads/events/';
        
        // Create directory if it doesn't exist
        if (!file_exists($upload_dir)) {
            mkdir($upload_dir, 0777, true);
        }
        
        $file_extension = strtolower(pathinfo($_FILES['event_image']['name'], PATHINFO_EXTENSION));
        $allowed_extensions = ['jpg', 'jpeg', 'png', 'gif'];
        
        if (in_array($file_extension, $allowed_extensions)) {
            $new_filename = uniqid() . '.' . $file_extension;
            $upload_path = $upload_dir . $new_filename;
            
            if (move_uploaded_file($_FILES['event_image']['tmp_name'], $upload_path)) {
                $image_path = 'uploads/events/' . $new_filename;
            } else {
                $error = "Gagal mengupload gambar.";
            }
        } else {
            $error = "Format gambar tidak didukung. Gunakan JPG, JPEG, PNG, atau GIF.";
        }
    }
    
    if (!isset($error)) {
        try {
            $query = "INSERT INTO events (title, description, organizer, location, event_date, start_time, end_time, max_participants, status, image_path) 
                     VALUES (:title, :description, :organizer, :location, :event_date, :start_time, :end_time, :max_participants, :status, :image_path)";
            
            $stmt = $db->prepare($query);
            $stmt->bindParam(':title', $title);
            $stmt->bindParam(':description', $description);
            $stmt->bindParam(':organizer', $organizer);
            $stmt->bindParam(':location', $location);
            $stmt->bindParam(':event_date', $event_date);
            $stmt->bindParam(':start_time', $start_time);
            $stmt->bindParam(':end_time', $end_time);
            $stmt->bindParam(':max_participants', $max_participants);
            $stmt->bindParam(':status', $status);
            $stmt->bindParam(':image_path', $image_path);
            
            if ($stmt->execute()) {
                $success = "Event berhasil ditambahkan!";
            } else {
                $error = "Gagal menambahkan event.";
            }
        } catch(PDOException $exception) {
            $error = "Error: " . $exception->getMessage();
        }
    }
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Tambah Event - VolunteerHub Admin</title>
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

        .form-card {
            background: white;
            border-radius: 0.75rem;
            padding: 2rem;
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
        }

        .image-preview {
            max-width: 300px;
            max-height: 200px;
            border-radius: 0.5rem;
            margin-top: 1rem;
        }

        .upload-area {
            border: 2px dashed #d1d5db;
            border-radius: 0.5rem;
            padding: 2rem;
            text-align: center;
            transition: border-color 0.2s;
        }

        .upload-area:hover {
            border-color: #3b82f6;
        }

        .upload-area.dragover {
            border-color: #3b82f6;
            background-color: #eff6ff;
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
                    <a class="nav-link" href="dashboard_admin.php">
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
                    <a class="nav-link" href="kelola_pendaftaran.php">
                        <i class="bi bi-clipboard-check"></i>
                        Kelola Pendaftaran
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link" href="kelola_users.php">
                        <i class="bi bi-people"></i>
                        Kelola Volunteer
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
                    <h1 class="h3 fw-semibold text-dark mb-1">Tambah Event Baru</h1>
                    <p class="text-muted mb-0">Buat event volunteer baru untuk komunitas</p>
                </div>
                <a href="events.php" class="btn btn-outline-secondary">
                    <i class="bi bi-arrow-left me-1"></i>
                    Kembali
                </a>
            </div>
        </header>

        <!-- Main Content -->
        <main class="px-4 pb-5">
            <?php if (isset($success)): ?>
                <div class="alert alert-success alert-dismissible fade show" role="alert">
                    <?php echo $success; ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
            <?php endif; ?>

            <?php if (isset($error)): ?>
                <div class="alert alert-danger alert-dismissible fade show" role="alert">
                    <?php echo $error; ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
            <?php endif; ?>

            <div class="form-card">
                <form method="POST" enctype="multipart/form-data">
                    <div class="row">
                        <div class="col-md-8">
                            <div class="mb-3">
                                <label for="title" class="form-label fw-medium">Judul Event *</label>
                                <input type="text" class="form-control" id="title" name="title" required>
                            </div>

                            <div class="mb-3">
                                <label for="description" class="form-label fw-medium">Deskripsi *</label>
                                <textarea class="form-control" id="description" name="description" rows="4" required></textarea>
                            </div>

                            <div class="row">
                                <div class="col-md-6">
                                    <div class="mb-3">
                                        <label for="organizer" class="form-label fw-medium">Organizer *</label>
                                        <input type="text" class="form-control" id="organizer" name="organizer" required>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="mb-3">
                                        <label for="location" class="form-label fw-medium">Lokasi *</label>
                                        <input type="text" class="form-control" id="location" name="location" required>
                                    </div>
                                </div>
                            </div>

                            <div class="row">
                                <div class="col-md-4">
                                    <div class="mb-3">
                                        <label for="event_date" class="form-label fw-medium">Tanggal Event *</label>
                                        <input type="date" class="form-control" id="event_date" name="event_date" required>
                                    </div>
                                </div>
                                <div class="col-md-4">
                                    <div class="mb-3">
                                        <label for="start_time" class="form-label fw-medium">Waktu Mulai *</label>
                                        <input type="time" class="form-control" id="start_time" name="start_time" required>
                                    </div>
                                </div>
                                <div class="col-md-4">
                                    <div class="mb-3">
                                        <label for="end_time" class="form-label fw-medium">Waktu Selesai *</label>
                                        <input type="time" class="form-control" id="end_time" name="end_time" required>
                                    </div>
                                </div>
                            </div>

                            <div class="row">
                                <div class="col-md-6">
                                    <div class="mb-3">
                                        <label for="max_participants" class="form-label fw-medium">Maksimal Peserta *</label>
                                        <input type="number" class="form-control" id="max_participants" name="max_participants" min="1" required>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="mb-3">
                                        <label for="status" class="form-label fw-medium">Status *</label>
                                        <select class="form-select" id="status" name="status" required>
                                            <option value="active">Aktif</option>
                                            <option value="inactive">Tidak Aktif</option>
                                        </select>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="col-md-4">
                            <div class="mb-3">
                                <label for="event_image" class="form-label fw-medium">Gambar Event</label>
                                <div class="upload-area" id="uploadArea">
                                    <i class="bi bi-cloud-upload text-muted" style="font-size: 3rem;"></i>
                                    <p class="text-muted mt-2 mb-2">Drag & drop gambar atau klik untuk pilih</p>
                                    <input type="file" class="form-control" id="event_image" name="event_image" accept="image/*" style="display: none;">
                                    <button type="button" class="btn btn-outline-primary btn-sm" onclick="document.getElementById('event_image').click()">
                                        Pilih Gambar
                                    </button>
                                </div>
                                <img id="imagePreview" class="image-preview" style="display: none;">
                                <small class="text-muted">Format: JPG, JPEG, PNG, GIF. Maksimal 5MB.</small>
                            </div>
                        </div>
                    </div>

                    <div class="d-flex justify-content-end gap-2 mt-4">
                        <a href="events.php" class="btn btn-outline-secondary">Batal</a>
                        <button type="submit" class="btn btn-primary">
                            <i class="bi bi-plus-circle me-1"></i>
                            Tambah Event
                        </button>
                    </div>
                </form>
            </div>
        </main>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Image upload preview
        const imageInput = document.getElementById('event_image');
        const imagePreview = document.getElementById('imagePreview');
        const uploadArea = document.getElementById('uploadArea');

        imageInput.addEventListener('change', function(e) {
            const file = e.target.files[0];
            if (file) {
                const reader = new FileReader();
                reader.onload = function(e) {
                    imagePreview.src = e.target.result;
                    imagePreview.style.display = 'block';
                };
                reader.readAsDataURL(file);
            }
        });

        // Drag and drop functionality
        uploadArea.addEventListener('dragover', function(e) {
            e.preventDefault();
            uploadArea.classList.add('dragover');
        });

        uploadArea.addEventListener('dragleave', function(e) {
            e.preventDefault();
            uploadArea.classList.remove('dragover');
        });

        uploadArea.addEventListener('drop', function(e) {
            e.preventDefault();
            uploadArea.classList.remove('dragover');
            
            const files = e.dataTransfer.files;
            if (files.length > 0) {
                imageInput.files = files;
                const event = new Event('change', { bubbles: true });
                imageInput.dispatchEvent(event);
            }
        });

        uploadArea.addEventListener('click', function() {
            imageInput.click();
        });
    </script>
</body>
</html>
