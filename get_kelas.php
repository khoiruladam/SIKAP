<?php
include '../config/koneksi.php';
header('Content-Type: application/json');

$tingkat = $_GET['tingkat'] ?? '';
$jurusan = $_GET['jurusan'] ?? '';

if ($tingkat === '' || $jurusan === '') {
    echo json_encode([]);
    exit;
}

$stmt = $koneksi->prepare("
    SELECT id, nama_kelas
    FROM kelas
    WHERE tingkat = ? AND jurusan = ?
    ORDER BY nama_kelas ASC
");

$stmt->bind_param("ss", $tingkat, $jurusan);
$stmt->execute();

$result = $stmt->get_result();
$data = [];

while ($row = $result->fetch_assoc()) {
    $data[] = $row;
}

echo json_encode($data);
