<?php
session_start();
include '../config/koneksi.php';
if ($_SESSION['role'] != 'peserta') {
  header("Location: ../index.php");
  exit;
}

// Fungsi ubah tanggal jadi nama hari
function namaHari($tanggal)
{
  $hari = date('l', strtotime($tanggal));
  $daftar_hari = [
    'Sunday' => 'Minggu',
    'Monday' => 'Senin',
    'Tuesday' => 'Selasa',
    'Wednesday' => 'Rabu',
    'Thursday' => 'Kamis',
    'Friday' => 'Jumat',
    'Saturday' => 'Sabtu'
  ];
  return $daftar_hari[$hari] ?? '-';
}

// Ambil data
$user_id = $_SESSION['id'];
$absen = mysqli_query($koneksi, "SELECT * FROM absensi WHERE user_id='$user_id' ORDER BY tanggal DESC");
$kegiatan = mysqli_query($koneksi, "SELECT * FROM kegiatan WHERE user_id='$user_id' ORDER BY tanggal DESC");
$bimbingan = mysqli_query($koneksi, "SELECT * FROM bimbingan WHERE user_id='$user_id' ORDER BY tanggal DESC");
$pembelajaran = mysqli_query($koneksi, "SELECT * FROM pembelajaran ORDER BY created_at DESC");
?>
<!DOCTYPE html>
<html lang="id">

<head>
  <meta charset="UTF-8">
  <title>History PKL</title>
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
  <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css" rel="stylesheet">
  <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700&display=swap" rel="stylesheet">
  <style>
    body {
      background: #f0f2f5;
      font-family: 'Poppins', sans-serif;
    }

    .navbar {
      background: #212529 !important;
    }

    .navbar-brand {
      color: #fff !important;
      font-weight: 600;
    }

    .nav-tabs {
      border-bottom: 2px solid #e0e0e0;
    }

    .nav-tabs .nav-link {
      color: #555;
      font-weight: 500;
      border: none;
    }

    .nav-tabs .nav-link.active {
      color: #212529;
      border-bottom: 3px solid #212529;
      background: none;
    }

    .card {
      border-radius: 14px;
      border: none;
      box-shadow: 0 4px 12px rgba(0, 0, 0, 0.08);
      margin-bottom: 20px;
    }

    .card h5 {
      font-weight: 600;
      color: #212529;
    }

    .search-box {
      max-width: 280px;
      margin-bottom: 12px;
    }

    .img-thumbnail {
      max-width: 80px;
      max-height: 80px;
      object-fit: cover;
      border-radius: 8px;
    }

    .badge-status {
      padding: 4px 10px;
      border-radius: 20px;
      font-size: 12px;
    }
  </style>
</head>

<body>

  <!-- Navbar -->
  <nav class="navbar navbar-expand-lg shadow-sm fixed-top">
    <div class="container-fluid px-3">
      <span class="navbar-brand"><i class="bi bi-clock-history me-2"></i>History PKL</span>
      <div class="d-flex gap-2">
        <a href="../peserta/export_doc.php" class="btn btn-outline-light btn-sm"><i class="bi bi-file-earmark-word"></i> Export</a>
        <a href="dashboard.php" class="btn btn-outline-light btn-sm"><i class="bi bi-arrow-left-circle me-1"></i> Kembali</a>
      </div>
    </div>
  </nav>

  <div class="container py-5" style="margin-top:70px;">

    <!-- Tabs -->
    <ul class="nav nav-tabs mb-4" id="historyTab" role="tablist">
      <li class="nav-item"><button class="nav-link active" data-bs-toggle="tab" data-bs-target="#absen">Absen</button></li>
      <li class="nav-item"><button class="nav-link" data-bs-toggle="tab" data-bs-target="#kegiatan">Kegiatan</button></li>
      <li class="nav-item"><button class="nav-link" data-bs-toggle="tab" data-bs-target="#bimbingan">Bimbingan</button></li>
      <li class="nav-item"><button class="nav-link" data-bs-toggle="tab" data-bs-target="#pembelajaran">Pembelajaran</button></li>
    </ul>

    <div class="tab-content">

      <!-- Absen -->
      <div class="tab-pane fade show active" id="absen">
        <input type="text" id="searchAbsen" class="form-control search-box" placeholder="Cari absen...">
        <div class="table-responsive">
          <table class="table table-hover align-middle text-center" id="absenTable">
            <thead class="table-light">
              <tr>
                <th>Tanggal</th>
                <th>Hari</th>
                <th>Masuk</th>
                <th>Istirahat1</th>
                <th>Istirahat2</th>
                <th>Pulang</th>
                <th>Status</th>
                <th>Keterangan</th>
                <th>Surat</th>
              </tr>
            </thead>
            <tbody>
              <?php while ($row = mysqli_fetch_assoc($absen)): ?>
                <tr>
                  <td><?= date('d/m/Y', strtotime($row['tanggal'])) ?></td>
                  <td><?= $row['hari'] ?: namaHari($row['tanggal']) ?></td>
                  <td><?= $row['jam_masuk'] ?: '-' ?></td>
                  <td><?= $row['jam_istirahat1'] ?: '-' ?></td>
                  <td><?= $row['jam_istirahat2'] ?: '-' ?></td>
                  <td><?= $row['jam_pulang'] ?: '-' ?></td>
                  <td>
                    <?php if ($row['status'] == 'Masuk'): ?>
                      <span class="badge bg-success badge-status"><?= $row['status'] ?></span>
                    <?php elseif ($row['status'] == 'Tidak Masuk'): ?>
                      <span class="badge bg-danger badge-status"><?= $row['status'] ?></span>
                    <?php else: ?>
                      <span class="badge bg-secondary badge-status">-</span>
                    <?php endif; ?>
                  </td>
                  <td><?= $row['keterangan'] ?: '-' ?></td>
                  <td>
                    <?php if (!empty($row['surat_keterangan'])): ?>
                      <a href="../uploads/surat/<?= urlencode($row['surat_keterangan']) ?>" target="_blank" class="btn btn-sm btn-outline-primary"><i class="bi bi-file-earmark-text"></i></a>
                      <?php else: ?>-<?php endif; ?>
                  </td>
                </tr>
              <?php endwhile; ?>
            </tbody>
          </table>
        </div>
      </div>

      <!-- Kegiatan -->
      <div class="tab-pane fade" id="kegiatan">
        <input type="text" id="searchKegiatan" class="form-control search-box" placeholder="Cari kegiatan...">
        <div class="row mt-3">
          <?php while ($row = mysqli_fetch_assoc($kegiatan)): ?>
            <div class="col-md-6">
              <div class="card p-3">
                <h5><?= htmlspecialchars($row['deskripsi_kegiatan']) ?></h5>
                <p class="mb-1"><strong>Unit:</strong> <?= htmlspecialchars($row['unit_kerja'] ?: '-') ?></p>
                <p class="mb-1"><strong>Catatan:</strong> <?= htmlspecialchars($row['catatan'] ?: '-') ?></p>
                <p class="mb-1"><strong>Tanggal:</strong> <?= date('d/m/Y', strtotime($row['tanggal'])) ?> (<?= htmlspecialchars($row['hari']) ?>)</p>
                <?php if (!empty($row['foto'])): ?>
                  <img src="../uploads/<?= htmlspecialchars($row['foto']) ?>" class="img-thumbnail mt-2">
                <?php endif; ?>
              </div>
            </div>
          <?php endwhile; ?>
        </div>
      </div>

      <!-- Bimbingan -->
      <div class="tab-pane fade" id="bimbingan">
        <input type="text" id="searchBimbingan" class="form-control search-box" placeholder="Cari bimbingan...">
        <div class="row mt-3">
          <?php while ($row = mysqli_fetch_assoc($bimbingan)): ?>
            <div class="col-md-6">
              <div class="card p-3">
                <p class="mb-1"><strong>Uraian:</strong> <?= htmlspecialchars($row['uraian']) ?></p>
                <p class="mb-1"><strong>Tanggal:</strong> <?= date('d/m/Y', strtotime($row['tanggal'])) ?> (<?= namaHari($row['tanggal']) ?>)</p>
                <?php if (!empty($row['foto'])): ?>
                  <img src="../uploads/bimbingan/<?= htmlspecialchars($row['foto']) ?>" class="img-thumbnail mt-2">
                <?php endif; ?>
              </div>
            </div>
          <?php endwhile; ?>
        </div>
      </div>

      <!-- Pembelajaran -->
      <div class="tab-pane fade" id="pembelajaran">
        <input type="text" id="searchPembelajaran" class="form-control search-box" placeholder="Cari pembelajaran...">
        <div class="row mt-3">
          <?php while ($row = mysqli_fetch_assoc($pembelajaran)): ?>
            <div class="col-md-6">
              <div class="card p-3">
                <h5><?= htmlspecialchars($row['judul']) ?></h5>
                <p class="mb-1"><?= htmlspecialchars($row['keterangan']) ?></p>
                <p class="mb-1"><strong>Tanggal Dibuat:</strong> <?= date('d/m/Y', strtotime($row['created_at'])) ?></p>
                <?php if (!empty($row['video'])): ?>
                  <a href="<?= htmlspecialchars($row['video']) ?>" target="_blank" class="btn btn-sm btn-outline-primary">Lihat Video</a>
                <?php endif; ?>
              </div>
            </div>
          <?php endwhile; ?>
        </div>
      </div>

    </div>
  </div>

  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
  <script>
    function addSearch(inputId, containerSelector) {
      const input = document.getElementById(inputId);
      input.addEventListener("keyup", function() {
        const filter = this.value.toLowerCase();
        document.querySelectorAll(containerSelector + " .card").forEach(card => {
          card.style.display = card.innerText.toLowerCase().includes(filter) ? "" : "none";
        });
        document.querySelectorAll(containerSelector + " tbody tr").forEach(row => {
          row.style.display = row.innerText.toLowerCase().includes(filter) ? "" : "none";
        });
      });
    }

    addSearch("searchAbsen", "#absenTable");
    addSearch("searchKegiatan", "#kegiatan .row");
    addSearch("searchBimbingan", "#bimbingan .row");
    addSearch("searchPembelajaran", "#pembelajaran .row");
  </script>
</body>

</html>