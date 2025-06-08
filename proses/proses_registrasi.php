<?php
require_once '../koneksi.php';

// Ambil data dari form
$nama     = $_POST['nama'];
$email    = $_POST['email'];
$password = password_hash($_POST['password'], PASSWORD_DEFAULT);
$kontak   = $_POST['kontak'];
$keahlian = $_POST['keahlian'];
$bio      = $_POST['bio'];
$role     = 'user'; // default role

// Cek apakah email sudah digunakan
$cek = mysqli_query($conn, "SELECT * FROM volunteers WHERE email='$email'");
if (mysqli_num_rows($cek) > 0) {
  echo "<script>alert('Email sudah terdaftar!'); window.location='../register.php';</script>";
  exit;
}

// Simpan data ke database
$query = "INSERT INTO volunteers (nama, email, password, kontak, keahlian, bio, role) 
          VALUES (?, ?, ?, ?, ?, ?, ?)";
$stmt = $conn->prepare($query);
$stmt->bind_param("sssssss", $nama, $email, $password, $kontak, $keahlian, $bio, $role);

if ($stmt->execute()) {
  echo "<script>alert('Registrasi berhasil! Silakan login.'); window.location='../login.php';</script>";
} else {
  echo "<script>alert('Registrasi gagal: " . $conn->error . "'); window.location='../register.php';</script>";
}
?>
