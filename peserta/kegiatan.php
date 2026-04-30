<?php
session_start();
include '../config/koneksi.php';
date_default_timezone_set('Asia/Jakarta');

if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'peserta') {
  header("Location: ../index.php");
  exit;
}

$user_id = (int)$_SESSION['id'];

/* ================= FLASH ================= */
$flash = $_SESSION['flash'] ?? null;
unset($_SESSION['flash']);

/* ================= HARI ================= */
$hariMap = [
  'Sunday' => 'Minggu',
  'Monday' => 'Senin',
  'Tuesday' => 'Selasa',
  'Wednesday' => 'Rabu',
  'Thursday' => 'Kamis',
  'Friday' => 'Jumat',
  'Saturday' => 'Sabtu'
];

/* ================= SIMPAN ================= */
if (isset($_POST['simpan'])) {
  $tanggal = $_POST['tanggal'];
  $hari = $hariMap[date('l', strtotime($tanggal))];
  $unit = mysqli_real_escape_string($koneksi, $_POST['unit_kerja']);
  $desk = mysqli_real_escape_string($koneksi, $_POST['deskripsi']);
  $cat  = mysqli_real_escape_string($koneksi, $_POST['catatan']);

  $foto = null;
  if (!empty($_FILES['foto']['name'])) {
    $dir = "../uploads/kegiatan/";
    if (!is_dir($dir)) mkdir($dir, 0777, true);
    $foto = time() . '_' . basename($_FILES['foto']['name']);
    move_uploaded_file($_FILES['foto']['tmp_name'], $dir . $foto);
  }

  $stmt = $koneksi->prepare("
    INSERT INTO kegiatan 
    (user_id,tanggal,hari,unit_kerja,deskripsi_kegiatan,catatan,foto)
    VALUES (?,?,?,?,?,?,?)
  ");
  $stmt->bind_param("issssss", $user_id, $tanggal, $hari, $unit, $desk, $cat, $foto);
  $stmt->execute();
  $stmt->close();

  $_SESSION['flash'] = ['success', 'Kegiatan berhasil disimpan'];
  header("Location: kegiatan.php");
  exit;
}

/* ================= UPDATE ================= */
if (isset($_POST['update'])) {
  $id = (int)$_POST['id_edit'];
  $tanggal = $_POST['tanggal_edit'];
  $hari = $hariMap[date('l', strtotime($tanggal))];
  $unit = mysqli_real_escape_string($koneksi, $_POST['unit_kerja']);
  $desk = mysqli_real_escape_string($koneksi, $_POST['deskripsi']);
  $cat  = mysqli_real_escape_string($koneksi, $_POST['catatan']);

  $fotoSql = '';
  if (!empty($_FILES['foto_edit']['name'])) {
    $dir = "../uploads/kegiatan/";
    if (!is_dir($dir)) mkdir($dir, 0777, true);
    $f = time() . '_' . basename($_FILES['foto_edit']['name']);
    move_uploaded_file($_FILES['foto_edit']['tmp_name'], $dir . $f);
    $fotoSql = ", foto='$f'";
  }

  mysqli_query($koneksi, "
    UPDATE kegiatan SET
      tanggal='$tanggal',
      hari='$hari',
      unit_kerja='$unit',
      deskripsi_kegiatan='$desk',
      catatan='$cat'
      $fotoSql
    WHERE id='$id' AND user_id='$user_id'
  ");

  $_SESSION['flash'] = ['success', 'Kegiatan diperbarui'];
  header("Location: kegiatan.php");
  exit;
}

/* ================= HAPUS ================= */
if (isset($_GET['hapus'])) {
  mysqli_query($koneksi, "
    DELETE FROM kegiatan 
    WHERE id='" . (int)$_GET['hapus'] . "' AND user_id='$user_id'
  ");
  $_SESSION['flash'] = ['success', 'Kegiatan dihapus'];
  header("Location: kegiatan.php");
  exit;
}

$data = mysqli_query($koneksi, "
  SELECT * FROM kegiatan 
  WHERE user_id='$user_id' 
  ORDER BY tanggal DESC
");
?>
<!DOCTYPE html>
<html lang="id">

<head>
  <meta charset="UTF-8">
  <title>Kegiatan Harian</title>
  <meta name="viewport" content="width=device-width, initial-scale=1">

  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
  <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons/font/bootstrap-icons.css" rel="stylesheet">
  <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600&display=swap" rel="stylesheet">
  <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

  <style>
    body {
      background: #f0f2f5;
      font-family: 'Poppins', sans-serif
    }

    .card {
      border-radius: 14px;
      box-shadow: 0 6px 18px rgba(0, 0, 0, .05)
    }

    .card-header {
      background: #fff;
      font-weight: 600
    }

    .btn-main {
      background: #343a40;
      color: #fff;
      border-radius: 8px
    }

    .btn-main:hover {
      background: #23272b;
      color: #fff
    }

    .table thead {
      background: #e9ecef
    }

    .badge-soft {
      background: #e7f1ff;
      color: #0d6efd;
      font-weight: 600
    }

    .img-thumb {
      width: 48px;
      height: 48px;
      object-fit: cover;
      border-radius: 6px
    }
  </style>
</head>

<body>
  <div class="container my-5">

    <!-- HEADER -->
    <div class="d-flex justify-content-between align-items-center mb-4">
      <div>
        <h4 class="fw-bold mb-0">Kegiatan Harian</h4>
        <small class="text-muted">Catatan aktivitas peserta</small>
      </div>
      <a href="dashboard.php" class="btn btn-main">
        <i class="bi bi-arrow-left"></i> Kembali
      </a>
    </div>

    <!-- FORM -->
    <div class="card mb-4">
      <div class="card-body p-4">
        <h6 class="fw-bold mb-3"><i class="bi bi-pencil-square me-1"></i> Input Kegiatan</h6>

        <form method="POST" enctype="multipart/form-data" class="row g-3">
          <div class="col-md-6">
            <label>Tanggal</label>
            <input type="date" name="tanggal" class="form-control" required>
          </div>
          <div class="col-md-6">
            <label>Unit Kerja</label>
            <input type="text" name="unit_kerja" class="form-control" required>
          </div>
          <div class="col-12">
            <label>Deskripsi</label>
            <textarea name="deskripsi" class="form-control" rows="3" required></textarea>
          </div>
          <div class="col-12">
            <label>Catatan</label>
            <input type="text" name="catatan" class="form-control">
          </div>
          <div class="col-12">
            <label>Foto</label>
            <input type="file" name="foto" class="form-control">
          </div>
          <div class="col-12 text-end">
            <button name="simpan" class="btn btn-main px-4">
              <i class="bi bi-save"></i> Simpan
            </button>
          </div>
        </form>
      </div>
    </div>

    <!-- RIWAYAT -->
    <div class="card">
      <div class="card-body p-4">
        <h6 class="fw-bold mb-3"><i class="bi bi-clock-history me-1"></i> Riwayat Kegiatan</h6>

        <div class="table-responsive">
          <table class="table table-bordered align-middle text-center">
            <thead>
              <tr>
                <th>Tanggal</th>
                <th>Unit</th>
                <th>Deskripsi</th>
                <th>Foto</th>
                <th>Aksi</th>
              </tr>
            </thead>
            <tbody>
              <?php while ($r = mysqli_fetch_assoc($data)): ?>
                <tr>
                  <td>
                    <b><?= date('d/m/Y', strtotime($r['tanggal'])) ?></b><br>
                    <small class="text-muted"><?= $r['hari'] ?></small>
                  </td>
                  <td><span class="badge badge-soft"><?= $r['unit_kerja'] ?></span></td>
                  <td class="text-start"><?= $r['deskripsi_kegiatan'] ?></td>
                  <td>
                    <?php if ($r['foto']): ?>
                      <img src="../uploads/kegiatan/<?= $r['foto'] ?>" class="img-thumb">
                    <?php else: ?><span class="text-muted">-</span><?php endif; ?>
                  </td>
                  <td>
                    <div class="d-flex gap-1 justify-content-center">
                      <button class="btn btn-sm btn-outline-primary editBtn"
                        data-id="<?= $r['id'] ?>"
                        data-tanggal="<?= $r['tanggal'] ?>"
                        data-unit="<?= $r['unit_kerja'] ?>"
                        data-desk="<?= $r['deskripsi_kegiatan'] ?>"
                        data-cat="<?= $r['catatan'] ?>"
                        data-bs-toggle="modal" data-bs-target="#editModal">
                        <i class="bi bi-pencil"></i>
                      </button>
                      <a href="?hapus=<?= $r['id'] ?>" class="btn btn-sm btn-outline-danger"
                        onclick="return confirm('Hapus data?')">
                        <i class="bi bi-trash"></i>
                      </a>
                    </div>
                  </td>
                </tr>
              <?php endwhile; ?>
            </tbody>
          </table>
        </div>
      </div>
    </div>

  </div>

  <!-- MODAL EDIT -->
  <div class="modal fade" id="editModal">
    <div class="modal-dialog modal-dialog-centered">
      <div class="modal-content">
        <form method="POST" enctype="multipart/form-data">
          <div class="modal-header bg-dark text-white">
            <h6 class="modal-title">Edit Kegiatan</h6>
            <button class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
          </div>
          <div class="modal-body">
            <input type="hidden" name="id_edit" id="edit_id">
            <label>Tanggal</label>
            <input type="date" name="tanggal_edit" id="edit_tanggal" class="form-control mb-2">
            <label>Unit Kerja</label>
            <input type="text" name="unit_kerja" id="edit_unit" class="form-control mb-2">
            <label>Deskripsi</label>
            <textarea name="deskripsi" id="edit_desk" class="form-control mb-2"></textarea>
            <label>Catatan</label>
            <input type="text" name="catatan" id="edit_cat" class="form-control mb-2">
            <label>Ganti Foto</label>
            <input type="file" name="foto_edit" class="form-control">
          </div>
          <div class="modal-footer">
            <button name="update" class="btn btn-main px-4">Simpan</button>
          </div>
        </form>
      </div>
    </div>
  </div>

  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
  <script>
    document.querySelectorAll('.editBtn').forEach(b => {
      b.onclick = () => {
        edit_id.value = b.dataset.id;
        edit_tanggal.value = b.dataset.tanggal;
        edit_unit.value = b.dataset.unit;
        edit_desk.value = b.dataset.desk;
        edit_cat.value = b.dataset.cat;
      }
    });
  </script>

  <?php if ($flash): ?>
    <script>
      Swal.fire({
        icon: '<?= $flash[0] ?>',
        title: '<?= $flash[1] ?>',
        timer: 1800,
        showConfirmButton: false
      });
    </script>
  <?php endif; ?>

</body>

</html>