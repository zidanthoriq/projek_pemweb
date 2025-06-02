<?php include('data/events.php'); ?>
<!DOCTYPE html>
<html lang="id">
<head>
  <meta charset="UTF-8">
  <title>Pencarian Event</title>
  <!-- Google Fonts: Poppins -->
  <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;600&display=swap" rel="stylesheet">
  <!-- Bootstrap CSS -->
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css">
  <style>
    body {
      font-family: 'Poppins', sans-serif;
      background-color: #f8f9fa;
    }
    .navbar-brand {
      color: #007bff !important;
      font-weight: 600;
      font-size: 20px;
    }
    .nav-link {
      font-weight: 500;
      color: #444 !important;
    }
    .nav-link.active {
      color: #007bff !important;
    }
    .nav-link.logout {
      color: #dc3545 !important;
    }
    .form-label {
      font-weight: 500;
    }
    .form-section {
      background-color: #fff;
      padding: 30px;
      border-radius: 12px;
      box-shadow: 0 0 12px rgba(0,0,0,0.05);
    }
    h2 {
      font-weight: 600;
    }
  </style>
</head>
<body>

<!-- Header / Navbar -->
<nav class="navbar navbar-expand-lg bg-white border-bottom py-3">
  <div class="container">
    <a class="navbar-brand" href="#">Volunteer</a>
    <div class="collapse navbar-collapse justify-content-end">
      <ul class="navbar-nav">
        <li class="nav-item">
          <a class="nav-link" href="#">Beranda</a>
        </li>
        <li class="nav-item">
          <a class="nav-link active" href="#">Event</a>
        </li>
        <li class="nav-item">
          <a class="nav-link" href="#">Riwayat</a>
        </li>
        <li class="nav-item">
          <a class="nav-link logout" href="#">Logout</a>
        </li>
      </ul>
    </div>
  </div>
</nav>

<!-- Form Pencarian -->
<div class="container py-5">
  <div class="form-section">
    <h2 class="mb-4">Cari Event Volunteer</h2>
    <form method="GET" action="events.php" class="row g-3">
      <div class="col-md-4">
        <label class="form-label">Nama/Kategori</label>
        <input type="text" name="q" class="form-control" placeholder="Cari nama atau kategori">
      </div>
      <div class="col-md-2">
        <label class="form-label">Tanggal</label>
        <input type="date" name="tanggal" class="form-control">
      </div>
      <div class="col-md-2">
        <label class="form-label">Lokasi</label>
        <select name="lokasi" class="form-select">
          <option value="">Semua</option>
          <option value="Jakarta">Jakarta</option>
          <option value="Bandung">Bandung</option>
          <option value="Surabaya">Surabaya</option>
        </select>
      </div>
      <div class="col-md-2">
        <label class="form-label">Kategori</label>
        <select name="kategori" class="form-select">
          <option value="">Semua</option>
          <option value="Lingkungan">Lingkungan</option>
          <option value="Kesehatan">Kesehatan</option>
          <option value="Pendidikan">Pendidikan</option>
        </select>
      </div>
      <div class="col-md-2 d-flex align-items-end">
        <button type="submit" class="btn btn-primary w-100">Cari</button>
      </div>
    </form>
  </div>
</div>

</body>
</html>
