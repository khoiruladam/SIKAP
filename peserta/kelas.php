<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);    
session_start();
include '../config/koneksi.php';

/* ================= CEK LOGIN ================= */
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'peserta') {
    header("Location: ../index.php");
    exit;
}

$userId = $_SESSION['id'];

/* ================= AMBIL DATA KELAS SISWA ================= */
$stmt = $koneksi->prepare("
    SELECT 
        k.nama_kelas,
        k.tingkat,
        k.jurusan,
        k.wali_kelas,
        k.tahun_ajaran
    FROM users u
    LEFT JOIN kelas k ON u.id = k.id
    WHERE u.id = ?
");
$stmt->bind_param("i", $userId);
$stmt->execute();
$result = $stmt->get_result();
$kelas = $result->fetch_assoc();
$stmt->close();

/* ================= CEK KELAS ================= */
$belumAdaKelas = (!$kelas || !$kelas['nama_kelas']);
?>
<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="UTF-8">
<title>Kelas Saya</title>
<meta name="viewport" content="width=device-width, initial-scale=1">

<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
<link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css" rel="stylesheet">
<link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700&display=swap" rel="stylesheet">

<style>
body {
    font-family: 'Poppins', sans-serif;
    background: #f4f6f9;
}
.card-dashboard {
    border-radius: 18px;
    box-shadow: 0 10px 30px rgba(0,0,0,.08);
}
.info-label {
    font-size: .8rem;
    color: #6c757d;
}
.info-value {
    font-weight: 600;
    font-size: .95rem;
}
</style>
</head>

<body>

<div class="container py-4">

    <div class="d-flex align-items-center mb-4">
        <i class="bi bi-people-fill fs-3 me-2 text-primary"></i>
        <div>
            <h4 class="mb-0">Kelas Saya</h4>
            <small class="text-muted">Informasi kelas tempat Anda terdaftar</small>
        </div>
    </div>

    <?php if ($belumAdaKelas): ?>

        <!-- JIKA BELUM PUNYA KELAS -->
        <div class="alert alert-warning rounded-4">
            <i class="bi bi-exclamation-triangle me-2"></i>
            Anda belum terdaftar di kelas mana pun.
            <br>
            <small>Silakan hubungi admin atau wali kelas.</small>
        </div>

    <?php else: ?>

        <!-- CARD KELAS -->
        <div class="card card-dashboard">
            <div class="card-body p-4">

                <div class="row g-3">

                    <div class="col-md-6">
                        <div class="info-label">Nama Kelas</div>
                        <div class="info-value"><?= htmlspecialchars($kelas['nama_kelas']) ?></div>
                    </div>

                    <div class="col-md-6">
                        <div class="info-label">Tingkat</div>
                        <div class="info-value"><?= htmlspecialchars($kelas['tingkat']) ?></div>
                    </div>

                    <div class="col-md-6">
                        <div class="info-label">Jurusan</div>
                        <div class="info-value"><?= htmlspecialchars($kelas['jurusan']) ?></div>
                    </div>

                    <div class="col-md-6">
                        <div class="info-label">Tahun Ajaran</div>
                        <div class="info-value"><?= htmlspecialchars($kelas['tahun_ajaran']) ?></div>
                    </div>

                    <div class="col-12">
                        <div class="info-label">Wali Kelas</div>
                        <div class="info-value">
                            <?= $kelas['wali_kelas'] ? htmlspecialchars($kelas['wali_kelas']) : '-' ?>
                        </div>
                    </div>

                </div>

            </div>
        </div>

    <?php endif; ?>

</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
