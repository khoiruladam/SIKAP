<?php
session_start();
include "../config/koneksi.php"; // koneksi ke database

if (!isset($_SESSION['id'])) {
    header("Location: ../index.php");
    exit;
}

$id = $_SESSION['id'];

// Ambil username baru dari form
$username_baru = trim($_POST['username_baru']);

// Cek apakah username baru sudah dipakai
$cek = mysqli_query($koneksi, "SELECT * FROM users WHERE username='$username_baru' AND id != '$id'");
if (mysqli_num_rows($cek) > 0) {
    $_SESSION['alert'] = [
        'icon' => 'error',
        'title' => 'Gagal',
        'text'  => 'Username sudah digunakan!'
    ];
    header("Location: ../peserta/dashboard.php");
    exit;
}

// Update username
$query = mysqli_query($koneksi, "UPDATE users SET username='$username_baru' WHERE id='$id'");
if ($query) {
    $_SESSION['alert'] = [
        'icon' => 'success',
        'title' => 'Berhasil',
        'text'  => 'Username berhasil diganti. Silakan login ulang.'
    ];
} else {
    $_SESSION['alert'] = [
        'icon' => 'error',
        'title' => 'Gagal',
        'text'  => 'Terjadi kesalahan saat mengganti username.'
    ];
}

// Kembali ke dashboard
header("Location: ../peserta/dashboard.php");
exit;
