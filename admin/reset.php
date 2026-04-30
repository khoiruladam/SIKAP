<?php
session_start();
include '../config/koneksi.php';

if (!isset($_SESSION['role']) || !in_array($_SESSION['role'], ['admin', 'guru'])) {
    header("Location: ../index.php");
    exit;
}

$tipe = $_GET['tipe'] ?? '';

switch ($tipe) {
    case 'absensi':
        mysqli_query($koneksi, "TRUNCATE TABLE absensi");
        break;
    case 'kegiatan':
        mysqli_query($koneksi, "TRUNCATE TABLE kegiatan");
        break;
    case 'bimbingan':
        mysqli_query($koneksi, "TRUNCATE TABLE bimbingan");
        break;
    case 'user':
        mysqli_query($koneksi, "DELETE FROM users WHERE role='peserta'");
        break;
}

header("Location: dashboard.php");
exit;
