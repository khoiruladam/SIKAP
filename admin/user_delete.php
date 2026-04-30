<?php
session_start();
include __DIR__ . '/../config/koneksi.php';

// Hanya admin
if (!isset($_SESSION['role']) || $_SESSION['role'] != 'admin') {
    header("Location: ../index.php");
    exit;
}

// Ambil ID user
$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

if ($id > 0) {
    // Mulai transaksi untuk keamanan
    mysqli_begin_transaction($koneksi);

    try {
        // Hapus data absensi terkait
        mysqli_query($koneksi, "DELETE FROM absensi WHERE user_id = $id");
        // Hapus data kegiatan terkait
        mysqli_query($koneksi, "DELETE FROM kegiatan WHERE user_id = $id");
        // Hapus data bimbingan terkait
        mysqli_query($koneksi, "DELETE FROM bimbingan WHERE user_id = $id");
        // Hapus user
        $stmt = mysqli_prepare($koneksi, "DELETE FROM users WHERE id = ?");
        mysqli_stmt_bind_param($stmt, "i", $id);
        mysqli_stmt_execute($stmt);
        mysqli_stmt_close($stmt);

        // Commit transaksi
        mysqli_commit($koneksi);

        $_SESSION['success'] = "User beserta semua data terkait berhasil dihapus.";

    } catch (Exception $e) {
        mysqli_rollback($koneksi);
        $_SESSION['error'] = "Terjadi kesalahan: " . $e->getMessage();
    }
} else {
    $_SESSION['error'] = "ID user tidak valid.";
}

// Redirect kembali ke dashboard user
header("Location: dashboard.php?page=user");
exit;
