<?php
session_start();
include '../config/koneksi.php';

if(!in_array($_SESSION['role'],['admin','guru'])){
    header("Location: dashboard.php");
    exit;
}

$id = (int)($_GET['id'] ?? 0);
$bimbingan = mysqli_fetch_assoc(mysqli_query($koneksi,"SELECT * FROM bimbingan WHERE id=$id"));
if(!$bimbingan){
    $_SESSION['error'] = "Bimbingan tidak ditemukan.";
    header("Location: dashboard.php?page=bimbingan");
    exit;
}

if($_SERVER['REQUEST_METHOD']=='POST'){
    $uraian = mysqli_real_escape_string($koneksi,$_POST['uraian']);
    $update = mysqli_query($koneksi,"UPDATE bimbingan SET uraian='$uraian' WHERE id=$id");

    if($update){
        $_SESSION['success'] = "Bimbingan berhasil diperbarui.";
    } else {
        $_SESSION['error'] = "Terjadi kesalahan saat memperbarui bimbingan.";
    }

    header("Location: dashboard.php?page=bimbingan");
    exit;
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="UTF-8">
<title>Edit Bimbingan</title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
<style>
body{font-family:'Poppins',sans-serif;background:#f5f5f5;}
.card{max-width:600px;margin:50px auto;border-radius:12px;}
</style>
</head>
<body>
<div class="card shadow-sm">
  <div class="card-header bg-white fw-bold">Edit Bimbingan</div>
  <div class="card-body">
    <form method="POST">
      <div class="mb-3">
        <label>Uraian</label>
        <textarea name="uraian" class="form-control" required><?= htmlspecialchars($bimbingan['uraian']) ?></textarea>
      </div>
      <button class="btn btn-primary w-100">Simpan</button>
      <a href="dashboard.php?page=bimbingan" class="btn btn-secondary w-100 mt-2">Batal</a>
    </form>
  </div>
</div>
</body>
</html>
