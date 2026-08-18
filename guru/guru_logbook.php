<?php
require_once '../includes/config.php';
checkLogin();

if(getRole() !== 'pembimbing_sekolah') {
    die("Akses ditolak. Halaman ini khusus Guru Pembimbing Sekolah.");
}

$user_id = $_SESSION['user_id'];
$msg = '';

if(isset($_GET['verify']) && $conn) {
    $logbook_id = (int)$_GET['verify'];
    
    // Proteksi IDOR: Pastikan logbook ini milik siswa di bawah bimbingan guru ini
    $cek = $conn->prepare("
        SELECT l.id FROM logbook_internal l
        JOIN tim_proyek tp ON tp.id_siswa = l.id_siswa
        JOIN proyek_internal p ON tp.id_proyek = p.id
        WHERE l.id = ? AND p.id_pembimbing_sekolah = ?
    ");
    $cek->bind_param("ii", $logbook_id, $user_id);
    $cek->execute();
    
    if($cek->get_result()->fetch_assoc()) {
        $stmt = $conn->prepare("UPDATE logbook_internal SET is_verified = 1, id_verifier = ? WHERE id = ?");
        $stmt->bind_param("ii", $user_id, $logbook_id);
        if($stmt->execute()) {
            $msg = '<div class="notification-banner success"><i data-lucide="check-circle"></i> <span>Logbook berhasil diverifikasi.</span></div>';
        }
    } else {
        $msg = '<div class="notification-banner danger"><i data-lucide="alert-circle"></i> <span>Akses ditolak (IDOR terdeteksi). Logbook ini bukan milik siswa bimbingan Anda.</span></div>';
    }
}

$logbooks = [];
if($conn) {
    $query = "
        SELECT l.*, u.name as nama_siswa, u.jurusan, p.judul_proyek
        FROM logbook_internal l
        JOIN users u ON l.id_siswa = u.id
        JOIN tim_proyek tp ON tp.id_siswa = u.id
        JOIN proyek_internal p ON tp.id_proyek = p.id
        WHERE p.id_pembimbing_sekolah = ? AND p.tahun_ajaran = ? AND l.is_verified = 0
        ORDER BY l.tanggal DESC, l.jam_masuk ASC
    ";
    $stmt = $conn->prepare($query);
    $stmt->bind_param("is", $user_id, $TAHUN_AJARAN_AKTIF);
    $stmt->execute();
    $res = $stmt->get_result();
    while($row = $res->fetch_assoc()) $logbooks[] = $row;
} else {
    if(!isset($_GET['verify'])) {
        $logbooks = [
            [
                'id' => 1, 'tanggal' => date('Y-m-d'), 'nama_siswa' => 'Ahmad Siswa PPLG', 'jurusan' => 'PPLG', 
                'judul_proyek' => 'Aplikasi Kasir', 'jam_masuk' => '07:30:00', 'jam_pulang' => '14:00:00',
                'sesi_1' => 'Mendesain UI/UX dengan Figma', 'sesi_2' => 'Memotong aset gambar dan slicing ke HTML',
                'sesi_3' => 'Memperbaiki CSS Flexbox', 'kendala' => 'Sulit menyelaraskan elemen flex di layar kecil.',
                'rencana_besok' => 'Lanjut membuat form input login.'
            ]
        ];
    }
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
    <title>Verifikasi Logbook - Guru SIPKL</title>
    <link rel="icon" type="image/svg+xml" href="../assets/img/favicon.svg">
    <link rel="preconnect" href="https://fonts.googleapis.com"><link rel="preconnect" href="https://fonts.gstatic.com" crossorigin><link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Maven+Pro:wght@400;500;600;700&display=swap"><link rel="stylesheet" href="../assets/css/style_v2.css?v=<?= filemtime('../assets/css/style_v2.css') ?>">
    <script src="https://unpkg.com/lucide@latest"></script>
    <style>
        .logbook-item {
            background: var(--bg-color);
            border: 1px solid var(--border);
            border-radius: 12px;
            padding: 16px;
            margin-bottom: 16px;
        }
        .log-section { margin-bottom:10px; font-size:14px; line-height:1.5; color:var(--text-main); }
        .log-title { font-weight:700; color:var(--accent); font-size:12px; text-transform:uppercase; letter-spacing:0.5px; display:block; margin-bottom:2px;}
    </style>
</head>
<body>
    <div id="page-loader"><div class="loader-spinner"></div></div>
    <div class="app-container">
        <header class="app-header">
            <div style="display:flex; align-items:center; gap:15px;">
                <a href="index.php" class="icon-btn"><i data-lucide="arrow-left"></i></a>
                <h1>Verifikasi Logbook</h1>
            </div>
            <div class="badge bg-warning" style="color:var(--primary); box-shadow:0 2px 5px rgba(245,158,11,0.3);">
                <?= count($logbooks) ?> Antrean
            </div>
        </header>
        
        <main class="main-content">
            <?= $msg ?>
            
            <?php if(empty($logbooks)): ?>
            <div class="card" style="text-align:center; padding:40px 20px;">
                <i data-lucide="clipboard-check" style="width:64px; height:64px; color:var(--border); margin:0 auto 16px;"></i>
                <h2 style="font-size:16px; color:var(--text-muted);">Antrean Kosong</h2>
                <p style="font-size:13px; color:var(--text-muted); margin-top:8px;">Semua logbook harian siswa telah Anda verifikasi. Pekerjaan yang luar biasa!</p>
            </div>
            <?php else: ?>
                <?php foreach($logbooks as $log): ?>
                <div class="card" style="padding:16px;">
                    <div style="display:flex; justify-content:space-between; align-items:flex-start; margin-bottom:16px; border-bottom:1px solid var(--border); padding-bottom:12px;">
                        <div>
                            <div style="font-weight:700; color:var(--primary); font-size:16px; margin-bottom:4px;"><?= htmlspecialchars($log['nama_siswa']) ?></div>
                            <div style="font-size:12px; color:var(--text-muted); display:flex; align-items:center; gap:4px;">
                                <i data-lucide="folder-git-2" style="width:14px; height:14px;"></i> <?= htmlspecialchars($log['judul_proyek']) ?>
                            </div>
                        </div>
                        <div style="text-align:right;">
                            <div style="font-size:13px; font-weight:700; color:var(--accent);"><?= date('d M Y', strtotime($log['tanggal'])) ?></div>
                            <div style="font-size:11px; color:var(--text-muted); margin-top:2px;">
                                <i data-lucide="clock" style="width:12px; height:12px; display:inline; vertical-align:middle;"></i> 
                                <?= substr($log['jam_masuk'], 0, 5) ?> - <?= substr($log['jam_pulang'], 0, 5) ?>
                            </div>
                        </div>
                    </div>
                    
                    <div class="logbook-item">
                        <div class="log-section">
                            <span class="log-title">Sesi 1 (08.00 - 09.30)</span>
                            <?= nl2br(htmlspecialchars($log['sesi_1'])) ?>
                        </div>
                        <div class="log-section">
                            <span class="log-title">Sesi 2 (10.00 - 11.30)</span>
                            <?= nl2br(htmlspecialchars($log['sesi_2'])) ?>
                        </div>
                        <div class="log-section">
                            <span class="log-title">Sesi 3 (12.30 - 14.00)</span>
                            <?= nl2br(htmlspecialchars($log['sesi_3'])) ?>
                        </div>
                        <?php if(!empty($log['kendala'])): ?>
                        <div class="log-section" style="background:#fee2e2; padding:10px; border-radius:8px; border-left:3px solid var(--danger); margin-top:16px;">
                            <span class="log-title" style="color:var(--danger);">Kendala Dihadapi</span>
                            <span style="color:#991b1b;"><?= nl2br(htmlspecialchars($log['kendala'])) ?></span>
                        </div>
                        <?php endif; ?>
                    </div>
                    
                    <a href="?verify=<?= $log['id'] ?>" class="btn btn-primary" style="background:var(--success); box-shadow:0 4px 10px rgba(16,185,129,0.3);" onclick="return confirm('Verifikasi logbook ini?')">
                        <i data-lucide="check-circle" style="width:18px;"></i> ACC Logbook Siswa
                    </a>
                </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </main>
        
        <?php 
            $active_page = 'logbook';
            include '../includes/bottom_nav.php'; 
        ?>
    </div>
    <script>
        lucide.createIcons();
    </script>
</body>
</html>
