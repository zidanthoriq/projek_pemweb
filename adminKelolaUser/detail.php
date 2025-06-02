<?php
$volunteers = [
  1 => [
    'nama' => 'Sarah Wijaya',
    'email' => 'sarah.wijaya@email.com',
    'hp' => '0812-3456-7890',
    'keahlian' => 'Pendidikan, Komunikasi',
    'pengalaman' => [
      'Pengajar anak jalanan - Rumah Singgah 2023',
      'Panitia Workshop Literasi Digital - 2024'
    ]
  ],
  2 => [
    'nama' => 'Ahmad Fauzi',
    'email' => 'ahmad.fauzi@email.com',
    'hp' => '0813-9876-1234',
    'keahlian' => 'Kesehatan, Sosial',
    'pengalaman' => [
      'Relawan Posyandu - 2022',
      'Koordinator Donor Darah - 2023'
    ]
  ],
  3 => [
    'nama' => 'Lina Pratiwi',
    'email' => 'lina.pratiwi@email.com',
    'hp' => '0852-1111-2222',
    'keahlian' => 'Lingkungan, Dokumentasi',
    'pengalaman' => [
      'Dokumentasi Aksi Tanam Pohon - 2023',
      'Tim Kreatif Bersih Sungai - 2024'
    ]
  ],
  4 => [
    'nama' => 'Budi Santoso',
    'email' => 'budi.santoso@email.com',
    'hp' => '0878-5555-6666',
    'keahlian' => 'Teknologi, Pendidikan',
    'pengalaman' => [
      'Pelatihan Coding untuk Anak - 2022',
      'Mentor IT di Komunitas Pemuda - 2023'
    ]
  ],
  5 => [
    'nama' => 'Rina Agustina',
    'email' => 'rina.agustina@email.com',
    'hp' => '0819-7777-8888',
    'keahlian' => 'Sosial, Komunikasi',
    'pengalaman' => [
      'MC Kegiatan Bakti Sosial - 2023',
      'Relawan Konseling Remaja - 2024'
    ]
  ],
  6 => [
    'nama' => 'Dewi Lestari',
    'email' => 'dewi.lestari@email.com',
    'hp' => '0851-2345-6789',
    'keahlian' => 'Kesehatan, Manajemen',
    'pengalaman' => [
      'Koordinator Posko Vaksinasi - 2021',
      'Manajer Event Kesehatan Mental - 2023'
    ]
  ],
  7 => [
    'nama' => 'Andi Wijaya',
    'email' => 'andi.wijaya@email.com',
    'hp' => '0823-4567-1234',
    'keahlian' => 'Teknologi, Desain',
    'pengalaman' => [
      'Desain Poster Event Sosial - 2022',
      'Pengembang Website Komunitas - 2024'
    ]
  ]
];

$id = $_GET['id'] ?? 0;
$data = $volunteers[$id] ?? null;
?>

<!DOCTYPE html>
<html lang="id">
<head>
  <meta charset="UTF-8">
  <title>Detail Volunteer</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
  <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600&display=swap" rel="stylesheet">
  <style>
    body {
      font-family: 'Inter', sans-serif;
      background-color: #f5f7fa;
    }
    h3 {
      font-weight: 600;
    }
    .card-title {
      font-weight: 600;
    }
  </style>
</head>
<body>

<div class="container my-5">
  <h3 class="mb-4 text-dark">Detail Volunteer</h3>

  <?php if ($data): ?>
  <div class="card shadow-sm border-0">
    <div class="card-body">
      <h5 class="card-title"><?= $data['nama'] ?></h5>
      <p><strong>Email:</strong> <?= $data['email'] ?></p>
      <p><strong>Nomor HP:</strong> <?= $data['hp'] ?></p>
      <p><strong>Keahlian:</strong> <?= $data['keahlian'] ?></p>
      <p><strong>Pengalaman Relawan:</strong></p>
      <ul>
        <?php foreach ($data['pengalaman'] as $item): ?>
          <li><?= $item ?></li>
        <?php endforeach; ?>
      </ul>
    </div>
  </div>
  <?php else: ?>
    <div class="alert alert-danger">Data volunteer tidak ditemukan.</div>
  <?php endif; ?>

  <a href="kelola_volunteer.php" class="btn btn-secondary mt-4 shadow-sm">Kembali</a>
</div>

</body>
</html>
