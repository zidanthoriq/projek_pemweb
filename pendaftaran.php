<!DOCTYPE html>
<html lang="id">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1" />
  <title>Riwayat Pendaftaran | VolunteerHub</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet" />
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

    .topbar {
      background-color: #ffffff;
      border-bottom: 1px solid #dee2e6;
      height: 60px;
      display: flex;
      align-items: center;
      justify-content: space-between;
      padding: 0 30px;
      position: sticky;
      top: 0;
      z-index: 1000;
    }

    .topbar a {
      font-weight: 500;
      padding: 6px 12px;
      border-radius: 6px;
      text-decoration: none;
      color: #212529;
    }

    .topbar a.active {
      background-color: #000;
      color: #fff !important;
    }

    .topbar a.text-danger {
      color: #dc3545 !important;
    }

    .badge-menunggu {
      background-color: #fff3cd;
      color: #856404;
    }

    .badge-diterima {
      background-color: #d4edda;
      color: #155724;
    }

    .badge-ditolak {
      background-color: #f8d7da;
      color: #721c24;
    }

    .table td, .table th {
      vertical-align: middle;
    }

    
        .dropdown-toggle::after {
            display: none;
        }

        .profile-img {
            width: 2rem;
            height: 2rem;
            border-radius: 50%;
            margin-right: 0.5rem;
        }

        .dropdown-arrow {
            width: 1rem;
            height: 1rem;
            margin-left: 0.5rem;
        }

        .card-custom {
            border: none;
            border-radius: 0.5rem;
            box-shadow: 0 1px 3px 0 rgba(0, 0, 0, 0.1);
            margin-bottom: 1.5rem;
            overflow: hidden;
        }

        .card-header-custom {
            background-color: #f9fafb;
            border-bottom: 1px solid #e5e7eb;
            padding: 1rem;
        }

        .card-footer-custom {
            background-color: #f9fafb;
            border-top: 1px solid #e5e7eb;
            padding: 1rem;
        }

        .stat-card {
            display: flex;
            align-items: center;
        }

        .stat-icon {
            display: flex;
            align-items: center;
            justify-content: center;
            width: 3rem;
            height: 3rem;
            border-radius: 0.375rem;
            margin-right: 1.25rem;
            flex-shrink: 0;
        }

        .stat-icon img {
            width: 1.5rem;
            height: 1.5rem;
        }

        .stat-icon.blue { background-color: #3b82f6; }
        .stat-icon.green { background-color: #10b981; }
        .stat-icon.yellow { background-color: #f59e0b; }

        .stat-title {
            font-size: 0.875rem;
            font-weight: 500;
            color: #6b7280;
            margin-bottom: 0.25rem;
        }

        .stat-value {
            font-size: 1.5rem;
            font-weight: 600;
            color: #1f2937;
            margin-bottom: 0;
        }

        .event-list {
            list-style: none;
            padding: 0;
            margin: 0;
        }

        .event-item {
            border-bottom: 1px solid #e5e7eb;
        }

        .event-item:last-child {
            border-bottom: none;
        }

        .event-link {
            display: block;
            padding: 1rem;
            color: inherit;
            text-decoration: none;
            transition: background-color 0.2s;
        }

        .event-link:hover {
            background-color: #f9fafb;
        }

        .event-title {
            font-size: 0.875rem;
            font-weight: 500;
            color: #2563eb;
            margin-bottom: 0.5rem;
        }

        .event-badge {
            display: inline-flex;
            align-items: center;
            padding: 0.25rem 0.5rem;
            border-radius: 9999px;
            font-size: 0.75rem;
            font-weight: 600;
        }

        .event-badge.green {
            background-color: #d1fae5;
            color: #065f46;
        }

        .event-badge.yellow {
            background-color: #fef3c7;
            color: #92400e;
        }

        .event-info {
            display: flex;
            flex-wrap: wrap;
            font-size: 0.875rem;
            color: #6b7280;
        }

        .event-info-item {
            display: flex;
            align-items: center;
            margin-right: 1.5rem;
            margin-bottom: 0.5rem;
        }

        .event-info-item img {
            width: 1.25rem;
            height: 1.25rem;
            margin-right: 0.375rem;
        }

        .quick-action {
            display: flex;
            align-items: center;
            padding: 0.75rem;
            border-radius: 0.5rem;
            margin-bottom: 0.75rem;
            transition: background-color 0.2s;
            text-decoration: none;
        }

        .quick-action img {
            width: 1.5rem;
            height: 1.5rem;
            margin-right: 0.75rem;
        }

        .quick-action span {
            font-size: 0.875rem;
            font-weight: 500;
            color: #1f2937;
        }

        .quick-action.blue {
            background-color: #eff6ff;
        }

        .quick-action.blue:hover {
            background-color: #dbeafe;
        }

        .quick-action.green {
            background-color: #ecfdf5;
        }

        .quick-action.green:hover {
            background-color: #d1fae5;
        }

        .quick-action.purple {
            background-color: #f5f3ff;
        }

        .quick-action.purple:hover {
            background-color: #ede9fe;
        }

        .event-card-image {
            height: 12rem;
            object-fit: cover;
        }

        .event-card-title {
            font-size: 1.125rem;
            font-weight: 500;
            color: #1f2937;
            margin-bottom: 0.25rem;
        }

        .event-card-desc {
            font-size: 0.875rem;
            color: #6b7280;
            margin-bottom: 1rem;
        }

        .event-card-info {
            display: flex;
            align-items: center;
            font-size: 0.875rem;
            color: #6b7280;
            margin-bottom: 0.5rem;
        }

        .event-card-info img {
            width: 1.25rem;
            height: 1.25rem;
            margin-right: 0.375rem;
        }

        .progress-custom {
            height: 0.5rem;
            border-radius: 9999px;
            background-color: #e5e7eb;
            margin: 0.5rem 0;
        }

        .progress-bar-custom {
            height: 100%;
            border-radius: 9999px;
        }

        .progress-bar-blue {
            background-color: #3b82f6;
        }

        .progress-bar-green {
            background-color: #10b981;
        }

        .progress-bar-orange {
            background-color: #f59e0b;
        }

        .progress-label {
            display: flex;
            justify-content: space-between;
            font-size: 0.75rem;
            color: #6b7280;
        }

        .footer-custom {
            background-color: white;
            border-top: 1px solid #e5e7eb;
            margin-top: 3rem;
            padding: 3rem 0;
        }

        .footer-brand {
            font-size: 1.5rem;
            font-weight: 700;
            color: #1f2937;
            margin-bottom: 1rem;
        }

        .footer-desc {
            color: #6b7280;
            line-height: 1.5;
            margin-bottom: 2rem;
        }

        .footer-nav {
            display: flex;
            flex-wrap: wrap;
            justify-content: center;
            gap: 2rem;
            margin-bottom: 2rem;
        }

        .footer-nav a {
            color: #6b7280;
            text-decoration: none;
            font-size: 0.875rem;
            font-weight: 500;
            transition: color 0.2s;
        }

        .footer-nav a:hover {
            color: #2563eb;
        }

        .footer-copyright {
            font-size: 0.875rem;
            color: #9ca3af;
            text-align: center;
        }

        .dropdown-menu {
            padding: 0;
            overflow: hidden;
            border-radius: 0.5rem;
            box-shadow: 0 10px 15px -3px rgba(0, 0, 0, 0.1), 0 4px 6px -2px rgba(0, 0, 0, 0.05);
            border: 1px solid rgba(0, 0, 0, 0.05);
        }

        .dropdown-menu .dropdown-header {
            padding: 0.75rem 1rem;
            border-bottom: 1px solid #e5e7eb;
        }

        .dropdown-menu .dropdown-item {
            padding: 0.75rem 1rem;
            display: flex;
            align-items: center;
        }

        .dropdown-menu .dropdown-item img {
            margin-right: 0.75rem;
            width: 1rem;
            height: 1rem;
        }

        .dropdown-menu .dropdown-item:hover {
            background-color: #f9fafb;
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
          <a class="nav-link" href="dashboard_user.html">Beranda</a>
        </li>
        <li class="nav-item active">
          <a class="nav-link active" href="searchbar/search.php">Cari Event</a>
        </li>
        <li class="nav-item">
          <a class="nav-link" href="#">Riwayat</a>
        </li>
        <li class="nav-item">
          <!-- User Profile -->
            <div class="dropdown">
                <button class="btn btn-link dropdown-toggle d-flex align-items-center text-decoration-none" type="button" data-bs-toggle="dropdown">
                    <img src="/placeholder.svg?height=32&width=32&text=JD" alt="Profile" class="profile-img">
                    <span class="text-dark fw-medium">[NAMA USER]</span>
                    <img src="/placeholder.svg?height=16&width=16&text=▼" alt="Dropdown" class="dropdown-arrow">
                </button>
                <ul class="dropdown-menu dropdown-menu-end">
                    <li class="dropdown-header">
                        <p class="mb-0 fw-medium">[NAMA USER]</p>
                        <p class="mb-0 text-muted small">example@email.com</p>
                    </li>
                    <li><a class="dropdown-item" href="riwayat.html">
                        <img src="/placeholder.svg?height=16&width=16&text=📋" alt="History">
                        Riwayat Pendaftaran
                    </a></li>
                    <li><a class="dropdown-item" href="#">
                        <img src="/placeholder.svg?height=16&width=16&text=⚙️" alt="Settings">
                        Pengaturan
                    </a></li>
                    <li><hr class="dropdown-divider"></li>
                    <li><a class="dropdown-item" href="#">
                        <img src="/placeholder.svg?height=16&width=16&text=🚪" alt="Logout">
                        Logout
                    </a></li>
                </ul>
            </div>
        </li>
      </ul>
    </div>
  </div>
</nav>

<!-- Content -->
<div class="container py-5">
  <h5 class="mb-3">Riwayat Pendaftaran</h5>
  <div class="table-responsive bg-white shadow-sm rounded p-3">
    <table class="table align-middle">
      <thead class="table-light">
        <tr>
          <th>Nama Event</th>
          <th>Tanggal</th>
          <th>Lokasi</th>
          <th>Status</th>
          <th>Aksi</th>
        </tr>
      </thead>
      <tbody>
        <tr>
          <td>Bersih Pantai 2025</td>
          <td>10 Jan 2025</td>
          <td>Pantai Kuta, Bali</td>
          <td><span class="badge badge-menunggu">Menunggu</span></td>
          <td><button class="btn btn-danger btn-sm" onclick="openModal(this)">Batalkan</button></td>
        </tr>
        <tr>
          <td>Donor Darah Massal</td>
          <td>15 Des 2024</td>
          <td>RS Umum Jakarta</td>
          <td><span class="badge badge-diterima">Diterima</span></td>
          <td>-</td>
        </tr>
        <tr>
          <td>Penanaman 1000 Pohon</td>
          <td>20 Nov 2024</td>
          <td>Hutan Kota Bandung</td>
          <td><span class="badge badge-ditolak">Ditolak</span></td>
          <td><span class="text-danger" style="cursor:pointer;" onclick="showReason('Maaf, kuota peserta telah penuh untuk event ini.')">Lihat Alasan</span></td>
        </tr>
        <tr>
          <td>Pengajaran Anak Jalanan</td>
          <td>5 Nov 2024</td>
          <td>RPTRA Kemayoran</td>
          <td><span class="badge badge-menunggu">Menunggu</span></td>
          <td><button class="btn btn-danger btn-sm" onclick="openModal(this)">Batalkan</button></td>
        </tr>
      </tbody>
    </table>
  </div>
</div>

<!-- Modal Pembatalan -->
<div class="modal fade" id="confirmModal" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title">Konfirmasi Pembatalan</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
      </div>
      <div class="modal-body" id="modalText"></div>
      <div class="modal-footer">
        <button class="btn btn-secondary" data-bs-dismiss="modal">Kembali</button>
        <button class="btn btn-danger" onclick="confirmCancel()">Ya, Batalkan</button>
      </div>
    </div>
  </div>
</div>

<!-- Modal Alasan -->
<div class="modal fade" id="reasonModal" tabindex="-1">
  <div class="modal-dialog modal-dialog-centered">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title">Alasan Penolakan</h5>
        <button class="btn-close" data-bs-dismiss="modal"></button>
      </div>
      <div class="modal-body" id="reasonText"></div>
      <div class="modal-footer">
        <button class="btn btn-secondary" data-bs-dismiss="modal">Tutup</button>
      </div>
    </div>
  </div>
</div>

<script>
  let selectedButton = null;

  function openModal(button) {
    selectedButton = button;
    const row = button.closest("tr");
    const eventName = row.cells[0].innerText;
    document.getElementById("modalText").textContent =
      `Apakah Anda yakin ingin membatalkan pendaftaran untuk event "${eventName}"?`;
    new bootstrap.Modal(document.getElementById("confirmModal")).show();
  }

  function confirmCancel() {
    const row = selectedButton.closest("tr");
    row.cells[3].innerHTML = '<span class="badge badge-diterima">Dibatalkan</span>';
    row.cells[4].innerHTML = '-';
    bootstrap.Modal.getInstance(document.getElementById("confirmModal")).hide();
  }

  function showReason(reason) {
    document.getElementById("reasonText").textContent = reason;
    new bootstrap.Modal(document.getElementById("reasonModal")).show();
  }
</script>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>

    <script>
        document.addEventListener('DOMContentLoaded', function() {
            var dropdownElementList = [].slice.call(document.querySelectorAll('.dropdown-toggle'))
            var dropdownList = dropdownElementList.map(function (dropdownToggleEl) {
                return new bootstrap.Dropdown(dropdownToggleEl)
            })
        });
    </script>
</body>
</html>
