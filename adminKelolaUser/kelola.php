<!DOCTYPE html>
<html lang="id">
<head>
  <meta charset="UTF-8">
  <title>Kelola Akun Volunteer</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
  <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600&display=swap" rel="stylesheet">
  <style>
    body {
      font-family: 'Inter', sans-serif;
      background-color: #f5f7fa;
    }
    h4 {
      font-weight: 600;
    }
    .table thead th {
      font-size: 0.875rem;
      background-color: #f1f3f5;
    }
    .table td {
      font-size: 0.95rem;
    }
    .btn-info {
      background-color: #4dabf7;
      border: none;
    }
    .btn-info:hover {
      background-color: #339af0;
    }
    .search-input {
      max-width: 300px;
    }
  </style>
</head>
<body>

<div class="d-flex">
  <?php include 'sidebar.php'; ?>

  <div class="flex-grow-1">
    <?php include 'navbar.php'; ?>

    <div class="container mt-5">
      <h4 class="mb-4 text-dark">Kelola Akun Volunteer</h4>

      <input type="text" id="searchInput" class="form-control search-input mb-3 shadow-sm" placeholder="Cari nama atau keahlian...">

      <div class="table-responsive">
        <table class="table table-hover shadow-sm bg-white rounded" id="volunteerTable">
          <thead>
            <tr>
              <th>No</th>
              <th>Nama</th>
              <th>Email</th>
              <th>Keahlian</th>
              <th>Aksi</th>
            </tr>
          </thead>
          <tbody>
            <?php
            $volunteers = [
              ['id'=>1, 'nama'=>'Sarah Wijaya', 'email'=>'sarah.wijaya@email.com', 'keahlian'=>'Pendidikan, Komunikasi'],
              ['id'=>2, 'nama'=>'Ahmad Fauzi', 'email'=>'ahmad.fauzi@email.com', 'keahlian'=>'Kesehatan, Sosial'],
              ['id'=>3, 'nama'=>'Lina Pratiwi', 'email'=>'lina.pratiwi@email.com', 'keahlian'=>'Lingkungan, Dokumentasi'],
              ['id'=>4, 'nama'=>'Budi Santoso', 'email'=>'budi.santoso@email.com', 'keahlian'=>'Teknologi, Pendidikan'],
              ['id'=>5, 'nama'=>'Rina Agustina', 'email'=>'rina.agustina@email.com', 'keahlian'=>'Sosial, Komunikasi'],
              ['id'=>6, 'nama'=>'Dewi Lestari', 'email'=>'dewi.lestari@email.com', 'keahlian'=>'Kesehatan, Manajemen'],
              ['id'=>7, 'nama'=>'Andi Wijaya', 'email'=>'andi.wijaya@email.com', 'keahlian'=>'Teknologi, Desain'],
            ];

            foreach ($volunteers as $index => $v) {
              echo "<tr>
                      <td>".($index+1)."</td>
                      <td>{$v['nama']}</td>
                      <td>{$v['email']}</td>
                      <td>{$v['keahlian']}</td>
                      <td><a href='detail.php?id={$v['id']}' class='btn btn-sm btn-info'>Lihat Detail</a></td>
                    </tr>";
            }
            ?>
          </tbody>
        </table>
      </div>
    </div>
  </div>
</div>

<script>
// Pencarian langsung berdasarkan nama atau keahlian
document.getElementById("searchInput").addEventListener("keyup", function() {
  const searchValue = this.value.toLowerCase();
  const rows = document.querySelectorAll("#volunteerTable tbody tr");

  rows.forEach(row => {
    const nama = row.children[1].textContent.toLowerCase();
    const keahlian = row.children[3].textContent.toLowerCase();
    if (nama.includes(searchValue) || keahlian.includes(searchValue)) {
      row.style.display = "";
    } else {
      row.style.display = "none";
    }
  });
});
</script>

</body>
</html>
