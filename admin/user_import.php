<?php
session_start();
require '../config/koneksi.php';

/* ================= RESET MESSAGE ================= */
unset($_SESSION['import_success'], $_SESSION['import_error']);

/* ================= VALIDASI FILE ================= */
if (!isset($_FILES['file']) || $_FILES['file']['error'] !== 0) {
    $_SESSION['import_error'] = 'Gagal upload file.';
    header("Location: dashboard.php?page=user");
    exit;
}

$file = $_FILES['file'];
$ext  = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));

if ($ext !== 'csv') {
    $_SESSION['import_error'] = 'Format file tidak didukung. Gunakan CSV.';
    header("Location: dashboard.php?page=user");
    exit;
}

/* ================= BUKA CSV ================= */
$handle = fopen($file['tmp_name'], 'r');
if (!$handle) {
    $_SESSION['import_error'] = 'File tidak dapat dibaca.';
    header("Location: dashboard.php?page=user");
    exit;
}

/* ================= PREPARE QUERY ================= */
$stmt = $koneksi->prepare("
  INSERT INTO users
  (nama, username, password, role, kelas, alamat, bio)
  VALUES (?, ?, ?, ?, ?, ?, ?)
");

$success = 0;
$failed  = 0;
$line    = 0;

/* ================= LOOP ================= */
while (($row = fgetcsv($handle, 1000, ',')) !== false) {

    $line++;
    if ($line === 1) continue; // skip header

    $nama     = trim($row[0] ?? '');
    $username = trim($row[1] ?? '');
    $password = trim($row[2] ?? '');
    $role     = trim($row[3] ?? '');
    $kelas    = trim($row[4] ?? '');
    $alamat   = trim($row[5] ?? '');
    $bio      = trim($row[6] ?? '');

    if ($nama === '' || $username === '' || $password === '' || $role === '') {
        $failed++;
        continue;
    }

    $hash = password_hash($password, PASSWORD_DEFAULT);

    $stmt->bind_param(
        "sssssss",
        $nama,
        $username,
        $hash,
        $role,
        $kelas,
        $alamat,
        $bio
    );

    if ($stmt->execute()) {
        $success++;
    } else {
        $failed++;
    }
}

fclose($handle);
$stmt->close();

/* ================= SET MESSAGE ================= */
if ($success > 0) {
    $_SESSION['import_success'] =
        "Import selesai. Berhasil: $success data" .
        ($failed ? ", Gagal: $failed data" : '');
} else {
    $_SESSION['import_error'] = 'Import gagal. Periksa format CSV.';
}

header("Location: user.php?page=user");
exit;
