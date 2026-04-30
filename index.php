<?php session_start(); ?>
<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="UTF-8">
<title>SIKAPEDU</title>
<meta name="viewport" content="width=device-width, initial-scale=1">

<!-- Bootstrap & Icons -->
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
<link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css" rel="stylesheet">
<link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700&display=swap" rel="stylesheet">
<link rel="icon" type="image/png" href="../imge/kelasin.png">    

<style>
:root{
    --primary:#0d6efd;
    --header-blue:#0052cc;
}

body{
    min-height:100vh;
    font-family:'Poppins',sans-serif;
    background:url('imge/bg_pc.png') center/cover no-repeat;
}

/* BG MOBILE */
@media (max-width:768px){
    body{
        background:url('imge/bg_mobile.png') center/cover no-repeat;
    }
}

.overlay{
    min-height:100vh;
    background:rgba(0,0,0,.45);
    display:flex;
    flex-direction:column;
    align-items:center;
    justify-content:center;
    padding:15px;
}

.login-card{
    width:100%;
    max-width:420px;
    background:#fff;
    border-radius:22px;
    box-shadow:0 20px 40px rgba(0,0,0,.25);
    animation:fadeUp .6s ease;
    overflow:hidden;
    padding:0; /* padding di dalam form dan header */
}

@keyframes fadeUp{
    from{opacity:0; transform:translateY(25px)}
    to{opacity:1; transform:translateY(0)}
}

/* ===== HEADER BIRU ===== */
.login-header-container{
    background-color: var(--header-blue);
    padding:20px 25px;
    display:flex;
    align-items:center;
}

.login-logo{
    width:140px;
    height:auto;
}

.login-text{
    margin-left:9px;
}

.login-text .login-title{
    margin:0;
    font-size:30px;
    font-weight:700;
    color:#fff;
}

.login-text .login-sub{
    margin:1px 0 0;
    font-size:12px;
    color:#e0e0e0;
}

/* ===== FORM ===== */
.form-floating>.form-control{
    height:56px;
    padding-left:46px;
    border-radius:14px;
    font-size:.92rem;
}

.form-floating>label{
    padding-left:46px;
    font-size:.85rem;
}

.input-icon{
    position:absolute;
    top:50%;
    left:15px;
    transform:translateY(-50%);
    color:#6c757d;
    z-index:5;
}

.form-control:focus{
    border-color:var(--primary);
    box-shadow:0 0 0 .15rem rgba(13,110,253,.25);
}

.toggle-password{
    position:absolute;
    top:50%;
    right:15px;
    transform:translateY(-50%);
    cursor:pointer;
    color:#6c757d;
    z-index:5;
}

/* NOTE */
.note{
    font-size:.82rem;
    background:#f8f9fa;
    padding:10px 14px;
    border-left:4px solid #ffc107;
    border-radius:10px;
    margin-bottom:18px;
    color:#495057;
}

/* BUTTON */
.btn-primary{
    height:52px;
    border-radius:14px;
    font-weight:600;
    letter-spacing:.3px;
}

/* FOOTER */
.footer-credit{
    margin-top:18px;
    font-size:.75rem;
    color:rgba(255,255,255,.75);
    letter-spacing:.4px;
}

/* FORM CONTAINER */
.form-container{
    padding:30px 25px 35px;
}

/* Efek hover button */
button:hover {
  opacity: 0.9;
  transition: 0.3s;
}
.form-text {
  margin-top: 4px;
  padding-left: 2px;
}
    
</style>
</head>

<body>
<div class="overlay">
    
<div class="login-card">

  <!-- HEADER BIRU -->
  <div class="login-header-container">
    <img src="../imge/kelasin.png" alt="Logo" class="login-logo">
    <div class="login-text">
        <h4 class="login-title">SIKAP</h4>
        <p class="login-sub">Sistem Informasi Kehadiran Aktivitas Pembelajaran</p>
    </div>
  </div>

  <div class="form-container">
        <form action="cekLogin.php" method="POST">

       <!-- Username -->
<div class="form-floating mb-3 position-relative">
    <i class="bi bi-person input-icon"></i>
    <input type="text" name="username" class="form-control" placeholder="Username" required>
    <label>Username</label>
</div>

<!-- NIS -->
<div class="form-floating mb-3 position-relative">
    <i class="bi bi-card-text input-icon"></i>
    <input type="text" name="nis" class="form-control" placeholder="NIS" required>
    <label>Nomor Induk Siswa (NIS)</label>
</div>

<!-- Email -->
<div class="form-floating mb-3 position-relative">
    <i class="bi bi-envelope input-icon"></i>

    <input
        type="email"
        name="email"
        id="emailInput"
        class="form-control"
        placeholder="Email"
        value="bawaan@gmail.com"
        required
    >
    <label>Email</label>

    <!-- Inline hint (tidak menggeser layout) -->
    <div id="emailHint" class="form-text text-warning small d-none">
        Email masih default, silakan ganti di dashboard
    </div>
</div>



<!-- Password -->
<div class="form-floating mb-4 position-relative">
    <i class="bi bi-lock input-icon"></i>
    <input type="password" id="password" name="password" class="form-control" placeholder="Password" required>
    <label>Password</label>
    <i class="bi bi-eye-slash toggle-password" onclick="togglePassword()"></i>
</div>

            <button class="btn btn-primary w-100">Login</button>
        </form>

       <div class="text-center mt-3">
    <small class="text-muted d-block mb-1">
        Belum punya akun? 
        <a href="register.php" class="fw-semibold text-decoration-none">Daftar</a>
    </small>
</div>

        <?php if(isset($_SESSION['error'])): ?>
            <div class="alert alert-danger mt-3 text-center">
                <?= $_SESSION['error']; unset($_SESSION['error']); ?>
            </div>
        <?php endif; ?>
  </div>

</div>

<?php if(isset($_SESSION['success'])): ?>
<div class="alert alert-success alert-dismissible fade show mt-3 text-center" role="alert">
    <?= $_SESSION['success']; unset($_SESSION['success']); ?>
    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
</div>
<?php endif; ?>

<!-- FOOTER DI LUAR CARD -->
<div class="footer-credit text-center">
    Absensi created by <strong>Arthawara</strong>
</div>

</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
<script>
function togglePassword(){
    const pass = document.getElementById('password');
    pass.type = pass.type === 'password' ? 'text' : 'password';
}
</script>
<script>
const emailInput = document.getElementById("emailInput");
const emailHint = document.getElementById("emailHint");
const defaultEmail = "bawaan@gmail.com";

function validateEmailDefault() {
    const isDefault = emailInput.value.trim() === defaultEmail;

    emailHint.classList.toggle("d-none", !isDefault);
    emailInput.classList.toggle("is-warning", isDefault);
}

// cek saat load
validateEmailDefault();

// cek saat user mengetik
emailInput.addEventListener("input", validateEmailDefault);
</script>

    
</body>
</html>
