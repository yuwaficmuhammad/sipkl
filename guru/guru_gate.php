<?php
require_once '../includes/config.php';
checkLogin();

if(getRole() !== 'pembimbing_sekolah') {
    die("Akses ditolak. Halaman ini khusus Guru Pembimbing Sekolah.");
}

$user_id = $_SESSION['user_id'];
$msg = '';

if(isset($_GET['approve_gate']) && isset($_GET['proyek_id']) && $conn) {
    $proyek_id = (int)$_GET['proyek_id'];
    $gate = (int)$_GET['approve_gate'];
    
    if($gate >= 1 && $gate <= 4) {
        $stmt = $conn->prepare("UPDATE proyek_internal SET status_gate = ?, is_remedial = 0 WHERE id = ? AND id_pembimbing_sekolah = ?");
        $stmt->bind_param("iii", $gate, $proyek_id, $user_id);
        if($stmt->execute()) {
            $msg = '<div class="notification-banner success"><i data-lucide="shield-check"></i> <span>Gate '.$gate.' berhasil disahkan (ACC)!</span></div>';
        }
    }
}

if(isset($_GET['reject_gate']) && isset($_GET['proyek_id']) && $conn) {
    $proyek_id = (int)$_GET['proyek_id'];
    $gate = (int)$_GET['reject_gate'];
    if($gate >= 1 && $gate <= 4) {
        $col = "doc_gate_" . $gate;
        // Hapus nama dokumen dan tandai remedial
        $stmt = $conn->prepare("UPDATE proyek_internal SET $col = NULL, is_remedial = 1 WHERE id = ? AND id_pembimbing_sekolah = ?");
        $stmt->bind_param("ii", $proyek_id, $user_id);
        if($stmt->execute()) {
            $msg = '<div class="notification-banner warning"><i data-lucide="alert-triangle"></i> <span>Dokumen Gate '.$gate.' DITOLAK. Siswa harus unggah ulang dokumen revisi.</span></div>';
        }
    }
}

if(isset($_GET['remedial_proyek']) && $conn) {
    $proyek_id = (int)$_GET['remedial_proyek'];
    $stmt = $conn->prepare("UPDATE proyek_internal SET is_remedial = 1 WHERE id = ? AND id_pembimbing_sekolah = ?");
    $stmt->bind_param("ii", $proyek_id, $user_id);
    if($stmt->execute()) {
        $msg = '<div class="notification-banner warning"><i data-lucide="alert-triangle"></i> <span>Tim ditandai REMEDIAL. Wajib perbaikan.</span></div>';
    }
}

$proyeks = [];
if($conn) {
    $stmt = $conn->prepare("
        SELECT p.*,
               COUNT(DISTINCT tp_all.id_siswa) as jml_anggota,
               ketua_u.name as nama_ketua
        FROM proyek_internal p
        LEFT JOIN tim_proyek tp_all ON tp_all.id_proyek = p.id
        LEFT JOIN tim_proyek tp_ketua ON tp_ketua.id_proyek = p.id AND tp_ketua.is_ketua = 1
        LEFT JOIN users ketua_u ON tp_ketua.id_siswa = ketua_u.id
        WHERE p.id_pembimbing_sekolah = ? AND p.tahun_ajaran = ?
        GROUP BY p.id, ketua_u.name");
    $stmt->bind_param("is", $user_id, $TAHUN_AJARAN_AKTIF);
    $stmt->execute();
    $res = $stmt->get_result();
    while($row = $res->fetch_assoc()) $proyeks[] = $row;
} else {
    $proyeks = [
        ['id'=>1, 'kode_proyek'=>'PRJ-001', 'nama_klien'=>'Toko Bangunan ABC', 'judul_proyek'=>'Aplikasi Kasir Toko', 'nama_ketua'=>'Ahmad Siswa', 'jml_anggota'=>3, 'status_gate'=>2, 'is_remedial'=>0]
    ];
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
    <title>Gatekeeping - Guru SIPKL</title>
    <link rel="icon" type="image/svg+xml" href="../assets/img/favicon.svg">
    <link rel="preconnect" href="https://fonts.googleapis.com"><link rel="preconnect" href="https://fonts.gstatic.com" crossorigin><link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Maven+Pro:wght@400;500;600;700&display=swap"><link rel="stylesheet" href="../assets/css/style_v2.css?v=<?= filemtime('../assets/css/style_v2.css') ?>">
    <script src="https://unpkg.com/lucide@latest" defer></script>
    <style>
        .gate-box { display:flex; gap:12px; margin-top:20px; }
        .gate-btn { 
            flex:1; text-align:center; padding:12px 6px; border-radius:12px; font-size:12px; font-weight:700; 
            text-decoration:none; color:var(--text-muted); background:var(--bg-color); border:1px solid var(--border);
            display:flex; flex-direction:column; align-items:center; gap:8px; transition:var(--transition);
        }
        .gate-btn .lucide { width:20px; height:20px; }
        
        .gate-btn.passed { background:var(--success); color:white; border-color:var(--success); box-shadow:0 4px 10px rgba(16,185,129,0.2); }
        .gate-btn.current { background:var(--surface); color:var(--accent); border:2px solid var(--accent); transform:scale(1.05); box-shadow:0 4px 12px rgba(59,130,246,0.15); }
        .gate-btn.locked { opacity: 0.5; pointer-events:none; }
    </style>
</head>
<body>
    <div id="page-loader"><div class="loader-spinner"></div></div>
    <div class="app-container">
        <header class="app-header">
            <div style="display:flex; align-items:center; gap:15px;">
                <a href="index.php" class="icon-btn"><i data-lucide="arrow-left"></i></a>
                <h1>Gatekeeping</h1>
            </div>
            <i data-lucide="shield-check" style="color:var(--primary);"></i>
        </header>
        
        <main class="main-content">
            <?= $msg ?>
            
            <div class="notification-banner" style="background:#e0f2fe; color:#0369a1; border-left:4px solid #0284c7;">
                <i data-lucide="info"></i>
                <span>Menu ini digunakan saat <b>Audit Day (Kamis)</b> untuk mengesahkan kelulusan tim ke Gate berikutnya.</span>
            </div>
            
            <?php foreach($proyeks as $p): ?>
            <div class="card" style="<?= $p['is_remedial'] ? 'border-color:var(--danger); background:#fef2f2;' : '' ?>">
                <div style="display:flex; justify-content:space-between; align-items:flex-start;">
                    <div>
                        <div style="font-weight:700; color:var(--primary); font-size:16px; margin-bottom:6px;"><?= htmlspecialchars($p['judul_proyek']) ?></div>
                        <div style="font-size:12px; color:var(--text-muted); display:flex; align-items:center; gap:6px; margin-bottom:4px;">
                            <i data-lucide="building-2" style="width:14px; height:14px;"></i> <?= htmlspecialchars($p['nama_klien']) ?>
                        </div>
                        <div style="font-size:12px; color:var(--text-muted); display:flex; align-items:center; gap:6px;">
                            <i data-lucide="users" style="width:14px; height:14px;"></i> <?= htmlspecialchars($p['nama_ketua']) ?> & <?= $p['jml_anggota']-1 ?> lainnya
                        </div>
                    </div>
                    <?php if($p['is_remedial']): ?>
                        <div class="badge bg-danger" style="box-shadow:0 2px 5px rgba(239,68,68,0.3);">REMEDIAL</div>
                    <?php endif; ?>
                </div>
                
                <div class="gate-box">
                    <?php for($i=1; $i<=4; $i++): 
                        $status_class = 'locked';
                        $href = '#';
                        $icon = 'lock';
                        $is_current = false;
                        $has_doc = false;
                        
                        if($p['status_gate'] >= $i) {
                            $status_class = 'passed';
                            $icon = 'check-circle-2';
                        } else if($p['status_gate'] == $i - 1) {
                            $status_class = 'current';
                            $icon = 'unlock';
                            $is_current = true;
                            
                            $doc_col = "doc_gate_" . $i;
                            $has_doc = !empty($p[$doc_col]);
                            if($has_doc) {
                                $href = "?approve_gate=$i&proyek_id=" . $p['id'];
                            } else {
                                $href = "javascript:Swal.fire({icon:'warning', title:'Terkunci!', text:'Siswa belum mengunggah dokumen PDF untuk Gate ini.', confirmButtonColor:'#0ea5e9'})";
                            }
                        }
                    ?>
                    <a href="<?= $href ?>" class="gate-btn <?= $status_class ?>" <?= ($is_current && $has_doc) ? 'onclick="return confirm(\'Dokumen sudah diperiksa? Klik OK untuk ACC Gate '.$i.'\')"' : '' ?> <?= ($is_current && !$has_doc) ? 'style="opacity:0.4; cursor:not-allowed;" title="Menunggu unggahan PDF dari Siswa"' : '' ?>>
                        <span>G<?= $i ?></span>
                        <i data-lucide="<?= $icon ?>"></i>
                    </a>
                    <?php endfor; ?>
                </div>
                
                <?php 
                // Tampilkan pratinjau dokumen untuk Gate yang sedang berjalan
                $current_gate = $p['status_gate'] + 1;
                if($current_gate <= 4) {
                    $doc_col = "doc_gate_" . $current_gate;
                    if(!empty($p[$doc_col])) {
                        echo '<div style="background:#f0fdf4; border:1px solid #bbf7d0; padding:12px; border-radius:8px; margin-top:16px; font-size:12px;">';
                        echo '<div style="font-weight:600; color:#166534; margin-bottom:8px;"><i data-lucide="file-check-2" style="width:14px; margin-right:4px;"></i> Dokumen Syarat Gate '.$current_gate.' Tersedia</div>';
                        echo '<div style="display:flex; gap:10px; align-items:center;">';
                        echo '<a href="uploads/'.$p[$doc_col].'" target="_blank" class="btn btn-primary" style="padding:6px 12px; font-size:11px; width:auto; background:#16a34a;"><i data-lucide="external-link" style="width:14px;"></i> Lihat PDF</a>';
                        echo '<a href="?reject_gate='.$current_gate.'&proyek_id='.$p['id'].'" class="btn btn-outline" style="padding:6px 12px; font-size:11px; width:auto; border-color:var(--danger); color:var(--danger);" onclick="return confirm(\'Tolak dokumen ini dan minta siswa mengulang unggahan?\')"><i data-lucide="x-circle" style="width:14px;"></i> Tolak (Revisi)</a>';
                        echo '</div></div>';
                    } else {
                        echo '<div style="background:#fff7ed; border:1px dashed #fdba74; padding:12px; border-radius:8px; margin-top:16px; font-size:12px; color:#c2410c;">';
                        echo '<i data-lucide="clock" style="width:14px; margin-right:4px;"></i> Menunggu tim ini mengunggah Dokumen PDF Gate '.$current_gate;
                        echo '</div>';
                    }
                }
                ?>
                
                <?php if(!$p['is_remedial'] && $p['status_gate'] < 4): ?>
                <div style="margin-top:20px; text-align:right;">
                    <a href="?remedial_proyek=<?= $p['id'] ?>" class="btn btn-outline" style="color:var(--danger); border-color:var(--danger); padding:10px 16px; font-size:13px; display:inline-flex; width:auto;" onclick="return confirm('Tandai tim ini sebagai REMEDIAL? Mereka harus mengulang audit minggu depan.')">
                        <i data-lucide="alert-circle" style="width:16px;"></i> Tandai Remedial
                    </a>
                </div>
                <?php endif; ?>
            </div>
            <?php endforeach; ?>
            
            <?php if(empty($proyeks)): ?>
                <div class="card" style="text-align:center; padding:40px 20px;">
                    <i data-lucide="folder-search" style="width:64px; height:64px; color:var(--border); margin:0 auto 16px;"></i>
                    <h2 style="font-size:16px; color:var(--text-muted);">Belum Ada Proyek</h2>
                </div>
            <?php endif; ?>
        </main>
        
        <?php 
            $active_page = 'gate';
            include '../includes/bottom_nav.php'; 
        ?>
    </div>
    <script>
        lucide.createIcons();
    </script>
</body>
</html>
