<?php
session_start();
include '../config/koneksi.php';

if(!in_array($_SESSION['role'],['admin','guru'])){
    header("Location: dashboard.php");
    exit;
}

$id = (int)($_GET['id'] ?? 0);

if($id > 0){
    $delete = mysqli_query($koneksi,"DELETE FROM bimbingan WHERE id=$id");
    if($delete){
        $_SESSION['success'] = "Bimbingan berhasil dihapus.";
    } else {
        $_SESSION['error'] = "Terjadi kesalahan saat menghapus bimbingan.";
    }
} else {
    $_SESSION['error'] = "ID bimbingan tidak valid.";
}

header("Location: dashboard.php?page=bimbingan");
exit;
