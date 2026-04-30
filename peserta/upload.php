<?php
session_start();
include '../config/koneksi.php';
if ($_SESSION['role'] != 'peserta') {
    header("Location: ../index.php");
    exit;
}

$user_id = $_SESSION['id'];
$tanggal = date("Y-m-d");

if (isset($_POST['upload'])) {
    $target_dir = "../uploads/";
    $file_name = time() . "_" . basename($_FILES["foto"]["name"]);
    $target_file = $target_dir . $file_name;

    if (move_uploaded_file($_FILES["foto"]["tmp_name"], $target_file)) {
        mysqli_query($koneksi, "INSERT INTO kegiatan (user_id, tanggal, foto) VALUES ('$user_id', '$tanggal', '$file_name')");
        $msg = "Foto berhasil diupload!";
    } else {
        $msg = "Upload gagal.";
    }
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
  <meta charset="UTF-8">
  <title>Upload Foto</title>
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="bg-light">
<div class="container mt-4">
  <h4>Upload Foto Kegiatan</h4>
  <?php if (isset($msg)) echo "<div class='alert alert-info'>$msg</div>"; ?>
  <form method="POST" enctype="multipart/form-data">
    <input type="file" name="foto" class="form-control mb-3" required>
    <button name="upload" class="btn btn-warning w-100">Upload</button>
  </form>
  <a href="dashboard.php" class="btn btn-secondary w-100 mt-3">Kembali</a>
</div>
</body>
</html>
