<?php
$currentPage = basename($_SERVER['PHP_SELF']); // Dapatkan nama file aktif
?>
<nav class="navbar navbar-expand-lg bg-white border-bottom py-3">
  <div class="container">
    <a class="navbar-brand text-primary" href="#">Volunteer</a>
    <div class="collapse navbar-collapse justify-content-end">
      <ul class="navbar-nav">
        <li class="nav-item">
          <a class="nav-link <?= $currentPage === 'search.php' ? 'active text-primary ' : 'text-dark' ?>" href="../dashboard_user.html">Beranda</a>
        </li>
        <li class="nav-item active">
          <a class="nav-link <?= $currentPage === 'events.php' || $currentPage === 'detail.php' ? 'active text-primary ' : 'text-dark' ?>" href="events.php">Cari Event</a>
        </li>
        <li class="nav-item">
          <a class="nav-link text-dark" href="#">Riwayat</a>
        </li>
        <li class="nav-item">
          <a class="nav-link text-danger " href="#">Logout</a>
        </li>
      </ul>
    </div>
  </div>
</nav>
