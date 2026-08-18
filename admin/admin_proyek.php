<?php
require_once '../includes/config.php';
checkLogin();

if(getRole() !== 'admin') {
    die("Akses ditolak. Halaman ini hanya untuk Admin Pokja PKL.");
}

$msg = '';
$gurus = [];
$siswas = [];
$proyeks = [];

if($conn) {
    $stmt_g = $conn->prepare("SELECT id, name, jurusan FROM users WHERE role = 'pembimbing_sekolah'");
    $stmt_g->execute();
    $res = $stmt_g->get_result();
    while($row = $res->fetch_assoc()) $gurus[] = $row;
    
    $stmt_s = $conn->prepare("SELECT id, name, username, jurusan FROM users WHERE role = 'siswa' AND tahun_ajaran = ? AND id NOT IN (SELECT id_siswa FROM tim_proyek)");
    $stmt_s->bind_param("s", $TAHUN_AJARAN_AKTIF);
    $stmt_s->execute();
    $res = $stmt_s->get_result();
    while($row = $res->fetch_assoc()) $siswas[] = $row;
    
    $stmt_p = $conn->prepare("
        SELECT p.*, u.name as nama_guru,
               COUNT(tp.id_siswa) as jml_anggota
        FROM proyek_internal p
        JOIN users u ON p.id_pembimbing_sekolah = u.id
        LEFT JOIN tim_proyek tp ON tp.id_proyek = p.id
        WHERE p.tahun_ajaran = ?
        GROUP BY p.id, u.name");
    $stmt_p->bind_param("s", $TAHUN_AJARAN_AKTIF);
    $stmt_p->execute();
    $res = $stmt_p->get_result();
    while($row = $res->fetch_assoc()) $proyeks[] = $row;
    
    if($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['action']) && $_POST['action'] == 'add') {
        $kode_proyek = $_POST['kode_proyek'];
        $nama_klien = $_POST['nama_klien'];
        $judul_proyek = $_POST['judul_proyek'];
        $id_guru = (int)$_POST['id_guru'];
        $id_ketua = (int)$_POST['id_ketua'];
        
        $conn->begin_transaction();
        try {
            $stmt = $conn->prepare("INSERT INTO proyek_internal (kode_proyek, nama_klien, judul_proyek, id_pembimbing_sekolah, tahun_ajaran) VALUES (?, ?, ?, ?, ?)");
            $stmt->bind_param("sssis", $kode_proyek, $nama_klien, $judul_proyek, $id_guru, $TAHUN_AJARAN_AKTIF);
            $stmt->execute();
            $proyek_id = $conn->insert_id;
            
            $stmt2 = $conn->prepare("INSERT INTO tim_proyek (id_proyek, id_siswa, is_ketua) VALUES (?, ?, 1)");
            $stmt2->bind_param("ii", $proyek_id, $id_ketua);
            $stmt2->execute();
            
            $conn->commit();
            $msg = '<div class="notification-banner success"><i data-lucide="check-circle"></i> <span>Proyek & Tim berhasil dibuat!</span></div>';
            header("Refresh:2"); 
        } catch(Exception $e) {
            $conn->rollback();
            $msg = '<div class="notification-banner danger"><i data-lucide="alert-circle"></i> <span>Gagal: '.$e->getMessage().'</span></div>';
        }
    }
} else {
    $gurus = [['id'=>2, 'name'=>'Bu Guru Pembimbing', 'jurusan'=>'Busana']];
    $siswas = [['id'=>4, 'name'=>'Ahmad Siswa PPLG', 'username'=>'siswa', 'jurusan'=>'PPLG']];
    $proyeks = [
        ['id'=>1, 'kode_proyek'=>'PRJ-001', 'nama_klien'=>'Toko Bangunan ABC', 'judul_proyek'=>'Aplikasi Kasir', 'nama_guru'=>'Bu Guru', 'jml_anggota'=>3, 'status_gate'=>2]
    ];
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
    <title>Kelola Proyek - Admin SIPKL</title>
    <link rel="icon" type="image/svg+xml" href="../assets/img/favicon.svg">
    <link rel="preconnect" href="https://fonts.googleapis.com"><link rel="preconnect" href="https://fonts.gstatic.com" crossorigin><link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Maven+Pro:wght@400;500;600;700&display=swap"><link rel="stylesheet" href="../assets/css/style_v2.css?v=<?= filemtime('../assets/css/style_v2.css') ?>">
    <script src="https://unpkg.com/lucide@latest"></script>
    <style>
        .table-responsive { overflow-x: auto; margin: 0 -20px; padding: 0 20px; }
        table { width: 100%; border-collapse: collapse; font-size: 13px; }
        th, td { padding: 14px 10px; border-bottom: 1px solid var(--border); text-align: left; }
        th { color: var(--text-muted); font-weight: 700; text-transform: uppercase; font-size:11px; }
        .gate-badge { display:inline-flex; align-items:center; justify-content:center; width:22px; height:22px; background:#e2e8f0; border-radius:50%; font-size:9px; font-weight:bold; margin-right:4px; color:var(--text-muted); }
        .gate-badge.active { background:var(--success); color:white; box-shadow:0 2px 4px rgba(16,185,129,0.3); }
    </style>
</head>
<body>
    <div id="page-loader"><div class="loader-spinner"></div></div>
    <div class="app-container">
        <header class="app-header">
            <div style="display:flex; align-items:center; gap:15px;">
                <a href="../index.php" class="icon-btn"><i data-lucide="arrow-left"></i></a>
                <h1>Manajemen Proyek</h1>
            </div>
            <i data-lucide="folder-git-2" style="color:var(--primary);"></i>
        </header>
        
        <main class="main-content">
            <?= $msg ?>
            
            <div style="margin-bottom:15px;">
                <button onclick="openModal('modalAddProyek')" class="btn btn-primary" style="width:auto; padding:10px 16px; font-size:14px; border-radius:12px;"><i data-lucide="folder-plus" style="width:18px;"></i> Buat Proyek Baru</button>
            </div>
            
            <!-- MODAL TAMBAH PROYEK -->
            <div class="modal-overlay" id="modalAddProyek">
                <div class="modal-content">
                    <div class="modal-header">
                        <h3><i data-lucide="folder-plus" style="vertical-align:middle; margin-right:8px; width:20px;"></i> Buat Proyek Baru</h3>
                        <button type="button" class="modal-close" onclick="closeModal('modalAddProyek')"><i data-lucide="x"></i></button>
                    </div>
                    <form method="POST">
                        <input type="hidden" name="action" value="add">
                    <div class="grid-2">
                        <div class="form-group form-floating">
                            <input type="text" name="kode_proyek" class="form-control" id="kode" placeholder=" " required>
                            <label for="kode">Kode Proyek</label>
                        </div>
                        <div class="form-group form-floating">
                            <input type="text" name="nama_klien" class="form-control" id="klien" placeholder=" " required>
                            <label for="klien">Klien / Industri</label>
                        </div>
                    </div>
                    
                    <div class="form-group form-floating">
                        <input type="text" name="judul_proyek" class="form-control" id="judul" placeholder=" " required>
                        <label for="judul">Judul Proyek</label>
                    </div>
                    
                    <div class="form-group">
                        <label class="form-label">Guru Pembimbing</label>
                        <select name="id_guru" class="form-control-standard" required>
                            <option value="">-- Pilih Guru --</option>
                            <?php foreach($gurus as $g): ?>
                            <option value="<?= $g['id'] ?>"><?= htmlspecialchars($g['name']) ?> (<?= $g['jurusan'] ?>)</option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="form-group">
                        <label class="form-label">Ketua Tim (Siswa)</label>
                        <select name="id_ketua" class="form-control-standard" required>
                            <option value="">-- Pilih Siswa --</option>
                            <?php foreach($siswas as $s): ?>
                            <option value="<?= $s['id'] ?>"><?= htmlspecialchars($s['name']) ?> (<?= $s['jurusan'] ?>)</option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    
                    <button type="submit" class="btn btn-primary" style="margin-top:10px;">
                        <i data-lucide="network"></i> Rilis Tim & Proyek
                    </button>
                    </form>
                </div>
            </div>
            
            <div class="card">
                <div class="card-title"><i data-lucide="folders"></i> Daftar Proyek Berjalan</div>
                <div class="table-responsive">
                    <table>
                        <thead>
                            <tr>
                                <th>Proyek & Gate</th>
                                <th>Tim Kerja</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach($proyeks as $p): ?>
                            <tr>
                                <td style="width:55%;">
                                    <div style="font-weight:700; color:var(--primary); font-size:14px; margin-bottom:4px;"><?= htmlspecialchars($p['judul_proyek']) ?></div>
                                    <div style="font-size:11px; color:var(--text-muted); margin-bottom:8px;">
                                        <i data-lucide="building" style="width:12px; height:12px; display:inline; vertical-align:text-bottom;"></i> <?= htmlspecialchars($p['nama_klien']) ?>
                                    </div>
                                    <div style="display:flex;">
                                        <?php for($i=1; $i<=4; $i++): ?>
                                            <span class="gate-badge <?= $p['status_gate'] >= $i ? 'active' : '' ?>"><?= $i ?></span>
                                        <?php endfor; ?>
                                    </div>
                                </td>
                                <td>
                                    <div style="font-size:12px; font-weight:600; color:var(--text-main); margin-bottom:4px; display:flex; align-items:center; gap:4px;">
                                        <i data-lucide="graduation-cap" style="width:14px; color:var(--accent);"></i> <?= htmlspecialchars($p['nama_guru']) ?>
                                    </div>
                                    <div style="font-size:11px; color:var(--text-muted); display:flex; align-items:center; gap:4px; margin-bottom:8px;">
                                        <i data-lucide="users" style="width:12px;"></i> <?= $p['jml_anggota'] ?> Anggota
                                    </div>
                                    <a href="#" class="badge bg-primary" style="text-decoration:none;">Detail</a>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                            <?php if(empty($proyeks)): ?>
                            <tr><td colspan="2" style="text-align:center;">Belum ada proyek</td></tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </main>
        
        <?php 
            $active_page = 'proyek';
            include '../includes/bottom_nav.php'; 
        ?>
    </div>
    <script>
        lucide.createIcons();
        function openModal(id) {
            document.getElementById(id).classList.add('active');
            document.body.style.overflow = 'hidden';
        }
        function closeModal(id) {
            document.getElementById(id).classList.remove('active');
            document.body.style.overflow = 'auto';
        }
    </script>
</body>
</html>
