<?php
require_once '../includes/config.php';
checkLogin();

if(getRole() !== 'admin') {
    die("Akses ditolak. Halaman ini hanya untuk Admin Pokja PKL.");
}

// 1. Dapatkan daftar tahun ajaran arsip (Hanya yang berstatus Tidak Aktif)
$tahun_ajarans = [];
if($conn) {
    $res = $conn->query("SELECT nama FROM tahun_ajaran WHERE is_active = 0 ORDER BY nama DESC");
    if($res) {
        while($row = $res->fetch_assoc()) {
            $tahun_ajarans[] = $row['nama'];
        }
    }
} else {
    $tahun_ajarans = ['2026/2027', '2025/2026', '2024/2025'];
}

// 2. Tahun ajaran yang dipilih untuk dilihat arsipnya
$selected_ta = $_GET['ta'] ?? ($tahun_ajarans[0] ?? $TAHUN_AJARAN_AKTIF);

// 3. Ambil data arsip berdasarkan $selected_ta
$proyeks = [];
$siswas = [];

if($conn) {
    // Ambil Proyek
    $stmt = $conn->prepare("
        SELECT p.*, u.name as nama_guru,
               COUNT(tp.id_siswa) as jml_anggota
        FROM proyek_internal p
        JOIN users u ON p.id_pembimbing_sekolah = u.id
        LEFT JOIN tim_proyek tp ON tp.id_proyek = p.id
        WHERE p.tahun_ajaran = ?
        GROUP BY p.id, u.name
    ");
    $stmt->bind_param("s", $selected_ta);
    $stmt->execute();
    $res_p = $stmt->get_result();
    while($row = $res_p->fetch_assoc()) $proyeks[] = $row;
    
    // Ambil Siswa (Nilai DUDI belum ada di tabel, kita asumsikan status saja untuk sekarang,
    // di masa depan bisa di-join ke tabel nilai_dudi jika dibuat)
    $stmt_s = $conn->prepare("SELECT id, username, name, jurusan FROM users WHERE role='siswa' AND tahun_ajaran = ?");
    $stmt_s->bind_param("s", $selected_ta);
    $stmt_s->execute();
    $res_s = $stmt_s->get_result();
    while($row = $res_s->fetch_assoc()) $siswas[] = $row;
} else {
    if($selected_ta == '2025/2026') {
        $proyeks = [
            ['id'=>99, 'kode_proyek'=>'PRJ-OLD', 'nama_klien'=>'CV Lama', 'judul_proyek'=>'Web Company Profile', 'nama_guru'=>'Bu Guru', 'jml_anggota'=>4, 'status_gate'=>4]
        ];
        $siswas = [
            ['username'=>'alumni1', 'name'=>'Alumni Siswa A', 'jurusan'=>'PPLG']
        ];
    }
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
    <title>Arsip Laporan - Admin SIPKL</title>
    <link rel="icon" type="image/svg+xml" href="../assets/img/favicon.svg">
    <link rel="preconnect" href="https://fonts.googleapis.com"><link rel="preconnect" href="https://fonts.gstatic.com" crossorigin><link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Maven+Pro:wght@400;500;600;700&display=swap"><link rel="stylesheet" href="../assets/css/style_v2.css?v=<?= filemtime('../assets/css/style_v2.css') ?>">
    <script src="https://unpkg.com/lucide@latest" defer></script>
    <style>
        .table-responsive { overflow-x: auto; margin: 0 -20px; padding: 0 20px; }
        table { width: 100%; border-collapse: collapse; font-size: 13px; }
        th, td { padding: 12px 10px; border-bottom: 1px solid var(--border); text-align: left; }
        th { color: var(--text-muted); font-weight: 700; text-transform: uppercase; font-size:11px; }
        .gate-badge { display:inline-flex; align-items:center; justify-content:center; width:22px; height:22px; background:#e2e8f0; border-radius:50%; font-size:9px; font-weight:bold; margin-right:4px; color:var(--text-muted); }
        .gate-badge.active { background:var(--success); color:white; }
    </style>
</head>
<body>
    <div id="page-loader"><div class="loader-spinner"></div></div>
    <div class="app-container">
        <header class="app-header">
            <div style="display:flex; align-items:center; gap:15px;">
                <a href="../index.php" class="icon-btn"><i data-lucide="arrow-left"></i></a>
                <h1>Arsip & Rekapitulasi</h1>
            </div>
            <i data-lucide="archive" style="color:var(--text-muted);"></i>
        </header>
        
        <main class="main-content">
            <div style="background:#f1f5f9; color:var(--text-main); border-left:4px solid #94a3b8; font-size:13px; margin-bottom:15px; padding:12px 15px; border-radius:8px; display:flex; align-items:flex-start; gap:10px;">
                <i data-lucide="info" style="color:#64748b; width:18px; flex-shrink:0;"></i>
                <span>Data di bawah ini bersifat <b>Read-Only</b> (hanya baca). Mengubah dropdown ini tidak memengaruhi sistem aktif.</span>
            </div>
            
            <div class="card">
                <div class="card-title"><i data-lucide="filter"></i> Filter Arsip Tahunan</div>
                <form method="GET" style="display:flex; gap:10px; align-items:flex-end;">
                    <div class="form-group" style="margin-bottom:0; flex:1;">
                        <label class="form-label">Pilih Tahun Ajaran Lalu</label>
                        <select name="ta" class="form-control-standard">
                            <?php foreach($tahun_ajarans as $t): ?>
                            <option value="<?= htmlspecialchars($t) ?>" <?= $t == $selected_ta ? 'selected' : '' ?>>
                                Tahun Ajaran <?= htmlspecialchars($t) ?> <?= $t == $TAHUN_AJARAN_AKTIF ? '(Aktif)' : '' ?>
                            </option>
                            <?php endforeach; ?>
                            <?php if(empty($tahun_ajarans)): ?>
                                <option value="">Belum ada data arsip</option>
                            <?php endif; ?>
                        </select>
                    </div>
                    <button type="submit" class="btn btn-primary" style="width:auto; padding:12px 16px;">
                        <i data-lucide="search" style="width:18px;"></i>
                    </button>
                </form>
            </div>
            
            <div class="card">
                <div class="card-title"><i data-lucide="folder-git-2"></i> Rekap Proyek Internal (<?= count($proyeks) ?>)</div>
                <div class="table-responsive">
                    <table>
                        <thead>
                            <tr>
                                <th>Proyek & Pencapaian Gate</th>
                                <th>Tim Pembimbing</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach($proyeks as $p): ?>
                            <tr>
                                <td style="width:60%;">
                                    <div style="font-weight:700; color:var(--primary); font-size:13px; margin-bottom:4px;"><?= htmlspecialchars($p['judul_proyek']) ?></div>
                                    <div style="display:flex; margin-top:8px;">
                                        <?php for($i=1; $i<=4; $i++): ?>
                                            <span class="gate-badge <?= $p['status_gate'] >= $i ? 'active' : '' ?>"><?= $i ?></span>
                                        <?php endfor; ?>
                                    </div>
                                </td>
                                <td>
                                    <div style="font-size:12px; color:var(--text-main); font-weight:600;"><i data-lucide="graduation-cap" style="width:12px;"></i> <?= htmlspecialchars($p['nama_guru']) ?></div>
                                    <div style="font-size:11px; color:var(--text-muted); margin-top:4px;"><i data-lucide="users" style="width:12px;"></i> <?= $p['jml_anggota'] ?> Siswa</div>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                            <?php if(empty($proyeks)): ?>
                            <tr><td colspan="2" style="text-align:center;">Tidak ada proyek di tahun ini.</td></tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
            
            <div class="card">
                <div class="card-title"><i data-lucide="users"></i> Daftar Siswa Angkatan (<?= count($siswas) ?>)</div>
                <div class="table-responsive">
                    <table>
                        <thead>
                            <tr>
                                <th>Nama Lengkap & NISN</th>
                                <th>Jurusan</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach($siswas as $s): ?>
                            <tr>
                                <td>
                                    <div style="font-weight:600; color:var(--primary); font-size:13px;"><?= htmlspecialchars($s['name']) ?></div>
                                    <div style="font-size:11px; color:var(--text-muted); margin-top:2px;">@<?= htmlspecialchars($s['username']) ?></div>
                                </td>
                                <td><span class="badge bg-success"><?= htmlspecialchars($s['jurusan'] ?? '-') ?></span></td>
                            </tr>
                            <?php endforeach; ?>
                            <?php if(empty($siswas)): ?>
                            <tr><td colspan="2" style="text-align:center;">Tidak ada siswa di tahun ini.</td></tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
                <div style="display:flex; gap:10px; margin-top:16px;">
                    <a href="admin_export.php?ta=<?= urlencode($selected_ta) ?>" class="btn" style="flex:1; background:var(--bg-color); color:var(--text-main); border:1px solid var(--border); display:inline-flex; align-items:center; justify-content:center; gap:8px; text-decoration:none;">
                        <i data-lucide="file-spreadsheet" style="width:18px;"></i> Laporan CSV
                    </a>
                    <a href="admin_export_pdf.php?ta=<?= urlencode($selected_ta) ?>" class="btn" style="flex:1; background:#0ea5e9; color:#fff; border:1px solid #0ea5e9; display:inline-flex; align-items:center; justify-content:center; gap:8px; text-decoration:none;">
                        <i data-lucide="printer" style="width:18px;"></i> Laporan PDF
                    </a>
                </div>
            </div>
            
        </main>
        
        <?php 
            $active_page = 'arsip';
            include '../includes/bottom_nav.php'; 
        ?>
    </div>
    <script>
        lucide.createIcons();
    </script>
</body>
</html>
