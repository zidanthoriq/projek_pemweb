<?php include('data/events.php'); ?>
<?php
function filterEvent($event, $q, $tanggal, $lokasi, $kategori) {
  $matchQ = !$q || stripos($event['nama'], $q) !== false || stripos($event['kategori'], $q) !== false;
  $matchTanggal = !$tanggal || $event['tanggal'] == $tanggal;
  $matchLokasi = !$lokasi || $event['lokasi'] == $lokasi;
  $matchKategori = !$kategori || $event['kategori'] == $kategori;
  return $matchQ && $matchTanggal && $matchLokasi && $matchKategori;
}

$q = $_GET['q'] ?? '';
$tanggal = $_GET['tanggal'] ?? '';
$lokasi = $_GET['lokasi'] ?? '';
$kategori = $_GET['kategori'] ?? '';

$filtered = array_filter($events, fn($e) => filterEvent($e, $q, $tanggal, $lokasi, $kategori));
?>
<!DOCTYPE html>
<html lang="id">
<head>
  <meta charset="UTF-8">
  <title>Daftar Event</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
  <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;600&display=swap" rel="stylesheet">
  <style>
    body {
      font-family: 'Poppins', sans-serif;
      background-color: #f8f9fa;
    }
    .navbar-brand {
      font-weight: 600;
      font-size: 20px;
    }
    h2 {
      font-weight: 600;
      margin-bottom: 30px;
    }
    .card {
      border-radius: 12px;
      box-shadow: 0 4px 16px rgba(0, 0, 0, 0.05);
      transition: transform 0.2s ease;
    }
    .card:hover {
      transform: translateY(-5px);
    }
    .card-body h5 {
      font-weight: 600;
      font-size: 18px;
      margin-bottom: 15px;
    }
    .card-body p {
      font-size: 14px;
      margin-bottom: 8px;
    }
    .btn {
      font-size: 14px;
      padding: 6px 14px;
    }
    .btn-back {
      margin-bottom: 25px;
    }
    .nav-link.active {
  color: #007bff !important;
  font-weight: 600;
}

  </style>
</head>
<body>

  <!-- Header  -->
  <?php include('header.php'); ?>

  <div class="container py-5">
    <h2>Daftar Event</h2>
    <a href="index.php" class="btn btn-primary btn-back">← Kembali ke Pencarian</a>
    <div class="row g-4">
      <?php if (count($filtered) === 0): ?>
        <p>Tidak ada event ditemukan.</p>
      <?php endif; ?>
      <?php foreach ($filtered as $event): ?>
        <div class="col-md-4">
          <div class="card h-100">
            <div class="card-body">
              <h5><?= htmlspecialchars($event['nama']) ?></h5>
              <p><strong>Tanggal:</strong> <?= $event['tanggal'] ?></p>
              <p><strong>Lokasi:</strong> <?= $event['lokasi'] ?></p>
              <p><strong>Kuota:</strong> <?= $event['kuota'] ?></p>
              <a href="detail.php?id=<?= $event['id'] ?>" class="btn btn-outline-primary btn-sm">Detail</a>
              <a href="daftar.php?id=<?= $event['id'] ?>" class="btn btn-primary btn-sm ms-2">Daftar</a>
            </div>
          </div>
        </div>
      <?php endforeach; ?>
    </div>
  </div>
</body>
</html>
