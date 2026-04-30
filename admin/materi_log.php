<?php
session_start();
include '../config/koneksi.php';

if (!isset($_SESSION['id'])) exit;

$user_id   = (int) $_SESSION['id'];
$materi_id = (int) ($_POST['materi_id'] ?? 0);
$aksi      = $_POST['aksi'] ?? '';

if (!$materi_id || !in_array($aksi, ['buka_materi','tonton_video'])) exit;

mysqli_query($koneksi, "
    INSERT IGNORE INTO pembelajaran_log (materi_id, user_id, aksi)
    VALUES ($materi_id, $user_id, '$aksi')
");
