<!DOCTYPE html>
<html>
<head>
  <title>Registrasi Volunteer</title>
  <link rel="stylesheet" href="css/style.css">
</head>
<body>
  <div class="container">
    <h2>Registrasi Volunteer</h2>
    <form action="proses/proses_registrasi.php" method="post">
      <input type="text" name="nama" placeholder="Nama Lengkap" required>
      <input type="email" name="email" placeholder="Email" required>
      <input type="password" name="password" placeholder="Password" required>
      <input type="text" name="kontak" placeholder="No. Kontak / WhatsApp" required>
      <input type="text" name="keahlian" placeholder="Keahlian" required>
      <textarea name="bio" placeholder="Bio / Pengalaman" rows="3"></textarea>
      <button type="submit">Daftar</button>
    </form>
    <p class="small-text">Sudah punya akun? <a href="login.php">Login di sini</a></p>
  </div>
</body>
</html>
