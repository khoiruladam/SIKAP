<?php
session_start();
include '../config/koneksi.php';

if(!in_array($_SESSION['role'],['admin','guru'])){
    header("Location: dashboard.php");
    exit;
}

$id = (int)($_GET['id'] ?? 0);

if($id > 0){
    $delete = mysqli_query($koneksi,"DELETE FROM kegiatan WHERE id=$id");
    if($delete){
        $_SESSION['success'] = "Kegiatan berhasil dihapus.";
    } else {
        $_SESSION['error'] = "Terjadi kesalahan saat menghapus kegiatan.";
    }
} else {
    $_SESSION['error'] = "ID kegiatan tidak valid.";
}

header("Location: dashboard.php?page=kegiatan");
exit;
