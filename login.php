<?php session_start(); ?>
<!DOCTYPE html>
<html>
  
<head>
  <title>Login Volunteer</title>
  <link rel="stylesheet" href="css/style.css">
</head>

<body>
  <div class="container">
    <h2>Login Volunteer</h2>
    <form action="proses/proses_login.php" method="post">
      <input type="email" name="email" placeholder="Email" required>
      <input type="password" name="password" placeholder="Password" required>
      <button type="submit">Masuk</button>
    </form>
    <p class="small-text">Belum punya akun? <a href="register.php">Daftar sekarang</a></p>
  </div>
</body>
</html>
