<?php
session_start();
include '../config/koneksi.php';

if ($_SESSION['role'] != 'peserta') {
  header("Location: ../index.php");
  exit;
}

$user_id = $_SESSION['id'];

// Tambah / Edit
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
  $aksi = $_POST['aksi'] ?? '';
  $tanggal = $_POST['tanggal'] ?? '';
  $uraian  = $_POST['uraian'] ?? '';
  $foto = null;

  if (!empty($_FILES['foto']['name'])) {
    $uploadDir = "../uploads/bimbingan/";
    if (!is_dir($uploadDir)) mkdir($uploadDir, 0777, true);
    $fileName = time() . "_" . basename($_FILES['foto']['name']);
    $targetPath = $uploadDir . $fileName;
    if (move_uploaded_file($_FILES['foto']['tmp_name'], $targetPath)) $foto = $fileName;
  }

  if ($aksi == 'tambah') {
    $stmt = $koneksi->prepare("INSERT INTO bimbingan (user_id, tanggal, uraian, foto) VALUES (?, ?, ?, ?)");
    $stmt->bind_param("isss", $user_id, $tanggal, $uraian, $foto);
    $stmt->execute();
    $_SESSION['flash_msg'] = "✅ Bimbingan berhasil ditambahkan!";
  } elseif ($aksi == 'edit') {
    $id = intval($_POST['id']);
    if ($foto) {
      $stmt = $koneksi->prepare("UPDATE bimbingan SET tanggal=?, uraian=?, foto=? WHERE id=? AND user_id=?");
      $stmt->bind_param("sssii", $tanggal, $uraian, $foto, $id, $user_id);
    } else {
      $stmt = $koneksi->prepare("UPDATE bimbingan SET tanggal=?, uraian=? WHERE id=? AND user_id=?");
      $stmt->bind_param("ssii", $tanggal, $uraian, $id, $user_id);
    }
    $stmt->execute();
    $_SESSION['flash_msg'] = "✏️ Bimbingan berhasil diperbarui!";
  }
  header("Location: bimbingan.php");
  exit;
}

// Hapus
if (isset($_GET['hapus'])) {
  $id = intval($_GET['hapus']);
  $stmt = $koneksi->prepare("DELETE FROM bimbingan WHERE id=? AND user_id=?");
  $stmt->bind_param("ii", $id, $user_id);
  $stmt->execute();
  $_SESSION['flash_msg'] = "🗑️ Data bimbingan dihapus!";
  header("Location: bimbingan.php");
  exit;
}

// Ambil data
$stmt = $koneksi->prepare("SELECT * FROM bimbingan WHERE user_id=? ORDER BY tanggal DESC");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$bimbingan = $stmt->get_result();
$stmt->close();
?>
<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="UTF-8">
<title>Bimbingan</title>
<meta name="viewport" content="width=device-width, initial-scale=1">
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
<link href="https://cdn.jsdelivr.net/npm/bootstrap-icons/font/bootstrap-icons.css" rel="stylesheet">
<link href="https://cdn.jsdelivr.net/npm/flatpickr/dist/flatpickr.min.css" rel="stylesheet">
<link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700&display=swap" rel="stylesheet">
<link rel="icon" type="image/png" href="pngwing.com (1).png">

<style>
body{
  background:#f0f2f5;
  font-family:'Poppins',sans-serif;
  font-size:14px;
}
.page-header{
  display:flex;
  justify-content:space-between;
  align-items:center;
  margin-bottom:24px;
}
.page-header h4{
  font-weight:600;
  margin:0;
}
.page-header small{color:#6c757d}
.card{
  border-radius:14px;
  border:none;
  box-shadow:0 6px 18px rgba(0,0,0,.06);
}
.card-item{
  border-radius:12px;
  border:1px solid #e5e7eb;
  padding:16px;
  background:#fff;
}
.card-item:not(:last-child){margin-bottom:16px}
.badge-date{
  background:#e9ecef;
  color:#212529;
  border-radius:20px;
  padding:4px 10px;
  font-size:12px;
}
.badge-day{
  background:#dee2e6;
  color:#495057;
  border-radius:20px;
  padding:4px 10px;
  font-size:12px;
  margin-left:6px;
}
.btn-main{
  background:#212529;
  color:#fff;
  border-radius:10px;
  padding:8px 18px;
  font-weight:500;
}
.btn-main:hover{background:#000;color:#fff}
/* FOTO DI CARD */
.img-preview{
  width:100%;
  max-height:180px;      /* batas tinggi */
  object-fit:cover;
  border-radius:10px;
  margin-top:12px;
  border:1px solid #dee2e6;
}

/* PREVIEW DI MODAL */
#previewImage{
  display:none;
  width:100%;
  max-height:140px;      /* LEBIH KECIL */
  object-fit:contain;   /* tidak terpotong */
  border-radius:8px;
  border:1px solid #dee2e6;
  margin-top:8px;
}

/* KHUSUS HP */
@media (max-width:576px){
  .img-preview{max-height:140px;}
  #previewImage{max-height:110px;}
}


.action-btn{
  width:32px;height:32px;
  display:flex;
  align-items:center;
  justify-content:center;
}
    @media (max-width: 576px){
  #previewImage{
    max-height:110px;
  }
}

</style>
</head>
<body>

<div class="container my-4">

  <div class="page-header">
    <div>
      <h4><i class="bi bi-journal-check me-1"></i> Bimbingan PKL</h4>
      <small>Catatan bimbingan peserta</small>
    </div>
    <div class="d-flex gap-3">
  <a href="dashboard.php" class="btn btn-outline-secondary btn-sm px-3">
    <i class="bi bi-arrow-left me-1"></i> Kembali
  </a>
  <button class="btn btn-main btn-sm px-3" data-bs-toggle="modal" data-bs-target="#modalTambah">
    <i class="bi bi-plus-circle me-1"></i> Tambah
  </button>
</div>
  </div>

  <div class="row">
    <?php if ($bimbingan->num_rows > 0): ?>
<?php
$hari=["Minggu","Senin","Selasa","Rabu","Kamis","Jumat","Sabtu"];
while($row=$bimbingan->fetch_assoc()):
$d=new DateTime($row['tanggal']);
?>
<div class="card-item">
  <div class="d-flex justify-content-between align-items-start">
    <div>
      <h6 class="fw-semibold mb-1"><?= htmlspecialchars($row['uraian']) ?></h6>
      <span class="badge-date">
        <i class="bi bi-calendar-event"></i>
        <?= $d->format('d M Y') ?>
      </span>
      <span class="badge-day">
        <?= $hari[$d->format('w')] ?>
      </span>
    </div>
    <div class="d-flex gap-1">
  <button class="btn btn-outline-primary btn-sm action-btn editBtn"
    data-id="<?= $row['id'] ?>"
    data-tanggal="<?= date('Y-m-d', strtotime($row['tanggal'])) ?>"
    data-uraian="<?= htmlspecialchars($row['uraian']) ?>"
    data-foto="<?= $row['foto'] ?>">
    <i class="bi bi-pencil"></i>
  </button>

  <button class="btn btn-outline-danger btn-sm action-btn deleteBtn"
    data-id="<?= $row['id'] ?>">
    <i class="bi bi-trash"></i>
  </button>
</div>

  </div>

  <?php if($row['foto']): ?>
    <img src="../uploads/bimbingan/<?= $row['foto'] ?>" class="img-preview">
  <?php endif; ?>
</div>
<?php endwhile; ?>
<?php else: ?>
<p class="text-center text-muted mt-5">Belum ada catatan bimbingan.</p>
<?php endif; ?>
  </div>
</div>

<!-- Modal Tambah/Edit -->
<div class="modal fade" id="modalTambah" tabindex="-1">
  <div class="modal-dialog modal-dialog-centered">
    <div class="modal-content border-0 shadow-sm">
      <div class="modal-header bg-white border-bottom">
        <h5 class="modal-title fw-bold" id="modalTitle">Tambah Bimbingan</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
      </div>
      <form method="POST" enctype="multipart/form-data" id="formBimbingan">
        <div class="modal-body p-4">
          <input type="hidden" name="id" id="bimbingan_id">
          <input type="hidden" name="aksi" id="aksi" value="tambah">
          <div class="mb-3">
  <label class="form-label fw-semibold">Tanggal</label>
  <input type="text" name="tanggal" id="tanggal" class="form-control datepicker" placeholder="YYYY-MM-DD" required>
  <small id="hariOutput" class="text-muted fst-italic"></small>
</div>
          <div class="mb-3">
            <label class="form-label fw-semibold">Uraian Kegiatan</label>
            <textarea name="uraian" id="uraian" class="form-control" rows="3" required></textarea>
          </div>
          <div class="mb-3">
            <label class="form-label fw-semibold">Foto (opsional)</label>
            <input type="file" name="foto" id="fotoInput" class="form-control" accept="image/*">
            <img id="previewImage" src="#" alt="Preview Foto">
          </div>
        </div>
        <div class="modal-footer bg-light border-0">
          <button type="submit" class="btn-main">Simpan</button>
        </div>
      </form>
    </div>
  </div>
</div>

<!-- Modal Hapus -->
<div class="modal fade" id="modalHapus" tabindex="-1">
  <div class="modal-dialog modal-dialog-centered">
    <div class="modal-content border-0 shadow-sm">
      <div class="modal-body text-center p-4">
        <i class="bi bi-exclamation-triangle text-danger display-4 mb-3"></i>
        <h5 class="fw-bold mb-2">Hapus Data Bimbingan?</h5>
        <p class="text-muted mb-4">Data yang dihapus tidak dapat dikembalikan.</p>
        <div class="d-flex justify-content-center gap-3">
          <button type="button" class="btn btn-secondary px-4" data-bs-dismiss="modal">Batal</button>
          <a id="confirmDelete" href="#" class="btn btn-danger px-4">Hapus</a>
        </div>
      </div>
    </div>
  </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/flatpickr"></script>
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<script>
document.addEventListener("DOMContentLoaded", function(){

  // Flatpickr
  const tanggalInput = document.getElementById("tanggal");
  flatpickr(tanggalInput, {
    dateFormat: "Y-m-d",
    onChange: function(selectedDates){
      if(selectedDates.length > 0){
        const date = selectedDates[0];
        document.getElementById("hariOutput").textContent = "Hari: " + hariList[date.getDay()];
      } else {
        document.getElementById("hariOutput").textContent = "";
      }
    }
  });

  // Hari list
  const hariList = ["Minggu","Senin","Selasa","Rabu","Kamis","Jumat","Sabtu"];

  // Preview foto
  const fotoInput = document.getElementById('fotoInput');
  const previewImage = document.getElementById('previewImage');

  fotoInput.addEventListener('change', function(){
    const file = this.files[0];
    if(!file){
      previewImage.style.display = 'none';
      return;
    }
    const reader = new FileReader();
    reader.onload = function(e){
      previewImage.src = e.target.result;
      previewImage.style.display = 'block';
    }
    reader.readAsDataURL(file);
  });

  // Edit data
  document.querySelectorAll(".editBtn").forEach(btn => {
    btn.addEventListener("click", function(){
      const modal = new bootstrap.Modal(document.getElementById("modalTambah"));

      document.getElementById("modalTitle").innerText = "Edit Bimbingan";
      document.getElementById("aksi").value = "edit";
      document.getElementById("bimbingan_id").value = this.dataset.id;
      document.getElementById("uraian").value = this.dataset.uraian;

      // Set tanggal di Flatpickr
      tanggalInput._flatpickr.setDate(this.dataset.tanggal.split(' ')[0]);

      // Preview foto jika ada
      if(this.dataset.foto){
        previewImage.src = "../uploads/bimbingan/" + this.dataset.foto;
        previewImage.style.display = 'block';
      } else {
        previewImage.style.display = 'none';
      }

      // Tampilkan hari otomatis
      const date = new Date(this.dataset.tanggal);
      document.getElementById("hariOutput").textContent = "Hari: " + hariList[date.getDay()];

      modal.show();
    });
  });

  // Modal hapus
  document.querySelectorAll('.deleteBtn').forEach(btn => {
    btn.addEventListener('click', function(){
      const id = this.dataset.id;
      document.getElementById('confirmDelete').setAttribute('href','?hapus=' + id);
      new bootstrap.Modal(document.getElementById('modalHapus')).show();
    });
  });

  // Reset form saat modal ditutup
  document.getElementById("modalTambah").addEventListener("hidden.bs.modal", function(){
    document.getElementById("formBimbingan").reset();
    document.getElementById("aksi").value = "tambah";
    document.getElementById("modalTitle").innerText = "Tambah Bimbingan";
    previewImage.style.display = 'none';
    document.getElementById("hariOutput").textContent = "";
  });

  // Toast alert
  <?php if(isset($_SESSION['flash_msg'])): ?>
  Swal.fire({
    toast: true,
    position: 'top-end',
    icon: 'success',
    title: '<?= $_SESSION['flash_msg'] ?>',
    showConfirmButton: false,
    timer: 1800
  });
  <?php unset($_SESSION['flash_msg']); endif; ?>

});
</script>

</body>
</html>
