<?php
session_start();
include '../config/koneksi.php';

if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'peserta') {
    header("Location: ../index.php");
    exit;
}

/* ================= DATA ================= */
$q = mysqli_query($koneksi, "SELECT * FROM pembelajaran ORDER BY created_at DESC");
$materi = [];
while ($r = mysqli_fetch_assoc($q)) $materi[] = $r;

$qm = mysqli_query($koneksi, "SELECT DISTINCT mapel FROM pembelajaran ORDER BY mapel ASC");
$mapelList = [];
while ($m = mysqli_fetch_assoc($qm)) $mapelList[] = $m['mapel'];

function getProgress()
{
    return rand(20, 100); // placeholder
}
?>
<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Pembelajaran</title>

    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css" rel="stylesheet">

    <style>
        body {
            background: #f4f6f9
        }

        /* CARD */
        .materi-card {
            margin-bottom: 16px
        }

        .hover-card {
            transition: .25s ease;
        }

        .hover-card:hover {
            transform: translateY(-6px);
            box-shadow: 0 14px 28px rgba(0, 0, 0, .12);
        }

        /* IMAGE */
        .object-fit-cover {
            object-fit: cover
        }

        /* TEXT */
        .text-truncate-2 {
            display: -webkit-box;
            -webkit-line-clamp: 2;
            -webkit-box-orient: vertical;
            overflow: hidden;
        }

        .card-desc {
            font-size: .85rem;
            color: #6c757d;
            margin-top: 6px;
        }

        /* BADGE */
        .badge-new {
            position: absolute;
            top: 10px;
            left: 10px;
            font-size: .75rem;
        }

        /* FOOTER */
        .card-footer-custom {
            margin-top: auto;
            padding-top: 12px;
            border-top: 1px solid #e9ecef;
        }

        /* PROGRESS */
        .progress-wrapper {
            font-size: .8rem
        }

        .progress-label {
            display: flex;
            justify-content: space-between;
            margin-bottom: 4px;
            color: #6c757d;
        }

        .progress {
            height: 8px;
            border-radius: 20px;
            background: #e9ecef;
        }

        .progress-bar {
            background: linear-gradient(90deg, #0d6efd, #20c997);
        }
    </style>
</head>

<body>

    <div class="container py-4">

        <!-- HEADER -->
        <div class="d-flex justify-content-between align-items-center mb-3">
            <h4 class="mb-0">
                <i class="bi bi-journal-text me-1"></i> Pembelajaran
            </h4>
            <a href="dashboard.php" class="btn btn-outline-secondary btn-sm">
                <i class="bi bi-arrow-left"></i> Kembali
            </a>
        </div>

        <!-- FILTER -->
        <input type="text" id="searchMateri" class="form-control mb-2" placeholder="Cari materi...">

        <select id="filterMapel" class="form-select mb-4">
            <option value="">Semua Mapel</option>
            <?php foreach ($mapelList as $m): ?>
                <option value="<?= strtolower($m) ?>"><?= htmlspecialchars($m) ?></option>
            <?php endforeach; ?>
        </select>

        <!-- LIST -->
        <div class="row g-4" id="materiContainer">

            <?php foreach ($materi as $row):
                $progress = getProgress();
                $isBaru = strtotime($row['created_at']) >= strtotime('-7 days');
                $foto = $row['foto'] ?: 'bg_pc.jpg';

                /* EMBED VIDEO */
                $embed = '';
                if (!empty($row['video_url'])) {
                    $u = trim($row['video_url']);
                    if (preg_match('~watch\?v=([^&]+)~', $u, $m))
                        $embed = 'https://www.youtube.com/embed/' . $m[1];
                    elseif (preg_match('~youtu\.be/([^?&]+)~', $u, $m))
                        $embed = 'https://www.youtube.com/embed/' . $m[1];
                    elseif (preg_match('~/d/([^/]+)~', $u, $m))
                        $embed = 'https://drive.google.com/file/d/' . $m[1] . '/preview';
                }
            ?>

                <div class="col-md-4 materi-card"
                    data-id="<?= $row['id'] ?>"
                    data-mapel="<?= strtolower($row['mapel']) ?>"
                    data-judul="<?= strtolower($row['judul']) ?>">

                    <div class="card h-100 border-0 hover-card position-relative">

                        <img src="../uploads/pembelajaran/<?= htmlspecialchars($foto) ?>"
                            class="card-img-top object-fit-cover" style="height:180px">

                        <?php if ($isBaru): ?>
                            <span class="badge bg-warning text-dark badge-new">
                                <i class="bi bi-stars"></i> Materi Baru
                            </span>
                        <?php endif; ?>

                        <div class="card-body d-flex flex-column">

                            <h6 class="fw-semibold mb-1"><?= htmlspecialchars($row['judul']) ?></h6>
                            <small class="text-muted">
                                <?= htmlspecialchars($row['mapel']) ?> • <?= htmlspecialchars($row['guru_nama']) ?>
                            </small>

                            <p class="card-desc text-truncate-2">
                                <?= htmlspecialchars($row['keterangan']) ?>
                            </p>

                            <?php if ($embed): ?>
                                <button class="btn btn-primary btn-sm mb-2"
                                    onclick="openVideo('<?= $embed ?>','<?= addslashes($row['judul']) ?>',<?= $row['id'] ?>)">
                                    <i class="bi bi-play-circle"></i> Tonton Video
                                </button>
                            <?php endif; ?>

                            <?php if ($row['file_materi']): ?>
                                <a href="../uploads/pembelajaran/materi/<?= htmlspecialchars($row['file_materi']) ?>"
                                    target="_blank"
                                    onclick="logMateri(<?= $row['id'] ?>,'buka_materi')"
                                    class="btn btn-outline-success btn-sm mb-2">
                                    <i class="bi bi-file-earmark-text"></i> Lihat Materi
                                </a>
                            <?php endif; ?>

                            <!-- FOOTER -->
                            <div class="card-footer-custom">

                                <div class="progress-wrapper mb-2">
                                    <div class="progress-label">
                                        <span>Progres Anda</span>
                                        <span class="fw-semibold"><?= $progress ?>%</span>
                                    </div>
                                    <div class="progress">
                                        <div class="progress-bar" style="width:<?= $progress ?>%"></div>
                                    </div>
                                </div>

                                <a href="quiz.php?materi_id=<?= $row['id'] ?>"
                                    class="btn btn-warning btn-sm w-100">
                                    <i class="bi bi-pencil-square"></i> Kerjakan Quiz
                                </a>

                            </div>
                        </div>
                    </div>
                </div>

            <?php endforeach; ?>
        </div>
    </div>

    <!-- MODAL VIDEO -->
    <div class="modal fade" id="modalVideo" tabindex="-1">
        <div class="modal-dialog modal-xl modal-dialog-centered">
            <div class="modal-content bg-dark">
                <div class="modal-header border-0">
                    <h6 class="modal-title text-white" id="modalVideoTitle"></h6>
                    <button class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body p-0">
                    <div class="ratio ratio-16x9">
                        <iframe id="modalVideoFrame" allowfullscreen></iframe>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>

    <script>
        const cards = document.querySelectorAll('.materi-card');

        searchMateri.oninput = e => {
            const q = e.target.value.toLowerCase();
            cards.forEach(c => {
                c.style.display = c.dataset.judul.includes(q) ? 'block' : 'none';
            });
        };

        filterMapel.onchange = e => {
            const m = e.target.value;
            cards.forEach(c => {
                c.style.display = !m || c.dataset.mapel === m ? 'block' : 'none';
            });
        };

        function logMateri(id, aksi) {
            fetch('../admin/materi_log.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded'
                },
                body: `materi_id=${id}&aksi=${aksi}`
            });
        }

        function openVideo(url, title, id) {
            modalVideoTitle.innerText = title;
            modalVideoFrame.src = url + '?autoplay=1';
            logMateri(id, 'tonton_video');
            new bootstrap.Modal(modalVideo).show();
        }
        modalVideo.addEventListener('hidden.bs.modal', () => modalVideoFrame.src = '');
    </script>

</body>

</html>