<?php
session_start();
include '../config/koneksi.php';

// Aktifkan error reporting sementara untuk debugging
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Pastikan hanya admin/guru
if (!isset($_SESSION['role']) || !in_array($_SESSION['role'], ['admin', 'guru'])) {
    header("Location: ../index.php");
    exit;
}

$id = $_GET['id'] ?? '';
if (!$id) {
    $_SESSION['error'] = "ID absensi tidak ditemukan.";
    header("Location: dashboard.php?page=absensi");
    exit;
}

// Ambil data absensi + nama user
$sql = "
    SELECT a.*, u.nama 
    FROM absensi a
    LEFT JOIN users u ON a.user_id = u.id
    WHERE a.id = '$id'
";
$query = $koneksi->query($sql);

if (!$query || $query->num_rows == 0) {
    $_SESSION['error'] = "Data absensi tidak ditemukan.";
    header("Location: dashboard.php?page=absensi");
    exit;
}

$data = $query->fetch_assoc();

// Jika tombol hapus ditekan
if (isset($_POST['hapus'])) {
    $delete = $koneksi->query("DELETE FROM absensi WHERE id='$id'");
    if ($delete) {
        $_SESSION['success'] = "Data absensi berhasil dihapus.";
    } else {
        $_SESSION['error'] = "Gagal menghapus data absensi.";
    }
    header("Location: dashboard.php?page=absensi");
    exit;
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>Hapus Absensi</title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="bg-light">

<div class="container py-5">
  <div class="card shadow-sm border-0 rounded-4 mx-auto" style="max-width:600px;">
    <div class="card-header bg-danger text-white border-0 py-3">
      <h5 class="mb-0 fw-semibold">Konfirmasi Hapus Absensi</h5>
    </div>
    <div class="card-body text-center">
      <p>Apakah Anda yakin ingin menghapus data absensi berikut?</p>
      <table class="table table-bordered text-start">
        <tr><th>Nama</th><td><?= htmlspecialchars($data['nama'] ?? 'Tidak diketahui'); ?></td></tr>
        <tr><th>Tanggal</th><td><?= htmlspecialchars($data['tanggal'] ?? '-'); ?></td></tr>
        <tr><th>Keterangan</th><td><?= htmlspecialchars($data['keterangan'] ?? '-'); ?></td></tr>
      </table>
      <form method="POST" class="mt-4">
        <a href="dashboard.php?page=absensi" class="btn btn-secondary px-4">Batal</a>
        <button type="submit" name="hapus" class="btn btn-danger px-4">Ya, Hapus</button>
      </form>
    </div>
  </div>
</div>

</body>
</html>
