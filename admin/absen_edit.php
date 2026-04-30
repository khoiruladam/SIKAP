<?php
session_start();
include '../config/koneksi.php';

// Pastikan hanya admin/guru
if (!isset($_SESSION['role']) || !in_array($_SESSION['role'], ['admin','guru'])) {
    header("Location: ../index.php");
    exit;
}

$id = $_GET['id'] ?? '';
if (!$id) {
    $_SESSION['error'] = "ID absensi tidak ditemukan.";
    header("Location: dashboard.php?page=absensi");
    exit;
}

$query = $koneksi->query("SELECT * FROM absensi WHERE id='$id'");
if (!$query || $query->num_rows == 0) {
    $_SESSION['error'] = "Data absensi tidak ditemukan.";
    header("Location: dashboard.php?page=absensi");
    exit;
}

$data = $query->fetch_assoc();

// Proses update
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $status = $_POST['status'];
    $keterangan = $_POST['keterangan'];
    
    $update = $koneksi->query("UPDATE absensi SET status='$status', keterangan='$keterangan' WHERE id='$id'");
    
    if ($update) {
        $_SESSION['success'] = "Data absensi berhasil diperbarui.";
    } else {
        $_SESSION['error'] = "Gagal memperbarui data absensi.";
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
<title>Edit Absensi</title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="bg-light">

<div class="container py-5">
  <div class="card shadow-sm border-0 rounded-4 mx-auto" style="max-width:600px;">
    <div class="card-header bg-white border-0 py-3">
      <h5 class="mb-0 fw-semibold">Edit Data Absensi</h5>
    </div>
    <div class="card-body">
      <form method="POST">
        <div class="mb-3">
          <label class="form-label">Status</label>
          <select name="status" class="form-select" required>
            <option value="Hadir" <?= ($data['status']=='Hadir'?'selected':'') ?>>Hadir</option>
            <option value="Izin" <?= ($data['status']=='Izin'?'selected':'') ?>>Izin</option>
            <option value="Sakit" <?= ($data['status']=='Sakit'?'selected':'') ?>>Sakit</option>
            <option value="Alpa" <?= ($data['status']=='Alpa'?'selected':'') ?>>Alpa</option>
          </select>
        </div>
        <div class="mb-3">
          <label class="form-label">Keterangan</label>
          <textarea name="keterangan" class="form-control" rows="3"><?= htmlspecialchars($data['keterangan']) ?></textarea>
        </div>
        <div class="d-flex justify-content-between">
          <a href="dashboard.php?page=absensi" class="btn btn-secondary">Batal</a>
          <button type="submit" class="btn btn-primary">Simpan Perubahan</button>
        </div>
      </form>
    </div>
  </div>
</div>

</body>
</html>
