<?php
session_start();
include '../config/koneksi.php';
if ($_SESSION['role'] != 'peserta') {
    header("Location: ../index.php");
    exit;
}

$user_id = $_SESSION['id'];

// Ambil parameter sorting dari URL (default: tanggal DESC)
$sort_column = $_GET['sort'] ?? 'tanggal'; // tanggal atau status
$sort_order  = $_GET['order'] ?? 'DESC'; // ASC atau DESC

// Validasi kolom agar aman
$allowed_columns = ['tanggal','status'];
$allowed_order   = ['ASC','DESC'];
if(!in_array($sort_column,$allowed_columns)) $sort_column = 'tanggal';
if(!in_array($sort_order,$allowed_order)) $sort_order = 'DESC';

// Ambil data absensi, kegiatan, bimbingan sesuai sort
$absen = mysqli_query($koneksi, "SELECT * FROM absensi WHERE user_id='$user_id' ORDER BY $sort_column $sort_order");
$kegiatan = mysqli_query($koneksi, "SELECT * FROM kegiatan WHERE user_id='$user_id' ORDER BY tanggal DESC");
$bimbingan = mysqli_query($koneksi, "SELECT * FROM bimbingan WHERE user_id='$user_id' ORDER BY tanggal DESC");

// Nama file Word
$nama_file = "History_PKL_" . $_SESSION['nama'] . ".doc";

// Header agar download sebagai Word
header("Content-Type: application/vnd.ms-word");
header("Content-Disposition: attachment; filename=\"$nama_file\"");
header("Pragma: no-cache");
header("Expires: 0");
?>
<html>
<head>
<meta charset="UTF-8">
<style>
  body { font-family: Arial, sans-serif; font-size: 12pt; }
  h2 { text-align: center; margin-bottom: 20px; }
  h3 { margin-top: 25px; color: #2c3e50; }
  table { width: 100%; border-collapse: collapse; margin-bottom: 20px; }
  th, td { border: 1px solid #000; padding: 6px; text-align: center; vertical-align: top; }
  th { background: #f2f2f2; }
  td { word-wrap: break-word; max-width: 200px; text-align: left; }
</style>
</head>
<body>

<h2>History PKL - <?= $_SESSION['nama'] ?></h2>

<h3>📌 History Absen</h3>
<table>
  <tr>
    <th>Tanggal</th>
    <th>Jam Masuk</th>
    <th>Jam Istirahat 1</th>
    <th>Jam Istirahat 2</th>
    <th>Jam Pulang</th>
    <th>Status</th>
    <th>Keterangan</th>
  </tr>
  <?php while($row = mysqli_fetch_assoc($absen)): ?>
  <tr>
    <td><?= $row['tanggal'] ?></td>
    <td><?= $row['jam_masuk'] ?: '-' ?></td>
    <td><?= $row['jam_istirahat1'] ?: '-' ?></td>
    <td><?= $row['jam_istirahat2'] ?: '-' ?></td>
    <td><?= $row['jam_pulang'] ?: '-' ?></td>
    <td><?= $row['status'] ?></td>
    <td><?= $row['keterangan'] ?: '-' ?></td>
  </tr>
  <?php endwhile; ?>
</table>

<h3>📝 History Kegiatan</h3>
<table>
  <tr>
    <th>Tanggal</th>
    <th>Unit Kerja</th>
    <th>Deskripsi Kegiatan</th>
    <th>Catatan</th>
    <th>Foto</th>
  </tr>
  <?php while($row = mysqli_fetch_assoc($kegiatan)): ?>
  <tr>
    <td><?= $row['tanggal'] ?></td>
    <td><?= $row['unit_kerja'] ?: '-' ?></td>
    <td><?= $row['deskripsi_kegiatan'] ?></td>
    <td><?= $row['catatan'] ?: '-' ?></td>
    <td><?= $row['foto'] ? $row['foto'] : '-' ?></td>
  </tr>
  <?php endwhile; ?>
</table>

<h3>🎓 History Bimbingan</h3>
<table>
  <tr>
    <th>Tanggal</th>
    <th>Uraian</th>
    <th>Foto</th>
  </tr>
  <?php while($row = mysqli_fetch_assoc($bimbingan)): ?>
  <tr>
    <td><?= $row['tanggal'] ?></td>
    <td><?= $row['uraian'] ?></td>
    <td><?= $row['foto'] ? $row['foto'] : '-' ?></td>
  </tr>
  <?php endwhile; ?>
</table>

<p><i>Dicetak pada: <?= date("d-m-Y H:i") ?></i></p>

</body>
</html>
