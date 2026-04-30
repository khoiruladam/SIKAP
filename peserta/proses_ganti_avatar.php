<?php
session_start();
include '../config/koneksi.php';

// Pastikan user login
if (!isset($_SESSION['id'])) {
    header("Location: ../index.php");
    exit;
}

$user_id = $_SESSION['id'];
$upload_dir = "../avatars/";

// Pastikan ada file yang diupload
if (isset($_FILES['avatar']) && $_FILES['avatar']['error'] === 0) {
    $file_tmp = $_FILES['avatar']['tmp_name'];
    $file_name = basename($_FILES['avatar']['name']);
    $file_ext  = strtolower(pathinfo($file_name, PATHINFO_EXTENSION));

    // Validasi ekstensi
    $allowed_ext = ['jpg','jpeg','png'];
    if (!in_array($file_ext, $allowed_ext)) {
        $_SESSION['error'] = "Format file tidak diperbolehkan!";
        header("Location: ../dashboard.php");
        exit;
    }

    // Nama file baru: userID_timestamp.ext
    $new_name = $user_id . '_' . time() . '.' . $file_ext;
    $target_file = $upload_dir . $new_name;

    if (move_uploaded_file($file_tmp, $target_file)) {
        // Hapus avatar lama jika bukan default
        $stmt = $koneksi->prepare("SELECT avatar FROM users WHERE id=?");
        $stmt->bind_param("i", $user_id);
        $stmt->execute();
        $result = $stmt->get_result()->fetch_assoc();
        $stmt->close();

        if (!empty($result['avatar']) && $result['avatar'] != '96.png' && file_exists($upload_dir . $result['avatar'])) {
            unlink($upload_dir . $result['avatar']);
        }

        // Update database
        $stmt = $koneksi->prepare("UPDATE users SET avatar=? WHERE id=?");
        $stmt->bind_param("si", $new_name, $user_id);
        $stmt->execute();
        $stmt->close();

        $_SESSION['avatar'] = $new_name;
        $_SESSION['success'] = "Avatar berhasil diubah!";
    } else {
        $_SESSION['error'] = "Gagal mengupload avatar!";
    }
} else {
    $_SESSION['error'] = "Tidak ada file yang dipilih!";
}

header("Location: ../dashboard.php");
exit;
