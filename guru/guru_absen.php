<?php
require_once '../includes/config.php';
checkLogin();

if(getRole() !== 'pembimbing_sekolah') {
    die("Akses ditolak.");
}

$user_id = $_SESSION['user_id'];
$hari_ini = date('Y-m-d');
$filter_tgl = $_GET['tanggal'] ?? $hari_ini;

$absensi = [];
if($conn) {
    $stmt = $conn->prepare("
        SELECT a.*, u.name as nama_siswa, u.jurusan, u.username
        FROM absensi_siswa a
        JOIN users u ON a.id_siswa = u.id
        JOIN tim_proyek tp ON tp.id_siswa = a.id_siswa
        JOIN proyek_internal p ON tp.id_proyek = p.id
        WHERE a.tanggal = ? AND p.id_pembimbing_sekolah = ? AND a.tipe_pkl = 'internal'
        ORDER BY a.waktu_datang DESC
    ");
    $stmt->bind_param("si", $filter_tgl, $user_id);
    $stmt->execute();
    $res = $stmt->get_result();
    while($row = $res->fetch_assoc()) $absensi[] = $row;
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
    <title>Pantau Absensi (Guru) - SIPKL</title>
    <link rel="icon" type="image/svg+xml" href="../assets/img/favicon.svg">
    <link rel="stylesheet" href="../assets/css/style.css">
    <script src="https://unpkg.com/lucide@latest"></script>
    <style>
        .table-responsive { overflow-x: auto; margin: 0 -20px; padding: 0 20px; }
        .data-table { width: 100%; border-collapse: collapse; min-width: 800px; }
        .data-table th, .data-table td { padding: 12px; text-align: left; border-bottom: 1px solid var(--border); font-size: 13px; }
        .data-table th { background: var(--bg-color); color: var(--text-muted); font-weight: 600; }
        .data-table tr:hover { background: #f8fafc; }
        .img-thumb { width: 40px; height: 40px; border-radius: 8px; object-fit: cover; cursor: pointer; border: 1px solid var(--border); transition: transform 0.2s; }
        .img-thumb:hover { transform: scale(1.1); }
        .info-cell { display: flex; align-items: center; gap: 10px; }
        .map-link { font-size: 11px; color: var(--primary); text-decoration: none; display: flex; align-items: center; gap: 3px; margin-top: 4px; }
        .map-link:hover { text-decoration: underline; }
    </style>
</head>
<body>
    <div id="page-loader"><div class="loader-spinner"></div></div>
    <div class="app-container">
        <header class="app-header">
            <div style="display:flex; align-items:center; gap:15px;">
                <a href="../index.php" class="icon-btn"><i data-lucide="arrow-left"></i></a>
                <h1>Pantau Absensi Tim</h1>
            </div>
        </header>

        <main class="main-content">
            <div class="card" style="margin-bottom:20px; padding:15px;">
                <form method="GET" style="display:flex; gap:10px; align-items:flex-end;">
                    <div style="flex:1;">
                        <label style="font-size:12px; color:var(--text-muted); font-weight:600;">Filter Tanggal</label>
                        <input type="date" name="tanggal" value="<?= $filter_tgl ?>" class="form-control" style="padding:10px; border-radius:8px; border:1px solid var(--border); width:100%;" onchange="this.form.submit()">
                    </div>
                    <button type="submit" class="btn btn-primary" style="padding:10px 15px;"><i data-lucide="search" style="width:18px;"></i></button>
                </form>
            </div>

            <div class="card">
                <div class="card-title"><i data-lucide="calendar-check"></i> Kehadiran Bimbingan: <?= date('d M Y', strtotime($filter_tgl)) ?></div>
                <div class="table-responsive">
                    <table class="data-table">
                        <thead>
                            <tr>
                                <th>Nama Siswa & Jurusan</th>
                                <th>Datang (Waktu & Lokasi)</th>
                                <th>Pulang (Waktu & Lokasi)</th>
                                <th>Status</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if(empty($absensi)): ?>
                            <tr><td colspan="4" style="text-align:center; padding:20px; color:var(--text-muted);">Belum ada data absensi di tanggal ini.</td></tr>
                            <?php else: foreach($absensi as $a): ?>
                            <tr>
                                <td>
                                    <div style="font-weight:600;"><?= htmlspecialchars($a['nama_siswa']) ?></div>
                                    <div style="font-size:12px; color:var(--text-muted);"><?= htmlspecialchars($a['username']) ?> &bull; <?= htmlspecialchars($a['jurusan']) ?></div>
                                </td>
                                <td>
                                    <?php if($a['waktu_datang']): ?>
                                    <div class="info-cell">
                                        <?php if($a['foto_datang']): ?>
                                        <a href="../uploads/absensi/<?= $a['foto_datang'] ?>" target="_blank"><img src="../uploads/absensi/<?= $a['foto_datang'] ?>" class="img-thumb"></a>
                                        <?php endif; ?>
                                        <div>
                                            <div style="font-weight:700; color:var(--text-main);"><?= substr($a['waktu_datang'], 0, 5) ?></div>
                                            <?php if($a['latlong_datang']): ?>
                                            <a href="https://www.google.com/maps/search/?api=1&query=<?= urlencode($a['latlong_datang']) ?>" target="_blank" class="map-link"><i data-lucide="map-pin" style="width:12px;"></i> Buka Peta</a>
                                            <?php endif; ?>
                                        </div>
                                    </div>
                                    <?php else: echo "-"; endif; ?>
                                </td>
                                <td>
                                    <?php if($a['waktu_pulang']): ?>
                                    <div class="info-cell">
                                        <?php if($a['foto_pulang']): ?>
                                        <a href="../uploads/absensi/<?= $a['foto_pulang'] ?>" target="_blank"><img src="../uploads/absensi/<?= $a['foto_pulang'] ?>" class="img-thumb"></a>
                                        <?php endif; ?>
                                        <div>
                                            <div style="font-weight:700; color:var(--warning);"><?= substr($a['waktu_pulang'], 0, 5) ?></div>
                                            <?php if($a['latlong_pulang']): ?>
                                            <a href="https://www.google.com/maps/search/?api=1&query=<?= urlencode($a['latlong_pulang']) ?>" target="_blank" class="map-link"><i data-lucide="map-pin" style="width:12px;"></i> Buka Peta</a>
                                            <?php endif; ?>
                                        </div>
                                    </div>
                                    <?php else: echo "-"; endif; ?>
                                </td>
                                <td>
                                    <div style="margin-bottom:6px;"><span class="badge bg-success"><?= $a['status'] ?></span></div>
                                    <?php if(isset($a['is_wajah_valid'])): ?>
                                        <?php if($a['is_wajah_valid'] == 1): ?>
                                            <div style="font-size:10px; color:var(--success); margin-bottom:3px; display:flex; align-items:center; gap:4px;"><i data-lucide="check-circle" style="width:12px;"></i> Wajah Cocok</div>
                                        <?php else: ?>
                                            <div style="font-size:10px; color:var(--danger); margin-bottom:3px; display:flex; align-items:center; gap:4px;"><i data-lucide="x-circle" style="width:12px;"></i> Wajah Beda</div>
                                        <?php endif; ?>
                                    <?php endif; ?>
                                    
                                    <?php if(isset($a['is_lokasi_valid'])): ?>
                                        <?php if($a['is_lokasi_valid'] == 1): ?>
                                            <div style="font-size:10px; color:var(--success); display:flex; align-items:center; gap:4px;"><i data-lucide="check-circle" style="width:12px;"></i> Radius Valid (<?= $a['jarak_meter'] ?>m)</div>
                                        <?php else: ?>
                                            <div style="font-size:10px; color:var(--danger); display:flex; align-items:center; gap:4px;"><i data-lucide="x-circle" style="width:12px;"></i> Luar Radius (<?= $a['jarak_meter'] ?>m)</div>
                                        <?php endif; ?>
                                    <?php endif; ?>
                                </td>
                            </tr>
                            <?php endforeach; endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </main>
        
        <?php 
            $active_page = 'home';
            include '../includes/bottom_nav.php'; 
        ?>
    </div>
    <script>lucide.createIcons();</script>
</body>
</html>
