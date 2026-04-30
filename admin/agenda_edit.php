<?php
session_start();
require_once '../config/koneksi.php';

/* ================= AUTH ================= */
if (!isset($_SESSION['role']) || !in_array($_SESSION['role'], ['admin', 'guru'])) {
    header("Location: ../index.php");
    exit;
}

/* ================= VALIDASI ID ================= */
$id = (int)($_GET['id'] ?? 0);
if ($id <= 0) {
    header("Location: dashboard.php");
    exit;
}

/* ================= HELPER ================= */
function e($v) {
    return htmlspecialchars($v ?? '', ENT_QUOTES, 'UTF-8');
}

/* ================= AMBIL DATA ================= */
$stmt = $koneksi->prepare(
    "SELECT agenda_singkat, tanggal_mulai, tanggal_selesai 
     FROM agenda 
     WHERE id = ?"
);
$stmt->bind_param("i", $id);
$stmt->execute();
$agenda = $stmt->get_result()->fetch_assoc();
$stmt->close();

if (!$agenda) {
    header("Location: dashboard.php");
    exit;
}

/* ================= PROSES UPDATE ================= */
if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $agenda_singkat = trim($_POST['agenda_singkat'] ?? '');
    $start_date     = $_POST['tanggal_mulai'] ?? '';
    $end_date       = $_POST['tanggal_selesai'] ?? '';

    if ($agenda_singkat === '' || $start_date === '' || $end_date === '') {
        header("Location: agenda_edit.php?id=$id&error=invalid");
        exit;
    }

    if ($start_date > $end_date) {
        header("Location: agenda_edit.php?id=$id&error=date");
        exit;
    }

    $stmt = $koneksi->prepare(
        "UPDATE agenda 
         SET agenda_singkat = ?, tanggal_mulai = ?, tanggal_selesai = ?
         WHERE id = ?"
    );
    $stmt->bind_param("sssi", $agenda_singkat, $start_date, $end_date, $id);
    $stmt->execute();
    $stmt->close();

    header("Location: dashboard.php?success=agenda_updated");
    exit;
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Edit Agenda</title>
    <meta name="viewport" content="width=device-width, initial-scale=1">

    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons/font/bootstrap-icons.css" rel="stylesheet">
</head>

<body class="bg-light">

<div class="container my-5" style="max-width:620px;">
    <div class="card shadow-sm">
        <div class="card-body">

            <h5 class="fw-semibold mb-4">
                <i class="bi bi-pencil-square me-1 text-warning"></i>
                Edit Agenda
            </h5>

            <form method="POST">

                <!-- AGENDA -->
                <div class="mb-3">
                    <label class="form-label fw-semibold">
                        Agenda Singkat
                    </label>
                    <input
                        type="text"
                        name="agenda_singkat"
                        class="form-control"
                        value="<?= e($agenda['agenda_singkat']) ?>"
                        required>
                </div>

                <!-- TANGGAL -->
                <div class="row g-2 mb-4">
                    <div class="col-6">
                        <label class="form-label fw-semibold">
                            Tanggal Mulai
                        </label>
                        <input
                            type="date"
                            name="start_date"
                            class="form-control"
                            value="<?= e($agenda['tanggal_mulai']) ?>"
                            required>
                    </div>

                    <div class="col-6">
                        <label class="form-label fw-semibold">
                            Tanggal Selesai
                        </label>
                        <input
                            type="date"
                            name="end_date"
                            class="form-control"
                            value="<?= e($agenda['tanggal_selesai']) ?>"
                            required>
                    </div>
                </div>

                <!-- AKSI -->
                <div class="d-flex justify-content-between">
                    <a href="dashboard.php" class="btn btn-secondary btn-sm">
                        <i class="bi bi-arrow-left"></i> Kembali
                    </a>

                    <button class="btn btn-primary btn-sm">
                        <i class="bi bi-save"></i> Simpan Perubahan
                    </button>
                </div>

            </form>

        </div>
    </div>
</div>

</body>
</html>
