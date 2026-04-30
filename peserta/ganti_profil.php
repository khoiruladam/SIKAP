<?php
session_start();
include '../config/koneksi.php'; // sesuaikan path ke koneksi.php

// Pastikan user sudah login
if (!isset($_SESSION['id'])) {
    header("Location: ../index.php");
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $id     = $_SESSION['id'];
    $bio    = trim($_POST['bio'] ?? '');
    $kelas  = trim($_POST['kelas'] ?? '');
    $alamat = trim($_POST['alamat'] ?? '');
    $nis    = trim($_POST['nis'] ?? '');

    // Validasi sederhana
    if ($nis === '' || !preg_match('/^[0-9]+$/', $nis)) {
        $_SESSION['error'] = "NIS harus berupa angka dan tidak boleh kosong.";
        header("Location: dashboard.php");
        exit;
    }

    // Query update data user
    $stmt = $koneksi->prepare("
        UPDATE users 
        SET bio = ?, kelas = ?, alamat = ?, nis = ?
        WHERE id = ?
    ");
    $stmt->bind_param("ssssi", $bio, $kelas, $alamat, $nis, $id);

    if ($stmt->execute()) {
        $_SESSION['success'] = "Profil berhasil diperbarui!";
    } else {
        $_SESSION['error'] = "Terjadi kesalahan saat memperbarui profil.";
    }

    $stmt->close();
    header("Location: dashboard.php");
    exit;

} else {
    // Jika bukan request POST
    header("HTTP/1.1 405 Method Not Allowed");
    echo "Metode tidak diizinkan.";
    exit;
}
?>
