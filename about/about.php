<!DOCTYPE html>
<html lang="id">
<head>
  <meta charset="UTF-8">
  <title>Tentang Sistem | Sistem Absensi</title>
  <meta name="viewport" content="width=device-width, initial-scale=1">

  <!-- Bootstrap -->
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
  <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons/font/bootstrap-icons.css" rel="stylesheet">
  <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700&display=swap" rel="stylesheet">  
  <link rel="icon" type="image/png" href="pngwing.com(1).png">  

  <style>
    body {
      background: #f4f6f9;
      font-size: 0.9rem;
      color: #212529;
    }

    /* ================= HEADER ================= */
    .page-header {
      background: #0b4b78;
      color: #fff;
      padding: 14px 0;
    }

    .page-header h1 {
      font-size: 1.15rem;
      font-weight: 600;
      margin: 0;
    }

    /* ================= KOP SURAT ================= */
    .kop-surat {
      border-bottom: 3px solid #0b4b78;
      padding-bottom: 12px;
      margin-bottom: 18px;
      text-align: center;
    }

    .kop-title {
      font-size: 1.05rem;
      font-weight: 700;
      color: #0b4b78;
    }

    .kop-subtitle {
      font-size: 0.8rem;
      color: #6c757d;
    }

    /* ================= CARD ================= */
    .card {
      border-radius: 6px;
      border: 1px solid #dee2e6;
      margin-bottom: 14px;
    }

    .section-title {
      font-size: 0.95rem;
      font-weight: 600;
      color: #0b4b78;
      margin-bottom: 6px;
    }

    footer {
      font-size: 0.75rem;
      color: #6c757d;
      border-top: 1px solid #dee2e6;
      padding: 12px 0;
      margin-top: 40px;
    }

    /* ================= PRINT MODE ================= */
    @media print {
      .no-print {
        display: none !important;
      }
      body {
        background: #fff;
      }
      .card {
        border: none;
      }
    }
  </style>
</head>

<body>

<!-- HEADER -->
<div class="page-header no-print">
  <div class="container d-flex justify-content-between align-items-center">
    <h1><i class="bi bi-info-circle me-1"></i> Tentang Sistem</h1>

    <!-- BUTTON KEMBALI -->
    <a href="../peserta/dashboard.php" class="btn btn-sm btn-light">
      <i class="bi bi-arrow-left"></i> Kembali
    </a>
  </div>
</div>

<div class="container my-4">

  <!-- KOP SURAT -->
  <div class="kop-surat">
    <div class="kop-title">SISTEM ABSENSI DIGITAL</div>
    <div class="kop-subtitle">
      ARTHAWARA CORPORATION<br>
      Sistem Informasi Internal
    </div>
  </div>

  <!-- NOMOR DOKUMEN -->
  <div class="text-center mb-3" style="font-size:0.8rem;">
    <strong>Nomor Dokumen:</strong> 001/ABSEN-ARW/VI/2025<br>
    <strong>Tanggal Berlaku:</strong> 5 September 2025
  </div>

  <!-- PROFIL -->
  <div class="card">
    <div class="card-body">
      <div class="section-title">Profil Sistem</div>
      <p class="mb-0">
        Sistem Absensi Digital merupakan aplikasi internal yang digunakan
        untuk pencatatan kehadiran peserta secara elektronik, terstruktur,
        dan terdokumentasi sebagai bagian dari administrasi.
      </p>
    </div>
  </div>

  <!-- FUNGSI -->
  <div class="card">
    <div class="card-body">
      <div class="section-title">Fungsi Utama</div>
      <ul class="mb-0">
        <li>Pencatatan absensi harian</li>
        <li>Pengelolaan dan rekapitulasi data kehadiran</li>
        <li>Dokumentasi absensi secara digital</li>
        <li>Peningkatan akurasi dan transparansi data</li>
      </ul>
    </div>
  </div>

  <!-- DASAR HUKUM -->
  <div class="card">
    <div class="card-body">
      <div class="section-title">Dasar Hukum</div>
      <ul class="mb-0">
        <li>UU No. 11 Tahun 2008 tentang Informasi dan Transaksi Elektronik</li>
        <li>UU No. 19 Tahun 2016 tentang Perubahan UU ITE</li>
        <li>Peraturan Pemerintah tentang Penyelenggaraan Sistem Elektronik</li>
        <li>Ketentuan internal administrasi kehadiran</li>
      </ul>
    </div>
  </div>

  <!-- KEBIJAKAN PRIVASI -->
  <div class="card">
    <div class="card-body">
      <div class="section-title">Kebijakan Privasi</div>
      <ul class="mb-0">
        <li>Data pengguna bersifat rahasia dan dilindungi</li>
        <li>Digunakan hanya untuk kepentingan administrasi internal</li>
        <li>Tidak didistribusikan ke pihak ketiga tanpa izin resmi</li>
        <li>Keamanan akun menjadi tanggung jawab pengguna</li>
      </ul>
    </div>
  </div>

  <!-- PENGELOLA -->
  <div class="card">
    <div class="card-body">
      <div class="section-title">Pengelola Sistem</div>
      <p class="mb-0 fw-semibold">ARTHAWARA CORPORATION</p>
    </div>
  </div>

  <!-- TANDA TANGAN -->
<div class="row mt-5">
  <div class="col-md-6"></div>

  <div class="col-md-4 text-center">
    <p class="mb-1">Ditetapkan di: Kuningan</p>
    <p class="mb-2">Pada tanggal: 5 September 2025</p>

    <!-- TANDA TANGAN -->
    <img
      src="../imge/tanda_tangan.png"
      alt="Tanda Tangan"
      style="height:130px; margin-bottom:-10px;"
    >

    <p class="fw-semibold mb-0">Kepala Pengelola Sistem</p>
    <p class="mb-0"><strong>KHOIRUL ADAM</strong></p>
    <p class="text-muted small mb-0">NIP / ID: 202601001.6764</p>
  </div>
</div>
</div>
	
         <!-- AKSI DOKUMEN -->
<div class="container no-print">
  <div class="d-flex justify-content-end mt-4">

    <div class="dropdown">
      <button
        class="btn btn-sm btn-outline-secondary dropdown-toggle"
        type="button"
        data-bs-toggle="dropdown"
        aria-expanded="false">
        <i class="bi bi-file-earmark-text"></i> Aksi Dokumen
      </button>

      <ul class="dropdown-menu dropdown-menu-end shadow-sm">
        <li>
          <button class="dropdown-item" onclick="window.print()">
            <i class="bi bi-printer me-2"></i> Cetak
          </button>
        </li>
        <li>
          <button class="dropdown-item" onclick="exportPDF()">
            <i class="bi bi-file-earmark-pdf me-2"></i> Simpan PDF
          </button>
        </li>
        <li>
          <button class="dropdown-item" onclick="exportDOC()">
            <i class="bi bi-file-earmark-word me-2"></i> Simpan DOC
          </button>
        </li>
      </ul>
    </div>

  </div>
</div>


          
<!-- FOOTER -->
<footer class="text-center">
  Website Version 1.0.0.1 • ARTHAWARA CORPORATION
</footer>

</body>
        <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
        <script>
  function exportPDF() {
    alert('Gunakan Cetak → Save as PDF pada browser.\n(Fitur PDF otomatis dapat ditambahkan)');
    window.print();
  }

  function exportDOC() {
    alert('Ekspor DOC memerlukan proses server-side.\nFitur ini siap dikembangkan.');
  }
</script>

</html>
