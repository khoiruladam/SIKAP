<?php
date_default_timezone_set('Asia/Jakarta');
?>
<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <title>Sistem Dalam Perbaikan</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;600&display=swap" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
<link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css" rel="stylesheet">
    <link rel="icon" type="image/png" href="pngwing.com (1).png">   

    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
            font-family: 'Poppins', sans-serif;
        }

        /* ================= BACKGROUND ================= */
        body {
            min-height: 100vh;
            background:
                linear-gradient(rgba(0, 0, 0, 0.45), rgba(0, 0, 0, 0.45)),
                url('../imge/perbaikan/bg-pc.png') center center / cover no-repeat;
            display: flex;
            align-items: center;
            justify-content: center;
            overflow: hidden;
        }

        /* ================= CARD ================= */
        .card {
            background: rgba(255, 255, 255, 0.92);
            backdrop-filter: blur(6px);
            border-radius: 22px;
            padding: 44px 38px;
            max-width: 420px;
            width: 90%;
            text-align: center;
            box-shadow: 0 35px 80px rgba(0, 0, 0, 0.45);
            animation: fadeUp 1s ease forwards;
            position: relative;
            z-index: 10;
            /* PALING ATAS */
        }

        @keyframes fadeUp {
            from {
                opacity: 0;
                transform: translateY(40px);
            }

            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        .icon {
            width: 88px;
            height: 88px;
            margin: 0 auto 20px;
            border-radius: 50%;
            background: #f1f1f1;
            display: flex;
            align-items: center;
            justify-content: center;
            animation: pulse 2s infinite;
        }

        .icon img {
            width: 48px;
            height: auto;
            animation: floatIcon 3s ease-in-out infinite;
        }

        @keyframes floatIcon {
            0% {
                transform: translateY(0);
            }

            50% {
                transform: translateY(-6px);
            }

            100% {
                transform: translateY(0);
            }
        }


        @keyframes pulse {
            0% {
                box-shadow: 0 0 0 0 rgba(0, 0, 0, 0.2);
            }

            70% {
                box-shadow: 0 0 0 18px rgba(0, 0, 0, 0);
            }

            100% {
                box-shadow: 0 0 0 0 rgba(0, 0, 0, 0);
            }
        }

        h1 {
            font-size: 22px;
            font-weight: 600;
            margin-bottom: 12px;
            color: #222;
        }

        p {
            font-size: 14px;
            line-height: 1.7;
            color: #444;
        }

        .time {
            margin-top: 22px;
            font-size: 13px;
            color: #666;
        }

        .btn-back {
            display: inline-block;
            margin-top: 28px;
            padding: 10px 28px;
            border-radius: 30px;
            background: #2c5364;
            color: #fff;
            text-decoration: none;
            font-size: 13px;
            transition: 0.3s ease;
        }

        .btn-back:hover {
            background: #203a43;
            transform: translateY(-2px);
        }

        footer {
            margin-top: 26px;
            font-size: 12px;
            color: #888;
        }

        /* ================= BUBBLE ================= */
        .bubble-img {
            position: absolute;
            z-index: 1;
            opacity: 0.35;
            animation: floatImg linear infinite;
            pointer-events: none;
        }

        /* Default DESKTOP */
        .bubble-mobile-1,
        .bubble-mobile-2 {
            display: none;
        }

        @keyframes floatImg {
            from {
                transform: translateY(120vh);
            }

            to {
                transform: translateY(-140vh);
            }
        }

        /* ================= MOBILE ================= */
@media (max-width: 768px) {

    body {
        background:
            linear-gradient(rgba(0, 0, 0, 0.55), rgba(0, 0, 0, 0.55)),
            url('../imge/perbaikan/bg-mobile.png') center top / cover no-repeat;
    }

    .card {
        padding: 36px 26px;
    }

    .bubble-desktop {
        display: none;
    }

    .bubble-mobile-1,
    .bubble-mobile-2 {
        display: block;
    }

    .bubble-mobile-1 {
        width: 50px;
        left: 10%;
        animation-duration: 32s;
    }

    .bubble-mobile-2 {
        width: 75px;
        left: 65%;
        animation-duration: 38s;
    }
}

    </style>
</head>

<body>

    <!-- Bubble DESKTOP -->
    <img src="../imge/perbaikan/bubble1.png" class="bubble-img bubble-desktop" style="width:90px; left:12%; animation-duration:24s;">
    <img src="../imge/perbaikan/bubble2.png" class="bubble-img bubble-desktop" style="width:140px; left:65%; animation-duration:30s;">
    <img src="../imge/perbaikan/bubble3.png" class="bubble-img bubble-desktop" style="width:70px; left:40%; animation-duration:20s;">

    <!-- Bubble MOBILE -->
    <img src="../imge/perbaikan/bubble1.png" class="bubble-img bubble-mobile-1">
    <img src="../imge/perbaikan/bubble2.png" class="bubble-img bubble-mobile-2">

    <div class="card">
        <div class="icon">
            <img src="pngwing.com (1).png" alt="Maintenance">
        </div>


        <h1>Sistem Sedang Dalam Perbaikan</h1>

        <p>
            Website absensi sementara tidak dapat diakses.<br>
            Kami sedang melakukan peningkatan sistem agar
            lebih stabil dan optimal.
        </p>

        <div class="time">
            <?= date('l, d F Y H:i'); ?> WIB
        </div>

        <a href="javascript:history.back()" class="btn-back">
            ← Kembali ke halaman sebelumnya
        </a>

        <footer>
            © <?= date('Y'); ?> Sistem Absensi Berbasis Digital
        </footer>
    </div>

</body>

</html>