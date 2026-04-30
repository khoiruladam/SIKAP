<?php
session_start();
include '../config/koneksi.php';

if (!isset($_SESSION['role']) || $_SESSION['role'] != 'admin') {
  header("Location: dashboard.php");
  exit;
}

$id = (int)($_GET['id'] ?? 0);

// Ambil data user
$user = mysqli_fetch_assoc(mysqli_query($koneksi, "SELECT * FROM users WHERE id=$id"));
if (!$user) {
  header("Location: dashboard.php?page=user");
  exit;
}

// ================== RESET PASSWORD ==================
if (isset($_GET['reset_pass'])) {
  $new_pass = strval(rand(10000000, 99999999)); // 8 digit
  $hash = password_hash($new_pass, PASSWORD_DEFAULT);

  mysqli_query($koneksi, "UPDATE users SET password='$hash' WHERE id=$id");

  $_SESSION['reset_info'] = $new_pass;
  header("Location: user_edit.php?id=$id");
  exit;
}

// ================== UPDATE DATA ==================
if ($_SERVER['REQUEST_METHOD'] === 'POST') {

  $nama     = mysqli_real_escape_string($koneksi, $_POST['nama']);
  $nis      = mysqli_real_escape_string($koneksi, $_POST['nis']);
  $username = mysqli_real_escape_string($koneksi, $_POST['username']);
  $role     = $_POST['role'];
  $kelas    = mysqli_real_escape_string($koneksi, $_POST['kelas']);
  $alamat   = mysqli_real_escape_string($koneksi, $_POST['alamat']);
  $bio      = mysqli_real_escape_string($koneksi, $_POST['bio']);
  $token    = mysqli_real_escape_string($koneksi, $_POST['token']);

  // Upload avatar
  if (!empty($_FILES['avatar']['name'])) {
    $ext = pathinfo($_FILES['avatar']['name'], PATHINFO_EXTENSION);
    $avatar = 'avatar_' . $id . '_' . time() . '.' . $ext;
    move_uploaded_file($_FILES['avatar']['tmp_name'], "../uploads/avatar/$avatar");
  } else {
    $avatar = $user['avatar'];
  }

  // Password manual (opsional)
  if (!empty($_POST['password'])) {
    $pass_hash = password_hash($_POST['password'], PASSWORD_DEFAULT);
    $pass_sql = ", password='$pass_hash'";
  } else {
    $pass_sql = "";
  }

  mysqli_query($koneksi, "
    UPDATE users SET
      nama='$nama',
      nis='$nis',
      username='$username',
      role='$role',
      kelas='$kelas',
      alamat='$alamat',
      bio='$bio',
      token='$token',
      avatar='$avatar'
      $pass_sql
    WHERE id=$id
  ");

  header("Location: dashboard.php?page=user");
  exit;
}
?>

<!DOCTYPE html>
<html lang="id">

<head>
  <meta charset="UTF-8">
  <title>Edit User</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
  <style>
    body {
      background: #f5f6f8;
      font-family: Poppins, sans-serif;
    }

    .card {
      max-width: 720px;
      margin: 40px auto;
    }
  </style>
</head>

<body>

  <div class="card shadow-sm">
    <div class="card-header fw-semibold bg-white">
      Edit Data User
    </div>

    <div class="card-body">

      <?php if (isset($_SESSION['reset_info'])): ?>
        <div class="alert alert-success">
          Password baru: <strong><?= $_SESSION['reset_info'] ?></strong>
        </div>
      <?php unset($_SESSION['reset_info']);
      endif; ?>

      <form method="POST" enctype="multipart/form-data" id="editForm">

        <!-- AVATAR SECTION -->
        <div class="text-center mb-4">
          <img id="avatarPreview"
            src="<?= !empty($user['avatar']) ? '../avatars/' . htmlspecialchars($user['avatar']) : '../imge/default.png' ?>"
            class="rounded-circle border mb-2"
            style="width:110px;height:110px;object-fit:cover">

          <div class="mx-auto" style="max-width:300px">
            <input type="file"
              name="avatar"
              class="form-control form-control-sm"
              accept="image/*"
              onchange="previewAvatar(this)">
            <div id="avatarInfo" class="small text-muted mt-1">
              Maksimal 2MB (JPG, PNG)
            </div>
          </div>
        </div>

        <!-- FORM DATA -->
        <div class="row g-3">

          <div class="col-md-6">
            <label class="form-label">Nama</label>
            <input type="text" name="nama" class="form-control"
              value="<?= htmlspecialchars($user['nama']) ?>" required>
          </div>

          <div class="col-md-6">
            <label class="form-label">NIS</label>
            <input type="text" name="nis" class="form-control"
              value="<?= htmlspecialchars($user['nis'] ?? '') ?>">
          </div>

          <div class="col-md-6">
            <label class="form-label">Username</label>
            <input type="text" name="username" class="form-control"
              value="<?= htmlspecialchars($user['username']) ?>" required>
          </div>

          <div class="col-md-6">
            <label class="form-label">Role</label>
            <select name="role" class="form-select">
              <option value="admin" <?= $user['role'] == 'admin' ? 'selected' : '' ?>>Admin</option>
              <option value="guru" <?= $user['role'] == 'guru' ? 'selected' : '' ?>>Guru</option>
              <option value="peserta" <?= $user['role'] == 'peserta' ? 'selected' : '' ?>>Peserta</option>
            </select>
          </div>

          <div class="col-md-6">
            <label class="form-label">Kelas</label>
            <input type="text" name="kelas" class="form-control"
              value="<?= htmlspecialchars($user['kelas'] ?? '') ?>">
          </div>

          <div class="col-md-6">
            <label class="form-label">Password (opsional)</label>
            <input type="password" name="password" class="form-control">
          </div>

          <div class="col-md-12">
            <label class="form-label">Alamat</label>
            <textarea name="alamat" class="form-control"
              rows="2"><?= htmlspecialchars($user['alamat'] ?? '') ?></textarea>
          </div>

          <div class="col-md-12">
            <label class="form-label">Bio</label>
            <textarea name="bio" class="form-control"
              rows="3"><?= htmlspecialchars($user['bio'] ?? '') ?></textarea>
          </div>

          <div class="col-md-12">
            <label class="form-label">Token</label>
            <input type="text" name="token" class="form-control"
              value="<?= htmlspecialchars($user['token'] ?? '') ?>">
          </div>

        </div>

        <!-- ACTION BUTTON -->
        <div class="mt-4 d-grid gap-2">
          <button type="submit" class="btn btn-primary" id="btnSubmit">
            <span id="btnText">Simpan Perubahan</span>
            <span id="btnLoading" class="spinner-border spinner-border-sm d-none"></span>
          </button>

          <a href="user_edit.php?id=<?= $id ?>&reset_pass=1"
            class="btn btn-outline-danger"
            onclick="return confirm('Reset password user?')">
            Reset Password (8 Digit)
          </a>

          <a href="dashboard.php?page=user" class="btn btn-secondary">
            Kembali
          </a>
        </div>

      </form>
    </div>
  </div>


</body>
<script>
  function previewAvatar(input) {
    const file = input.files[0];
    if (!file) return;

    const maxSize = 2 * 1024 * 1024; // 2MB
    const info = document.getElementById('avatarInfo');
    const preview = document.getElementById('avatarPreview');

    // Validasi ukuran
    if (file.size > maxSize) {
      alert('Ukuran file maksimal 2MB');
      input.value = '';
      return;
    }

    // Validasi tipe
    if (!file.type.startsWith('image/')) {
      alert('File harus berupa gambar');
      input.value = '';
      return;
    }

    // Preview gambar
    const reader = new FileReader();
    reader.onload = function(e) {
      preview.src = e.target.result;
    };
    reader.readAsDataURL(file);

    // Info ukuran
    const sizeKB = (file.size / 1024).toFixed(1);
    info.innerHTML = `Ukuran file: <strong>${sizeKB} KB</strong>`;
  }
</script>
<script>
  document.getElementById('editForm').addEventListener('submit', function() {
    const btn = document.getElementById('btnSubmit');
    document.getElementById('btnText').classList.add('d-none');
    document.getElementById('btnLoading').classList.remove('d-none');
    btn.disabled = true;
  });
</script>

</html>