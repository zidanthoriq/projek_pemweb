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
  <?php include('header.php'); ?>

  <meta charset="UTF-8">
  <title>Detail Event</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
  <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;600&display=swap" rel="stylesheet">
  <style>
    body {
      background: rgba(0, 0, 0, 0.5);
      font-family: 'Poppins', sans-serif;
    }
    .modal-like {
      max-width: 650px;
      margin: 5% auto;
      background: #fff;
      padding: 30px 40px;
      border-radius: 12px;
      box-shadow: 0 4px 25px rgba(0, 0, 0, 0.2);
    }
    h2 {
      font-weight: 600;
      margin-bottom: 25px;
    }
    p {
      margin-bottom: 10px;
      font-size: 16px;
    }
    strong {
      width: 100px;
      display: inline-block;
    }
    .btn-secondary {
      padding: 8px 20px;
      font-size: 15px;
    }
    .nav-link.active {
  color: #007bff !important;
  font-weight: 600;
}

  </style>
</head>
<body>
  <div class="modal-like">
    <h2>Detail Event</h2>
    <p><strong>Nama:</strong> <?= htmlspecialchars($event['nama']) ?></p>
    <p><strong>Tanggal:</strong> <?= $event['tanggal'] ?></p>
    <p><strong>Lokasi:</strong> <?= $event['lokasi'] ?></p>
    <p><strong>Kategori:</strong> <?= $event['kategori'] ?></p>
    <p><strong>Kuota:</strong> <?= $event['kuota'] ?></p>
    <p><strong>Deskripsi:</strong> <?= $event['deskripsi'] ?></p>
    <p><strong>Benefit:</strong> <?= $event['benefit'] ?></p>
    <div class="text-end mt-4">
      <a href="events.php" class="btn btn-secondary">← Kembali</a>
    </div>
  </div>
</body>
</html>
