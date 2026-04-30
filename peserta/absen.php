<?php
ini_set('display_errors', 1);
error_reporting(E_ALL);

session_start();
include '../config/koneksi.php';
date_default_timezone_set('Asia/Jakarta');

/* ================= HELPERS ================= */
function flash($msg, $icon = 'success') {
    $_SESSION['flash_msg']  = $msg;
    $_SESSION['flash_icon'] = $icon;
}

/* ================= ABSENSI VIA TOKEN / QR ================= */
$token = $_POST['token_manual'] ?? $_POST['token_qr'] ?? null;
$user_id = $_SESSION['id'] ?? null;

if ($token) {
    $stmt = $koneksi->prepare("SELECT id FROM users WHERE token=? LIMIT 1");
    $stmt->bind_param("s", $token);
    $stmt->execute();
    $user = $stmt->get_result()->fetch_assoc();
    $stmt->close();

    if (!$user) {
        flash("Token absensi tidak valid!", "error");
        header("Location: absen.php");
        exit;
    }

    $user_id = (int)$user['id'];
    $_SESSION['id']   = $user_id;
    $_SESSION['role'] = 'peserta';

    // Cek double absen
    $tanggal = date("Y-m-d");
    $cek = $koneksi->prepare("SELECT id FROM absensi WHERE user_id=? AND tanggal=? LIMIT 1");
    $cek->bind_param("is", $user_id, $tanggal);
    $cek->execute();
    $cek->store_result();
    if ($cek->num_rows === 0) {
        // Insert QR absensi otomatis
        $hariMap = ['Sunday'=>'Minggu','Monday'=>'Senin','Tuesday'=>'Selasa','Wednesday'=>'Rabu',
                    'Thursday'=>'Kamis','Friday'=>'Jumat','Saturday'=>'Sabtu'];
        $hari = $hariMap[date('l')];
        $jam_masuk = date("H:i:s");

        $stmtInsert = $koneksi->prepare(
            "INSERT INTO absensi (user_id, tanggal, hari, jam_masuk, status) VALUES (?, ?, ?, ?, 'Masuk')"
        );
        $stmtInsert->bind_param("isss", $user_id, $tanggal, $hari, $jam_masuk);
        $stmtInsert->execute();
        $stmtInsert->close();

        flash("Absensi QR berhasil!", "success");
    } else {
        flash("Anda sudah melakukan absensi hari ini!", "warning");
    }
    $cek->close();
    header("Location: absen.php");
    exit;
}

/* ================= LOGIN CHECK ================= */
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'peserta') {
    header("Location: ../index.php");
    exit;
}

$user_id = (int)$_SESSION['id'];

/* ================= EDIT ABSENSI ================= */
if ($_SERVER['REQUEST_METHOD'] === 'POST' && !empty($_POST['edit_id'])) {
    $edit_id = (int)$_POST['edit_id'];
    $jam_istirahat1 = $_POST['jam_istirahat1'] ?: null;
    $jam_istirahat2 = $_POST['jam_istirahat2'] ?: null;
    $keterangan     = $_POST['keterangan'] ?: null;

    $stmtEdit = $koneksi->prepare(
        "UPDATE absensi 
         SET jam_istirahat1=?, jam_istirahat2=?, keterangan=? 
         WHERE id=? AND user_id=?"
    );
    $stmtEdit->bind_param("sssii", $jam_istirahat1, $jam_istirahat2, $keterangan, $edit_id, $user_id);

    if ($stmtEdit->execute()) {
        flash("Data absensi berhasil diperbarui!", "success");
    } else {
        flash("Gagal memperbarui absensi: ".$stmtEdit->error, "error");
    }
    $stmtEdit->close();
    header("Location: absen.php");
    exit;
}

/* ================= ABSENSI MANUAL ================= */
if ($_SERVER['REQUEST_METHOD'] === 'POST' && empty($_POST['edit_id'])) {

    // Ambil tanggal (jika kosong = hari ini)
    $tanggalInput = (!empty($_POST['tanggal']))
        ? $_POST['tanggal']
        : date('Y-m-d');

    // Validasi format tanggal
    $d = DateTime::createFromFormat('Y-m-d', $tanggalInput);
    if (!$d || $d->format('Y-m-d') !== $tanggalInput) {
        flash("Format tanggal tidak valid!", "error");
        header("Location: absen.php");
        exit;
    }
    $tanggal = $d->format('Y-m-d');

    // Mapping hari
    $hariMap = [
        'Sunday'    => 'Minggu',
        'Monday'    => 'Senin',
        'Tuesday'   => 'Selasa',
        'Wednesday' => 'Rabu',
        'Thursday'  => 'Kamis',
        'Friday'    => 'Jumat',
        'Saturday'  => 'Sabtu'
    ];
    $hari = $hariMap[date('l', strtotime($tanggal))];

    // Cek double absen
    $cek = $koneksi->prepare("SELECT id FROM absensi WHERE user_id=? AND tanggal=?");
    $cek->bind_param("is", $user_id, $tanggal);
    $cek->execute();
    $cek->store_result();
    if ($cek->num_rows > 0) {
        $cek->close();
        flash("Anda sudah absen pada tanggal ini!", "warning");
        header("Location: absen.php");
        exit;
    }
    $cek->close();

    // Ambil data form
    $status           = $_POST['status'] ?? 'Masuk';
    $keterangan       = $_POST['keterangan'] ?? null;
    $jam_masuk        = $_POST['jam_masuk'] ?? null;
    $jam_istirahat1   = $_POST['jam_istirahat1'] ?? null;
    $jam_istirahat2   = $_POST['jam_istirahat2'] ?? null;
    $jam_pulang       = $_POST['jam_pulang'] ?? null;

    // Surat keterangan
    $suratNama = null;
    if ($status === 'Tidak Masuk' && !empty($_FILES['surat']['name'])) {
        $ext = strtolower(pathinfo($_FILES['surat']['name'], PATHINFO_EXTENSION));
        $suratNama = 'SURAT_'.$user_id.'_'.time().'.'.$ext;
        if (!is_dir('../uploads/surat')) {
            mkdir('../uploads/surat', 0777, true);
        }
        move_uploaded_file($_FILES['surat']['tmp_name'], '../uploads/surat/'.$suratNama);
    }

    // Simpan absensi
    $stmt = $koneksi->prepare(
        "INSERT INTO absensi 
        (user_id, tanggal, hari, status, keterangan, jam_masuk, jam_istirahat1, jam_istirahat2, jam_pulang, surat_keterangan)
        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)"
    );
    $stmt->bind_param(
        "isssssssss",
        $user_id, $tanggal, $hari, $status, $keterangan,
        $jam_masuk, $jam_istirahat1, $jam_istirahat2,
        $jam_pulang, $suratNama
    );
    $stmt->execute();
    $stmt->close();

    flash("Absen berhasil!", "success");
    header("Location: absen.php");
    exit;
}


/* ================= RIWAYAT ABSENSI ================= */
$history = $koneksi->prepare("SELECT * FROM absensi WHERE user_id=? ORDER BY tanggal DESC");
$history->bind_param("i", $user_id);
$history->execute();
$result_history = $history->get_result();
?>


<!DOCTYPE html>
<html lang="id">

<head>
  <meta charset="UTF-8">
  <title>Absensi Peserta</title>
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
  <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons/font/bootstrap-icons.css" rel="stylesheet">
  <link href="https://cdn.jsdelivr.net/npm/flatpickr/dist/flatpickr.min.css" rel="stylesheet">
  <link href="https://cdn.jsdelivr.net/npm/sweetalert2@11/dist/sweetalert2.min.css" rel="stylesheet">
  <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700&display=swap" rel="stylesheet">
  <script src="https://unpkg.com/html5-qrcode"></script>
  <link rel="icon" type="image/png" href="pngwing.com (1).png">

  <style>
    body {
      background: #f0f2f5;
      font-family: 'Poppins', sans-serif;
    }

    .card {
      border-radius: 12px;
      box-shadow: 0 4px 12px rgba(0, 0, 0, 0.05);
    }

    .btn-main {
      background: #343a40;
      color: #fff;
      border-radius: 8px;
    }

    .btn-main:hover {
      background: #23272b;
      color: #fff;
    }

    .table thead {
      background: #e9ecef;
    }

    #tanggalContainer {
      display: none;
    }

    .form-check-label {
      background: #fff;
      border: 1px solid #ddd;
      transition: all 0.3s ease;
    }

    .form-check-input:checked+.form-check-label {
      border: 2px solid #343a40;
      background: #f1f3f4;
      box-shadow: 0 4px 10px rgba(0, 0, 0, 0.1);
    }
  </style>
</head>

<body>

  <div class="container my-5">
    <div class="d-flex justify-content-between align-items-center mb-4">
      <div>
        <h3 class="fw-bold mb-0">Form Absensi</h3>
        <p class="text-muted mb-0">Silakan isi atau perbarui absensi</p>
      </div>
      <a href="dashboard.php" class="btn btn-main"><i class="bi bi-arrow-left"></i> Kembali</a>
    </div>

    <div class="container my-5">




     <form method="POST" action="absen.php" enctype="multipart/form-data">

  <!-- ===================== ABSENSI QR / TOKEN ===================== -->
  <div class="card mb-4 shadow-sm">
    <div class="card-body p-4">

      <h5 class="fw-bold mb-3 d-flex align-items-center gap-2">
        <i class="bi bi-qr-code-scan text-primary fs-4"></i>
        Absensi via QR / Token
      </h5>

      <div class="border rounded-4 p-4 bg-light shadow-sm">

       <div class="text-center mb-3">
  <span class="badge rounded-pill px-4 py-2 bg-success-subtle text-success">
    Absensi Aktif Hari Ini
  </span>
  <p class="text-muted small mt-2">
    Fitur absen via QR atau Token dapat digunakan setiap hari. <br>
    Jam masuk dan pulang bersifat fleksibel.
  </p>
</div>


        <p class="text-muted small text-center mb-4">
          Pilih metode absensi yang ingin digunakan.
        </p>

        <!-- PILIH METODE -->
        <div class="d-flex justify-content-center mb-4">
          <div class="btn-group" role="group">

            <input type="radio" class="btn-check" name="metode_absen" id="metodeQr" value="qr">
            <label class="btn btn-outline-primary px-4" for="metodeQr">
              <i class="bi bi-qr-code me-1"></i> QR Code
            </label>

            <input type="radio" class="btn-check" name="metode_absen" id="metodeToken" value="token">
            <label class="btn btn-outline-primary px-4" for="metodeToken">
              <i class="bi bi-key me-1"></i> Token
            </label>

          </div>
        </div>

       <!-- QR SECTION -->
<div id="qrSection" class="text-center d-none">
  <div class="border border-dashed rounded-4 p-4 bg-white">
   <div id="qr-reader" style="display:none;"></div>   
    <i class="bi bi-image fs-2 text-primary mb-2 d-block"></i>
    <p class="mb-3 fw-semibold">Scan QR dari Galeri</p>

    <label class="btn btn-primary btn-sm px-4">
      Pilih Gambar
      <!-- Input file asli untuk memilih gambar QR -->
      <input 
        type="file" 
        id="qr-file" 
        name="qr_file" 
        accept="image/*" 
        hidden
      >
    </label>

    <!-- Input hidden untuk menyimpan token hasil decode -->
    <input type="hidden" name="token_qr" id="tokenQrResult">
  </div>
</div>


        <!-- TOKEN SECTION -->
        <div id="tokenSection" class="row justify-content-center d-none">
          <div class="col-md-8">
            <label class="form-label fw-semibold text-secondary">
              Token Absensi
            </label>

            <div class="input-group input-group-lg shadow-sm">
              <span class="input-group-text bg-white">
                <i class="bi bi-key text-primary"></i>
              </span>
              <input
                type="text"
                name="token_manual"
                class="form-control"
                placeholder="Masukkan token absensi"
                autocomplete="off">
            </div>

            <small class="text-muted d-block mt-2 text-center">
              Token dapat diperoleh dari ID Card QR
            </small>
          </div>
        </div>

      </div>
    </div>
  </div>

  <!-- ===================== ABSENSI MANUAL ===================== -->
  <div class="card shadow-sm">
    <div class="card-body p-4">

      <h6 class="fw-bold mb-3 d-flex align-items-center gap-2">
        <i class="bi bi-pencil-square text-secondary"></i>
        Absensi Manual
      </h6>

      <div class="row g-3">

        <!-- Pilihan Hari -->
        <div class="col-12">
          <label class="form-label fw-semibold text-secondary mb-2">
            Pilih Waktu Absensi
          </label>

          <div class="d-flex flex-wrap gap-3">
            <div>
              <input class="form-check-input visually-hidden" type="radio"
                name="pilihan_hari" id="radioHariIni"
                value="hari_ini" checked>

              <label for="radioHariIni"
                class="d-flex align-items-center gap-2 px-3 py-2 rounded-3 border shadow-sm bg-white user-select-none">
                <i class="bi bi-calendar-check text-success fs-5"></i>
                <span class="fw-semibold">
                  Hari Ini
                  <span class="badge bg-light text-dark border ms-2 small">
                    <?= date('d M Y') ?>
                  </span>
                </span>
              </label>
            </div>

            <div>
              <input class="form-check-input visually-hidden" type="radio"
                name="pilihan_hari" id="radioLain" value="lain">

              <label for="radioLain"
                class="d-flex align-items-center gap-2 px-3 py-2 rounded-3 border shadow-sm bg-white user-select-none">
                <i class="bi bi-calendar-event text-primary fs-5"></i>
                <span class="fw-semibold">Pilih Tanggal Lain</span>
              </label>
            </div>
          </div>
        </div>

        <!-- Tanggal -->
        <div class="col-md-6" id="tanggalContainer">
          <label class="form-label fw-semibold text-secondary">Tanggal</label>
          <div class="input-group shadow-sm">
            <span class="input-group-text bg-light border-end-0">
              <i class="bi bi-calendar4-week text-muted"></i>
            </span>
            <input type="date" name="tanggal" id="tanggalField"
              class="form-control border-start-0">
          </div>
        </div>

        <!-- Hari -->
        <div class="col-md-6">
          <label class="form-label fw-semibold text-secondary">Hari</label>
          <input type="text" id="hariField" class="form-control" readonly>
        </div>

        <!-- Status -->
        <div class="col-md-6">
          <label class="form-label">Status Kehadiran</label>
          <select name="status" class="form-select" required>
            <option value="Masuk">Masuk</option>
            <option value="Tidak Masuk">Tidak Masuk</option>
          </select>
        </div>

        <!-- Surat -->
        <div class="col-12 d-none" id="suratContainer">
          <label class="form-label fw-semibold text-muted">
            Surat Keterangan
          </label>

          <div class="border rounded-3 p-3 bg-light">
            <input type="file"
              name="surat"
              id="suratField"
              class="form-control"
              accept=".jpg,.jpeg,.png,.pdf,.doc,.docx">

            <small class="text-muted d-block mt-1">
  Wajib diisi jika status <b>Tidak Masuk</b><br>
  Format file yang didukung: <b>JPG, JPEG, PNG, PDF, DOC, DOCX</b><br>
  Ukuran maksimal file: <b>2 MB</b>
</small>

          </div>
        </div>

        <!-- Jam -->
        <div class="col-md-6">
          <label class="form-label">Jam Masuk</label>
          <input type="text" name="jam_masuk" class="form-control timepicker">
        </div>

        <div class="col-md-6">
          <label class="form-label">Jam Istirahat 1</label>
          <input type="text" name="jam_istirahat1" class="form-control timepicker">
        </div>

        <div class="col-md-6">
          <label class="form-label">Jam Istirahat 2</label>
          <input type="text" name="jam_istirahat2" class="form-control timepicker">
        </div>

        <div class="col-md-6">
          <label class="form-label">Jam Pulang</label>
          <input type="text" name="jam_pulang" class="form-control timepicker">
        </div>

        <!-- Keterangan -->
        <div class="col-12">
          <label class="form-label">Keterangan</label>
          <input type="text" name="keterangan"
            class="form-control"
            placeholder="Libur, Izin, Sakit...">
        </div>

      </div>

      <!-- SUBMIT -->
      <div class="mt-4 text-end">
        <button type="submit" class="btn btn-main px-4 py-2">
          Simpan Absensi
        </button>
      </div>

    </div>
  </div>

</form>

<br>
        <br>

      <!-- RIWAYAT ABSENSI -->
      <div class="card p-4">
        <h5 class="fw-bold mb-3">Riwayat Absensi</h5>
        <div class="table-responsive">
          <table class="table table-bordered align-middle text-center mb-0">
            <thead>
              <tr>
                <th>No</th>
                <th>Tanggal</th>
                <th>Hari</th>
                <th>Status</th>
                <th>Surat Ket</th>
                <th>Istirahat 1</th>
                <th>Istirahat 2</th>
                <th>Pulang</th>
                <th>Keterangan</th>
                <th>Aksi</th>

              </tr>
            </thead>
            <tbody>
              <?php $no = 1; ?>
              <?php while ($row = $result_history->fetch_assoc()): ?>
                <tr>
                  <td><?= $no++ ?></td>
                  <td><?= htmlspecialchars($row['tanggal']) ?></td>
                  <td><?= htmlspecialchars($row['hari']) ?></td>

                  <!-- STATUS -->
                  <td>
                    <?php
                    $badge = 'bg-secondary';
                    if ($row['status'] === 'Masuk') $badge = 'bg-success';
                    elseif ($row['status'] === 'Tidak Masuk') $badge = 'bg-danger';
                    elseif ($row['status'] === 'Izin') $badge = 'bg-warning text-dark';
                    ?>
                    <span class="badge <?= $badge ?>">
                      <?= htmlspecialchars($row['status']) ?>
                    </span>
                  </td>

                  <!-- SURAT -->
                  <td>
                    <?php if (!empty($row['surat_keterangan'])): ?>
                      <a href="../uploads/surat/<?= urlencode($row['surat_keterangan']) ?>"
                        target="_blank"
                        class="btn btn-sm btn-outline-primary">
                        <i class="bi bi-file-earmark-text"></i>
                        Lihat
                      </a>
                    <?php else: ?>
                      <span class="text-muted">-</span>
                    <?php endif; ?>
                  </td>

                  <!-- JAM -->
                  <td><?= $row['jam_istirahat1'] ?: '-' ?></td>
                  <td><?= $row['jam_istirahat2'] ?: '-' ?></td>
                  <td><?= $row['jam_pulang'] ?: '-' ?></td>

                  <!-- KETERANGAN MANUAL -->
                  <td class="text-start">
                    <?= $row['keterangan']
                      ? htmlspecialchars($row['keterangan'])
                      : '<span class="text-muted fst-italic">-</span>' ?>
                  </td>

                  <!-- AKSI -->
                  <td>
                    <button
  type="button"
  class="btn btn-sm btn-warning editBtn"
  data-id="<?= $row['id'] ?>"
  data-tanggal="<?= $row['tanggal'] ?>"
  data-hari="<?= $row['hari'] ?>"
  data-jam_istirahat1="<?= $row['jam_istirahat1'] ?>"
  data-jam_istirahat2="<?= $row['jam_istirahat2'] ?>"
  data-keterangan="<?= htmlspecialchars($row['keterangan'] ?? '', ENT_QUOTES) ?>">
  <i class="bi bi-pencil-square"></i>
</button>

                  </td>
                </tr>
              <?php endwhile; ?>
            </tbody>


          </table>
        </div>
      </div>
    </div>


    <!-- Modal Edit Absensi -->
    <div class="modal fade" id="editModal" tabindex="-1" aria-hidden="true">
      <div class="modal-dialog modal-dialog-centered modal-lg">
        <div class="modal-content rounded-3 shadow">

          <div class="modal-header bg-dark text-white">
            <h5 class="modal-title">Edit Data Absensi</h5>
            <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
          </div>

          <form method="POST" action="absen.php" class="p-4">

            <input type="hidden" name="edit_id" id="edit_id">

            <div class="row">
              <div class="col-md-6 mb-3">
                <label class="form-label fw-semibold">Tanggal</label>
                <input
                  type="text"
                  id="edit_tanggal"
                  class="form-control bg-light"
                  readonly>
              </div>

              <div class="col-md-6 mb-3">
                <label class="form-label fw-semibold">Hari</label>
                <input
                  type="text"
                  id="edit_hari"
                  class="form-control bg-light"
                  readonly>
              </div>
            </div>


            <div class="row">
              <div class="col-md-6 mb-3">
                <label class="form-label">Jam Istirahat 1</label>
                <input type="text" name="jam_istirahat1" id="edit_jam_istirahat1" class="form-control timepicker">
              </div>

              <div class="col-md-6 mb-3">
                <label class="form-label">Jam Istirahat 2</label>
                <input type="text" name="jam_istirahat2" id="edit_jam_istirahat2" class="form-control timepicker">
              </div>
            </div>

            <div class="mb-3">
              <label class="form-label">Keterangan</label>
              <input type="text" name="keterangan" id="edit_keterangan" class="form-control"
                placeholder="Contoh: Mengikuti ketentuan sekolah">
            </div>

            <!-- Box Ketentuan -->
            <div class="border rounded p-3 mb-4 bg-light">
              <label class="form-label fw-semibold mb-2">Ketentuan Perubahan Absensi</label>
              <div style="max-height:120px; overflow-y:auto; font-size:0.9rem;">
                <ul class="mb-0">
                  <li>Perubahan absensi hanya dapat dilakukan oleh pihak berwenang.</li>
                  <li>Data yang diubah harus sesuai dengan kondisi sebenarnya.</li>
                  <li>Kesalahan atau penyalahgunaan data menjadi tanggung jawab pengubah.</li>
                  <li>Perubahan akan tercatat dalam sistem sebagai riwayat.</li>
                </ul>
              </div>
            </div>

            <div class="text-end">
              <button type="submit" class="btn btn-main px-4">
                Simpan Perubahan
              </button>
            </div>

          </form>

        </div>
      </div>
    </div>


    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/flatpickr"></script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
     <script>
document.addEventListener("DOMContentLoaded", function() {

  /* ===============================
     FLATPICKR INIT
  =============================== */
  flatpickr(".timepicker", {
    enableTime: true,
    noCalendar: true,
    dateFormat: "H:i",
    time_24hr: true
  });

  flatpickr(".datepicker", {
    dateFormat: "Y-m-d",
    maxDate: "today"
  });

  /* ===============================
     RADIO TANGGAL
  =============================== */
  const radioHariIni = document.getElementById('radioHariIni');
  const radioLain = document.getElementById('radioLain');
  const tanggalContainer = document.getElementById('tanggalContainer');
  const tanggalField = document.getElementById('tanggalField');
  const hariField = document.getElementById('hariField');
  const hariList = ['Minggu','Senin','Selasa','Rabu','Kamis','Jumat','Sabtu'];

  function updateHariField(dateObj) {
    hariField.value = hariList[dateObj.getDay()];
  }

  function updateTanggalInput() {
    tanggalContainer.style.display = radioLain.checked ? 'block' : 'none';
    if (radioHariIni.checked) {
      tanggalField.value = "";
      updateHariField(new Date());
    }
  }

  if (tanggalField) {
    tanggalField.addEventListener('change', function() {
      if (this.value) {
        const [y,m,d] = this.value.split("-");
        updateHariField(new Date(y,m-1,d));
      }
    });
  }

  radioHariIni?.addEventListener('change', updateTanggalInput);
  radioLain?.addEventListener('change', updateTanggalInput);
  updateTanggalInput();

  /* ===============================
     MODAL EDIT ABSENSI
  =============================== */
  const editModalEl = document.getElementById("editModal");
  const editModal = editModalEl ? new bootstrap.Modal(editModalEl) : null;

  const editId = document.getElementById("edit_id");
  const editHari = document.getElementById("edit_hari");

  const editDatePicker = flatpickr("#edit_tanggal", {
    dateFormat: "Y-m-d",
    maxDate: "today",
    onChange: function(selectedDates, dateStr) {
      if (dateStr && editHari) {
        const [y,m,d] = dateStr.split("-");
        editHari.value = hariList[new Date(y,m-1,d).getDay()];
      }
    }
  });

  document.querySelectorAll(".editBtn").forEach(btn => {
    btn.addEventListener("click", function() {
      editId.value = this.dataset.id || '';
      document.getElementById("edit_jam_istirahat1").value = this.dataset.jam_istirahat1 || '';
      document.getElementById("edit_jam_istirahat2").value = this.dataset.jam_istirahat2 || '';
      document.getElementById("edit_keterangan").value = this.dataset.keterangan || '';

      if (this.dataset.tanggal) {
        editDatePicker.setDate(this.dataset.tanggal, true);
      } else {
        editDatePicker.clear();
        editHari.value = '';
      }

      editModal.show();
    });
  });

  /* ===============================
     QR FILE SCANNER FINAL
  =============================== */
  const qrFileInput = document.getElementById('qr-file');
  const tokenQrInput = document.getElementById('tokenQrResult');
  const tokenPreview = document.createElement('div'); // preview token
  const absenForm = qrFileInput?.closest('form'); 
  let isProcessing = false;

  if (qrFileInput && tokenQrInput && absenForm) {
    qrFileInput.parentNode.appendChild(tokenPreview);
    tokenPreview.style.marginTop = '10px';
    tokenPreview.style.fontWeight = '600';
    tokenPreview.style.color = '#343a40';
    tokenPreview.style.fontSize = '0.95rem';

    qrFileInput.addEventListener('change', async function() {
      const file = this.files[0];
      if (!file || isProcessing) return;
      isProcessing = true;

      Swal.fire({
        title: 'Membaca QR Code...',
        text: 'Mohon tunggu sebentar',
        allowOutsideClick: false,
        didOpen: () => Swal.showLoading()
      });

      try {
        // Buat container sementara untuk HTML5 QR
        const tempDiv = document.createElement('div');
        tempDiv.style.display = 'none';
        tempDiv.id = 'qr-temp-scanner';
        document.body.appendChild(tempDiv);

        const qrScanner = new Html5Qrcode("qr-temp-scanner");

        const decodedText = await qrScanner.scanFile(file, true);
        if (!decodedText) throw new Error("QR tidak terbaca");

        // set token ke input hidden dan tampilkan preview
        tokenQrInput.value = decodedText;
        tokenPreview.textContent = "Token: " + decodedText;

        await qrScanner.clear();
        document.body.removeChild(tempDiv);

       Swal.fire({
  icon: 'success',
  title: 'QR Berhasil Dibaca',
  html: `<p>Token berhasil didapatkan:<br><b>${decodedText}</b></p>`,
  timer: 1500,
  showConfirmButton: false
}).then(() => {
  // isi tanggal & hari otomatis kalau kosong
  if (!tanggalField.value) {
    const today = new Date();
    const yyyy = today.getFullYear();
    const mm = String(today.getMonth()+1).padStart(2,'0');
    const dd = String(today.getDate()).padStart(2,'0');
    tanggalField.value = `${yyyy}-${mm}-${dd}`;
    hariField.value = hariList[today.getDay()];
  }

  absenForm.submit();
});


      } catch (err) {
        Swal.fire({
          icon: 'error',
          title: 'QR Tidak Terbaca',
          text: 'Pastikan gambar jelas, tidak blur, dan berformat PNG/JPG/JPEG'
        });
        tokenPreview.textContent = "";
      } finally {
        isProcessing = false;
        this.value = "";
      }
    });
  }

  /* ===============================
     PILIH METODE ABSEN (QR / TOKEN)
  =============================== */
  const qrSection = document.getElementById('qrSection');
  const tokenSection = document.getElementById('tokenSection');

  document.querySelectorAll('input[name="metode_absen"]').forEach(el => {
    el.addEventListener('change', function() {
      qrSection.classList.add('d-none');
      tokenSection.classList.add('d-none');

      if (this.value === 'qr') qrSection.classList.remove('d-none');
      if (this.value === 'token') tokenSection.classList.remove('d-none');
    });
  });

  /* ===============================
     SURAT KETERANGAN REQUIRED
  =============================== */
  const statusSelect = document.querySelector('select[name="status"]');
  const suratContainer = document.getElementById('suratContainer');
  const suratField = document.getElementById('suratField');

  statusSelect?.addEventListener('change', function() {
    if (this.value === 'Tidak Masuk') {
      suratContainer.classList.remove('d-none');
      suratField.required = true;
    } else {
      suratContainer.classList.add('d-none');
      suratField.required = false;
      suratField.value = '';
    }
  });

  /* ===============================
     NOTIFIKASI FLASH
  =============================== */
  <?php if (isset($_SESSION['flash_msg'])): ?>
    Swal.fire({
      icon: '<?= $_SESSION['flash_icon'] ?>',
      title: '<?= $_SESSION['flash_msg'] ?>',
      confirmButtonText: 'OK'
    });
  <?php unset($_SESSION['flash_msg'], $_SESSION['flash_icon']); endif; ?>

});

</script>



</body>

</html>