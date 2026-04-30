<?php
// ===== Debugging =====
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

session_start();
include '../config/koneksi.php';
date_default_timezone_set('Asia/Jakarta');

// ===== Cek login peserta =====
if (!isset($_SESSION['role']) || $_SESSION['role'] != 'peserta') {
  header("Location: ../index.php");
  exit;
}

/* ================= HELPER QUERY ================= */
function q($conn, $sql) {
    $res = mysqli_query($conn, $sql);
    if ($res === false) {
        die("SQL Error: " . htmlspecialchars(mysqli_error($conn)) . "<br>Query: " . htmlspecialchars($sql));
    }
    return $res;
}
function e($value) {
    return htmlspecialchars($value ?? '', ENT_QUOTES, 'UTF-8');
}


$user_id = $_SESSION['id'];
$tanggal = date("Y-m-d");

// ===== Update last_active =====
$now = date("Y-m-d H:i:s");
$koneksi->query("UPDATE users SET last_active='$now' WHERE id=$user_id");

// ===== Ambil data user + kelas =====
$stmt = $koneksi->prepare("
  SELECT 
    u.nama,
    u.kelas_id,
    u.kelas AS kelas_bio,
    k.tingkat,
    k.jurusan,
    k.nama_kelas,
    u.nis,
    u.alamat,
    u.password,
    u.bio,
    u.avatar,
    u.last_active,
    u.email
  FROM users u
  LEFT JOIN kelas k ON u.kelas_id = k.id
  WHERE u.id=?
");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$user_data = $stmt->get_result()->fetch_assoc();
$stmt->close();

$nama   = $user_data['nama'] ?? 'Peserta';
$nis    = $user_data['nis'] ?? '';
$alamat = $user_data['alamat'] ?? '';
$sandi  = $user_data['password'] ?? '';
$bio    = $user_data['bio'] ?? '';
$avatar = $user_data['avatar'] ?? 'default.png';
$email  = $user_data['email'] ?? '';

$kelas_id   = $user_data['kelas_id'] ?? null;
$kelas_bio  = $user_data['kelas_bio'] ?? ''; // kelas custom peserta

// kelas sekolah (FK)
if (!empty($user_data['nama_kelas'])) {
    $kelas_sekolah = $user_data['tingkat'].' '.$user_data['jurusan'].' '.$user_data['nama_kelas'];
} else {
    $kelas_sekolah = '-';
}


$_SESSION['nama']          = $nama;
$_SESSION['kelas_id']      = $kelas_id;
$_SESSION['kelas_sekolah'] = $kelas_sekolah; // relasi tabel kelas
$_SESSION['kelas_bio']     = $kelas_bio;     // input bebas peserta
$_SESSION['nis']           = $nis;
$_SESSION['alamat']        = $alamat;
$_SESSION['bio']           = $bio;
$_SESSION['avatar']        = $avatar;
$_SESSION['email']         = $email;

// ===== Ambil semua user untuk daftar + modal =====
$online_limit = date("Y-m-d H:i:s", strtotime("-5 minutes"));
$users = [];

$query = $koneksi->query("
  SELECT
    u.id,
    u.username,
    u.nama,
    u.nis,
    u.kelas_id,
    u.kelas AS kelas_bio,
    k.tingkat,
    k.jurusan,
    k.nama_kelas,
    u.alamat,
    u.bio,
    u.email,
    u.avatar,
    u.last_active
  FROM users u
  LEFT JOIN kelas k ON u.kelas_id = k.id
  ORDER BY u.nama ASC
");

while ($row = $query->fetch_assoc()) {

  if (strtolower($row['nama']) === 'admin') continue;

  $row['status'] = ($row['last_active'] >= $online_limit)
    ? 'Online'
    : 'Offline';

  $row['kelas_full'] = ($row['nama_kelas'])
    ? $row['tingkat'].' '.$row['jurusan'].' '.$row['nama_kelas']
    : '-';

  $users[] = $row;
}

// status user login
$_SESSION['online_status'] =
  ($user_data['last_active'] >= $online_limit) ? 'Online' : 'Offline';

// ===== Absen Hari Ini =====
$stmt = $koneksi->prepare("SELECT status, jam_masuk, jam_pulang FROM absensi WHERE user_id=? AND tanggal=? LIMIT 1");
$stmt->bind_param("is", $user_id, $tanggal);
$stmt->execute();
$absen_hari_ini = $stmt->get_result()->fetch_assoc();
$stmt->close();

$absen_status = $absen_hari_ini['status'] ?? null;
$jam_masuk    = $absen_hari_ini['jam_masuk'] ?? null;
$jam_pulang   = $absen_hari_ini['jam_pulang'] ?? null;

// ===== Riwayat Absensi Terakhir =====
$stmt = $koneksi->prepare("SELECT tanggal, status, jam_masuk, jam_pulang 
                           FROM absensi 
                           WHERE user_id=? 
                           ORDER BY tanggal DESC 
                           LIMIT 5");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$data_riwayat = $stmt->get_result();
$stmt->close();

// ===== Statistik 6 Bulan Terakhir =====
$status_list = ['Masuk', 'Tidak Masuk'];
$labelMonths = [];
$counts = ['Masuk' => [], 'Tidak Masuk' => []];

for ($i = 5; $i >= 0; $i--) {
  $m = date('n', strtotime("-{$i} months"));
  $y = date('Y', strtotime("-{$i} months"));
  $labelMonths[] = date('M Y', strtotime("-{$i} months"));

  foreach ($status_list as $st) {
    $stmt = $koneksi->prepare("SELECT COUNT(*) as cnt 
                                   FROM absensi 
                                   WHERE user_id=? AND MONTH(tanggal)=? AND YEAR(tanggal)=? AND status=?");
    $stmt->bind_param("iiis", $user_id, $m, $y, $st);
    $stmt->execute();
    $res = $stmt->get_result()->fetch_assoc();
    $counts[$st][] = intval($res['cnt'] ?? 0);
    $stmt->close();
  }
}

// ===== Ringkasan Bulan Ini =====
$bulan_now = date('n');
$tahun_now = date('Y');
$ringkas = [];

foreach ($status_list as $st) {
  $stmt = $koneksi->prepare("SELECT COUNT(*) as cnt 
                               FROM absensi 
                               WHERE user_id=? AND MONTH(tanggal)=? AND YEAR(tanggal)=? AND status=?");
  $stmt->bind_param("iiis", $user_id, $bulan_now, $tahun_now, $st);
  $stmt->execute();
  $res = $stmt->get_result()->fetch_assoc();
  $ringkas[$st] = intval($res['cnt'] ?? 0);
  $stmt->close();
}

// Total absen bulan ini
$stmt = $koneksi->prepare("SELECT COUNT(*) as cnt FROM absensi WHERE user_id=? AND MONTH(tanggal)=? AND YEAR(tanggal)=?");
$stmt->bind_param("iii", $user_id, $bulan_now, $tahun_now);
$stmt->execute();
$res = $stmt->get_result()->fetch_assoc();
$ringkas_total = intval($res['cnt'] ?? 0);
$stmt->close();

// ===== Flash Messages =====
$flash_messages = [];
foreach (['flash_msg', 'success', 'error'] as $f) {
  if (isset($_SESSION[$f])) {
    $flash_messages[$f] = $_SESSION[$f];
    unset($_SESSION[$f]);
  }
}

/* =====================================================
   PROSES POST (AVATAR & PROFILE)
===================================================== */
if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    /* ===============================
       1. GANTI AVATAR
    =============================== */
    if (
        isset($_FILES['avatar']) &&
        $_FILES['avatar']['error'] === UPLOAD_ERR_OK &&
        empty($_POST['hapus_avatar'])
    ) {
        $file = $_FILES['avatar'];
        $ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
        $allowed = ['jpg', 'jpeg', 'png'];

        if (in_array($ext, $allowed)) {

            $uploadDir = __DIR__ . '/../avatars/';
            if (!is_dir($uploadDir)) {
                mkdir($uploadDir, 0755, true);
            }

            $newName = 'avatar_' . $user_id . '_' . time() . '.' . $ext;
            $uploadPath = $uploadDir . $newName;

            if (move_uploaded_file($file['tmp_name'], $uploadPath)) {

                // Hapus avatar lama (jika bukan default)
                if (!empty($avatar) && $avatar !== '96.png' && file_exists($uploadDir . $avatar)) {
                    unlink($uploadDir . $avatar);
                }

                // Update database
                $stmt = $koneksi->prepare("UPDATE users SET avatar=? WHERE id=?");
                $stmt->bind_param("si", $newName, $user_id);
                $stmt->execute();
                $stmt->close();

                // Update session & variabel
                $_SESSION['avatar'] = $newName;
                $avatar = $newName;

                $flash_messages['success'] = "Avatar berhasil diperbarui.";
            }
        } else {
            $flash_messages['error'] = "Format avatar harus JPG atau PNG.";
        }
    }

    /* ===============================
       2. HAPUS AVATAR
    =============================== */
    if (isset($_POST['hapus_avatar'])) {

        $uploadDir = __DIR__ . '/../avatars/';

        if (!empty($avatar) && $avatar !== '96.png' && file_exists($uploadDir . $avatar)) {
            unlink($uploadDir . $avatar);
        }

        // Set avatar ke default
        $defaultAvatar = '96.png';

        $stmt = $koneksi->prepare("UPDATE users SET avatar=? WHERE id=?");
        $stmt->bind_param("si", $defaultAvatar, $user_id);
        $stmt->execute();
        $stmt->close();

        $_SESSION['avatar'] = $defaultAvatar;
        $avatar = $defaultAvatar;

        $flash_messages['success'] = "Avatar berhasil dihapus.";
    }

    /* ===============================
   3. UPDATE PROFIL
================================ */
if (isset($_POST['update_profile'])) {

    $bio_new   = trim($_POST['bio'] ?? '');
    $kelas_new = trim($_POST['kelas'] ?? ''); // kelas bio peserta
    $nis_new   = trim($_POST['nis'] ?? '');
    $email_new = trim($_POST['email'] ?? '');

    // ===== AMBIL DATA ALAMAT =====
    $provinsi      = trim($_POST['provinsi'] ?? '');
    $kabupaten     = trim($_POST['kabupaten'] ?? '');
    $kecamatan     = trim($_POST['kecamatan'] ?? '');
    $kelurahan     = trim($_POST['kelurahan'] ?? '');
    $alamat_detail = trim($_POST['alamat_detail'] ?? '');

    // ===== GABUNGKAN ALAMAT =====
    $alamatParts = [];
    if ($alamat_detail) $alamatParts[] = $alamat_detail;
    if ($kelurahan)     $alamatParts[] = 'Kel. ' . $kelurahan;
    if ($kecamatan)     $alamatParts[] = 'Kec. ' . $kecamatan;
    if ($kabupaten)     $alamatParts[] = $kabupaten;
    if ($provinsi)      $alamatParts[] = $provinsi;

    $alamat_new = implode(', ', $alamatParts);

    // ===== UPDATE DATABASE =====
    $stmt = $koneksi->prepare("
        UPDATE users 
        SET bio=?, kelas=?, alamat=?, nis=?, email=?
        WHERE id=?
    ");
    $stmt->bind_param("sssssi", $bio_new, $kelas_new, $alamat_new, $nis_new, $email_new, $user_id);
    $stmt->execute();
    $stmt->close();

    // ===== UPDATE SESSION (FIXED) =====
    $_SESSION['bio']       = $bio_new;
    $_SESSION['kelas_bio'] = $kelas_new; // BUKAN kelas_id
    $_SESSION['alamat']    = $alamat_new;
    $_SESSION['nis']       = $nis_new;
    $_SESSION['email']     = $email_new;

    // ===== UPDATE VARIABEL LOKAL =====
    $bio        = $bio_new;
    $kelas_bio  = $kelas_new;
    $alamat     = $alamat_new;
    $nis        = $nis_new;
    $email      = $email_new;

    $flash_messages['success'] = "Profil berhasil diperbarui.";
}
}

/* =====================================================
   DATA AGENDA
===================================================== */
$agendaList = q(
    $koneksi,
    "SELECT * FROM agenda ORDER BY tanggal_mulai ASC"
);

/* =====================================================
   AVATAR FINAL (UNTUK TAMPILAN)
===================================================== */
$avatarFile = (
    !empty($avatar) &&
    file_exists(__DIR__ . '/../avatars/' . $avatar)
)
    ? '../avatars/' . $avatar
    : '../avatars/96.png';
?>
<!DOCTYPE html>
<html lang="id">

<head>
  <meta charset="UTF-8">
  <title>KELASIN</title>
  <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
  <meta name="description" content="Dashboard peserta PKL 2025/2026.">
  <meta name="author" content="Muhammad Zidni Nur Iqram">
  <meta name="robots" content="noindex, nofollow">
  <link rel="icon" href="../assets/favicon.png" type="image/png">
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
  <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons/font/bootstrap-icons.css" rel="stylesheet">
  <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/animate.css/4.1.1/animate.min.css" />
  <link rel="icon" type="image/png" href="../imge/kelasin.png">

  <style>
    :root {
      --navy: #0b4b78;
      --muted: #6c757d;
      --glass: rgba(255, 255, 255, 0.95);
      --soft: #f6f8fb;
      --accent: #0d6efd;
      --card-shadow: 0 8px 28px rgba(14, 30, 37, 0.06);
    }

    html,
    body {
      height: 100%;
    }

    body {
      font-family: Inter, "Poppins", system-ui, -apple-system, "Segoe UI", Roboto, "Helvetica Neue", Arial;
      background: linear-gradient(180deg, #f4f8fb 0, #fbfdff 100%);
      margin: 0;
      color: #162032;
      -webkit-font-smoothing: antialiased;
      -moz-osx-font-smoothing: grayscale;
    }

    /* LAYOUT GRID */
    .app-shell {
      display: grid;
      grid-template-columns: 260px 1fr;
      gap: 20px;
      padding: 22px;
    }

    /* ====== SIDEBAR LOGO ====== */
    .sidebar .logo-wrap img {
      width: 48px;
      height: 48px;
      object-fit: contain;
      display: block;
    }

    /* ====== BRAND WRAPPER ====== */
    .sidebar .brand {
      display: flex;
      align-items: center;
      flex-wrap: nowrap;
      /* supaya teks tidak hilang karena wrap */
      gap: 10px;
      overflow: visible !important;
      max-width: 100%;
      white-space: normal !important;
      /* teks bisa turun baris kalau sempit */
      padding-right: 10px;
    }

    /* ====== INFO TEKS ====== */
    .sidebar .brand-info {
      flex: 1;
      /* biar area teks fleksibel */
      min-width: 0;
      /* biar text-overflow tidak memaksa sembunyi */
      overflow: visible !important;
      white-space: normal !important;
      text-overflow: unset !important;
      color: #000;
      /* pastikan teks terlihat */
    }

    .sidebar .brand-text {
      font-weight: 700;
      font-size: 1rem;
      line-height: 1.2;
    }

    .sidebar .brand-sub {
      font-size: 0.85rem;
      color: #666;
    }

    /* ====== RESPONSIVE MODE ====== */
    @media (max-width: 576px) {
      .sidebar .brand {
        flex-direction: column;
        /* logo di atas, teks di bawah */
        align-items: center;
        text-align: center;
      }

      .sidebar .brand-info {
        width: 100%;
        white-space: normal;
      }

      .sidebar .brand-text {
        font-size: 0.95rem;
      }

      .sidebar .brand-sub {
        font-size: 0.8rem;
      }
    }



    .brand {
      display: flex;
      gap: 12px;
      align-items: center;
      padding-bottom: 12px;
      border-bottom: 1px solid #eef2f6;
      margin-bottom: 14px;
    }

    /* Logo wrapper: beri background navy muda supaya logo berwarna putih/putih background tetap terlihat */
    .brand .logo-wrap {
      width: 64px;
      height: 64px;
      border-radius: 10px;
      display: flex;
      align-items: center;
      justify-content: center;
      background: linear-gradient(180deg, rgba(11, 75, 120, 0.12), rgba(13, 110, 253, 0.06));
      box-shadow: 0 6px 16px rgba(11, 75, 120, 0.06);
      padding: 8px;
    }

    .brand img {
      width: 100%;
      height: 100%;
      object-fit: contain;
      border-radius: 8px;
      background: transparent;
      display: block;
    }

    .brand .brand-text {
      font-weight: 700;
      color: var(--navy);
      font-size: 0.95rem;
    }

    .brand .brand-sub {
      font-size: 0.78rem;
      color: var(--muted);
    }

    .profile-card {
      background: #fff;
      border-radius: 10px;
      padding: 10px;
      display: flex;
      gap: 10px;
      align-items: center;
      box-shadow: 0 6px 18px rgba(16, 24, 40, 0.04);
      margin-bottom: 14px;
    }

    .profile-card .avatar {
      width: 52px;
      height: 52px;
      border-radius: 8px;
      display: flex;
      align-items: center;
      justify-content: center;
      background: linear-gradient(180deg, #fff, #f4f6fb);
      font-size: 1.6rem;
      color: var(--navy);
    }

    .profile-card .meta .name {
      font-weight: 700;
      color: #162032;
      font-size: 0.95rem;
    }

    .profile-card .meta .kelas {
      font-size: 0.82rem;
      color: var(--muted);
    }

    .nav-link.custom {
      display: flex;
      gap: 12px;
      align-items: center;
      padding: 10px 12px;
      border-radius: 8px;
      color: #25304a;
      font-weight: 600;
      margin-bottom: 6px;
    }

    .nav-link.custom i {
      font-size: 1.15rem;
      color: var(--navy);
    }

    .nav-link.custom:hover,
    .nav-link.custom.active {
      background: linear-gradient(90deg, rgba(13, 110, 253, 0.08), rgba(11, 75, 120, 0.03));
      color: var(--navy);
      transform: translateY(-2px);
      text-decoration: none;
    }

    .sidebar .muted-small {
      font-size: 0.85rem;
      color: var(--muted);
    }

    /* MAIN */
    .main {
      padding: 6px 6px;
      min-height: calc(100vh - 44px);
    }

    :root {
      --navy: #002b5b;
      --bg-light: #ffffff;
      --bg-dark: #1e1e1e;
      --text-light: #f8f9fa;
      --text-dark: #212529;
      --border-light: #e9ecef;
      --border-dark: #444;
    }

    .topbar {
      display: flex;
      flex-wrap: wrap;
      padding: 1rem 1.25rem;
      border-bottom: 1px solid var(--border-light);
      background: var(--bg-light);
      border-radius: 0.5rem;
      color: var(--text-dark);
      transition: all 0.3s ease;
    }

    .user-info h4 {
      color: var(--navy);
      transition: color 0.3s ease;
    }

    .btn-sm {
      font-size: 0.875rem;
      padding: 0.4rem 0.75rem;
      border-radius: 0.4rem;
    }

    @media (max-width: 768px) {
      .topbar {
        flex-direction: column;
        align-items: flex-start;
        gap: 0.75rem;
      }

      .user-info {
        width: 100%;
      }
    }

    .card-elevated {
      background: #fff;
      border-radius: 12px;
      padding: 16px;
      box-shadow: var(--card-shadow);
      margin-bottom: 14px;
    }

    .stats-grid {
      display: grid;
      grid-template-columns: repeat(4, 1fr);
      gap: 15px;
      margin-bottom: 14px;
    }

    .stat {
      background: linear-gradient(180deg, #fff, #fbfdff);
      border-radius: 10px;
      padding: 12px;
      display: flex;
      gap: 12px;
      align-items: center;
      box-shadow: 0 6px 18px rgba(11, 75, 120, 0.04);
    }

    .stat .ico {
      width: 52px;
      height: 52px;
      border-radius: 10px;
      display: flex;
      align-items: center;
      justify-content: center;
      background: linear-gradient(180deg, rgba(11, 75, 120, 0.08), rgba(13, 110, 253, 0.06));
      color: var(--navy);
      font-size: 1.3rem;
    }

    .stat .num {
      font-weight: 800;
      color: #0f2b45;
      font-size: 1.15rem;
    }

    .stat .label {
      color: var(--muted);
      font-size: 0.82rem;
    }

    /* Responsive helpers */
    @media (max-width: 991px) {
      .app-shell {
        grid-template-columns: 1fr;
        padding: 12px;
      }

      .sidebar {
        position: relative;
        height: auto;
        top: 0;
        border-left: none;
      }

      .stats-grid {
        grid-template-columns: repeat(2, 1fr);
      }
    }

    @media (max-width: 576px) {
      .stats-grid {
        grid-template-columns: 1fr;
      }

      .brand .brand-text {
        display: none;
      }

      /* space saver */
      .brand .brand-sub {
        display: none;
      }
    }

    /* small table tweaks */
    .table thead th {
      border: none;
      color: #4b5563;
      font-weight: 700;
      background: transparent;
    }

    .table tbody td {
      vertical-align: middle;
    }

    /* footer */
    footer.appfoot {
      text-align: center;
      color: var(--muted);
      font-size: 0.9rem;
      margin-top: 10px;
    }

    /* dark mode (simple) */
    .dark {
      background: linear-gradient(180deg, #0f1724 0, #071229 100%);
      color: #dbeafe;
    }

    .dark .sidebar {
      background: rgba(8, 18, 33, 0.85);
      border-left-color: #0b69a3;
    }

    .dark .card-elevated,
    .dark .profile-card {
      background: rgba(255, 255, 255, 0.03);
      box-shadow: none;
      border: 1px solid rgba(255, 255, 255, 0.04);
    }

    .dark .stat .ico {
      filter: brightness(1.1);
    }

    .dark a.nav-link.custom {
      color: #dbeafe;
    }

    /* small utilities */
    .badge-soft {
      background: rgba(13, 110, 253, 0.09);
      color: var(--navy);
      font-weight: 700;
      padding: 6px 10px;
      border-radius: 8px;
    }

    .btn-compact {
      padding: 6px 10px;
      font-size: 0.9rem;
    }

    .btn-main {
      background: #0a2342;
      /* warna dashboard clean */
      color: #fff;
      border: none;
    }

    .btn-main:hover {
      background: #122f5c;
    }

    .input-group-text {
      min-width: 45px;
    }

    /* Foto profil */
    .profile-img {
      width: 110px;
      height: 110px;
      object-fit: cover;
      border: 4px solid #fff;
      box-shadow: 0 4px 10px rgba(0, 0, 0, 0.08);
      transition: all 0.3s ease;
    }

    .profile-img:hover {
      transform: scale(1.03);
    }

    .status-indicator {
      position: absolute;
      bottom: 6px;
      right: 8px;
      width: 14px;
      height: 14px;
      border-radius: 50%;
      box-shadow: 0 0 6px rgba(0, 0, 0, 0.2);
    }

    .info-card {
      background: #f8f8f8;
      border-radius: 1rem;
      padding: 10px 14px;
      box-shadow: 0 2px 6px rgba(0, 0, 0, 0.04);
      transition: all 0.3s ease;
    }

    .info-card:hover {
      background: #f1f1f1;
      transform: translateY(-2px);
    }

    .modal.fade .modal-dialog {
      transform: translateY(-20px);
      transition: all 0.35s ease-out;
    }

    .modal.show .modal-dialog {
      transform: translateY(0);
    }

    /* Animasi fade-in */
    .fade-in {
      animation: fadeIn 0.8s ease forwards;
      opacity: 0;
    }

    @keyframes fadeIn {
      to {
        opacity: 1;
      }
    }

    /* Glassmorphism putih */
    .status-card {
      background: rgba(255, 255, 255, 0.95);
      backdrop-filter: blur(8px);
      border-radius: 1rem;
      box-shadow: 0 6px 18px rgba(0, 0, 0, 0.08);
      padding: 1.2rem;
      display: flex;
      align-items: center;
      gap: 1rem;
      transition: transform 0.3s, box-shadow 0.3s;
    }

    .status-card:hover {
      transform: translateY(-3px);
      box-shadow: 0 12px 25px rgba(0, 0, 0, 0.12);
    }

    /* Animasi fade-in + bounce untuk badge */
    .fade-in {
      animation: fadeIn 0.8s ease forwards;
      opacity: 0;
    }

    @keyframes fadeIn {
      to {
        opacity: 1;
      }
    }

    .badge-bounce {
      display: inline-block;
      animation: bounce 0.6s ease;
      border-radius: 0.5rem;
      font-weight: 600;
      padding: 0.4rem 0.8rem;
      background: #fff;
      color: #333;

    }

    @keyframes bounce {
      0% {
        transform: translateY(-8px);
        opacity: 0;
      }

      50% {
        transform: translateY(3px);
        opacity: 1;
      }

      100% {
        transform: translateY(0);
        opacity: 1;
      }
    }

    /* Pulse icon untuk status belum absen */
    .pulse {
      animation: pulse 1.2s infinite;
    }

    @keyframes pulse {
      0% {
        transform: scale(1);
      }

      50% {
        transform: scale(1.15);
      }

      100% {
        transform: scale(1);
      }
    }

    .ico {
      font-size: 2.2rem;
      color: #333;
    }

    .status-text {
      font-size: 0.9rem;
      font-weight: 600;
    }

    .time-label {
      font-size: 0.85rem;
      color: #555;
    }

    .section {
      margin-bottom: 30px;
      /* Jarak antar section */
    }
    /* Wrapper */
.info-wrapper {
    display: flex;
    justify-content: center;
    gap: 18px;
}

/* Item */
.info-item {
    min-width: 90px;
    padding: 12px 14px;
    border-radius: 14px;
    background: #f8f9fa;
    text-align: center;
    box-shadow: 0 10px 24px rgba(0,0,0,.08);
    transition: .3s ease;
}

.info-item:hover {
    transform: translateY(-3px);
}

/* Icon */
.info-icon {
    width: 40px;
    height: 40px;
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    margin: 0 auto 6px;
    font-size: 18px;
}

/* Neutral */
.info-icon.neutral {
    background: #e9ecef;
    color: #6c757d;
}

/* Active */
.info-icon.active {
    background: #d1fae5;
    color: #198754;
    box-shadow: 0 0 0 6px rgba(25,135,84,.15);
}

/* Text */
.info-text {
    font-size: 13px;
    color: #6c757d;
}

.active-text {
    color: #198754;
    font-weight: 600;
}
.profile-bg{
    background:
        url("../imge/bg-modal-profile.png") center / cover no-repeat;
    border-radius: 22px;
}
.info-wrapper{
    display:flex;
    justify-content:center;
    gap:28px;
}

.info-item{
    display:flex;
    flex-direction:column;
    align-items:center;
    gap:6px;
    font-size:.85rem;
}

.info-icon{
    width:42px;
    height:42px;
    border-radius:50%;
    display:flex;
    align-items:center;
    justify-content:center;
    font-size:1.2rem;
    background:#f1f3f5;
    color:#6c757d;
}

.info-icon.active{
    background:#e7f1ff;
    color:#0d6efd;
}

.info-text.active-text{
    color:#0d6efd;
    font-weight:600;
}
.status-indicator{
    position:absolute;
    bottom:6px;
    right:6px;
    width:16px;
    height:16px;
    border-radius:50%;
}
.muted-small {
  font-size: 0.75rem;
  color: #6c757d;
  opacity: 0.85;
}

.about-link {
  font-size: 0.75rem;          /* sama dengan footer */
  color: #6c757d;
  text-decoration: none;
  cursor: pointer;
  transition: all 0.2s ease;
}

.about-link:hover {
  color: #0b4b78;              /* biru lembut */
  text-decoration: underline; /* tanda bisa diklik */
}
/* Styling tambahan agar modal lebih rapi */
.status-indicator {
    position: absolute;
    bottom: 0;
    right: 0;
    width: 15px;
    height: 15px;
    border-radius: 50%;
    border: 2px solid #fff;
}
.list-group-item {
    border: none;
    padding: 0.5rem 1rem;
    font-size: 0.95rem;
}
.list-group-item i {
    color: #0d6efd;
}
.profile-img {
    border: 3px solid #0d6efd;
}     
.profile-card {
    transition: transform 0.2s ease, box-shadow 0.2s ease;
}
.profile-card:hover {
    transform: translateY(-3px);
    box-shadow: 0 12px 25px rgba(0,0,0,0.2);
}      
.brand-info {
  overflow: hidden;
  text-overflow: ellipsis;
  white-space: nowrap;
}

/* Mobile fix */
@media (max-width: 800px) {
  .brand-info {
    white-space: normal;
    overflow: visible;
    text-overflow: unset;
  }
}      
 .profile-info {
  width: 100%;
  font-size: 0.9rem;
}

.row-info {
  display: grid;
  grid-template-columns: 80px 10px 1fr;
  align-items: start;
  margin-bottom: 6px;
}

.label {
  font-weight: 500;
  color: #6c757d;
  white-space: nowrap;
}

.separator {
  text-align: center;
}

.value {
  font-weight: 600;
  line-height: 1.4;
  word-break: break-word;
  white-space: pre-wrap;
}


  </style>
</head>

<body id="pageBody">

  <div class="container-fluid px-3">
    <div class="app-shell">

    <aside class="sidebar" id="sidebar">
  <div class="brand d-flex align-items-center px-2 py-3">
    <div class="logo-wrap flex-shrink-0">
      <img src="../imge/kelasin.png" alt="Logo Sekolah" class="logo-img" style="width:40px; height:auto;">
    </div>
    <div class="brand-info text-black ms-2" style="flex:1; min-width:0;">
      <div class="brand-text fw-bold">KELASIN</div>
      <div class="brand-sub small text-muted">Kegiatan Laporan dan Sistem Terintegrasi</div>
    </div>
  </div>

       <!-- Profil User Rapi -->
<!-- Profil User -->
<div class="profile-card d-flex flex-column align-items-center text-center p-4 bg-white shadow-sm rounded-3"
     style="max-width:550px; margin:auto;">

  <?php
  $avatar = $_SESSION['avatar'] ?? '96.png';
  $bio    = $_SESSION['bio'] ?? 'Tidak ada bio.';
  $nama   = $_SESSION['nama'] ?? 'User';
  $kelas  = $_SESSION['kelas_id'] ?? '-';
  $nis    = $_SESSION['nis'] ?? '-';
  $alamat = $_SESSION['alamat'] ?? '-';
  $email  = $_SESSION['email'] ?? '-';
  ?>

  <!-- Avatar -->
  <div class="mb-3">
    <img src="../avatars/<?= htmlspecialchars($avatar) ?>"
         class="rounded-circle border"
         style="width:110px;height:110px;object-fit:cover;">
  </div>

  <!-- Nama -->
  <div class="fw-bold fs-5"><?= htmlspecialchars($nama) ?></div>
  <div class="text-muted small mb-3"><?= htmlspecialchars($kelas) ?></div>

	  <div class="accordion w-100" id="accordionProfile">

    <div class="accordion-item border-0">
      <h2 class="accordion-header">
        <button class="accordion-button collapsed px-2 py-2"
                type="button"
                data-bs-toggle="collapse"
                data-bs-target="#collapseProfile">
          <small class="fw-semibold">Detail Profil</small>
        </button>
      </h2>

      <div id="collapseProfile" class="accordion-collapse collapse"
           data-bs-parent="#accordionProfile">

        <div class="accordion-body px-2 py-2">

  <!-- Info User -->
 <ul class="list-group list-group-flush w-100 mb-3" style="font-size:0.9rem;">
  
  <li class="list-group-item border-0 px-0 py-2">
              <div class="profile-info">

            <div class="info-row">
              <span class="label">NIS</span>
              <span class="value"><?= htmlspecialchars($nis) ?></span>
            </div>

            <div class="info-row">
              <span class="label">Email</span>
              <span class="value"><?= htmlspecialchars($email) ?></span>
            </div>

            <div class="info-row align-items-start">
              <span class="label">Alamat</span>
              <span class="value alamat-text">
                <?= nl2br(htmlspecialchars($alamat)) ?>
              </span>
            </div>

            <div class="mt-2 small text-muted fst-italic">
              <?= htmlspecialchars($bio) ?>
            </div>

          </div>

  </li>

</ul>
                  </div>
      </div>
    </div>
  </div>

  <!-- Tombol Edit -->
  <button class="btn btn-outline-secondary btn-sm w-100 mt-3"
          data-bs-toggle="modal"
          data-bs-target="#modalEditProfile">
    Edit Profile
  </button>

</div>
	

        <!-- Modal Edit Profile -->
        <div class="modal fade" id="modalEditProfile" tabindex="-1" aria-hidden="true">
          <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
              <div class="modal-header border-bottom">
                <h5 class="modal-title">Edit Profile</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
              </div>
              <div class="modal-body">

                <!-- Form Ganti Avatar -->
                <form method="post" enctype="multipart/form-data" class="mb-3">
                  <?php if (isset($msg_avatar)): ?>
                    <div class="alert alert-info"><?= $msg_avatar ?></div>
                  <?php endif; ?>
                  <label class="form-label fw-semibold">Ganti Avatar</label>
                  <input type="file" name="avatar" class="form-control mb-2" accept=".jpg,.jpeg,.png" onchange="previewAvatar(this)">
                  <div class="d-flex gap-2">
                    <button type="submit" class="btn btn-primary btn-sm flex-grow-1">Upload Avatar</button>
                    <button type="submit" name="hapus_avatar" class="btn btn-danger btn-sm flex-grow-1">Hapus Avatar</button>
                  </div>
                </form>

               <!-- Form Profil -->
<form method="post">
  <?php if (isset($msg_profile)): ?>
    <div class="alert alert-info"><?= $msg_profile ?></div>
  <?php endif; ?>
  <input type="hidden" name="update_profile" value="1">

  <div class="mb-3">
    <label class="form-label fw-semibold">Bio</label>
    <textarea name="bio" class="form-control" rows="3"><?= htmlspecialchars($bio) ?></textarea>
  </div>

  <div class="mb-3">
    <label class="form-label fw-semibold">Kelas</label>
    <input type="text" name="kelas" class="form-control" value="<?= htmlspecialchars($kelas) ?>">
  </div>

  <div class="mb-3">
  <label class="form-label fw-semibold">Provinsi</label>
  <select name="provinsi" id="provinsi" class="form-select" required>
    <option value="">Memuat provinsi...</option>
  </select>
</div>

<div class="mb-3">
  <label class="form-label fw-semibold">Kabupaten / Kota</label>
  <select name="kabupaten" id="kabupaten" class="form-select" required disabled>
    <option value="">Pilih Provinsi terlebih dahulu</option>
  </select>
</div>

<div class="mb-3">
  <label class="form-label fw-semibold">Kecamatan</label>
  <select name="kecamatan" id="kecamatan" class="form-select" required disabled>
    <option value="">Pilih Kabupaten/Kota terlebih dahulu</option>
  </select>
</div>

<div class="mb-3">
  <label class="form-label fw-semibold">Kelurahan / Desa</label>
  <select name="kelurahan" id="kelurahan" class="form-select" required disabled>
    <option value="">Pilih Kecamatan terlebih dahulu</option>
  </select>
</div>

<div class="mb-3">
  <label class="form-label fw-semibold">Alamat Tambahan</label>
  <textarea
    name="alamat_detail"
    class="form-control"
    rows="2"
    placeholder="Contoh: Jl. Melati No. 12 RT 03 RW 04"
  ></textarea>
</div>

  <div class="mb-3">
    <label class="form-label fw-semibold">NIS</label>
    <input type="text" name="nis" class="form-control" value="<?= htmlspecialchars($nis) ?>">
  </div>

  <!-- Tambahkan Email -->
  <div class="mb-3">
    <label class="form-label fw-semibold">Email</label>
    <input type="email" name="email" class="form-control" value="<?= htmlspecialchars($email) ?>" placeholder="contoh@email.com">
  </div>

  <button type="submit" class="btn btn-dark btn-sm w-100">Simpan Perubahan</button>
</form>

              </div>
            </div>
          </div>
        </div>
	<hr>
        <nav class="mb-4 p-3 rounded-4 shadow-sm bg-white border">
          <div class="d-flex justify-content-between align-items-center mb-3">
            <h6 class="fw-semibold mb-0 text-secondary">
              <i class="bi bi-grid me-2 text-muted"></i> Navigasi
            </h6>
            <!-- Tombol Profil -->
            <button class="btn btn-light border rounded-pill px-3 py-1 shadow-sm fw-semibold text-muted"
              data-bs-toggle="modal"
              data-bs-target="#userProfileModal"
              style="font-size: 0.9rem;">
              <i class="bi bi-person-circle me-1 text-muted"></i> Lihat Profil
            </button>
          </div>

          <div class="list-group list-group-flush">
            <a href="../peserta/dashboard.php" class="list-group-item list-group-item-action border-0 py-2 px-2 d-flex align-items-center custom active">
              <i class="bi bi-calendar-check me-2 text-muted"></i> Dashboard
            </a>
            <a href="../peserta/absen.php" class="list-group-item list-group-item-action border-0 py-2 px-2 d-flex align-items-center custom">
              <i class="bi bi-journal-text me-2 text-muted"></i> Absensi
            </a>
            <a href="../peserta/kegiatan.php" class="list-group-item list-group-item-action border-0 py-2 px-2 d-flex align-items-center custom">
              <i class="bi bi-people me-2 text-muted"></i> Kegiatan
            </a>
            <a href="../peserta/bimbingan.php" class="list-group-item list-group-item-action border-0 py-2 px-2 d-flex align-items-center custom">
              <i class="bi bi-journal-text me-2 text-muted"></i> Bimbingan
            </a>
            <a href="../peserta/history.php" class="list-group-item list-group-item-action border-0 py-2 px-2 d-flex align-items-center custom">
              <i class="bi bi-clock-history me-2 text-muted"></i> History
            </a> 
<a href="../peserta/pembelajaran.php"
   class="list-group-item list-group-item-action border-0 py-2 px-2 d-flex align-items-center custom">
  <i class="bi bi-book-half me-2 text-muted"></i> Pembelajaran
</a>  
<a href="../peserta/kelas.php"
   class="list-group-item list-group-item-action border-0 py-2 px-2 d-flex align-items-center custom">
  <i class="bi bi-people-fill me-2 text-muted"></i> Kelas
</a>
              
            <a href="../peserta/qr.php" class="list-group-item list-group-item-action border-0 py-2 px-2 d-flex align-items-center custom">
              <i class="bi bi-qr-code me-2 text-muted"></i> QR Code
            </a>
				<a href="../about/index.php"

          </div>
        </nav>
        <div class="d-grid gap-2 mb-2">
          <a href="../autentikasi/logout.php" class="btn btn-outline-secondary btn-sm btn-compact">Keluar</a>
        </div>

 <div class="muted-small text-center mb-1">
  Website Version 1.0.0.3 &bull;
  <a href="../about/about.php" class="about-link text-decoration-none">
    <i class="bi bi-info-circle me-1"></i>
    Tentang Sistem
  </a>
</div>




      </aside>

      <!-- MAIN -->
      <main class="main">

        <!-- top controls -->
        <div class="topbar flex-column flex-md-row align-items-start align-items-md-center justify-content-between">
          <div class="user-info mb-3 mb-md-0">
            <h4 class="fw-semibold mb-1">Halo,
              <span class="text-primary"><?= htmlspecialchars($nama) ?></span> 👋
            </h4>
            <div class="d-flex align-items-center gap-2 mb-1">
              <span class="badge bg-primary-subtle text-primary"><?= htmlspecialchars($kelas) ?></span>
              <small class="text-muted" id="waktuNow"></small>
            </div>
            <div class="d-flex flex-wrap gap-2 mt-2">
              <button class="btn btn-outline-primary btn-sm" data-bs-toggle="modal" data-bs-target="#modalUsername">
                <i class="bi bi-person"></i> Ganti Username
              </button>
              <button class="btn btn-outline-dark btn-sm" data-bs-toggle="modal" data-bs-target="#modalPassword">
                <i class="bi bi-key"></i> Ganti Password
              </button>
              <!-- Tombol Refresh -->
              <button class="btn btn-outline-success btn-sm" id="btnRefreshDashboard">
                <i class="bi bi-arrow-clockwise"></i> Refresh
              </button>
            </div>
          </div>

          <div class="d-flex align-items-center gap-2 ms-auto">
          </div>
        </div>


        <br>
        <!-- quick stats -->
        <div class="stats-grid">
          <div class="stat card-elevated">
            <div class="ico"><i class="bi bi-calendar2-event"></i></div>
            <div>
              <div class="num"><?= date('d F Y') ?></div>
              <div class="label">Tanggal <a>Hari Ini</a></div>
            </div>
          </div>

          <div class="stat card-elevated">
            <div class="ico"><i class="bi bi-check2-circle"></i></div>
            <div>
              <div class="num"><?= $ringkas['Masuk'] ?? 0 ?></div>
              <div class="label">Hadir (Bulan Ini)</div>
            </div>
          </div>

          <div class="stat card-elevated">
            <div class="ico"><i class="bi bi-x-circle"></i></div>
            <div>
              <div class="num"><?= $ringkas['Tidak Masuk'] ?? 0 ?></div>
              <div class="label">Tidak Masuk (Bulan Ini)</div>
            </div>
          </div>

          <div class="stat card-elevated">
            <div class="ico"><i class="bi bi-clipboard-data"></i></div>
            <div>
              <div class="num"><?= $ringkas_total ?></div>
              <div class="label">Total Catatan (Bulan Ini)</div>
            </div>
          </div>
        </div>


        <div class="status-card fade-in">
          <div class="ico">
            <?php if (!$absen_status): ?>
              <i class="bi bi-x-square text-danger pulse"></i>
            <?php else: ?>
              <i class="bi bi-check2-square text-success"></i>
            <?php endif; ?>
          </div>

          <div class="flex-grow-1">
  <div class="fw-semibold mb-2 text-secondary" style="font-size:0.9rem;">
    Status Absensi Hari Ini
  </div>

  <?php if (!$absen_status): ?>
    <div class="text-danger fw-semibold" style="font-size:0.95rem;">
      Belum melakukan absensi
    </div>
  <?php else: ?>

    <!-- STATUS -->
    <div class="mb-3">
      <span class="badge bg-success px-3 py-2" style="font-size:0.9rem;">
        <?= htmlspecialchars($absen_status) ?>
      </span>
    </div>

    <!-- JAM -->
    <div class="d-flex gap-4">
      <div>
        <div class="small text-muted mb-1">Jam Masuk</div>
        <div class="fw-semibold">
          <?= $jam_masuk ?: '-' ?>
        </div>
      </div>

      <div>
        <div class="small text-muted mb-1">Jam Pulang</div>
        <div class="fw-semibold">
          <?= $jam_pulang ?: '-' ?>
        </div>
      </div>
    </div>

  <?php endif; ?>
</div>

        </div>
        <br>
       <div class="card shadow-sm border-0 rounded-4 overflow-hidden bg-white">
  <div class="card-body p-3">

    <div class="d-flex justify-content-between align-items-center mb-3">
      <h6 class="fw-bold mb-0" style="color:#0b4b78;">
        <i class="bi bi-people-fill me-1"></i> Daftar User
      </h6>
      <span class="badge bg-light text-dark border rounded-pill px-3">
        Total: <?= count($users) ?> user
      </span>
    </div>

    <div class="table-responsive">
      <table class="table table-hover align-middle mb-0">
        <thead class="table-light">
          <tr class="text-secondary">
            <th>Nama</th>
            <th class="text-center">Status</th>
          </tr>
        </thead>
        <tbody>

          <?php foreach ($users as $user): ?>
            <?php
              $avatar = !empty($user['avatar']) && file_exists("../avatars/{$user['avatar']}")
                ? "../avatars/{$user['avatar']}"
                : "../avatars/default.png";

              $statusClass = ($user['status'] === 'Online')
                ? 'bg-success-subtle text-success'
                : 'bg-secondary-subtle text-muted';
            ?>

            <tr style="cursor:pointer"
                data-bs-toggle="modal"
                data-bs-target="#userModal<?= $user['id'] ?>">

              <td class="d-flex align-items-center gap-2">
                <img src="<?= htmlspecialchars($avatar) ?>"
                     class="rounded-circle"
                     style="width:34px;height:34px;object-fit:cover;">
                <span class="fw-semibold"><?= htmlspecialchars($user['nama']) ?></span>
              </td>

              <td class="text-center">
                <span class="badge rounded-pill <?= $statusClass ?>">
                  <?= htmlspecialchars($user['status']) ?>
                </span>
              </td>
            </tr>

          <?php endforeach; ?>

        </tbody>
      </table>
    </div>

  </div>
</div>

          <?php foreach ($users as $user): ?>
<?php
  $avatar = !empty($user['avatar']) && file_exists("../avatars/{$user['avatar']}")
    ? "../avatars/{$user['avatar']}"
    : "../avatars/96.png";
?>

<div class="modal fade" id="userModal<?= $user['id'] ?>" tabindex="-1">
  <div class="modal-dialog modal-dialog-centered modal-md">
    <div class="modal-content border-0 rounded-4 shadow">

      <div class="modal-header border-0">
        <h5 class="modal-title fw-bold text-primary">Profil User</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
      </div>

      <div class="modal-body text-center">

        <img src="<?= htmlspecialchars($avatar) ?>"
             class="rounded-circle shadow mb-3"
             style="width:90px;height:90px;object-fit:cover;">

        <h5 class="fw-semibold"><?= htmlspecialchars($user['nama']) ?></h5>

        <span class="badge rounded-pill mb-3
          <?= $user['status'] === 'Online'
            ? 'bg-success-subtle text-success'
            : 'bg-secondary-subtle text-muted' ?>">
          <?= htmlspecialchars($user['status']) ?>
        </span>

        <hr>

        <div class="text-start small">

          <div class="d-flex justify-content-between mb-2">
            <span class="text-muted">NIS</span>
            <span class="fw-semibold"><?= htmlspecialchars($user['nis']) ?></span>
          </div>

          <div class="d-flex justify-content-between mb-2">
            <span class="text-muted">Kelas</span>
            <span class="fw-semibold"><?= htmlspecialchars($user['kelas_id']) ?></span>
          </div>

          <div class="d-flex justify-content-between mb-2">
            <span class="text-muted">Email</span>
            <span class="fw-semibold"><?= htmlspecialchars($user['email']) ?></span>
          </div>

          <div class="mb-2">
            <span class="text-muted">Alamat</span>
            <div class="fw-semibold"><?= htmlspecialchars($user['alamat']) ?></div>
          </div>

          <div>
            <span class="text-muted">Bio</span>
            <div><?= nl2br(htmlspecialchars($user['bio'])) ?></div>
          </div>

        </div>
      </div>

      <div class="modal-footer border-0">
        <button class="btn btn-secondary rounded-pill px-4" data-bs-dismiss="modal">
          Tutup
        </button>
      </div>

    </div>
  </div>
</div>
<?php endforeach; ?>


        <!-- grafik & riwayat -->
        <div class="row g-3">
          <div class="col-lg-7">
            <div class="card-elevated">
              <div class="d-flex justify-content-between align-items-center mb-2">
                <h5 class="mb-0">Grafik Absensi (6 Bulan Terakhir)</h5>
                <small class="text-muted">Status: Hadir/Tidak Masuk</small>
              </div>
              <canvas id="chartAbsensi" style="max-height:360px"></canvas>
            </div>
          </div>

          <div class="col-lg-5">
            <div class="card-elevated">
              <div class="d-flex justify-content-between align-items-center mb-3">
                <h6 class="mb-0">Riwayat Absensi Terbaru</h6>
                <a href="../peserta/history.php" class="btn btn-sm btn-outline-secondary">Lihat Semua</a>
              </div>
              <div class="table-responsive">
                <table class="table table-sm align-middle mb-0">
                  <thead>
                    <tr>
                      <th>Tanggal</th>
                      <th>Hari</th>
                      <th>Status</th>
                      <th>Masuk</th>
                      <th>Pulang</th>
                    </tr>
                  </thead>
                  <tbody>
                    <?php if ($data_riwayat->num_rows > 0): ?>
                      <?php
                      // Array nama hari dalam Bahasa Indonesia
                      $hari_indonesia = [
                        'Sunday'    => 'Minggu',
                        'Monday'    => 'Senin',
                        'Tuesday'   => 'Selasa',
                        'Wednesday' => 'Rabu',
                        'Thursday'  => 'Kamis',
                        'Friday'    => 'Jumat',
                        'Saturday'  => 'Sabtu'
                      ];
                      ?>
                      <?php while ($r = $data_riwayat->fetch_assoc()): ?>
                        <?php
                        $hari_en = date("l", strtotime($r['tanggal']));
                        $hari_id = $hari_indonesia[$hari_en] ?? $hari_en;
                        ?>
                        <tr>
                          <td><?= date("d-m-Y", strtotime($r['tanggal'])) ?></td>
                          <td><?= $hari_id ?></td>
                          <td>
                            <?php if ($r['status'] === "Masuk"): ?>
                              <span class="badge bg-success"><?= $r['status'] ?></span>
                            <?php else: ?>
                              <span class="badge bg-danger"><?= $r['status'] ?></span>
                            <?php endif; ?>
                          </td>
                          <td><?= $r['jam_masuk'] ?: '-' ?></td>
                          <td><?= $r['jam_pulang'] ?: '-' ?></td>
                        </tr>
                      <?php endwhile; ?>
                    <?php else: ?>
                      <tr>
                        <td colspan="5" class="text-muted">Belum ada data absensi</td>
                      </tr>
                    <?php endif; ?>
                  </tbody>
                </table>
              </div>
            </div>
          </div>

         <div class="card shadow-sm mt-2">
  <div class="card-body">

    <h6 class="fw-semibold mb-3">
      <i class="bi bi-calendar-event text-primary me-1"></i>
      Agenda Singkat
    </h6>

    <?php if (mysqli_num_rows($agendaList) > 0): ?>
      <ul class="list-group list-group-flush small">
        <?php while ($ag = mysqli_fetch_assoc($agendaList)): ?>
          <li class="list-group-item px-0">
            <div class="d-flex align-items-start gap-3">

              <!-- ICON -->
              <div class="text-primary">
                <i class="bi bi-dot fs-3"></i>
              </div>

              <!-- CONTENT -->
              <div>
                <div class="fw-semibold text-dark">
                  <?= e($ag['agenda_singkat']) ?>
                </div>
                <small class="text-muted">
                  <?= date('d M Y', strtotime($ag['tanggal_mulai'])) ?>
                  <?php if ($ag['tanggal_mulai'] !== $ag['tanggal_selesai']): ?>
                    – <?= date('d M Y', strtotime($ag['tanggal_selesai'])) ?>
                  <?php endif; ?>
                </small>
              </div>

            </div>
          </li>
        <?php endwhile; ?>
      </ul>
    <?php else: ?>
      <div class="text-muted fst-italic small">
        Belum ada agenda tersedia
      </div>
    <?php endif; ?>

  </div>
</div>

    <footer class="appfoot">
      <div class="mb-1 fw-bold">AdamAkiw</div>
      <div class="small muted-small">ARTHAWARA CORP</div>
    </footer>
    </main>
  </div>
  </div>

  <!-- Modal Username & Password (tetap sama) -->
  <div class="modal fade" id="modalUsername" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
      <div class="modal-content">
        <div class="modal-header">
          <h5 class="modal-title">Ganti Username</h5>
          <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
        </div>
        <div class="modal-body">
          <form action="proses_ganti_username.php" method="POST">
            <div class="mb-3">
              <label class="form-label">Username Baru</label>
              <input type="text" name="username_baru" class="form-control" required>
            </div>
            <div class="d-flex justify-content-end">
              <button type="button" class="btn btn-secondary me-2" data-bs-dismiss="modal">Batalkan</button>
              <button type="submit" class="btn btn-primary">Simpan</button>
            </div>
          </form>
        </div>
      </div>
    </div>
  </div>

  <div class="modal fade" id="modalPassword" tabindex="-1" aria-labelledby="modalPasswordLabel" aria-hidden="true">
    <div class="modal-dialog">
      <div class="modal-content shadow-lg">
        <div class="modal-header bg-dark text-white">
          <h5 class="modal-title" id="modalPasswordLabel">Ganti Password</h5>
          <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
        </div>
        <form action="proses_ganti_password.php" method="POST">
          <div class="modal-body">
            <div class="mb-3">
              <label for="password_lama" class="form-label">Password Lama</label>
              <div class="input-group">
                <input type="password" class="form-control" id="password_lama" name="password_lama" required>
                <button class="btn btn-outline-secondary" type="button" onclick="togglePassword('password_lama', this)">
                  <i class="bi bi-eye"></i>
                </button>
              </div>
            </div>

            <div class="mb-3">
              <label for="password_baru" class="form-label">Password Baru</label>
              <div class="input-group">
                <input type="password" class="form-control" id="password_baru" name="password_baru" required>
                <button class="btn btn-outline-secondary" type="button" onclick="togglePassword('password_baru', this)">
                  <i class="bi bi-eye"></i>
                </button>
              </div>
            </div>

            <div class="mb-3">
              <label for="konfirmasi_password" class="form-label">Konfirmasi Password</label>
              <div class="input-group">
                <input type="password" class="form-control" id="konfirmasi_password" name="konfirmasi_password" required>
                <button class="btn btn-outline-secondary" type="button" onclick="togglePassword('konfirmasi_password', this)">
                  <i class="bi bi-eye"></i>
                </button>
              </div>
            </div>

          </div>
          <div class="modal-footer">
            <a href="dashboard.php" class="btn btn-secondary btn-sm"><i></i> Batalkan</a>
            <button type="submit" class="btn btn-primary">Login Ulang</button>
          </div>
        </form>
      </div>
    </div>
  </div>

  <!-- 🌟 Modal Profil User -->
  <div class="modal fade" id="userProfileModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
      <div class="modal-content border-0 shadow-lg rounded-4 profile-bg">


      <?php
// Avatar user (FINAL, konsisten, aman)
$avatarFile = (!empty($user['avatar']) && file_exists(__DIR__ . "/../avatars/" . $user['avatar']))
  ? "../avatars/" . $user['avatar']
  : "../avatars/96.png";
?>


        <!-- Modal Profil -->
<div class="modal-header border-0 flex-column text-center mt-3">
    <div class="position-relative d-inline-block">
  <img src="<?= htmlspecialchars($avatarFile, ENT_QUOTES, 'UTF-8') ?>"
       alt="Foto Profil"
       class="rounded-circle shadow-sm profile-img"
       style="width:100px; height:100px; object-fit:cover;">
  <span class="status-indicator bg-success border border-white"></span>
</div>
    <h5 class="fw-bold mb-0 text-dark mt-3"><?= htmlspecialchars($nama) ?></h5>
    <small class="text-secondary">Kelas: <?= htmlspecialchars($kelas) ?></small>
</div>
<div class="row justify-content-center mt-3">
        <div class="col-10 col-md-8">
            <ul class="list-group list-group-flush text-start">
                <li class="list-group-item d-flex justify-content-between align-items-center">
                    <span><i class="bi bi-card-text me-2"></i>NIS</span>
                    <span class="fw-semibold"><?= htmlspecialchars($nis) ?></span>
                </li>
                <li class="list-group-item d-flex justify-content-between align-items-center">
                    <span><i class="bi bi-envelope me-2"></i>Email</span>
                    <span class="fw-semibold"><?= htmlspecialchars($email ?? '-') ?></span>
                </li>
            </ul>
        </div>
    </div>
<div class="modal-body px-4 pb-4 text-center">
    <!-- Bio -->
    <?php if (!empty($bio)): ?>
        <p class="text-secondary mt-3" style="font-size: 0.95rem; line-height: 1.6;">
            “<?= htmlspecialchars($bio) ?>”
        </p>
    <?php else: ?>
        <p class="text-muted fst-italic mt-3" style="font-size: 0.9rem;">
            Belum ada bio.
        </p>
    <?php endif; ?>

    <!-- Info NIS & Email -->
    
</div>
	


          <!-- Info tambahan -->
<div class="info-wrapper mt-4">
  <div class="info-item">
    <div class="info-icon neutral">
      <i class="bi bi-person-vcard"></i>
    </div>
    <span class="info-text">Siswa</span>
  </div>

  <div class="info-item">
    <div class="info-icon active">
      <i class="bi bi-journal-check"></i>
    </div>
    <span class="info-text active-text">Aktif</span>
  </div>
</div>

        <!-- Footer -->
        <div class="modal-footer border-0 justify-content-center pb-4">
          <button type="button"
            class="btn btn-light border rounded-pill px-4 fw-semibold shadow-sm"
            data-bs-dismiss="modal">
            <i class="bi bi-x-circle me-1"></i> Tutup
          </button>
        </div>
      </div>
    </div>
  </div>

  <!-- SCRIPTS -->
  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
  <script src="https://cdn.jsdelivr.net/npm/flatpickr"></script>
  <script src="https://cdn.jsdelivr.net/npm/flatpickr/dist/l10n/id.js"></script>
  <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
  <script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.min.js"></script>

  <script>
    document.addEventListener("DOMContentLoaded", function() {

      // Flatpickr
      flatpickr(".timepicker", {
        enableTime: true,
        noCalendar: true,
        dateFormat: "H:i",
        time_24hr: true,
        minuteIncrement: 1,
        allowInput: true
      });

      flatpickr(".datepicker", {
        dateFormat: "Y-m-d",
        altInput: true,
        altFormat: "l, d F Y",
        maxDate: "today",
        locale: "id",
        disableMobile: true
      });

      // Toggle tanggal Lain di modal absensi
      document.querySelectorAll('input[name="pilihan_tanggal"]').forEach(radio => {
        radio.addEventListener('change', function() {
          const container = document.getElementById('tanggalLainContainer');
          container.style.display = this.value === 'hari_lain' ? 'block' : 'none';
        });
      });

      // Toggle password
      window.togglePassword = function(id, el) {
        const input = document.getElementById(id);
        const icon = el.querySelector("i");
        if (input.type === "password") {
          input.type = "text";
          icon.classList.replace("bi-eye", "bi-eye-slash");
        } else {
          input.type = "password";
          icon.classList.replace("bi-eye-slash", "bi-eye");
        }
      };

      // Flash message
      <?php if (isset($_SESSION['flash_msg'])): ?>
        Swal.fire({
          toast: true,
          position: 'top-end',
          icon: '<?= strpos($_SESSION['flash_msg'], "berhasil") !== false ? "success" : "error" ?>',
          title: '<?= $_SESSION['flash_msg'] ?>',
          showConfirmButton: false,
          timer: 3500,
          timerProgressBar: true,
          background: '#fff',
          color: '#000'
        });
        <?php unset($_SESSION['flash_msg']); ?>
      <?php endif; ?>

      // Sidebar toggle mobile
      document.getElementById('sidebarToggle')?.addEventListener('click', function() {
        const side = document.getElementById('sidebar');
        if (!side) return;
        side.style.display = side.style.display === 'none' || getComputedStyle(side).display === 'none' ? 'block' : 'none';
      });

      // Realtime waktu
      function updateTime() {
        const now = new Date();
        const ops = {
          weekday: 'long',
          year: 'numeric',
          month: 'long',
          day: 'numeric'
        };
        const timeStr = now.toLocaleDateString('id-ID', ops) + ' • ' + now.toLocaleTimeString('id-ID');
        document.getElementById('waktuNow').innerText = timeStr;
      }
      updateTime();
      setInterval(updateTime, 1000);

      // Chart.js init sekali
      const ctx = document.getElementById('chartAbsensi')?.getContext('2d');
      if (ctx) {
        const chart = new Chart(ctx, {
          type: 'bar',
          data: {
            labels: <?= json_encode($labelMonths) ?>,
            datasets: [{
                label: 'Hadir',
                data: <?= json_encode($counts['Masuk']) ?>,
                backgroundColor: 'rgba(11,75,120,0.85)',
                borderRadius: 6
              },
              {
                label: 'Tidak Masuk',
                data: <?= json_encode($counts['Tidak Masuk']) ?>,
                backgroundColor: 'rgba(220,53,69,0.85)',
                borderRadius: 6
              }
            ]
          },
          options: {
            responsive: true,
            maintainAspectRatio: false,
            interaction: {
              mode: 'index',
              intersect: false
            },
            plugins: {
              legend: {
                position: 'top'
              },
              tooltip: {
                mode: 'index',
                intersect: false
              }
            },
            scales: {
              x: {
                stacked: false,
                grid: {
                  display: false
                }
              },
              y: {
                beginAtZero: true,
                ticks: {
                  stepSize: 1
                }
              }
            }
          }
        });
      }

      // Preview avatar
      window.previewAvatar = function(input) {
        const file = input.files[0];
        if (!file) return;
        const reader = new FileReader();
        reader.onload = function(e) {
          ['avatarPreviewModal', 'avatarPreview'].forEach(id => {
            const el = document.getElementById(id);
            if (el) {
              if (el.tagName === 'DIV') {
                el.outerHTML = `<img id="${id}" src="${e.target.result}" class="rounded-circle border" style="width:90px;height:90px;object-fit:cover;">`;
              } else {
                el.src = e.target.result;
              }
            }
          });
        };
        reader.readAsDataURL(file);
      };

      // Tombol refresh dashboard
      document.getElementById('btnRefreshDashboard')?.addEventListener('click', () => location.reload());

    });
  </script>
<script>
function previewAvatar(input) {
  if (input.files && input.files[0]) {
    const reader = new FileReader();
    reader.onload = function (e) {
      document.getElementById('avatarPreview').src = e.target.result;
    };
    reader.readAsDataURL(input.files[0]);
  }
}
</script>
<script>
const apiBase = 'https://www.emsifa.com/api-wilayah-indonesia/api';

const provinsiSelect  = document.getElementById('provinsi');
const kabupatenSelect = document.getElementById('kabupaten');
const kecamatanSelect = document.getElementById('kecamatan');
const kelurahanSelect = document.getElementById('kelurahan');

// Helper reset dropdown
function resetSelect(select, text, disabled = true) {
  select.innerHTML = `<option value="">${text}</option>`;
  select.disabled = disabled;
}

// Load Provinsi
fetch(`${apiBase}/provinces.json`)
  .then(res => res.json())
  .then(data => {
    resetSelect(provinsiSelect, '-- Pilih Provinsi --', false);
    data.forEach(p => {
      provinsiSelect.innerHTML += `<option value="${p.name}" data-id="${p.id}">${p.name}</option>`;
    });
  });

// Provinsi → Kabupaten
provinsiSelect.addEventListener('change', function () {
  const id = this.options[this.selectedIndex].dataset.id;
  resetSelect(kabupatenSelect, 'Memuat Kabupaten/Kota...');
  resetSelect(kecamatanSelect, 'Pilih Kabupaten/Kota terlebih dahulu');
  resetSelect(kelurahanSelect, 'Pilih Kecamatan terlebih dahulu');

  if (!id) return;

  fetch(`${apiBase}/regencies/${id}.json`)
    .then(res => res.json())
    .then(data => {
      resetSelect(kabupatenSelect, '-- Pilih Kabupaten / Kota --', false);
      data.forEach(k => {
        kabupatenSelect.innerHTML += `<option value="${k.name}" data-id="${k.id}">${k.name}</option>`;
      });
    });
});

// Kabupaten → Kecamatan
kabupatenSelect.addEventListener('change', function () {
  const id = this.options[this.selectedIndex].dataset.id;
  resetSelect(kecamatanSelect, 'Memuat Kecamatan...');
  resetSelect(kelurahanSelect, 'Pilih Kecamatan terlebih dahulu');

  if (!id) return;

  fetch(`${apiBase}/districts/${id}.json`)
    .then(res => res.json())
    .then(data => {
      resetSelect(kecamatanSelect, '-- Pilih Kecamatan --', false);
      data.forEach(k => {
        kecamatanSelect.innerHTML += `<option value="${k.name}" data-id="${k.id}">${k.name}</option>`;
      });
    });
});

// Kecamatan → Kelurahan
kecamatanSelect.addEventListener('change', function () {
  const id = this.options[this.selectedIndex].dataset.id;
  resetSelect(kelurahanSelect, 'Memuat Kelurahan...');

  if (!id) return;

  fetch(`${apiBase}/villages/${id}.json`)
    .then(res => res.json())
    .then(data => {
      resetSelect(kelurahanSelect, '-- Pilih Kelurahan / Desa --', false);
      data.forEach(k => {
        kelurahanSelect.innerHTML += `<option value="${k.name}">${k.name}</option>`;
      });
    });
});
</script>

</body>

</html>