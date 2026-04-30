<?php
session_start();
include '../config/koneksi.php';
date_default_timezone_set('Asia/Jakarta');

// Cek role peserta
if (!isset($_SESSION['role']) || $_SESSION['role'] != 'peserta') {
    header("Location: ../index.php");
    exit;
}

$user_id = $_SESSION['id'];

// Cek jika form dikirim
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['absen_submit'])) {

    $pilihan_tanggal = $_POST['pilihan_tanggal'] ?? 'hari_ini';
    
    if ($pilihan_tanggal === 'hari_lain' && !empty($_POST['tanggal_lain'])) {
        $tanggal = $_POST['tanggal_lain'];
    } else {
        $tanggal = $_POST['tanggal_hidden'] ?? date("Y-m-d");
    }

    // Validasi tanggal (tidak boleh melebihi hari ini)
    if (strtotime($tanggal) > strtotime(date("Y-m-d"))) {
        $_SESSION['flash_msg'] = "Tanggal tidak boleh di masa depan!";
        $_SESSION['flash_icon'] = "error";
        header("Location: absen.php");
        exit;
    }

    // Tentukan hari berdasarkan tanggal
    $hari = strftime("%A", strtotime($tanggal));
    $hari = ucfirst(strtolower($hari));

    // Ambil data input
    $jam_masuk      = $_POST['jam_masuk'] ?: null;
    $jam_istirahat1 = $_POST['jam_istirahat1'] ?: null;
    $jam_istirahat2 = $_POST['jam_istirahat2'] ?: null;
    $jam_pulang     = $_POST['jam_pulang'] ?: null;
    $status         = $_POST['status'] ?? 'Masuk';
    $keterangan     = $_POST['keterangan'] ?: null;

    // Cek apakah sudah ada data absensi untuk user dan tanggal ini
    $stmt_check = $koneksi->prepare("SELECT id FROM absensi WHERE user_id = ? AND tanggal = ?");
    $stmt_check->bind_param("is", $user_id, $tanggal);
    $stmt_check->execute();
    $result = $stmt_check->get_result();
    $data = $result->fetch_assoc();
    $stmt_check->close();

    if (!$data) {
        // Insert data baru
        $stmt = $koneksi->prepare("
            INSERT INTO absensi (user_id, tanggal, hari, jam_masuk, jam_istirahat1, jam_istirahat2, jam_pulang, status, keterangan)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)
        ");
        $stmt->bind_param("issssssss", $user_id, $tanggal, $hari, $jam_masuk, $jam_istirahat1, $jam_istirahat2, $jam_pulang, $status, $keterangan);
    } else {
        // Update data lama
        $stmt = $koneksi->prepare("
            UPDATE absensi 
            SET hari = ?, jam_masuk = ?, jam_istirahat1 = ?, jam_istirahat2 = ?, jam_pulang = ?, status = ?, keterangan = ? 
            WHERE id = ?
        ");
        $stmt->bind_param("sssssssi", $hari, $jam_masuk, $jam_istirahat1, $jam_istirahat2, $jam_pulang, $status, $keterangan, $data['id']);
    }

    if ($stmt->execute()) {
        $_SESSION['flash_msg'] = "Absensi berhasil disimpan!";
        $_SESSION['flash_icon'] = "success";
    } else {
        $_SESSION['flash_msg'] = "Gagal menyimpan data: " . $stmt->error;
        $_SESSION['flash_icon'] = "error";
    }

    $stmt->close();
    header("Location: absen.php");
    exit;
}
?>
