<?php
session_start();
include '../config/koneksi.php';

if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
    header("Location: ../index.php");
    exit;
}

/* ================= PATH ================= */
$uploadPath = realpath(__DIR__ . '/../uploads/pembelajaran') . '/';
$uploadUrl  = '../uploads/pembelajaran/';
$materiDir  = $uploadPath . 'materi/';
if (!is_dir($materiDir)) mkdir($materiDir, 0755, true);

/* ================= SAVE ================= */
if ($_SERVER['REQUEST_METHOD'] === 'POST' && empty($_POST['edit_id'])) {

    $judul = mysqli_real_escape_string($koneksi, $_POST['judul']);
    $mapel = mysqli_real_escape_string($koneksi, $_POST['mapel']);
    $guru  = mysqli_real_escape_string($koneksi, $_POST['guru_nama']);
    $ket   = mysqli_real_escape_string($koneksi, $_POST['keterangan']);
    $video = mysqli_real_escape_string($koneksi, $_POST['video_url']);

    $foto = '';
    if (!empty($_FILES['foto']['name'])) {
        $ext  = pathinfo($_FILES['foto']['name'], PATHINFO_EXTENSION);
        $foto = uniqid('thumb_') . '.' . $ext;
        move_uploaded_file($_FILES['foto']['tmp_name'], $uploadPath . $foto);
    }

    $file = '';
    if (!empty($_FILES['file_materi']['name'])) {
        $ext  = pathinfo($_FILES['file_materi']['name'], PATHINFO_EXTENSION);
        $file = uniqid('materi_') . '.' . $ext;
        move_uploaded_file($_FILES['file_materi']['tmp_name'], $materiDir . $file);
    }

    mysqli_query($koneksi, "
        INSERT INTO pembelajaran
        (judul,mapel,guru_nama,keterangan,foto,video_url,file_materi,created_at)
        VALUES
        ('$judul','$mapel','$guru','$ket','$foto','$video','$file',NOW())
    ");

    echo json_encode(['status' => 'ok']);
    exit;
}

/* ================= DATA ================= */
$data = mysqli_query($koneksi, "SELECT * FROM pembelajaran ORDER BY created_at DESC");

/* ================= LOG STAT ================= */
$buka = $video = [];

$q1 = mysqli_query($koneksi, "
    SELECT materi_id, COUNT(DISTINCT user_id) total
    FROM pembelajaran_log
    WHERE aksi='buka_materi'
    GROUP BY materi_id
");
while ($r = mysqli_fetch_assoc($q1)) $buka[$r['materi_id']] = $r['total'];

$q2 = mysqli_query($koneksi, "
    SELECT materi_id, COUNT(DISTINCT user_id) total
    FROM pembelajaran_log
    WHERE aksi='tonton_video'
    GROUP BY materi_id
");
while ($r = mysqli_fetch_assoc($q2)) $video[$r['materi_id']] = $r['total'];

/* ================= FUNCTION ================= */
function embedVideo($url)
{
    if (!$url) return '';
    if (preg_match('~watch\?v=([^\&]+)~', $url, $m))
        return 'https://www.youtube.com/embed/' . $m[1];
    if (preg_match('~youtu\.be/([^?\&]+)~', $url, $m))
        return 'https://www.youtube.com/embed/' . $m[1];
    if (preg_match('~/d/([^/]+)~', $url, $m))
        return 'https://drive.google.com/file/d/' . $m[1] . '/preview';
    return '';
}
?>
<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <title>Kelola Pembelajaran</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        body {
            background: #f4f6f9
        }

        .course-card {
            transition: .2s
        }

        .course-card:hover {
            transform: translateY(-4px)
        }

        .course-card img {
            height: 160px;
            object-fit: cover
        }

        .drag {
            border: 2px dashed #ced4da;
            padding: 14px;
            border-radius: 8px;
            text-align: center;
            cursor: pointer
        }
        .desc-preview {
    font-size: 0.85rem;
    color: #6c757d;
    line-height: 1.4;
    max-height: 3.6em; /* ± 3 baris */
    overflow: hidden;
}

    </style>
</head>

<body>
    <div class="container py-4">
        <h4 class="fw-bold mb-3">📚 Kelola Pembelajaran</h4>

        <!-- FORM -->
        <div class="card shadow-sm mb-4">
            <div class="card-body">
                <form id="formMateri" enctype="multipart/form-data">
                    <div class="row g-3">

                        <div class="col-md-4">
                            <label>Thumbnail</label>
                            <div class="drag" onclick="foto.click()">Pilih Gambar</div>
                            <input type="file" name="foto" id="foto" class="d-none" accept="image/*">
                            <img id="prevFoto" class="img-fluid mt-2 d-none rounded">
                        </div>

                        <div class="col-md-8">
                            <label>Video (YouTube / Drive)</label>
                            <input type="url" name="video_url" id="video_url" class="form-control">
                            <div class="ratio ratio-16x9 mt-2 d-none" id="videoBox">
                                <iframe id="videoPreview" allowfullscreen></iframe>
                            </div>
                        </div>

                        <div class="col-md-6">
                            <label>Judul</label>
                            <input type="text" name="judul" class="form-control" required>
                        </div>

                        <div class="col-md-3">
                            <label>Mapel</label>
                            <input type="text" name="mapel" class="form-control" required>
                        </div>

                        <div class="col-md-3">
                            <label>Guru</label>
                            <input type="text" name="guru_nama" class="form-control" required>
                        </div>

                        <div class="col-md-6">
                            <label>File Materi</label>
                            <div class="drag" onclick="file_materi.click()">Upload File</div>
                            <input type="file" name="file_materi" id="file_materi" class="d-none">
                            <div id="fileInfo" class="small text-muted"></div>
                        </div>

                        <div class="col-12">
                            <label>Deskripsi</label>
                            <textarea name="keterangan" class="form-control"></textarea>
                        </div>

                    </div>

                    <button type="button" id="btnSave" class="btn btn-primary mt-3">Simpan Materi</button>

                    <div class="progress mt-3 d-none" id="progressBox">
                        <div class="progress-bar progress-bar-striped progress-bar-animated" id="progressBar">0%</div>
                    </div>

                </form>
            </div>
        </div>

        <!-- LIST -->
<div class="row g-4">
<?php while ($r = mysqli_fetch_assoc($data)):
    $img   = $r['foto'] ? $uploadUrl . $r['foto'] : '../imge/bg_pc.png';
    $embed = embedVideo($r['video_url']);
?>
    <div class="col-md-4">
        <div class="card course-card shadow-sm h-100">

            <img src="<?= htmlspecialchars($img) ?>" alt="Thumbnail">

            <div class="card-body d-flex flex-column">

                <h6 class="fw-semibold mb-1">
                    <?= htmlspecialchars($r['judul']) ?>
                </h6>

                <small class="text-muted mb-1">
                    <?= htmlspecialchars($r['mapel']) ?> • <?= htmlspecialchars($r['guru_nama']) ?>
                </small>

                <!-- KETERANGAN -->
                <?php if (!empty($r['keterangan'])): ?>
                    <div class="desc-preview mb-2">
                        <?= nl2br(htmlspecialchars($r['keterangan'])) ?>
                    </div>
                <?php endif; ?>

                <!-- STAT -->
                <p class="small text-secondary mb-2">
                    👀 Dibuka: <?= $buka[$r['id']] ?? 0 ?> siswa<br>
                    ▶️ Nonton: <?= $video[$r['id']] ?? 0 ?> siswa
                </p>

                <!-- VIDEO -->
                <?php if ($embed): ?>
                    <div class="ratio ratio-16x9 mb-2">
                        <iframe
                            src="<?= htmlspecialchars($embed) ?>"
                            allow="accelerometer; autoplay; clipboard-write; encrypted-media; gyroscope; picture-in-picture"
                            allowfullscreen>
                        </iframe>
                    </div>
                <?php endif; ?>

                <!-- FILE -->
                <?php if ($r['file_materi']): ?>
                    <a href="<?= $uploadUrl . 'materi/' . $r['file_materi'] ?>"
                       class="btn btn-outline-success btn-sm mb-2">
                        Download Materi
                    </a>
                <?php endif; ?>

                <!-- ACTION -->
                <div class="mt-auto">
                    <a href="quiz_buat.php?materi_id=<?= $r['id'] ?>"
                       class="btn btn-warning btn-sm w-100 mb-2">
                        Buat Quiz
                    </a>

                    <div class="d-flex gap-1">
                        <button class="btn btn-outline-primary btn-sm w-50">
                            Edit
                        </button>
                        <button class="btn btn-outline-danger btn-sm w-50"
                                onclick="hapusMateri(<?= $r['id'] ?>)">
                            Hapus
                        </button>
                    </div>
                </div>

            </div>
        </div>
    </div>
<?php endwhile; ?>
</div>


    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        foto.onchange = () => {
            prevFoto.src = URL.createObjectURL(foto.files[0]);
            prevFoto.classList.remove('d-none')
        }
        file_materi.onchange = () => fileInfo.innerText = file_materi.files[0].name
        video_url.oninput = () => {
            videoPreview.src = video_url.value;
            videoBox.classList.remove('d-none')
        }

        btnSave.onclick = () => {
            const x = new XMLHttpRequest();
            x.open('POST', '', true);
            progressBox.classList.remove('d-none');
            x.upload.onprogress = e => {
                let p = Math.round(e.loaded / e.total * 100);
                progressBar.style.width = p + '%';
                progressBar.innerText = p + '%';
            };
            x.onload = () => location.reload();
            x.send(new FormData(formMateri));
        }

        function hapusMateri(id) {
            if (confirm('Hapus materi ini?')) {
                fetch('pembelajaran_hapus.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/x-www-form-urlencoded'
                    },
                    body: 'id=' + id
                }).then(() => location.reload());
            }
        }
    </script>

</body>

</html>