<?php
session_start();
require '../koneksi.php'; // Sesuaikan dengan path file koneksi

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $email = htmlspecialchars($_POST['email']);
    $password = $_POST['password'];

    // Cek apakah email ada di database
    $stmt = $conn->prepare("SELECT id, nama, email, password, role FROM volunteers WHERE email = ?");
    $stmt->bind_param("s", $email);
    $stmt->execute();
    $result = $stmt->get_result();

    // Jika user ditemukan
    if ($result->num_rows === 1) {
        $row = $result->fetch_assoc();

        // Verifikasi password
        if (password_verify($password, $row['password'])) {
            // Simpan data ke session
            $_SESSION['id'] = $row['id'];
            $_SESSION['nama'] = $row['nama'];
            $_SESSION['email'] = $row['email'];
            $_SESSION['role'] = $row['role'];

            // Arahkan ke dashboard sesuai peran
            if ($row['role'] === 'admin') {
                header("Location: ../dashboard_admin.html");
                exit;
            } else {
                header("Location: ../dashboard_user.html");
                exit;
            }
        } else {
            // Password salah
            echo "<script>alert('Password salah.'); window.location.href = '../login.php';</script>";
        }
    } else {
        // Email tidak ditemukan
        echo "<script>alert('Email tidak terdaftar.'); window.location.href = '../login.php';</script>";
    }

    $stmt->close();
    $conn->close();
} else {
    // Jika bukan metode POST
    header("Location: ../login.php");
    exit;
}
?>
