<?php
require_once '../config/database.php';
requireLogin();

$database = new Database();
$db = $database->getConnection();

$user_id = $_SESSION['user_id'];
$error = '';
$success = '';

// Handle form submission
if ($_POST) {
    $full_name = sanitizeInput($_POST['full_name']);
    $email = sanitizeInput($_POST['email']);
    $phone = sanitizeInput($_POST['phone']);
    $address = sanitizeInput($_POST['address']);
    $current_password = $_POST['current_password'];
    $new_password = $_POST['new_password'];
    $confirm_password = $_POST['confirm_password'];
    
    // Validation
    if (empty($full_name) || empty($email)) {
        $error = "Nama lengkap dan email wajib diisi!";
    } else {
        try {
            // Check if email already exists for other users
            $check_query = "SELECT id FROM users WHERE email = :email AND id != :user_id";
            $check_stmt = $db->prepare($check_query);
            $check_stmt->bindParam(':email', $email);
            $check_stmt->bindParam(':user_id', $user_id);
            $check_stmt->execute();
            
            if ($check_stmt->rowCount() > 0) {
                $error = "Email sudah digunakan oleh user lain!";
            } else {
                // Update basic profile
                $update_query = "UPDATE users SET full_name = :full_name, email = :email, phone = :phone, address = :address WHERE id = :user_id";
                $update_stmt = $db->prepare($update_query);
                $update_stmt->bindParam(':full_name', $full_name);
                $update_stmt->bindParam(':email', $email);
                $update_stmt->bindParam(':phone', $phone);
                $update_stmt->bindParam(':address', $address);
                $update_stmt->bindParam(':user_id', $user_id);
                
                if ($update_stmt->execute()) {
                    // Update session data
                    $_SESSION['full_name'] = $full_name;
                    $_SESSION['email'] = $email;
                    
                    // Handle password change if provided
                    if (!empty($current_password) && !empty($new_password)) {
                        if ($new_password !== $confirm_password) {
                            $error = "Password baru dan konfirmasi password tidak cocok!";
                        } elseif (strlen($new_password) < 6) {
                            $error = "Password baru minimal 6 karakter!";
                        } else {
                            // Verify current password
                            $password_query = "SELECT password FROM users WHERE id = :user_id";
                            $password_stmt = $db->prepare($password_query);
                            $password_stmt->bindParam(':user_id', $user_id);
                            $password_stmt->execute();
                            $user_data = $password_stmt->fetch(PDO::FETCH_ASSOC);
                            
                            if (password_verify($current_password, $user_data['password'])) {
                                // Update password
                                $hashed_password = password_hash($new_password, PASSWORD_DEFAULT);
                                $password_update_query = "UPDATE users SET password = :password WHERE id = :user_id";
                                $password_update_stmt = $db->prepare($password_update_query);
                                $password_update_stmt->bindParam(':password', $hashed_password);
                                $password_update_stmt->bindParam(':user_id', $user_id);
                                
                                if ($password_update_stmt->execute()) {
                                    $success = "Profil dan password berhasil diperbarui!";
                                } else {
                                    $error = "Profil berhasil diperbarui, tetapi gagal mengubah password.";
                                }
                            } else {
                                $error = "Password saat ini salah!";
                            }
                        }
                    } else {
                        $success = "Profil berhasil diperbarui!";
                    }
                } else {
                    $error = "Terjadi kesalahan saat memperbarui profil.";
                }
            }
        } catch(PDOException $exception) {
            $error = "Error: " . $exception->getMessage();
        }
    }
}

// Get user data
try {
    $user_query = "SELECT * FROM users WHERE id = :user_id";
    $user_stmt = $db->prepare($user_query);
    $user_stmt->bindParam(':user_id', $user_id);
    $user_stmt->execute();
    $user = $user_stmt->fetch(PDO::FETCH_ASSOC);
    
    // Get user statistics
    $stats_query = "SELECT 
                    COUNT(*) as total_registrations,
                    SUM(CASE WHEN status = 'approved' THEN 1 ELSE 0 END) as approved_registrations,
                    SUM(CASE WHEN status = 'pending' THEN 1 ELSE 0 END) as pending_registrations
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
    <title>Profil Saya - VolunteerHub</title>
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


        
        .profile-header {
            background: linear-gradient(135deg,rgb(59, 89, 220) 0%,rgb(92, 72, 246) 100%);
            color: white;
            padding: 3rem 0;
        }

        .profile-avatar {
            width: 120px;
            height: 120px;
            background-color: rgba(255, 255, 255, 0.2);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 3rem;
            margin: 0 auto 1rem;
        }

        .form-card {
            background: white;
            border-radius: 0.75rem;
            padding: 2rem;
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
            margin-bottom: 2rem;
        }

        .form-label {
            font-weight: 500;
            color: #374151;
            margin-bottom: 0.5rem;
        }

        .form-control {
            border: 1px solid #d1d5db;
            border-radius: 0.5rem;
            padding: 0.75rem;
            font-size: 0.875rem;
        }

        .form-control:focus {
            border-color: #3b82f6;
            box-shadow: 0 0 0 0.2rem rgba(59, 130, 246, 0.25);
        }

        .btn-primary {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            border: none;
            border-radius: 0.5rem;
            padding: 0.75rem 1.5rem;
            font-weight: 600;
        }

        .btn-primary:hover {
            transform: translateY(-1px);
            box-shadow: 0 4px 8px rgba(0, 0, 0, 0.15);
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

        .section-divider {
            border-top: 1px solid #e5e7eb;
            margin: 2rem 0;
            padding-top: 2rem;
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
                    <a class="nav-link" href="pendaftaran.php">Pendaftaran Saya</a>
                </li>
            </ul>
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

    <!-- Profile Header -->
    <div class="profile-header">
        <div class="container">
            <div class="text-center">
                <div class="profile-avatar">
                    <i class="bi bi-person"></i>
                </div>
                <h1 class="h2 fw-bold mb-2"><?php echo htmlspecialchars($user['full_name']); ?></h1>
                <p class="lead mb-0">Member sejak <?php echo formatDate($user['created_at']); ?></p>
            </div>
        </div>
    </div>

    <!-- Main Content -->
    <div class="container py-5">
        <!-- Statistics Cards -->
        <div class="row mb-5">
            <div class="col-md-4 mb-3">
                <div class="stat-card">
                    <div class="stat-number text-primary"><?php echo $stats['total_registrations']; ?></div>
                    <div class="stat-label">Total Pendaftaran</div>
                </div>
            </div>
            <div class="col-md-4 mb-3">
                <div class="stat-card">
                    <div class="stat-number text-success"><?php echo $stats['approved_registrations']; ?></div>
                    <div class="stat-label">Event Diikuti</div>
                </div>
            </div>
            <div class="col-md-4 mb-3">
                <div class="stat-card">
                    <div class="stat-number text-warning"><?php echo $stats['pending_registrations']; ?></div>
                    <div class="stat-label">Menunggu Persetujuan</div>
                </div>
            </div>
        </div>

        <div class="row justify-content-center">
            <div class="col-lg-8">
                <div class="form-card">
                    <div class="mb-4">
                        <h2 class="h3 fw-semibold text-dark mb-2">Edit Profil</h2>
                        <p class="text-muted">Perbarui informasi profil dan pengaturan akun Anda.</p>
                    </div>

                    <?php if ($error): ?>
                        <div class="alert alert-danger" role="alert">
                            <i class="bi bi-exclamation-triangle me-2"></i><?php echo $error; ?>
                        </div>
                    <?php endif; ?>
                    
                    <?php if ($success): ?>
                        <div class="alert alert-success" role="alert">
                            <i class="bi bi-check-circle me-2"></i><?php echo $success; ?>
                        </div>
                    <?php endif; ?>

                    <form method="POST">
                        <!-- Basic Information -->
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label for="full_name" class="form-label">Nama Lengkap <span class="text-danger">*</span></label>
                                <input type="text" class="form-control" id="full_name" name="full_name" 
                                       value="<?php echo htmlspecialchars($user['full_name']); ?>" required>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label for="email" class="form-label">Email <span class="text-danger">*</span></label>
                                <input type="email" class="form-control" id="email" name="email" 
                                       value="<?php echo htmlspecialchars($user['email']); ?>" required>
                            </div>
                        </div>

                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label for="phone" class="form-label">Nomor Telepon</label>
                                <input type="tel" class="form-control" id="phone" name="phone" 
                                       value="<?php echo htmlspecialchars($user['phone'] ?: ''); ?>" 
                                       placeholder="Contoh: 081234567890">
                            </div>
                            <div class="col-md-6 mb-3">
                                <label for="role" class="form-label">Role</label>
                                <input type="text" class="form-control" value="<?php echo ucfirst($user['role']); ?>" readonly>
                            </div>
                        </div>

                        <div class="mb-4">
                            <label for="address" class="form-label">Alamat</label>
                            <textarea class="form-control" id="address" name="address" rows="3" 
                                      placeholder="Masukkan alamat lengkap Anda"><?php echo htmlspecialchars($user['address'] ?: ''); ?></textarea>
                        </div>

                        <!-- Password Change Section -->
                        <div class="section-divider">
                            <h4 class="h5 fw-semibold text-dark mb-3">Ubah Password</h4>
                            <p class="text-muted small mb-3">Kosongkan jika tidak ingin mengubah password.</p>
                            
                            <div class="row">
                                <div class="col-md-4 mb-3">
                                    <label for="current_password" class="form-label">Password Saat Ini</label>
                                    <input type="password" class="form-control" id="current_password" name="current_password" 
                                           placeholder="Masukkan password saat ini">
                                </div>
                                <div class="col-md-4 mb-3">
                                    <label for="new_password" class="form-label">Password Baru</label>
                                    <input type="password" class="form-control" id="new_password" name="new_password" 
                                           placeholder="Minimal 6 karakter">
                                </div>
                                <div class="col-md-4 mb-3">
                                    <label for="confirm_password" class="form-label">Konfirmasi Password Baru</label>
                                    <input type="password" class="form-control" id="confirm_password" name="confirm_password" 
                                           placeholder="Ulangi password baru">
                                </div>
                            </div>
                        </div>

                        <!-- Form Actions -->
                        <div class="d-flex justify-content-between">
                            <a href="dashboard_user.php" class="btn btn-secondary">
                                <i class="bi bi-arrow-left me-2"></i>
                                Kembali ke Dashboard
                            </a>
                            <button type="submit" class="btn btn-primary">
                                <i class="bi bi-check-circle me-2"></i>
                                Simpan Perubahan
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <!-- Footer -->
    <footer class="bg-white border-top mt-5 py-4">
        <div class="container">
            <div class="text-center">
                <p class="text-muted small mb-0">
                    © 2025 VolunteerHub. Semua hak dilindungi undang-undang.
                </p>
            </div>
        </div>
    </footer>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    
    <script>
        // Password validation
        document.getElementById('new_password').addEventListener('input', function() {
            const newPassword = this.value;
            const confirmPassword = document.getElementById('confirm_password');
            
            if (newPassword.length > 0 && newPassword.length < 6) {
                this.setCustomValidity('Password minimal 6 karakter');
            } else {
                this.setCustomValidity('');
            }
            
            // Check confirm password match
            if (confirmPassword.value && confirmPassword.value !== newPassword) {
                confirmPassword.setCustomValidity('Password tidak cocok');
            } else {
                confirmPassword.setCustomValidity('');
            }
        });

        document.getElementById('confirm_password').addEventListener('input', function() {
            const newPassword = document.getElementById('new_password').value;
            const confirmPassword = this.value;
            
            if (confirmPassword && confirmPassword !== newPassword) {
                this.setCustomValidity('Password tidak cocok');
            } else {
                this.setCustomValidity('');
            }
        });

        // Require current password if new password is entered
        document.getElementById('new_password').addEventListener('input', function() {
            const currentPassword = document.getElementById('current_password');
            if (this.value) {
                currentPassword.required = true;
            } else {
                currentPassword.required = false;
            }
        });
    </script>
</body>
</html>