<?php
/* ================= DEBUG (DEV ONLY) ================= */
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

session_start();
include 'config/koneksi.php';

/* ================= CAPTCHA ================= */
if (!isset($_SESSION['captcha'])) {
    $a = rand(1, 9);
    $b = rand(1, 9);
    $_SESSION['captcha'] = [
        'q' => "$a + $b",
        'a' => $a + $b
    ];
}

/* ================= PROSES REGISTER ================= */
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['register'])) {

    /* CAPTCHA */
    if ((int)($_POST['captcha'] ?? 0) !== $_SESSION['captcha']['a']) {
        $_SESSION['error'] = "Verifikasi captcha salah.";
        header("Location: register.php");
        exit;
    }
    unset($_SESSION['captcha']);

    /* AMBIL DATA */
    $username  = trim($_POST['username'] ?? '');
    $email     = trim($_POST['email'] ?? '');
    $password  = $_POST['password'] ?? '';
    $nama      = trim($_POST['nama'] ?? '');
    $nis       = trim($_POST['nis'] ?? '');

    $provinsi  = $_POST['provinsi'] ?? '';
    $kabupaten = $_POST['kabupaten'] ?? '';
    $kecamatan = $_POST['kecamatan'] ?? '';
    $desa      = $_POST['desa'] ?? '';

    $kelas_id  = $_POST['kelas_id'] ?? '';

    /* VALIDASI */
    if (
        $username === '' || $email === '' || $password === '' ||
        $nama === '' || $nis === '' ||
        $provinsi === '' || $kabupaten === '' || $kecamatan === '' || $desa === '' ||
        $kelas_id === ''
    ) {
        $_SESSION['error'] = "Semua data wajib diisi.";
        header("Location: register.php");
        exit;
    }

    /* GABUNG ALAMAT */
    $alamat_full = "Desa $desa, Kecamatan $kecamatan, Kabupaten/Kota $kabupaten, Provinsi $provinsi";

    /* CEK USERNAME */
    $cek = $koneksi->prepare("SELECT id FROM users WHERE username=?");
    $cek->bind_param("s", $username);
    $cek->execute();
    $cek->store_result();
    if ($cek->num_rows > 0) {
        $_SESSION['error'] = "Username sudah digunakan.";
        header("Location: register.php");
        exit;
    }
    $cek->close();

    /* CEK EMAIL */
    $cek = $koneksi->prepare("SELECT id FROM users WHERE email=?");
    $cek->bind_param("s", $email);
    $cek->execute();
    $cek->store_result();
    if ($cek->num_rows > 0) {
        $_SESSION['error'] = "Email sudah digunakan.";
        header("Location: register.php");
        exit;
    }
    $cek->close();

    /* SIMPAN */
    $passwordHash = password_hash($password, PASSWORD_BCRYPT);

    $stmt = $koneksi->prepare("
        INSERT INTO users
        (username, email, password, nama, nis, alamat, kelas_id, role)
        VALUES (?, ?, ?, ?, ?, ?, ?, 'peserta')
    ");
    $stmt->bind_param(
        "ssssssi",
        $username,
        $email,
        $passwordHash,
        $nama,
        $nis,
        $alamat_full,
        $kelas_id
    );

    if ($stmt->execute()) {
        $_SESSION['success'] = "Registrasi berhasil. Silakan login.";
    } else {
        $_SESSION['error'] = "Registrasi gagal: " . $stmt->error;
    }

    header("Location: register.php");
    exit;
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="UTF-8">
<title>SIKAPEDU</title>
<meta name="viewport" content="width=device-width, initial-scale=1">
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
<link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css" rel="stylesheet">
<link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700&display=swap" rel="stylesheet">
<link rel="icon" type="image/png" href="../imge/kelasin.png">
<script src="https://cdnjs.cloudflare.com/ajax/libs/jspdf/2.5.1/jspdf.umd.min.js"></script>

<style>
:root{--primary:#0d6efd; --header-blue:#0052cc;}
body{min-height:100vh;font-family:'Poppins',sans-serif;background:url('imge/bg_pc.png') center/cover no-repeat;}
@media(max-width:768px){body{background:url('imge/bg_mobile.png') center/cover no-repeat}}
.overlay{min-height:100vh;background:rgba(0,0,0,.5);display:flex;align-items:center;justify-content:center;padding:15px;}
.card-register{width:100%;max-width:460px;background:#fff;border-radius:22px;box-shadow:0 20px 45px rgba(0,0,0,.3);overflow:hidden;padding:0;}
.step{display:none}.step.active{display:block}
.form-floating>.form-control,.form-floating>textarea,.form-floating>select{height:56px;border-radius:14px;font-size:.9rem;}
.btn{height:52px;border-radius:14px;font-weight:600}
.progress{height:6px;border-radius:10px;margin-bottom:25px}
.agreement-box{background:#f8f9fa;border-left:4px solid var(--primary);border-radius:14px;padding:14px;font-size:.85rem;}
.alert-fixed{
  position: fixed;
  top: 20px;
  left: 50%;
  transform: translateX(-50%);
  z-index: 1050;
  border-radius: 12px;
  padding: 15px 20px;
  display: flex;
  align-items: center;
  gap: 15px;
  font-weight: 500;
  color: #fff;
  min-width: 250px;
  max-width: 400px;
  box-shadow: 0 8px 20px rgba(0,0,0,0.3);
}
.alert-success-toast{background:#28a745;}
.checkmark-wrapper{width:30px;height:30px;position:relative;}
.checkmark-circle{width:30px;height:30px;border-radius:50%;background:#fff;position:relative;}
.checkmark{position:absolute;top:4px;left:6px;width:14px;height:20px;border-left:3px solid #28a745;border-bottom:3px solid #28a745;transform:rotate(-45deg);transform-origin:left top;animation:checkmark 0.5s ease forwards;}
@keyframes checkmark{0%{height:0;width:0;opacity:0;}50%{height:0;width:14px;opacity:1;}100%{height:14px;width:14px;opacity:1;}}
.toast-message{flex:1;position:relative;}
.progress-bar-toast{position:absolute;bottom:0;left:0;height:4px;background:rgba(255,255,255,0.7);width:0%;border-radius:0 0 8px 8px;}
.register-header{background-color: var(--header-blue);padding:20px 25px;display:flex;align-items:center;}
.register-logo{width:140px;height:auto;}
.register-text{margin-left:15px;}
.register-text .register-title{margin:0;font-size:30px;font-weight:700;color:#fff;}
.register-text .register-sub{margin:2px 0 0;font-size:12px;color:#e0e0e0;}
.form-container{padding:30px 25px 35px;}
</style>
</head>
<body>
<div class="overlay">
<div class="card-register">

  <div class="register-header">
    <img src="../imge/kelasin.png" alt="Logo" class="register-logo">
    <div class="register-text">
        <h4 class="register-title">SIKAP</h4>
        <p class="register-sub">Sistem Informasi Kehadiran Akivitas Pembelajaran</p>
    </div>
  </div>

  <div class="form-container">
<h4 class="text-center mb-1">Register Akun</h4>
<p class="text-muted text-center mb-3">Lengkapi data Anda untuk mendaftar</p>

<div class="progress mb-3">
  <div id="progressBar" class="progress-bar bg-primary" style="width:33%"></div>
</div>

<form method="POST" id="registerForm">

<!-- STEP 1 -->
<div class="step active">
  <div class="form-floating mb-3">
    <input type="text" name="username" class="form-control" required>
    <label>Username</label>
  </div>

  <div class="form-floating mb-3">
    <input type="email" name="email" class="form-control" required>
    <label>Email</label>
  </div>

  <div class="form-floating mb-3">
    <input type="password" id="password" name="password" class="form-control" required>
    <label>Password</label>
  </div>
  
  <div class="form-floating mb-4">
    <input type="password" id="confirmPassword" class="form-control" required>
    <label>Konfirmasi Password</label>
  </div>

  <button type="button" class="btn btn-primary w-100" onclick="nextStep()">Lanjut</button>
</div>

<!-- STEP 2 -->
<div class="step">
  <div class="form-floating mb-3">
    <input type="text" name="nama" class="form-control" required>
    <label>Nama Lengkap</label>
  </div>
  <div class="form-floating mb-3">
    <input type="text" name="nis" class="form-control" required>
    <label>NIS</label>
  </div>
 <div class="form-floating mb-3">
  <select id="tingkat" class="form-select" required>
    <option value="">Pilih Tingkat</option>
    <option value="X">X</option>
    <option value="XI">XI</option>
    <option value="XII">XII</option>
  </select>
  <label>Tingkat</label>
</div>

<div class="form-floating mb-3">
  <select id="jurusan" class="form-select" required>
    <option value="">Pilih Jurusan</option>
    <option value="AKL">AKL</option>
    <option value="MPLB">MPLB</option>
    <option value="PPLG">PPLG</option>
    <option value="PM">PM</option>
    <option value="TJKT">TJKT</option>
    <option value="UPL">UPL</option>
  </select>
  <label>Jurusan</label>
</div>
<div class="form-floating mb-4">
  <select name="kelas_id" id="kelas" class="form-select" required>
    <option value="">Pilih Kelas</option>

    <!-- AKL -->
    <option value="1" data-jurusan="AKL">1</option>
    <option value="2" data-jurusan="AKL">2</option>
    <option value="3" data-jurusan="AKL">3</option>  
    <option value="4" data-jurusan="AKL">4</option>  

    <!-- MPLB -->
    <option value="5" data-jurusan="MPLB">1</option>
    <option value="6" data-jurusan="MPLB">2</option>  
    <option value="7" data-jurusan="MPLB">3</option>    

    <!-- PPLG -->
    <option value="8" data-jurusan="PPLG">1</option>
    <option value="9" data-jurusan="PPLG">PPLG 2</option>
    <option value="10" data-jurusan="PPLG">PPLG 3</option>
      
    <!-- TJKT -->
    <option value="11" data-jurusan="TJKT">1</option>
   <option value="12" data-jurusan="TJKT">2</option>
   <option value="13" data-jurusan="TJKT">3</option>   

    <!-- UPL -->
    <option value="14" data-jurusan="UPL">1</option>
    <option value="15" data-jurusan="UPL">2</option>  
    
    <!-- PM -->
    <option value="16" data-jurusan="PM">1</option>
    <option value="17" data-jurusan="PM">2</option>
    <option value="18" data-jurusan="PM">3</option>   
  </select>
  <label>Kelas</label>
</div>
  <button type="button" class="btn btn-outline-secondary w-100 mb-2" onclick="prevStep()">Kembali</button>
  <button type="button" class="btn btn-primary w-100" onclick="nextStep()">Lanjut</button>
</div>
    
<!-- STEP 3 -->
<div class="step">

  <input type="hidden" name="alamat" id="alamatFull">

  <div class="form-floating mb-3">
    <select id="provinsi" name="provinsi" class="form-select" required>
      <option value="">Pilih Provinsi</option>
    </select>
    <label>Provinsi</label>
  </div>

  <div class="form-floating mb-3">
    <select id="kabupaten" name="kabupaten" class="form-select" disabled required>
      <option value="">Pilih Kabupaten / Kota</option>
    </select>
    <label>Kabupaten / Kota</label>
  </div>

  <div class="form-floating mb-3">
    <select id="kecamatan" name="kecamatan" class="form-select" disabled required>
      <option value="">Pilih Kecamatan</option>
    </select>
    <label>Kecamatan</label>
  </div>

  <div class="form-floating mb-3">
    <select name="desa" id="desa" class="form-select" disabled required>
  <option value="">Pilih Desa / Kelurahan</option>
</select>
    <label>Desa / Kelurahan</label>
  </div>

  <div class="form-floating mb-3">
    <textarea id="alamatDetail" class="form-control" style="height:90px" required></textarea>
    <label>Detail Alamat (RT/RW, Jalan, No Rumah)</label>
  </div>


  <div class="agreement-box mb-3 border rounded p-3 bg-light">
    <label class="form-label fw-semibold mb-2">Pernyataan & Ketentuan</label>
    <div class="terms-box border rounded p-2 mb-2" style="max-height:160px;overflow-y:auto;font-size:0.9rem">
      <ol class="mb-0">
        <li>Saya menyatakan seluruh data yang saya isikan benar dan dapat dipertanggungjawabkan.</li>
        <li>Saya memahami kesalahan/pemalsuan data berakibat pembatalan/sanksi.</li>
        <li>Saya bersedia mengikuti aturan sistem.</li>
        <li>Data digunakan untuk administrasi dan evaluasi.</li>
        <li>Dengan mencentang persetujuan, saya setuju seluruh ketentuan di atas.</li>
      </ol>
    </div>
    <div class="form-check">
      <input class="form-check-input" type="checkbox" id="agree" required>
      <label class="form-check-label fw-semibold" for="agree">
        Saya menyetujui seluruh pernyataan dan ketentuan di atas
      </label>
    </div>
  </div>

  <div class="form-floating mb-3">
    <input type="number" name="captcha" class="form-control" required>
    <label>Verifikasi: <?= $_SESSION['captcha']['q']; ?> = ?</label>
  </div>

  <div class="d-grid gap-2">
    <button type="button" class="btn btn-outline-secondary" onclick="prevStep()">Kembali</button>
    <button type="button" class="btn btn-primary" id="previewBtn" disabled>Preview Data</button>
    <button type="submit" name="register" class="btn btn-success" id="submitBtn">Daftar</button>
  </div>
</div>

<div class="text-center mt-3">
    <small class="text-muted">
        Punya akun? <a href="index.php" class="fw-semibold text-decoration-none">Login</a>
    </small>
</div>
</form>

<!-- ALERT ERROR / SUCCESS -->
<?php if (isset($_SESSION['success'])): ?>
<div id="successOverlay" style="
    position:fixed;
    inset:0;
    background:rgba(0,0,0,0.5);
    display:flex;
    justify-content:center;
    align-items:center;
    z-index:9999;
    font-family:'Poppins',sans-serif;
">
  <div style="
      background:#fff;
      padding:40px;
      border-radius:20px;
      min-width:300px;
      max-width:500px;
      text-align:center;
      box-shadow:0 10px 40px rgba(0,0,0,0.25);
  ">
    <!-- CHECK ICON -->
    <svg class="checkmark" viewBox="0 0 52 52" style="width:80px;height:80px;margin:auto;">
      <circle cx="26" cy="26" r="25" fill="none" stroke="#28a745" stroke-width="2"/>
      <path class="checkmark-check"
            d="M14 27l7 7 17-17"
            fill="none"
            stroke="#28a745"
            stroke-width="4"
            stroke-dasharray="48"
            stroke-dashoffset="48"/>
    </svg>

    <h2 style="margin:15px 0 5px;color:#000;">Berhasil!</h2>
    <p style="color:#333;font-size:1rem;">
      <?= htmlspecialchars($_SESSION['success']); ?>
    </p>

    <!-- PROGRESS -->
    <div style="margin-top:20px;background:#e9ecef;border-radius:10px;height:10px;overflow:hidden;">
      <div id="successProgress"
           style="height:100%;width:0%;background:#28a745;"></div>
    </div>
  </div>
</div>

<script>
(function(){
  const overlay = document.getElementById('successOverlay');
  if(!overlay) return;

  const check = overlay.querySelector('.checkmark-check');
  const bar   = document.getElementById('successProgress');

  if(check){
    check.getBoundingClientRect();
    check.style.transition = 'stroke-dashoffset .6s ease';
    check.style.strokeDashoffset = 0;
  }

  let p = 0;
  const timer = setInterval(()=>{
    p++;
    if(bar) bar.style.width = p + '%';
    if(p >= 100){
      clearInterval(timer);
      window.location.href = 'index.php';
    }
  },20);
})();
</script>

<?php unset($_SESSION['success']); endif; ?>
      
<!-- MODAL PREVIEW -->
<div class="modal fade" id="previewModal" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered modal-lg">
    <div class="modal-content rounded-4 shadow">
      <div class="modal-header">
        <h5 class="modal-title">Preview Data Anda</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
      </div>
      <div class="modal-body">
        <ul class="list-group list-group-flush" id="previewList"></ul>
      </div>
      <div class="modal-footer">
        <button class="btn btn-secondary" data-bs-dismiss="modal">Edit</button>
        <button class="btn btn-primary" id="downloadPDF">Download PDF</button>
      </div>
    </div>
  </div>
</div>


<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
<script>
document.addEventListener("DOMContentLoaded", function () {

  /* ================= STEP FORM ================= */
  let step = 0;
  const steps = document.querySelectorAll(".step");
  const progressBar = document.getElementById("progressBar");
  const agree = document.getElementById("agree");
  const previewBtn = document.getElementById("previewBtn");
  const registerForm = document.getElementById("registerForm");
  const previewList = document.getElementById("previewList");

  const previewModalEl = document.getElementById("previewModal");
  const previewModal = previewModalEl ? new bootstrap.Modal(previewModalEl) : null;

  function showStep(){
    steps.forEach(s => s.classList.remove("active"));
    if(steps[step]) steps[step].classList.add("active");
    progressBar.style.width = ((step+1)/steps.length*100) + "%";
  }

  window.nextStep = function(){
    if(step === 0){
      const pass = document.getElementById('password').value;
      const confirm = document.getElementById('confirmPassword').value;
      if(pass !== confirm){
        alert("Password dan konfirmasi tidak sama.");
        return;
      }
    }
    if(step < steps.length - 1){
      step++;
      showStep();
    }
  }

  window.prevStep = function(){
    if(step > 0){
      step--;
      showStep();
    }
  }

  showStep();

  if(agree && previewBtn){
    previewBtn.disabled = !agree.checked;
    agree.addEventListener("change", () => {
      previewBtn.disabled = !agree.checked;
    });
  }

  /* ================= WILAYAH ================= */
const provinsi  = document.getElementById("provinsi");
const kabupaten = document.getElementById("kabupaten");
const kecamatan = document.getElementById("kecamatan");
const desa      = document.getElementById("desa");

if (provinsi) {
  const api = "https://www.emsifa.com/api-wilayah-indonesia/api";

  // ===== PROVINSI =====
  fetch(`${api}/provinces.json`)
    .then(res => res.json())
    .then(data => {
      data.forEach(p => {
        provinsi.innerHTML += `
          <option value="${p.name}" data-id="${p.id}">
            ${p.name}
          </option>`;
      });
    });

  // ===== KABUPATEN =====
  provinsi.addEventListener("change", () => {
    const provId = provinsi.selectedOptions[0].dataset.id;

    kabupaten.disabled = false;
    kabupaten.innerHTML = '<option value="">Pilih Kabupaten / Kota</option>';
    kecamatan.innerHTML = '<option value="">Pilih Kecamatan</option>';
    desa.innerHTML = '<option value="">Pilih Desa / Kelurahan</option>';

    fetch(`${api}/regencies/${provId}.json`)
      .then(res => res.json())
      .then(data => {
        data.forEach(k => {
          kabupaten.innerHTML += `
            <option value="${k.name}" data-id="${k.id}">
              ${k.name}
            </option>`;
        });
      });
  });

  // ===== KECAMATAN =====
  kabupaten.addEventListener("change", () => {
    const kabId = kabupaten.selectedOptions[0].dataset.id;

    kecamatan.disabled = false;
    kecamatan.innerHTML = '<option value="">Pilih Kecamatan</option>';
    desa.innerHTML = '<option value="">Pilih Desa / Kelurahan</option>';

    fetch(`${api}/districts/${kabId}.json`)
      .then(res => res.json())
      .then(data => {
        data.forEach(kc => {
          kecamatan.innerHTML += `
            <option value="${kc.name}" data-id="${kc.id}">
              ${kc.name}
            </option>`;
        });
      });
  });

  // ===== DESA =====
  kecamatan.addEventListener("change", () => {
    const kecId = kecamatan.selectedOptions[0].dataset.id;

    desa.disabled = false;
    desa.innerHTML = '<option value="">Pilih Desa / Kelurahan</option>';

    fetch(`${api}/villages/${kecId}.json`)
      .then(res => res.json())
      .then(data => {
        data.forEach(d => {
          desa.innerHTML += `
            <option value="${d.name}">
              ${d.name}
            </option>`;
        });
      });
  });
}


  /* ================= FILTER KELAS ================= */
  const jurusan = document.getElementById("jurusan");
  const kelas = document.getElementById("kelas");

  if (jurusan && kelas) {
    const allOptions = Array.from(kelas.options);

    jurusan.addEventListener("change", function () {
      const selectedJurusan = this.value;

      kelas.innerHTML = '<option value="">Pilih Kelas</option>';
      kelas.disabled = true;

      if (!selectedJurusan) return;

      allOptions.forEach(opt => {
        if (opt.dataset.jurusan === selectedJurusan) {
          kelas.appendChild(opt);
        }
      });

      kelas.disabled = false;
    });
  }

  /* ================= BANGUN ALAMAT (WAJIB) ================= */
  function buildAlamat() {
  const p  = provinsi?.value || '';
  const k  = kabupaten?.value || '';
  const kc = kecamatan?.value || '';
  const d  = desa?.value || '';
  const detail = document.getElementById("alamatDetail")?.value || '';

  document.getElementById("alamatFull").value =
    `${detail}, ${d}, ${kc}, ${k}, ${p}`;
}


  /* ================= PREVIEW ================= */
  if (previewBtn && previewModal) {
    previewBtn.addEventListener("click", function () {

      buildAlamat(); // 🔴 WAJIB

      previewList.innerHTML = '';
      const formData = new FormData(registerForm);

      for (const [key, value] of formData.entries()) {
        if (key !== 'password' && key !== 'register') {
          const li = document.createElement('li');
          li.className = 'list-group-item';
          li.textContent = key.toUpperCase() + " : " + value;
          previewList.appendChild(li);
        }
      }

      previewModal.show();
    });
  }

  /* ================= SUBMIT FORM ================= */
  registerForm.addEventListener("submit", function (e) {

    buildAlamat();           // 🔴 WAJIB
    kelas.disabled = false;  // 🔴 WAJIB

    if (!kelas.value) {
      alert("Silakan pilih kelas terlebih dahulu.");
      e.preventDefault();
      return;
    }

  });

});
</script>

</body>
</html>
