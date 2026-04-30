<?php
session_start();
include '../config/koneksi.php';

if ($_SESSION['role'] != 'admin' && $_SESSION['role'] != 'guru') {
    header("Location: ../index.php");
    exit;
}

$sort_column = $_GET['sort'] ?? 'nama';
$sort_order  = $_GET['order'] ?? 'ASC';
$allowed_columns = ['nama','tanggal'];
$allowed_order   = ['ASC','DESC'];
if(!in_array($sort_column,$allowed_columns)) $sort_column = 'nama';
if(!in_array($sort_order,$allowed_order)) $sort_order = 'ASC';

// Tambahkan filter nama
$filter_nama = $_GET['filter_nama'] ?? '';
$filter_sql  = $filter_nama ? " AND u.nama LIKE '%".mysqli_real_escape_string($koneksi,$filter_nama)."%'" : "";

$nama_file = "Rekap_Peserta_".date("Y-m-d_H-i").".doc";

header("Content-Type: application/vnd.ms-word");
header("Content-Disposition: attachment; filename=\"$nama_file\"");
header("Pragma: no-cache");
header("Expires: 0");

$absen = mysqli_query($koneksi, "SELECT a.*, u.nama, u.kelas 
    FROM absensi a 
    JOIN users u ON a.user_id = u.id 
    WHERE 1=1 $filter_sql
    ORDER BY $sort_column $sort_order");

$kegiatan = mysqli_query($koneksi, "SELECT k.*, u.nama, u.kelas 
    FROM kegiatan k 
    JOIN users u ON k.user_id = u.id 
    WHERE 1=1 $filter_sql
    ORDER BY $sort_column $sort_order");

$bimbingan = mysqli_query($koneksi, "SELECT b.*, u.nama, u.kelas 
    FROM bimbingan b 
    JOIN users u ON b.user_id = u.id 
    WHERE 1=1 $filter_sql
    ORDER BY $sort_column $sort_order");
?>
<html>
<head>
<meta charset="UTF-8">
<title>Rekap Peserta</title>
<style>
body { font-family: Arial, sans-serif; font-size: 10pt; }
h2 { text-align: center; margin-bottom: 10px; font-size: 12pt; }
table { border-collapse: collapse; width: 100%; margin-bottom: 20px; table-layout: fixed; }
th, td { border: 1px solid #000; padding: 4px; text-align: center; vertical-align: middle; word-wrap: break-word; }
th { background: #f2f2f2; font-size: 10pt; }
img { max-width: 50px; max-height: 50px; }
</style>
</head>
<body>

<h2>📌 Rekap Absensi</h2>
<table>
<thead>
<tr>
<th>Nama</th><th>Kelas</th><th>Tanggal</th><th>Masuk</th><th>Istirahat 1</th><th>Istirahat 2</th><th>Pulang</th><th>Status</th><th>Keterangan</th>
</tr>
</thead>
<tbody>
<?php if(mysqli_num_rows($absen)>0): ?>
<?php while($row = mysqli_fetch_assoc($absen)): ?>
<tr>
<td><?= $row['nama'] ?></td>
<td><?= $row['kelas'] ?></td>
<td><?= $row['tanggal'] ?></td>
<td><?= $row['jam_masuk']?:'-' ?></td>
<td><?= $row['jam_istirahat1']?:'-' ?></td>
<td><?= $row['jam_istirahat2']?:'-' ?></td>
<td><?= $row['jam_pulang']?:'-' ?></td>
<td><?= $row['status'] ?></td>
<td><?= $row['keterangan']?:'-' ?></td>
</tr>
<?php endwhile; ?>
<?php else: ?>
<tr><td colspan="9">Tidak ada data ditemukan</td></tr>
<?php endif; ?>
</tbody>
</table>

<h2>📝 Rekap Kegiatan</h2>
<table>
<thead>
<tr>
<th>Nama</th><th>Kelas</th><th>Tanggal</th><th>Deskripsi Kegiatan</th><th>Catatan</th><th>Foto</th>
</tr>
</thead>
<tbody>
<?php if(mysqli_num_rows($kegiatan)>0): ?>
<?php while($row = mysqli_fetch_assoc($kegiatan)): ?>
<tr>
<td><?= $row['nama'] ?></td>
<td><?= $row['kelas'] ?></td>
<td><?= $row['tanggal'] ?></td>
<td><?= $row['deskripsi_kegiatan'] ?></td>
<td><?= $row['catatan']?:'-' ?></td>
<td><?= $row['foto'] && file_exists("../uploads/".$row['foto'])? "<img src='../uploads/".$row['foto']."' />":"-" ?></td>
</tr>
<?php endwhile; ?>
<?php else: ?>
<tr><td colspan="6">Tidak ada data ditemukan</td></tr>
<?php endif; ?>
</tbody>
</table>

<h2>🎓 Rekap Bimbingan</h2>
<table>
<thead>
<tr>
<th>Nama</th><th>Kelas</th><th>Tanggal</th><th>Uraian</th><th>Foto</th>
</tr>
</thead>
<tbody>
<?php if(mysqli_num_rows($bimbingan)>0): ?>
<?php while($row = mysqli_fetch_assoc($bimbingan)): ?>
<tr>
<td><?= $row['nama'] ?></td>
<td><?= $row['kelas'] ?></td>
<td><?= $row['tanggal'] ?></td>
<td><?= $row['uraian'] ?></td>
<td><?= $row['foto'] && file_exists("../uploads/bimbingan/".$row['foto'])? "<img src='../uploads/bimbingan/".$row['foto']."' />":"-" ?></td>
</tr>
<?php endwhile; ?>
<?php else: ?>
<tr><td colspan="5">Tidak ada data ditemukan</td></tr>
<?php endif; ?>
</tbody>
</table>

<p><i>Dicetak pada: <?= date("d-m-Y H:i") ?></i></p>

</body>
</html>
