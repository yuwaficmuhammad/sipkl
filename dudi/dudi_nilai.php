<?php
require_once '../includes/config.php';
checkLogin();

if(getRole() !== 'pembimbing_dudika') {
    die("Akses ditolak. Halaman ini khusus Pembimbing Industri (DUDIKA).");
}

$user_id = $_SESSION['user_id'];
$msg = '';

// $dudi_jurusan = ''; tidak diperlukan lagi karena relasi sudah langsung via tabel penempatan_dudi.

if($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['action']) && $_POST['action'] == 'nilai' && $conn) {
    csrf_check();
    $id_siswa = (int)$_POST['id_siswa'];
    $nilai_softskill = (int)$_POST['nilai_softskill'];
    $nilai_hardskill = (int)$_POST['nilai_hardskill'];
    $catatan = $_POST['catatan'] ?? '';
    
    // MENCEGAH IDOR: Pastikan siswa ini memang dibimbing oleh DUDI yang sedang login
    $cek_auth = $conn->prepare("SELECT id FROM penempatan_dudi WHERE id_siswa = ? AND id_dudika = ? AND tahun_ajaran = ?");
    $cek_auth->bind_param("iis", $id_siswa, $user_id, $TAHUN_AJARAN_AKTIF);
    $cek_auth->execute();
    if($cek_auth->get_result()->fetch_assoc()) {
        $stmt = $conn->prepare("SELECT id FROM penilaian_dudi WHERE id_siswa = ? AND id_dudika = ?");
        $stmt->bind_param("ii", $id_siswa, $user_id);
        $stmt->execute();
        $res = $stmt->get_result();
        
        if($res->fetch_assoc()) {
            $stmt = $conn->prepare("UPDATE penilaian_dudi SET nilai_softskill=?, nilai_hardskill=?, catatan_industri=? WHERE id_siswa=? AND id_dudika=?");
            $stmt->bind_param("iisii", $nilai_softskill, $nilai_hardskill, $catatan, $id_siswa, $user_id);
        } else {
            $stmt = $conn->prepare("INSERT INTO penilaian_dudi (id_siswa, id_dudika, nilai_softskill, nilai_hardskill, catatan_industri) VALUES (?, ?, ?, ?, ?)");
            $stmt->bind_param("iiiis", $id_siswa, $user_id, $nilai_softskill, $nilai_hardskill, $catatan);
        }
        
        if($stmt->execute()) {
            $msg = '<div class="notification-banner success"><i data-lucide="check-circle"></i> <span>Penilaian berhasil disimpan!</span></div>';
        }
    } else {
        $msg = '<div class="notification-banner danger"><i data-lucide="alert-triangle"></i> <span>Akses Ditolak: Anda tidak berhak menilai siswa ini.</span></div>';
    }
}

$siswas = [];
if($conn) {
    $stmt = $conn->prepare("
        SELECT u.id, u.name, u.jurusan, p.nilai_softskill, p.nilai_hardskill, p.catatan_industri 
        FROM users u 
        JOIN penempatan_dudi pd ON u.id = pd.id_siswa
        LEFT JOIN penilaian_dudi p ON u.id = p.id_siswa AND p.id_dudika = ?
        WHERE u.role = 'siswa' AND pd.id_dudika = ? AND pd.tahun_ajaran = ?
    ");
    $stmt->bind_param("iis", $user_id, $user_id, $TAHUN_AJARAN_AKTIF);
    $stmt->execute();
    $res = $stmt->get_result();
    while($row = $res->fetch_assoc()) $siswas[] = $row;
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
    <title>Penilaian Siswa - DUDIKA SIPKL</title>
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
                <h1>Penilaian DUDIKA</h1>
            </div>
            <i data-lucide="award" style="color:var(--primary);"></i>
        </header>
        
        <main class="main-content">
            <?= $msg ?>
            <div class="notification-banner" style="background:#fff3cd; color:#856404; border-left:4px solid #f59e0b; margin-bottom: 10px;">
                <i data-lucide="alert-triangle"></i>
                <span>Pengisian nilai ini akan langsung masuk ke rapor sekolah. Mohon isi dengan objektif.</span>
            </div>

            <div style="margin-bottom: 20px; display: flex; justify-content: flex-end;">
                <a href="dudi_export_pdf.php" class="btn" style="background:#0ea5e9; color:#fff; text-decoration:none; display:inline-flex; align-items:center; gap:8px; padding:10px 15px; border-radius:8px; font-weight:600; font-size:13px;">
                    <i data-lucide="printer" style="width:16px;"></i> Cetak Laporan PDF
                </a>
            </div>
            
            <?php if(empty($siswas)): ?>
                <div class="card" style="text-align:center; padding:40px 20px;">
                    <i data-lucide="folder-search" style="width:48px; height:48px; color:var(--text-muted); margin:0 auto 16px; opacity:0.5;"></i>
                    <h3 style="font-size:16px; color:var(--text-main); margin-bottom:8px;">Belum ada siswa</h3>
                    <p style="font-size:13px; color:var(--text-muted);">Tidak ada siswa dari jurusan Anda pada tahun ajaran aktif ini.</p>
                </div>
            <?php else: ?>
                <?php foreach($siswas as $s): 
                    $sudah_dinilai = !is_null($s['nilai_softskill']);
                ?>
                <form method="POST" class="card" style="<?= $sudah_dinilai ? 'border-left:4px solid var(--success);' : 'border-left:4px solid var(--warning);' ?>">
                    <input type="hidden" name="action" value="nilai">
                    <input type="hidden" name="id_siswa" value="<?= $s['id'] ?>">
                    <?= csrf_field() ?>
                    
                    <div style="display:flex; justify-content:space-between; align-items:flex-start; margin-bottom:15px; border-bottom:1px solid var(--border); padding-bottom:12px;">
                        <div>
                            <div style="font-weight:700; font-size:15px; color:var(--text-main); margin-bottom:4px;"><?= htmlspecialchars($s['name']) ?></div>
                            <div style="font-size:12px; color:var(--text-muted);"><i data-lucide="graduation-cap" style="width:12px; display:inline;"></i> <?= htmlspecialchars($s['jurusan']) ?></div>
                        </div>
                        <?php if($sudah_dinilai): ?>
                            <span class="badge bg-success">Sudah Dinilai</span>
                        <?php else: ?>
                            <span class="badge bg-warning">Belum Dinilai</span>
                        <?php endif; ?>
                    </div>
                    
                    <div class="grid-2">
                        <div class="form-group" style="margin-bottom:10px;">
                            <label class="form-label" style="font-size:12px;">Nilai Softskill (Sikap)</label>
                            <input type="number" name="nilai_softskill" min="0" max="100" class="form-control-standard" value="<?= $s['nilai_softskill'] ?? '' ?>" required>
                        </div>
                        <div class="form-group" style="margin-bottom:10px;">
                            <label class="form-label" style="font-size:12px;">Nilai Hardskill (Teknis)</label>
                            <input type="number" name="nilai_hardskill" min="0" max="100" class="form-control-standard" value="<?= $s['nilai_hardskill'] ?? '' ?>" required>
                        </div>
                    </div>
                    
                    <div class="form-group" style="margin-bottom:15px;">
                        <label class="form-label" style="font-size:12px;">Catatan / Pesan untuk Siswa</label>
                        <textarea name="catatan" class="form-control-standard" rows="2" placeholder="Cth: Rajin dan cepat tanggap..."><?= htmlspecialchars($s['catatan_industri'] ?? '') ?></textarea>
                    </div>
                    
                    <button type="submit" class="btn btn-primary" style="width:100%; <?= $sudah_dinilai ? 'background:#16a34a;' : '' ?>" onclick="return confirm('Simpan penilaian akhir untuk <?= addslashes($s['name']) ?>?')">
                        <i data-lucide="save" style="width:16px; margin-right:4px;"></i> <?= $sudah_dinilai ? 'Update Penilaian' : 'Simpan Penilaian' ?>
                    </button>
                </form>
                <?php endforeach; ?>
            <?php endif; ?>
        </main>
        
        <?php 
            $active_page = 'nilai';
            include '../includes/bottom_nav.php'; 
        ?>
    </div>
    <script>
        lucide.createIcons();
    </script>
</body>
</html>
