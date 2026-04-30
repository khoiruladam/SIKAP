<?php
session_start();
include '../config/koneksi.php';

// Pastikan user login
if (!isset($_SESSION['id'])) {
    header("Location: ../index.php");
    exit;
}

$user_id = $_SESSION['id'];
$upload_dir = "../avatars/";

// Ambil nama avatar sekarang
$stmt = $koneksi->prepare("SELECT avatar FROM users WHERE id=?");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result()->fetch_assoc();
$stmt->close();

// Hapus file avatar lama jika bukan default
if (!empty($result['avatar']) && $result['avatar'] != '96.png' && file_exists($upload_dir . $result['avatar'])) {
    unlink($upload_dir . $result['avatar']);
}

// Update database menjadi default
$default_avatar = '96.png';
$stmt = $koneksi->prepare("UPDATE users SET avatar=? WHERE id=?");
$stmt->bind_param("si", $default_avatar, $user_id);
$stmt->execute();
$stmt->close();

// Update session
$_SESSION['avatar'] = $default_avatar;
$_SESSION['success'] = "Avatar berhasil dihapus!";

header("Location: ../dashboard.php");
exit;
