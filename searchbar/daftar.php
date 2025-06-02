<?php include('data/events.php');
$id = $_GET['id'] ?? 0;
$event = array_filter($events, fn($e) => $e['id'] == $id);
$event = reset($event);
if (!$event) {
  echo "Event tidak ditemukan."; exit;
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
  <meta charset="UTF-8">
  <title>Pendaftaran Event</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
  <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;600&display=swap" rel="stylesheet">
  <style>
    body {
      font-family: 'Poppins', sans-serif;
      background-color: #f8f9fa;
    }
    .form-container {
      max-width: 600px;
      margin: 60px auto;
      background: #fff;
      padding: 40px;
      border-radius: 12px;
      box-shadow: 0 4px 20px rgba(0, 0, 0, 0.1);
    }
    h2 {
      font-weight: 600;
      margin-bottom: 25px;
      font-size: 24px;
    }
    p {
      font-size: 16px;
      margin-bottom: 20px;
    }
    .form-label {
      font-size: 15px;
      font-weight: 500;
    }
    .form-control {
      font-size: 15px;
    }
    .btn {
      font-size: 15px;
      padding: 8px 20px;
    }
    .btn-back {
      margin-top: 20px;
    }
    .nav-link.active {
  color: #007bff !important;
  font-weight: 600;
}

  </style>
</head>
<body>
  <?php include('header.php'); ?>

  <div class="form-container">
    <h2>Form Pendaftaran Event</h2>
    <p>Anda akan mendaftar ke event: <strong><?= htmlspecialchars($event['nama']) ?></strong></p>
    <form method="POST" action="#">
      <div class="mb-3">
        <label class="form-label">Nama Lengkap</label>
        <input type="text" class="form-control" required>
      </div>
      <div class="mb-3">
        <label class="form-label">Email</label>
        <input type="email" class="form-control" required>
      </div>
      <button type="submit" class="btn btn-success">Kirim Pendaftaran</button>
    </form>
    <a href="events.php" class="btn btn-primary btn-back">← Kembali ke Event</a>
  </div>
</body>
</html>
