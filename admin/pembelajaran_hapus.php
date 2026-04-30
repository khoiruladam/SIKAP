<?php
session_start();
include '../config/koneksi.php';
if ($_SESSION['role'] !== 'admin') exit;

$id = intval($_POST['id']);
$data = mysqli_fetch_assoc(
mysqli_query($koneksi,"SELECT foto,file_materi FROM pembelajaran WHERE id=$id")
);

@unlink('../uploads/pembelajaran/'.$data['foto']);
@unlink('../uploads/pembelajaran/materi/'.$data['file_materi']);

mysqli_query($koneksi,"DELETE FROM pembelajaran WHERE id=$id");

header("Location: pembelajaran.php?success=Materi berhasil dihapus");
exit;

