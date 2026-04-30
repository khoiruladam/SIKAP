<?php
// Debug mode (nyalakan hanya untuk local dev)
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

session_start();
include '../config/koneksi.php';

// Cek koneksi
if (!isset($koneksi) || !($koneksi instanceof mysqli)) {
    die("Kesalahan koneksi database. Periksa file koneksi.php");
}

// Cek role
$role = $_SESSION['role'] ?? null;
if ($role === null || !in_array($role, ['admin','guru'])) {
    header("Location: ../index.php");
    exit;
}

// Helper fungsi aman
function e($v){ return htmlspecialchars($v ?? '', ENT_QUOTES, 'UTF-8'); }

function fieldView($value, $minHeight = 38) {
  if (!empty($value)) {
    return '<div class="form-control bg-light">' . e($value) . '</div>';
  }

  return '
    <div class="form-control bg-light text-muted fst-italic"
         style="min-height:' . $minHeight . 'px;
                border:1px dashed #ced4da;">
      Tidak diisi
    </div>
  ';
}

// Page handling
$page = $_GET['page'] ?? 'absensi';
$page = in_array($page, ['absensi','kegiatan','bimbingan','user']) ? $page : 'absensi';

// Filter & sorting
$sort_column = $_GET['sort'] ?? 'nama';
$sort_order  = $_GET['order'] ?? 'ASC';
$allowed_columns = ['nama','tanggal'];
$allowed_order   = ['ASC','DESC'];
if(!in_array($sort_column,$allowed_columns)) $sort_column='nama';
if(!in_array($sort_order,$allowed_order)) $sort_order='ASC';

$filter_nama = trim($_GET['filter_nama'] ?? '');
$start_date = $_GET['start_date'] ?? '';
$end_date   = $_GET['end_date'] ?? '';

$filter_sql  = '';
if ($filter_nama) {
    $filter_sql .= " AND u.nama LIKE '%".mysqli_real_escape_string($koneksi,$filter_nama)."%' ";
}
if ($start_date) {
    $filter_sql .= " AND a.tanggal >= '".mysqli_real_escape_string($koneksi,$start_date)."' ";
}
if ($end_date) {
    $filter_sql .= " AND a.tanggal <= '".mysqli_real_escape_string($koneksi,$end_date)."' ";
}

// Query helper
function q($conn, $sql) {
    $res = mysqli_query($conn, $sql);
    if ($res === false) die("SQL Error: " . htmlspecialchars(mysqli_error($conn)) . "<br>Query: " . htmlspecialchars($sql));
    return $res;
}

// Ambil data sesuai halaman
switch($page){
    case 'absensi':
        $data = q($koneksi, "
            SELECT a.*, u.nama, u.kelas 
            FROM absensi a 
            JOIN users u ON a.user_id=u.id 
            WHERE 1=1 $filter_sql 
            ORDER BY $sort_column $sort_order
        ");
        break;
    case 'kegiatan':
        $data = q($koneksi, "
            SELECT k.*, u.nama, u.kelas 
            FROM kegiatan k 
            JOIN users u ON k.user_id=u.id 
            WHERE 1=1 ".($filter_nama? "AND u.nama LIKE '%".mysqli_real_escape_string($koneksi,$filter_nama)."%'":"")." 
            ORDER BY $sort_column $sort_order
        ");
        break;
    case 'bimbingan':
        $data = q($koneksi, "
            SELECT b.*, u.nama, u.kelas 
            FROM bimbingan b 
            JOIN users u ON b.user_id=u.id 
            WHERE 1=1 ".($filter_nama? "AND u.nama LIKE '%".mysqli_real_escape_string($koneksi,$filter_nama)."%'":"")." 
            ORDER BY $sort_column $sort_order
        ");
        break;
    case 'user':
        $data = q($koneksi, "SELECT * FROM users ORDER BY role ASC, nama ASC");
        break;
    default:
        $data = q($koneksi, "
            SELECT a.*, u.nama, u.kelas 
            FROM absensi a 
            JOIN users u ON a.user_id=u.id 
            WHERE 1=1 $filter_sql 
            ORDER BY $sort_column $sort_order
        ");
}

// Statistik singkat
$total_absensi   = (int)mysqli_fetch_row(q($koneksi, "SELECT COUNT(*) FROM absensi"))[0];
$total_kegiatan  = (int)mysqli_fetch_row(q($koneksi, "SELECT COUNT(*) FROM kegiatan"))[0];
$total_bimbingan = (int)mysqli_fetch_row(q($koneksi, "SELECT COUNT(*) FROM bimbingan"))[0];
$total_users     = (int)mysqli_fetch_row(q($koneksi, "SELECT COUNT(*) FROM users WHERE role='peserta'"))[0];

// Statistik kehadiran
$where_chart = "1=1";
if ($start_date) $where_chart .= " AND tanggal >= '".mysqli_real_escape_string($koneksi,$start_date)."'";
if ($end_date)   $where_chart .= " AND tanggal <= '".mysqli_real_escape_string($koneksi,$end_date)."'";

$hadir = (int)mysqli_fetch_row(q($koneksi, "
    SELECT COUNT(*) 
    FROM absensi 
    WHERE (status='Masuk' OR keterangan='Hadir') AND $where_chart
"))[0];

$izin  = (int)mysqli_fetch_row(q($koneksi, "
    SELECT COUNT(*) 
    FROM absensi 
    WHERE (status='Izin' OR keterangan='Izin') AND $where_chart
"))[0];

$sakit = (int)mysqli_fetch_row(q($koneksi, "
    SELECT COUNT(*) 
    FROM absensi 
    WHERE (status='Sakit' OR keterangan='Sakit') AND $where_chart
"))[0];

$alpa  = (int)mysqli_fetch_row(q($koneksi, "
    SELECT COUNT(*) 
    FROM absensi 
    WHERE (status='Tidak Masuk' OR keterangan='Alpa' OR status='Alpha') AND $where_chart
"))[0];

// Data admin
$admin_nama   = $_SESSION['nama'] ?? ($_SESSION['username'] ?? 'Admin');
$admin_avatar = '../assets/profile.png';
$agendaList   = q($koneksi, "SELECT * FROM agenda ORDER BY created_at DESC");

?>

<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="UTF-8">
<title>Dashboard Admin</title>
<meta name="viewport" content="width=device-width,initial-scale=1">
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
<link href="https://cdn.jsdelivr.net/npm/bootstrap-icons/font/bootstrap-icons.css" rel="stylesheet">
<link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600&display=swap" rel="stylesheet">
<link rel="icon" type="image/png" href="pngwing.com (1).png"> 
<style>
:root {
  --radius: 14px;
  --shadow: 0 6px 18px rgba(0,0,0,0.06);
}

body {
  font-family: 'Poppins', sans-serif;
  background: #f4f6f9;
  margin: 0;
  padding: 0;
}

.navbar {
  background: #fff;
  box-shadow: var(--shadow);
  padding: 10px 0;
}

.navbar-brand {
  font-weight: 600;
  color: #0d6efd !important;
}

.card {
  border-radius: var(--radius);
  box-shadow: var(--shadow);
  border: none;
}

.stat-card {
  padding: 16px;
  border-radius: 12px;
  text-align: center;
}

.stat-card .title {
  font-size: 13px;
  color: #6b7280;
}

.stat-card .value {
  font-size: 20px;
  font-weight: 700;
}

.table td,
.table th {
  vertical-align: middle;
  text-align: center;
  font-size: 14px;
}

.table-responsive {
  overflow-x: auto;
}

.nav-link.active {
  font-weight: 600;
  color: #0d6efd !important;
}

.btn-clean {
  border-radius: 8px;
}

.small-muted {
  font-size: 13px;
  color: #6b7280;
}

/* Chart canvas */
.chart-card canvas {
  max-width: 200px;  /* diameter chart maksimum */
  max-height: 200px;
  width: 100%;
  height: auto;
}

/* Statistik chart di samping */
.chart-statistics {
  display: flex;
  flex-wrap: wrap;
  gap: 15px;
  margin-top: 15px;
}

.chart-statistics .stat-item {
  display: flex;
  align-items: center;
  gap: 8px;
}

.chart-statistics .stat-item i {
  font-size: 1.25rem;
}

.chart-statistics .stat-item .label {
  font-size: 14px;
  color: #6b7280;
}

.chart-statistics .stat-item .value {
  font-weight: 600;
  font-size: 16px;
}

/* Tombol */
.btn-sm {
  padding: 4px 8px;
}

.btn-delete,
.btn-edit {
  width: 32px;
  height: 32px;
  display: inline-flex;
  align-items: center;
  justify-content: center;
}

/* Media Query */
@media (max-width: 767px) {
  .navbar .navbar-collapse {
    text-align: center;
  }

  .navbar-nav .nav-item {
    margin-bottom: 6px;
  }

  .stat-card {
    margin-bottom: 8px;
  }

  .table td,
  .table th {
    font-size: 12px;
  }

  .btn-sm {
    padding: 3px 6px;
  }

  .chart-card canvas {
    max-width: 50%;
    height: 100px;
  }

  .row.align-items-center {
    flex-direction: column;
  }

  .col-md-6 {
    max-width: 100%;
  }

  .chart-statistics {
    flex-direction: column;
    gap: 10px;
  }
}


</style>
</head>
<body>

<nav class="navbar navbar-expand-lg navbar-light bg-white shadow-sm sticky-top py-2">
  <div class="container">
    <!-- Brand -->
    <a class="navbar-brand d-flex align-items-center fw-bold text-primary" href="#">
      <span class="me-2" style="font-size:1.4rem;">📊</span> Dashboard Admin
    </a>

    <!-- Toggler -->
    <button class="navbar-toggler border-0" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav" aria-controls="navbarNav" aria-expanded="false" aria-label="Toggle navigation">
      <span class="navbar-toggler-icon"></span>
    </button>

    <!-- Nav items -->
    <div class="collapse navbar-collapse" id="navbarNav">
      <ul class="navbar-nav ms-auto align-items-center">
        <!-- Refresh / Home -->
        <li class="nav-item me-2">
          <a href="dashboard.php" class="nav-link text-muted" title="Refresh / Home">
            <i class="bi bi-house-fill" style="font-size:1.2rem;"></i>
          </a>
        </li>
        <!-- Logout -->
        <li class="nav-item">
          <a href="../autentikasi/logout.php" class="btn btn-outline-danger btn-sm fw-semibold">Keluar</a>
        </li>
      </ul>
    </div>
  </div>
</nav>

<div class="container my-4">

  <!-- Ringkasan Statistik -->
  <div class="row g-3 mb-4">
    <div class="col-6 col-md-3"><div class="card stat-card"><div class="title">Total Absensi</div><div class="value text-primary"><?= number_format($total_absensi) ?></div><div class="small-muted">Data keseluruhan</div></div></div>
    <div class="col-6 col-md-3"><div class="card stat-card"><div class="title">Total Kegiatan</div><div class="value text-success"><?= number_format($total_kegiatan) ?></div><div class="small-muted">Agenda & catatan</div></div></div>
    <div class="col-6 col-md-3"><div class="card stat-card"><div class="title">Total Bimbingan</div><div class="value text-warning"><?= number_format($total_bimbingan) ?></div><div class="small-muted">Catatan bimbingan</div></div></div>
      <div class="col-6 col-md-3"><div class="card stat-card"><div class="title">Total Peserta</div><div class="value text-danger"><?= number_format($total_users) ?></div><div class="small-muted">Akun peserta</div></div></div>
  </div>

  <!-- Statistik Kehadiran: Chart kiri, keterangan kanan -->
<div class="card mb-4 shadow-sm">
  <div class="card-body">
    <h6 class="fw-semibold mb-4">📊 Statistik Kehadiran</h6>

    <div class="row align-items-center">
      <!-- Chart kiri -->
      <div class="col-md-6 d-flex justify-content-center mb-3 mb-md-0">
        <canvas id="chartAbsensi" style="max-width:200px; width:100%; height:auto;"></canvas>
      </div>

      <!-- Keterangan kanan -->
      <div class="col-md-6 d-flex flex-wrap gap-2 justify-content-center justify-content-md-start">
        <!-- Hadir -->
        <div class="p-3 flex-fill" style="min-width:120px; background:#e7f1ff; border-radius:12px; text-align:center;">
          <i class="bi bi-check-circle-fill text-primary fs-4"></i>
          <div class="fw-semibold fs-5"><?= $hadir ?></div>
          <div class="text-muted">Hadir</div>
        </div>
        <!-- Izin -->
        <div class="p-3 flex-fill" style="min-width:120px; background:#fff4e5; border-radius:12px; text-align:center;">
          <i class="bi bi-file-earmark-text-fill text-warning fs-4"></i>
          <div class="fw-semibold fs-5"><?= $izin ?></div>
          <div class="text-muted">Izin</div>
        </div>
        <!-- Sakit -->
        <div class="p-3 flex-fill" style="min-width:120px; background:#e8f9f0; border-radius:12px; text-align:center;">
          <i class="bi bi-hospital-fill text-success fs-4"></i>
          <div class="fw-semibold fs-5"><?= $sakit ?></div>
          <div class="text-muted">Sakit</div>
        </div>
        <!-- Alpa -->
        <div class="p-3 flex-fill" style="min-width:120px; background:#fde8e8; border-radius:12px; text-align:center;">
          <i class="bi bi-x-circle-fill text-danger fs-4"></i>
          <div class="fw-semibold fs-5"><?= $alpa ?></div>
          <div class="text-muted">Alpa/Tanpa Keterangan</div>
        </div>
      </div>
    </div>
  </div>
</div>
    
    <!-- ================= AGENDA & ADMIN TOOLS ================= -->
<div class="row g-3 mb-3">

  <!-- AGENDA MANAGEMENT -->
  <div class="col-md-6">
    <div class="card shadow-sm h-100">
      <div class="card-body d-flex flex-column">

        <h6 class="fw-semibold mb-3">
          <i class="bi bi-megaphone-fill text-primary me-1"></i>
          Agenda Singkat
        </h6>

        <!-- LIST AGENDA -->
        <div class="mb-3" style="max-height:260px; overflow:auto;">
          <?php if (mysqli_num_rows($agendaList) > 0): ?>
            <ul class="list-group list-group-flush">
              <?php while ($ag = mysqli_fetch_assoc($agendaList)): ?>
                <li class="list-group-item d-flex justify-content-between align-items-start">

                  <div class="me-2">
  <div class="fw-medium">
    <?= e($ag['agenda_singkat']) ?>
  </div>

  <small class="text-muted d-block">
    <i class="bi bi-calendar-event me-1"></i>
    <span class="fw-semibold">Mulai:</span>
    <?= date('d M Y', strtotime($ag['tanggal_mulai'])) ?>
    &nbsp;|&nbsp;
    <span class="fw-semibold">Selesai:</span>
    <?= date('d M Y', strtotime($ag['tanggal_selesai'])) ?>
  </small>

  <small class="text-muted d-block">
    <i class="bi bi-clock-history me-1"></i>
    Dibuat:
    <?= date('d M Y H:i', strtotime($ag['created_at'])) ?>
  </small>
</div>


                  <div class="btn-group btn-group-sm">
                    <a href="agenda_edit.php?id=<?= $ag['id'] ?>"
                       class="btn btn-outline-warning"
                       title="Edit Agenda">
                      <i class="bi bi-pencil"></i>
                    </a>
                    <a href="agenda_delete.php?id=<?= $ag['id'] ?>"
                       class="btn btn-outline-danger"
                       onclick="return confirm('Hapus agenda ini?')"
                       title="Hapus Agenda">
                      <i class="bi bi-trash"></i>
                    </a>
                  </div>

                </li>
              <?php endwhile; ?>
            </ul>
          <?php else: ?>
            <div class="text-muted fst-italic text-center">
              Belum ada agenda
            </div>
          <?php endif; ?>
        </div>

       <!-- TAMBAH AGENDA -->
<form method="POST" action="../admin/prosses_update_agenda.php" class="mt-auto">

  <label class="form-label fw-semibold mb-1">
    Tambah Agenda Baru
  </label>

  <input
    type="text"
    name="agenda_singkat"
    class="form-control mb-3"
    placeholder="Tulis agenda singkat..."
    required>

  <div class="row g-2">
    <div class="col-6">
      <label class="form-label small text-muted fw-semibold">
        Tanggal Mulai
      </label>
      <input
        type="date"
        name="tanggal_mulai"
        class="form-control"
        required>
    </div>

    <div class="col-6">
      <label class="form-label small text-muted fw-semibold">
        Tanggal Selesai
      </label>
      <input
        type="date"
        name="tanggal_selesai"
        class="form-control"
        required>
    </div>
  </div>

  <button class="btn btn-primary btn-sm w-100 mt-3">
    <i class="bi bi-plus-circle me-1"></i>
    Simpan Agenda
  </button>

  <small class="text-muted d-block mt-1">
    Agenda akan langsung tampil di dashboard peserta.
  </small>

</form>


      </div>
    </div>
  </div>

  <!-- ADMIN TOOLS -->
<div class="col-md-6">
  <div class="card shadow-sm h-100">

    <div class="card-body">

      <!-- HEADER -->
      <div class="mb-3">
        <h5 class="fw-bold text-danger mb-1">
          <i class="bi bi-shield-exclamation me-1"></i>
          Admin Tools
        </h5>
        <small class="text-muted">
          Tindakan di bawah bersifat permanen
        </small>
      </div>

      <!-- RESET ACTIONS -->
      <div class="d-grid gap-2 mb-4"
           style="grid-template-columns: repeat(auto-fit, minmax(140px, 1fr));">

        <button class="btn btn-outline-danger btn-sm"
                data-bs-toggle="modal" data-bs-target="#resetAbsensi">
          <i class="bi bi-arrow-counterclockwise me-1"></i>
          Reset Absensi
        </button>

        <button class="btn btn-outline-danger btn-sm"
                data-bs-toggle="modal" data-bs-target="#resetKegiatan">
          <i class="bi bi-arrow-counterclockwise me-1"></i>
          Reset Kegiatan
        </button>

        <button class="btn btn-outline-danger btn-sm"
                data-bs-toggle="modal" data-bs-target="#resetBimbingan">
          <i class="bi bi-arrow-counterclockwise me-1"></i>
          Reset Bimbingan
        </button>

        <button class="btn btn-outline-danger btn-sm"
                data-bs-toggle="modal" data-bs-target="#resetUser">
          <i class="bi bi-person-x me-1"></i>
          Reset Akun
        </button>
      </div>

      <!-- PEMBATAS -->
      <div class="text-center text-muted mb-3">
        <small>Quick Access</small>
      </div>
		<hr>
      <!-- KELOLA PEMBELAJARAN -->
      <div class="p-4 rounded d-flex align-items-center justify-content-between flex-wrap gap-3"
           style="
             background: linear-gradient(135deg, #0d6efd, #3d8bfd);
             color: #fff;
           ">

        <div>
          <div class="fw-bold fs-5">
            <i class="bi bi-journal-bookmark-fill me-1"></i>
            Kelola Modul Pembelajaran
          </div>
          <div class="small opacity-75">
            Materi • Video • Modul
          </div>
        </div>

        <a href="../admin/pembelajaran.php"
           class="btn btn-light btn-sm fw-semibold shadow">
          <i class="bi bi-arrow-right-circle me-1"></i>
          Buka Halaman
        </a>

      </div>

    </div>
  </div>
</div>

</div>

  <!-- Filter -->
    <?php if (in_array($page, ['absensi', 'kegiatan', 'bimbingan'])): ?>
      <form class="row g-2 mb-3" method="GET">
        <input type="hidden" name="page" value="<?= e($page) ?>">
        <div class="col-md-3 col-6"><input type="text" class="form-control" name="filter_nama" placeholder="Cari nama..." value="<?= e($filter_nama) ?>"></div>
        <div class="col-md-2 col-6"><select name="sort" class="form-select">
            <option value="nama" <?= $sort_column == 'nama' ? 'selected' : '' ?>>Nama</option>
            <option value="tanggal" <?= $sort_column == 'tanggal' ? 'selected' : '' ?>>Tanggal</option>
          </select></div>
        <div class="col-md-2 col-6"><select name="order" class="form-select">
            <option value="ASC" <?= $sort_order == 'ASC' ? 'selected' : '' ?>>ASC</option>
            <option value="DESC" <?= $sort_order == 'DESC' ? 'selected' : '' ?>>DESC</option>
          </select></div>
        <div class="col-md-2 col-6 d-grid"><button class="btn btn-primary">Terapkan</button></div>
      </form>

      <div class="mb-3 d-flex flex-wrap gap-2">
        <a href="don.php?page=<?= urlencode($page) ?>&sort=<?= urlencode($sort_column) ?>&order=<?= urlencode($sort_order) ?>&filter_nama=<?= urlencode($filter_nama) ?>&start_date=<?= urlencode($start_date) ?>&end_date=<?= urlencode($end_date) ?>" class="btn btn-success btn-sm"><i class="bi bi-download"></i> Download Rekapan</a>
        <a href="dashboard.php?page=<?= $page ?>&start_date=<?= date('Y-m-d') ?>&end_date=<?= date('Y-m-d') ?>" class="btn btn-outline-secondary btn-sm">Hari Ini</a>
        <a href="dashboard.php?page=<?= $page ?>&start_date=<?= date('Y-m-d', strtotime('-7 days')) ?>&end_date=<?= date('Y-m-d') ?>" class="btn btn-outline-secondary btn-sm">7 Hari</a>
        <a href="dashboard.php?page" class="btn btn-outline-secondary btn-sm">Semua</a>
      </div>
    <?php endif; ?>

    <?php if (!empty($_SESSION['import_success'])): ?>
      <div class="alert alert-success alert-dismissible fade show">
        <i class="bi bi-check-circle me-1"></i>
        <?= $_SESSION['import_success'] ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
      </div>
    <?php unset($_SESSION['import_success']);
    endif; ?>

    <?php if (!empty($_SESSION['import_error'])): ?>
      <div class="alert alert-danger alert-dismissible fade show">
        <i class="bi bi-exclamation-triangle me-1"></i>
        <?= $_SESSION['import_error'] ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
      </div>
    <?php unset($_SESSION['import_error']);
    endif; ?>

    <!-- TABEL DATA DENGAN TAB NAV -->
<div class="card shadow-sm mb-5">
  <div class="card-header">

    <!-- TAB NAV -->
    <ul class="nav nav-tabs card-header-tabs">
      <li class="nav-item">
        <a class="nav-link <?= $page == 'absensi' ? 'active' : '' ?>" href="?page=absensi">Absensi</a>
      </li>
      <li class="nav-item">
        <a class="nav-link <?= $page == 'kegiatan' ? 'active' : '' ?>" href="?page=kegiatan">Kegiatan</a>
      </li>
      <li class="nav-item">
        <a class="nav-link <?= $page == 'bimbingan' ? 'active' : '' ?>" href="?page=bimbingan">Bimbingan</a>
      </li>
      <li class="nav-item">
        <a class="nav-link <?= $page == 'user' ? 'active' : '' ?>" href="?page=user">Akun</a>
      </li>
    </ul>

    <!-- INFO + IMPORT (KHUSUS USER) -->
    <div class="d-flex justify-content-between align-items-center mt-3">
      <div class="small text-muted">
        Total: <?= mysqli_num_rows($data) ?>
      </div>

      <?php if ($page == 'user'): ?>
        <button class="btn btn-success btn-sm"
          data-bs-toggle="modal"
          data-bs-target="#importUserModal">
          Import User (CSV)
        </button>
      <?php endif; ?>
    </div>
  </div>

  <!-- TABLE -->
  <div class="card-body table-responsive p-0">
    <table class="table table-bordered table-striped align-middle mb-0">
      <thead class="table-light">
        <tr>
          <?php
          if ($page == 'absensi')
            echo "<th>Nama</th>
                  <th>Kelas</th>
                  <th>Tanggal</th>
                  <th>Masuk</th>
                  <th>Istirahat1</th>
                  <th>Istirahat2</th>
                  <th>Pulang</th>
                  <th>Status</th>
                  <th>Keterangan</th>
                  <th>Surat</th>
                  <th>Diperbarui</th>
                  <th>Aksi</th>";
          elseif ($page == 'kegiatan')
            echo "<th>Nama</th><th>Kelas</th><th>Tanggal</th><th>Deskripsi</th><th>Catatan</th><th>Foto</th><th>Aksi</th>";
          elseif ($page == 'bimbingan')
            echo "<th>Nama</th><th>Kelas</th><th>Tanggal</th><th>Uraian</th><th>Foto</th><th>Aksi</th>";
          elseif ($page == 'user')
            echo "<th>Nama</th><th>Username</th><th>Role</th><th>Aksi</th>";
          ?>
        </tr>
      </thead>

      <tbody>
        <?php if (mysqli_num_rows($data) > 0): while ($row = mysqli_fetch_assoc($data)): ?>
          <tr>
            <?php if ($page == 'absensi'): ?>
              <td><?= e($row['nama']) ?></td>
              <td><?= e($row['kelas']) ?></td>
              <td><?= e($row['tanggal']) ?></td>
              <td><?= e($row['jam_masuk'] ?: '-') ?></td>
              <td><?= e($row['jam_istirahat1'] ?: '-') ?></td>
              <td><?= e($row['jam_istirahat2'] ?: '-') ?></td>
              <td><?= e($row['jam_pulang'] ?: '-') ?></td>
              <td><?= e($row['status'] ?: '-') ?></td>
              <td><?= e($row['keterangan'] ?: '-') ?></td>
              <td>
                <?php if(!empty($row['surat_keterangan'])): ?>
                  <a href="../uploads/surat/<?= e($row['surat_keterangan']) ?>" target="_blank" class="btn btn-sm btn-outline-primary">
                    <i class="bi bi-file-earmark-text"></i> Lihat
                  </a>
                <?php else: ?>
                  <span class="text-muted fst-italic">-</span>
                <?php endif; ?>
              </td>
              <td><?= e($row['updated_at'] ?? '-') ?></td>
              <td>
                <a href="absen_edit.php?id=<?= urlencode($row['id']) ?>" class="btn btn-warning btn-sm">Edit</a>
                <a href="absen_delete.php?id=<?= urlencode($row['id']) ?>" class="btn btn-danger btn-sm">Hapus</a>
              </td>
            <?php elseif ($page == 'kegiatan'): ?>
              <td><?= e($row['nama']) ?></td>
              <td><?= e($row['kelas']) ?></td>
              <td><?= e($row['tanggal']) ?></td>
              <td><?= e($row['deskripsi_kegiatan']) ?></td>
              <td><?= e($row['catatan']) ?></td>
              <td>
                <?php if(!empty($row['foto'])): ?>
                  <a href="../uploads/kegiatan/<?= e($row['foto']) ?>" target="_blank" class="btn btn-sm btn-outline-primary">Lihat</a>
                <?php else: ?>
                  <span class="text-muted fst-italic">-</span>
                <?php endif; ?>
              </td>
              <td>
                <a href="kegiatan_edit.php?id=<?= urlencode($row['id']) ?>" class="btn btn-warning btn-sm">Edit</a>
                <a href="kegiatan_delete.php?id=<?= urlencode($row['id']) ?>" class="btn btn-danger btn-sm">Hapus</a>
              </td>
            <?php elseif ($page == 'bimbingan'): ?>
              <td><?= e($row['nama']) ?></td>
              <td><?= e($row['kelas']) ?></td>
              <td><?= e($row['tanggal']) ?></td>
              <td><?= e($row['uraian']) ?></td>
              <td>
                <?php if(!empty($row['foto'])): ?>
                  <a href="../uploads/bimbingan/<?= e($row['foto']) ?>" target="_blank" class="btn btn-sm btn-outline-primary">Lihat</a>
                <?php else: ?>
                  <span class="text-muted fst-italic">-</span>
                <?php endif; ?>
              </td>
              <td>
                <a href="bimbingan_edit.php?id=<?= urlencode($row['id']) ?>" class="btn btn-warning btn-sm">Edit</a>
                <a href="bimbingan_delete.php?id=<?= urlencode($row['id']) ?>" class="btn btn-danger btn-sm">Hapus</a>
              </td>
            <?php elseif ($page == 'user'): ?>
              <td>
                <a href="#" class="fw-semibold text-primary text-decoration-none"
                   data-bs-toggle="modal"
                   data-bs-target="#userModal<?= $row['id'] ?>">
                   <?= e($row['nama']) ?>
                </a>
              </td>
              <td><?= e($row['username']) ?></td>
              <td><?= e($row['role']) ?></td>
              <td>
                <a href="user_edit.php?id=<?= urlencode($row['id']) ?>" class="btn btn-warning btn-sm">Edit</a>
                <a href="user_delete.php?id=<?= urlencode($row['id']) ?>" class="btn btn-danger btn-sm">Hapus</a>
              </td>
            <?php endif; ?>
          </tr>

                <!-- MODAL DETAIL USER -->
                <?php if ($page == 'user'): ?>
                  <div class="modal fade" id="userModal<?= $row['id'] ?>" tabindex="-1">
                    <div class="modal-dialog modal-dialog-centered modal-lg">
                      <div class="modal-content">
                        <div class="modal-header">
                          <h5 class="modal-title">Detail Akun</h5>
                          <button class="btn-close" data-bs-dismiss="modal"></button>
                        </div>

                        <div class="modal-body">
                          <div class="row g-3">

                            <div class="col-md-4 text-center">
                              <img src="<?= $row['avatar'] ? '../avatars/' . e($row['avatar']) : '../imge/96.png' ?>"
                                class="rounded-circle img-thumbnail mb-2"
                                style="width:120px;height:120px;object-fit:cover;">
                              <div class="small text-muted">
                                Last Active:<br>
                                <?= $row['last_active'] ? date('d/m/Y H:i', strtotime($row['last_active'])) : '-' ?>
                              </div>
                            </div>

                            <div class="col-md-8">
                              <div class="mb-2">
                                <label class="small text-muted">Nama</label>
                                <div class="form-control bg-light"><?= e($row['nama']) ?></div>
                              </div>
                              <div class="mb-2">
                                <label class="small text-muted">Username</label>
                                <div class="form-control bg-light"><?= e($row['username']) ?></div>
                              </div>
                              <div class="mb-2">
                                <label class="small text-muted">Role</label>
                                <div class="form-control bg-light"><?= e($row['role']) ?></div>
                              </div>
                              <div class="mb-2">
  <label class="small text-muted">Kelas</label>
  <?= fieldView($row['kelas'] ?? null) ?>
</div>

                            </div>

                            <div class="col-md-12">
  <label class="small text-muted">Alamat</label>
  <?= fieldView($row['alamat'] ?? null, 60) ?>
</div>


                            <div class="col-md-12">
  <label class="small text-muted">Bio</label>
  <?= fieldView($row['bio'] ?? null, 80) ?>
</div>


                            <div class="col-md-12">
                              <div class="alert alert-warning py-2 mb-0">
                                Demi keamanan, password tidak ditampilkan.
                              </div>
                            </div>

                          </div>
                        </div>

                        <div class="modal-footer">
                          <a href="user_reset_password.php?id=<?= urlencode($row['id']) ?>"
                            class="btn btn-outline-danger btn-sm">
                            Reset Password
                          </a>
                          <button class="btn btn-secondary btn-sm" data-bs-dismiss="modal">Tutup</button>
                        </div>
                      </div>
                    </div>
                  </div>
                <?php endif; ?>

              <?php endwhile;
            else: ?>
              <tr>
                <td colspan="12" class="text-center text-muted py-3">
                  Data Kosong / Tidak ada data ditemukan
                </td>
              </tr>
            <?php endif; ?>
          </tbody>
        </table>
      </div>
    </div>
    <!-- ================= MODAL RESET ================= -->
    <?php foreach (['Absensi', 'Kegiatan', 'Bimbingan', 'User'] as $x): ?>
      <div class="modal fade" id="reset<?= $x ?>">
        <div class="modal-dialog modal-dialog-centered">
          <div class="modal-content">
            <div class="modal-header">
              <h6>Reset <?= $x ?></h6>
            </div>
            <div class="modal-body">Data <?= strtolower($x) ?> akan dihapus permanen.</div>
            <div class="modal-footer">
              <button class="btn btn-secondary btn-sm" data-bs-dismiss="modal">Batal</button>
              <a href="reset.php?tipe=<?= strtolower($x) ?>" class="btn btn-danger btn-sm">Reset</a>
            </div>
          </div>
        </div>
      </div>
    <?php endforeach; ?>

    <!-- MODAL IMPORT USER -->
    <?php if ($page == 'user'): ?>
      <div class="modal fade" id="importUserModal" tabindex="-1">
        <div class="modal-dialog modal-dialog-centered">
          <form action="user_import.php" method="POST" enctype="multipart/form-data" class="modal-content">

            <div class="modal-header">
              <h5 class="modal-title">Import Data User</h5>
              <button class="btn-close" data-bs-dismiss="modal"></button>
            </div>

            <div class="modal-body">
              <div class="mb-3">
                <label class="form-label fw-semibold">CSV</label>
                <input type="file" name="file" class="form-control"
                  accept=".csv,.xls,.xlsx" required>
              </div>

              <div class="alert alert-info small mb-0">
                Kolom wajib:<br>
                <b>nama, username, password, role, kelas, alamat, bio</b>
              </div>
            </div>

            <div class="modal-footer">
              <button class="btn btn-primary btn-sm">Import</button>
              <button class="btn btn-secondary btn-sm" data-bs-dismiss="modal">Batal</button>
            </div>

          </form>
        </div>
      </div>
    <?php endif; ?>

<footer class="text-center small py-3 text-muted">
  &copy; <?= date('Y') ?> ABD (Absensi Berbasis Digital). Semua hak dilindungi.
</footer>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
const ctx = document.getElementById('chartAbsensi');
if(ctx){
  new Chart(ctx, {
    type: 'doughnut',
    data: {
      labels: ['Hadir','Izin','Sakit','Alpa'],
      datasets: [{
        data: [<?= $hadir ?>, <?= $izin ?>, <?= $sakit ?>, <?= $alpa ?>],
        backgroundColor: ['#0d6efd','#ffc107','#198754','#dc3545'],
        borderWidth: 1
      }]
    },
    options: {
      responsive: true,
      maintainAspectRatio: false, // ikuti ukuran canvas CSS
      cutout: '65%',              // lingkaran tidak terlalu tebal
      plugins: { legend: { display: false } }
    }
  });
}
</script>
</body>
</html>
