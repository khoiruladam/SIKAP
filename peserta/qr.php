<?php
session_start();
include '../config/koneksi.php';

if (!isset($_SESSION['id'])) {
    header("Location: ../index.php");
    exit;
}

$id = (int)$_SESSION['id'];

/* ================= USER ================= */
$stmt = $koneksi->prepare("SELECT nama, avatar, token FROM users WHERE id = ?");
$stmt->bind_param("i", $id);
$stmt->execute();
$user = $stmt->get_result()->fetch_assoc();
$stmt->close();

if (!$user) {
    die("User tidak ditemukan");
}

/* ================= AUTO TOKEN ================= */
if (empty($user['token'])) {
    $user['token'] = bin2hex(random_bytes(16));
    $up = $koneksi->prepare("UPDATE users SET token = ? WHERE id = ?");
    $up->bind_param("si", $user['token'], $id);
    $up->execute();
    $up->close();
}

/* ================= DATA ================= */
$nama  = htmlspecialchars($user['nama']);
$token = htmlspecialchars($user['token']);

$avatarPath = "../avatars/";
$foto = (!empty($user['avatar']) && file_exists($avatarPath . $user['avatar']))
    ? $avatarPath . $user['avatar']
    : $avatarPath . "96.png";

$qr_url = "https://" . $_SERVER['HTTP_HOST'] . "/peserta/absen.php?token=" . $token;
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>QR Code & Token</title>
    <meta name="viewport" content="width=device-width, initial-scale=1">

    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons/font/bootstrap-icons.css" rel="stylesheet">
    <script src="https://cdnjs.cloudflare.com/ajax/libs/qrcodejs/1.0.0/qrcode.min.js"></script>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="icon" type="image/png" href="pngwing.com (1).png">    

    <style>
        body {
    min-height: 100vh;
    background:
        linear-gradient(rgba(0,0,0,0.35), rgba(0,0,0,0.35)),
        url('../imge/bg_pc.png') center center / cover no-repeat;
    font-family: Arial, sans-serif;
    display: flex;
    align-items: center;
    justify-content: center;
}

        .card-wrapper {
            max-width: 520px;
            margin: auto;
        }
        .id-card {
            background: #ffffff;
            border-radius: 26px;
            padding: 32px;
            box-shadow: 0 25px 60px rgba(0, 0, 0, .18);
        }
        .id-header {
            text-align: center;
            margin-bottom: 22px;
        }
        .avatar {
            width: 110px;
            height: 110px;
            border-radius: 50%;
            object-fit: cover;
            border: 4px solid #dee2e6;
        }
        .qr-box {
            background: #fff;
            padding: 26px;
            border-radius: 22px;
            border: 3px dashed #adb5bd;
            display: flex;
            justify-content: center;
            margin-top: 18px;
        }
        .qr-note {
            font-size: 14px;
            color: #6c757d;
            text-align: center;
            margin-top: 12px;
        }
        /* ================= MOBILE ================= */
@media (max-width: 768px) {
    body {
        background:
            linear-gradient(rgba(0,0,0,0.45), rgba(0,0,0,0.45)),
            url('../imge/bg_mobile.png') center top / cover no-repeat;
    }
}
        	.download-wrapper {
    display: flex;
    justify-content: center;
    gap: 16px;
}

/* ================= BACK BUTTON ================= */
.btn-back-page {
    display: flex;
    align-items: center;
    gap: 10px;
    padding: 12px 24px;
    border-radius: 50px;
    border: 2px solid #dee2e6;
    background: #ffffff;
    color: #495057;
    font-size: 14px;
    font-weight: 600;
    cursor: pointer;
    transition: all .3s ease;
}

.btn-back-page .icon {
    width: 34px;
    height: 34px;
    border-radius: 50%;
    background: #f1f3f5;
    display: flex;
    align-items: center;
    justify-content: center;
}

.btn-back-page:hover {
    background: #f8f9fa;
    transform: translateY(-2px);
}

/* ================= DOWNLOAD BUTTON ================= */
.btn-download {
    display: flex;
    align-items: center;
    gap: 10px;
    padding: 12px 26px;
    border-radius: 50px;
    border: none;
    background: linear-gradient(135deg, #0d6efd, #4dabf7);
    color: #fff;
    font-size: 14px;
    font-weight: 600;
    cursor: pointer;
    box-shadow: 0 14px 35px rgba(13, 110, 253, 0.35);
    transition: all .35s ease;
    position: relative;
    overflow: hidden;
}

.btn-download::before {
    content: "";
    position: absolute;
    inset: 0;
    background: linear-gradient(
        120deg,
        transparent,
        rgba(255,255,255,.35),
        transparent
    );
    transform: translateX(-120%);
    transition: .6s;
}

.btn-download:hover::before {
    transform: translateX(120%);
}

.btn-download:hover {
    transform: translateY(-3px) scale(1.02);
    box-shadow: 0 22px 50px rgba(13, 110, 253, 0.5);
}

.btn-download:active {
    transform: scale(.96);
}

.btn-download .icon {
    width: 34px;
    height: 34px;
    border-radius: 50%;
    background: rgba(255,255,255,.2);
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 16px;
}
.token-box code {
            font-size: 13px;
            background: #f8f9fa;
            border: 1px dashed #adb5bd;
        }        


    </style>
</head>

<body>

<div class="container min-vh-100 d-flex align-items-center justify-content-center">
    <div class="card-wrapper">

        <div class="id-card" id="capture">
            <div class="id-header">
                <img src="<?= $foto ?>" class="avatar mb-3">
                <h4 class="fw-bold mb-0"><?= $nama ?></h4>
                <small class="text-muted">Peserta</small>
            </div>

            <div class="qr-box">
                <div id="qrcode"></div>
            </div>

            <div class="qr-note">
                Scan QR ini untuk melakukan absensi
            </div>
            <!-- TOKEN -->
                <div class="token-box mt-3 text-center">
                    <small class="text-muted d-block mb-1">Token Absensi</small>
                    <div class="d-flex justify-content-center align-items-center gap-2">
                        <code id="tokenText" class="px-3 py-2 rounded user-select-all">
                            <?= $token ?>
                        </code>
                        <button class="btn btn-sm btn-outline-secondary" onclick="copyToken()" title="Salin Token">
                            <i class="bi bi-clipboard"></i>
                        </button>
                    </div>
                </div>
        </div>

       <div class="download-wrapper mt-4">
    <button class="btn-back-page" onclick="history.back()">
        <span class="icon">
            <i class="bi bi-arrow-left"></i>
        </span>
        <span class="text">Kembali</span>
    </button>

    <button class="btn-download" onclick="downloadCard()">
        <span class="icon">
            <i class="bi bi-download"></i>
        </span>
        <span class="text">Download PNG</span>
    </button>
</div>



    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/html2canvas@1.4.1/dist/html2canvas.min.js"></script>

<script>
        /* ================= QR TAJAM ================= */
        new QRCode(document.getElementById("qrcode"), {
            text: "<?= $qr_url ?>",
            width: 320,
            height: 320,
            colorDark: "#000000",
            colorLight: "#ffffff",
            correctLevel: QRCode.CorrectLevel.H
        });

        /* ================= COPY TOKEN ================= */
        function copyToken() {
            const token = document.getElementById("tokenText").innerText;
            navigator.clipboard.writeText(token).then(() => {
                alert("Token berhasil disalin");
            });
        }

        /* ================= DOWNLOAD PNG ================= */
        function downloadCard() {
            html2canvas(document.querySelector("#capture"), {
                scale: 8,
                backgroundColor: "#ffffff",
                useCORS: true
            }).then(canvas => {
                const link = document.createElement('a');
                link.href = canvas.toDataURL("image/png");
                link.download = "ID_CARD_<?= preg_replace('/\s+/', '_', $nama) ?>.png";
                link.click();
            });
        }
    </script>

</body>
</html>
