<?php
require_once '../includes/config.php';
checkLogin();

if(getRole() !== 'siswa') {
    die("Akses ditolak. Halaman ini khusus Siswa.");
}

$msg = '';
$user_id = $_SESSION['user_id'];
$today = date('Y-m-d');

$sudah_isi = false;
$punya_tim = false;

if($conn) {
    // Cek apakah punya tim
    $stmt = $conn->prepare("SELECT id_proyek FROM tim_proyek WHERE id_siswa = ?");
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $res = $stmt->get_result();
    if($res->fetch_assoc()) {
        $punya_tim = true;
    }
    
    // Jika tidak punya tim, jangan izinkan isi logbook
    if(!$punya_tim) {
        header("Location: index.php");
        exit;
    }

    $stmt = $conn->prepare("SELECT id, is_verified FROM logbook_internal WHERE id_siswa = ? AND tanggal = ?");
    $stmt->bind_param("is", $user_id, $today);
    $stmt->execute();
    $res = $stmt->get_result();
    if($res->fetch_assoc()) $sudah_isi = true;
} else {
    $sudah_isi = isset($_GET['demo_isi']) ? true : false;
    $punya_tim = true; // For demo mode
}

if($_SERVER['REQUEST_METHOD'] == 'POST' && !$sudah_isi) {
    $jam_masuk = $_POST['jam_masuk'];
    $jam_pulang = $_POST['jam_pulang'];
    $catatan_apel = $_POST['catatan_apel'];
    $catatan_instruktur = $_POST['catatan_instruktur'];
    $sesi_1 = $_POST['sesi_1'];
    $sesi_2 = $_POST['sesi_2'];
    $sesi_3 = $_POST['sesi_3'];
    $kendala = $_POST['kendala'];
    $rencana = $_POST['rencana'];

    if($conn) {
        $stmt = $conn->prepare("INSERT INTO logbook_internal (id_siswa, tanggal, jam_masuk, jam_pulang, catatan_apel, catatan_instruktur, sesi_1, sesi_2, sesi_3, kendala, rencana_besok) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
        $stmt->bind_param("issssssssss", $user_id, $today, $jam_masuk, $jam_pulang, $catatan_apel, $catatan_instruktur, $sesi_1, $sesi_2, $sesi_3, $kendala, $rencana);
        if($stmt->execute()) {
            $sudah_isi = true;
            $msg = '<div class="notification-banner success"><i data-lucide="check-circle"></i> <span>Logbook hari ini berhasil disimpan!</span></div>';
        }
    } else {
        $sudah_isi = true;
        $msg = '<div class="notification-banner success"><i data-lucide="check-circle"></i> <span>Logbook (Demo) berhasil disimpan!</span></div>';
    }
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
    <title>Isi Logbook - Siswa SIPKL</title>
    <link rel="icon" type="image/svg+xml" href="../assets/img/favicon.svg">
    <link rel="stylesheet" href="../assets/css/style.css">
    <script src="https://unpkg.com/lucide@latest"></script>
</head>
<body>
    <div id="page-loader"><div class="loader-spinner"></div></div>
    <div class="app-container">
        <header class="app-header">
            <div style="display:flex; align-items:center; gap:15px;">
                <a href="index.php" class="icon-btn"><i data-lucide="arrow-left"></i></a>
                <h1>Logbook Harian</h1>
            </div>
            <div class="badge bg-accent" style="font-size:12px; font-weight:600; padding:4px 10px;"><?= date('d M Y') ?></div>
        </header>
        
        <main class="main-content">
            <?= $msg ?>
            
            <?php if($sudah_isi): ?>
            <div class="card" style="text-align:center; padding:40px 20px;">
                <i data-lucide="check-circle" style="width:64px; height:64px; color:var(--success); margin:0 auto 16px;"></i>
                <h2 style="font-size:18px; color:var(--primary); margin-bottom:12px; letter-spacing:-0.5px;">Logbook Selesai!</h2>
                <p style="font-size:14px; color:var(--text-muted); line-height:1.5;">Pekerjaan hari ini sudah terekam dan menunggu proses validasi Instruktur/Guru Pembimbing.</p>
                <a href="index.php" class="btn btn-outline" style="margin-top:24px;">Kembali ke Beranda</a>
            </div>
            <?php else: ?>
            
            <div class="notification-banner" style="background:#e0f2fe; color:#0284c7; border:1px solid #bae6fd;">
                <i data-lucide="info"></i>
                <span>Isi logbook dengan rinci sesuai tugas riil yang dikerjakan per sesi agar mudah di-ACC.</span>
            </div>
            
            <form method="POST">
                <div class="card">
                    <div class="card-title"><i data-lucide="clock"></i> Waktu Kehadiran</div>
                    <div class="grid-2">
                        <div class="form-group" style="margin-bottom:0;">
                            <label class="form-label">Jam Masuk</label>
                            <input type="time" name="jam_masuk" class="form-control-standard" required value="07:30">
                        </div>
                        <div class="form-group" style="margin-bottom:0;">
                            <label class="form-label">Jam Pulang</label>
                            <input type="time" name="jam_pulang" class="form-control-standard" required value="14:00">
                        </div>
                    </div>
                </div>
                
                <div class="card">
                    <div class="card-title"><i data-lucide="clipboard-list"></i> Catatan Pagi</div>
                    <div class="form-group">
                        <label class="form-label">Hasil Apel / Pengarahan</label>
                        <textarea name="catatan_apel" class="form-control-standard" placeholder="Tulis catatan penting..." required></textarea>
                    </div>
                    <div class="form-group" style="margin-bottom:0;">
                        <label class="form-label">Instruksi Pembimbing</label>
                        <textarea name="catatan_instruktur" class="form-control-standard" placeholder="Apa target hari ini?" required></textarea>
                    </div>
                </div>
                
                <div class="card" style="background:var(--bg-color); border:2px dashed var(--border);">
                    <div class="card-title" style="border-bottom:none; margin-bottom:0;"><i data-lucide="list-todo"></i> Rincian Sprint Kerja</div>
                    
                    <div class="form-group">
                        <label class="form-label" style="color:var(--accent);">Sesi 1 (08.00 - 09.30)</label>
                        <textarea name="sesi_1" class="form-control-standard" style="background:white;" placeholder="Pekerjaan di sesi 1..." required></textarea>
                    </div>
                    <div class="form-group">
                        <label class="form-label" style="color:var(--accent);">Sesi 2 (10.00 - 11.30)</label>
                        <textarea name="sesi_2" class="form-control-standard" style="background:white;" placeholder="Pekerjaan di sesi 2..." required></textarea>
                    </div>
                    <div class="form-group" style="margin-bottom:0;">
                        <label class="form-label" style="color:var(--accent);">Sesi 3 (12.30 - 14.00)</label>
                        <textarea name="sesi_3" class="form-control-standard" style="background:white;" placeholder="Pekerjaan di sesi 3..." required></textarea>
                    </div>
                </div>
                
                <div class="card">
                    <div class="card-title"><i data-lucide="target"></i> Evaluasi Akhir Hari</div>
                    <div class="form-group">
                        <label class="form-label text-danger" style="color:var(--danger);">Kendala (Jika ada)</label>
                        <textarea name="kendala" class="form-control-standard" placeholder="Masalah yang dihadapi..."></textarea>
                    </div>
                    <div class="form-group" style="margin-bottom:0;">
                        <label class="form-label">Rencana Besok</label>
                        <textarea name="rencana" class="form-control-standard" placeholder="Tindak lanjut besok..." required></textarea>
                    </div>
                </div>
                
                <button type="submit" class="btn btn-primary" style="margin-bottom:20px;">
                    <i data-lucide="send"></i> Kirim Laporan Logbook
                </button>
            </form>
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
