<?php
session_start();
include '../config/koneksi.php';

if(!in_array($_SESSION['role'],['admin','guru'])){
    header("Location: dashboard.php");
    exit;
}

$id = (int)($_GET['id'] ?? 0);
$kegiatan = mysqli_fetch_assoc(mysqli_query($koneksi,"SELECT * FROM kegiatan WHERE id=$id"));
if(!$kegiatan){
    $_SESSION['error'] = "Kegiatan tidak ditemukan.";
    header("Location: dashboard.php?page=kegiatan");
    exit;
}

if($_SERVER['REQUEST_METHOD']=='POST'){
    $deskripsi = mysqli_real_escape_string($koneksi,$_POST['deskripsi_kegiatan']);
    $catatan   = mysqli_real_escape_string($koneksi,$_POST['catatan']);

    $update = mysqli_query($koneksi,"UPDATE kegiatan SET deskripsi_kegiatan='$deskripsi', catatan='$catatan' WHERE id=$id");
    if($update){
        $_SESSION['success'] = "Kegiatan berhasil diperbarui.";
    }else{
        $_SESSION['error'] = "Terjadi kesalahan saat memperbarui kegiatan.";
    }

    header("Location: dashboard.php?page=kegiatan");
    exit;
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="UTF-8">
<title>Edit Kegiatan</title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
<style>
body{font-family:'Poppins',sans-serif;background:#f5f5f5;}
.card{max-width:600px;margin:50px auto;border-radius:12px;}
</style>
</head>
<body>
<div class="card shadow-sm">
  <div class="card-header bg-white fw-bold">Edit Kegiatan</div>
  <div class="card-body">
    <form method="POST">
      <div class="mb-3">
        <label>Deskripsi Kegiatan</label>
        <textarea name="deskripsi_kegiatan" class="form-control" required><?= htmlspecialchars($kegiatan['deskripsi_kegiatan']) ?></textarea>
      </div>
      <div class="mb-3">
        <label>Catatan</label>
        <textarea name="catatan" class="form-control"><?= htmlspecialchars($kegiatan['catatan']) ?></textarea>
      </div>
      <button class="btn btn-primary w-100">Simpan</button>
      <a href="dashboard.php?page=kegiatan" class="btn btn-secondary w-100 mt-2">Batal</a>
    </form>
  </div>
</div>
</body>
</html>
