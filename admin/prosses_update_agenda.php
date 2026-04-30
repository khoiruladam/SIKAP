<?php
session_start();
require '../config/koneksi.php';

/* ================= AUTH ================= */
if (!in_array($_SESSION['role'] ?? '', ['admin', 'guru'])) {
    header("Location: ../index.php");
    exit;
}

/* ================= INPUT ================= */
$agenda     = trim($_POST['agenda_singkat'] ?? '');
$start_date = $_POST['tanggal_mulai'] ?? '';
$end_date   = $_POST['tanggal_selesai'] ?? '';

/* ================= VALIDASI ================= */
if ($agenda === '' || $start_date === '' || $end_date === '') {
    header("Location: dashboard.php?error=invalid_input");
    exit;
}

if ($start_date > $end_date) {
    header("Location: dashboard.php?error=date_invalid");
    exit;
}

/* ================= INSERT ================= */
$stmt = $koneksi->prepare(
    "INSERT INTO agenda (agenda_singkat, tanggal_mulai, tanggal_selesai)
     VALUES (?, ?, ?)"
);

$stmt->bind_param("sss", $agenda, $start_date, $end_date);
$stmt->execute();
$stmt->close();

/* ================= REDIRECT ================= */
header("Location: dashboard.php?success=agenda_added");
exit;
