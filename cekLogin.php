<?php
session_start();
include 'config/koneksi.php'; // pastikan path sesuai lokasi file

// Pastikan request dari form login
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username'] ?? '');
    $nis      = trim($_POST['nis'] ?? '');

    if ($username === '' || $nis === '') {
        $_SESSION['error'] = "Harap isi username dan NIS.";
        header("Location: index.php");
        exit;
    }

    // Gunakan prepared statement untuk keamanan
    $stmt = $koneksi->prepare("SELECT id, username, nama, nis, role FROM users WHERE username = ? AND nis = ? LIMIT 1");
    $stmt->bind_param("ss", $username, $nis);
    $stmt->execute();
    $result = $stmt->get_result();
    $user = $result->fetch_assoc();
    $stmt->close();

    if ($user) {
        // Simpan data ke session
        $_SESSION['id']       = $user['id'];
        $_SESSION['username'] = $user['username'];
        $_SESSION['nama']     = $user['nama'];
        $_SESSION['nis']      = $user['nis'];
        $_SESSION['role']     = $user['role'] ?? 'peserta'; // fallback jika kolom role tidak ada

        // Redirect sesuai role
        if (!empty($user['role']) && $user['role'] === 'admin') {
            header("Location: admin/dashboard.php");
        } else {
            header("Location: peserta/dashboard.php");
        }
        exit;
    } else {
        $_SESSION['error'] = "Username atau NIS salah!";
        header("Location: index.php");
        exit;
    }

} else {
    // Jika diakses tanpa POST
    header("Location: index.php");
    exit;
}
?>
