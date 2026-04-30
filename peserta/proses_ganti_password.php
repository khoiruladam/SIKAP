<?php
session_start();
include "../config/koneksi.php";

if (!isset($_SESSION['id'])) {
    header("Location: index.php");
    exit;
}

$id = $_SESSION['id'];
$password_lama = $_POST['password_lama'];
$password_baru = $_POST['password_baru'];
$konfirmasi = $_POST['konfirmasi_password'];

// Ambil password user dari database
$result = mysqli_query($koneksi, "SELECT password FROM users WHERE id='$id'");
$data = mysqli_fetch_assoc($result);

// Siapkan pesan alert
$alert = [];

if ($password_lama === $data['password']) {
    if ($password_baru !== $konfirmasi) {
        $alert = [
            'icon' => 'error',
            'title' => 'Gagal',
            'text' => 'Konfirmasi password tidak cocok!'
        ];
        $_SESSION['alert'] = $alert;
        header("Location: ../peserta/dashboard.php");
        exit;
    } else {
        $query = mysqli_query($koneksi, "UPDATE users SET password='$password_baru' WHERE id='$id'");
        if ($query) {
            // Simpan alert sukses DISESSION sebelum logout
            $_SESSION['alert_login'] = [
                'icon' => 'success',
                'title' => 'Berhasil',
                'text' => 'Password berhasil diganti! Silakan login ulang.'
            ];

            // Hapus session lama agar logout
            session_unset();
            session_destroy();

            // Redirect ke halaman login
            header("Location: ../index.php");
            exit;
        } else {
            $alert = [
                'icon' => 'error',
                'title' => 'Gagal',
                'text' => 'Gagal mengganti password!'
            ];
            $_SESSION['alert'] = $alert;
            header("Location: ../peserta/dashboard.php");
            exit;
        }
    }
} else {
    $alert = [
        'icon' => 'error',
        'title' => 'Gagal',
        'text' => 'Password lama tidak cocok!'
    ];
    $_SESSION['alert'] = $alert;
    header("Location: ../peserta/dashboard.php");
    exit;
}
