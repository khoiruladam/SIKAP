<?php
session_start();
include '../config/koneksi.php';
if ($_SESSION['role'] !== 'admin') exit;

$id = intval($_POST['id']);
$judul = mysqli_real_escape_string($koneksi,$_POST['judul']);
$mapel = mysqli_real_escape_string($koneksi,$_POST['mapel']);
$guru  = mysqli_real_escape_string($koneksi,$_POST['guru_nama']);
$video = mysqli_real_escape_string($koneksi,$_POST['video_url']);
$ket   = mysqli_real_escape_string($koneksi,$_POST['keterangan']);

mysqli_query($koneksi,"
UPDATE pembelajaran SET
judul='$judul',mapel='$mapel',guru_nama='$guru',
video_url='$video',keterangan='$ket'
WHERE id=$id");

header("Location: pembelajaran.php?success=Materi berhasil diperbarui");
exit;


