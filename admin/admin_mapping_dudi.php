<?php
require_once '../includes/config.php';
checkLogin();

if(getRole() !== 'admin') {
    die("Akses ditolak.");
}

$msg = '';

// Proses Simpan Mapping Massal
if($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['action']) && $_POST['action'] == 'save_bulk') {
    csrf_check();
    $id_dudika = (int)($_POST['id_dudika'] ?? 0);
    $id_pembimbing = (int)($_POST['id_pembimbing'] ?? 0);
    $siswa_ids = $_POST['siswa_ids'] ?? [];
    
    if($id_dudika > 0 && $id_pembimbing > 0 && !empty($siswa_ids)) {
        if($conn) {
            $conn->begin_transaction();
            try {
                // Delete existing mapping for the selected students
                $stmt_del = $conn->prepare("DELETE FROM penempatan_dudi WHERE id_siswa = ? AND tahun_ajaran = ?");
                
                // Insert new mapping
                $stmt_ins = $conn->prepare("INSERT INTO penempatan_dudi (id_siswa, id_dudika, id_pembimbing_sekolah, tahun_ajaran) VALUES (?, ?, ?, ?)");
                
                $sukses = 0;
                foreach($siswa_ids as $id_siswa) {
                    // hapus yang lama
                    $stmt_del->bind_param("is", $id_siswa, $TAHUN_AJARAN_AKTIF);
                    $stmt_del->execute();
                    
                    // insert yang baru
                    $stmt_ins->bind_param("iiis", $id_siswa, $id_dudika, $id_pembimbing, $TAHUN_AJARAN_AKTIF);
                    $stmt_ins->execute();
                    $sukses++;
                }
                
                $conn->commit();
                $msg = '<div class="notification-banner success"><i data-lucide="check-circle"></i> <span>Berhasil menempatkan '.$sukses.' siswa ke DUDI dan Guru Pembimbing yang dipilih.</span></div>';
            } catch (Exception $e) {
                $conn->rollback();
                $msg = '<div class="notification-banner danger"><i data-lucide="alert-circle"></i> <span>Terjadi kesalahan: ' . $e->getMessage() . '</span></div>';
            }
        }
    } else {
        $msg = '<div class="notification-banner warning"><i data-lucide="alert-triangle"></i> <span>Mohon lengkapi pilihan DUDI, Guru Pembimbing, dan centang minimal 1 siswa.</span></div>';
    }
}

// Ambil Data DUDI
$dudis = [];
if($conn) {
    $res = $conn->query("SELECT id, name, jurusan FROM users WHERE role = 'pembimbing_dudika' ORDER BY name");
    while($row = $res->fetch_assoc()) $dudis[] = $row;
}

// Ambil Data Guru Pembimbing
$gurus = [];
if($conn) {
    $res = $conn->query("SELECT id, name, jurusan FROM users WHERE role = 'pembimbing_sekolah' ORDER BY name");
    while($row = $res->fetch_assoc()) $gurus[] = $row;
}

// Ambil Data Siswa beserta status mapping saat ini
$siswas = [];
if($conn) {
    $stmt = $conn->prepare("
        SELECT u.id, u.name, u.username, u.jurusan, 
               pd.id_dudika, d.name as dudi_name,
               pd.id_pembimbing_sekolah, g.name as guru_name
        FROM users u 
        LEFT JOIN penempatan_dudi pd ON u.id = pd.id_siswa AND pd.tahun_ajaran = ?
        LEFT JOIN users d ON pd.id_dudika = d.id
        LEFT JOIN users g ON pd.id_pembimbing_sekolah = g.id
        WHERE u.role = 'siswa' AND u.tahun_ajaran = ?
        ORDER BY pd.id_dudika IS NOT NULL, u.jurusan, u.name
    ");
    $stmt->bind_param("ss", $TAHUN_AJARAN_AKTIF, $TAHUN_AJARAN_AKTIF);
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
    <title>Mass Mapping DUDI - Admin SIPKL</title>
    <link rel="icon" type="image/svg+xml" href="../assets/img/favicon.svg">
    <link rel="preconnect" href="https://fonts.googleapis.com"><link rel="preconnect" href="https://fonts.gstatic.com" crossorigin><link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Maven+Pro:wght@400;500;600;700&display=swap"><link rel="stylesheet" href="../assets/css/style_v2.css?v=<?= filemtime('../assets/css/style_v2.css') ?>">
    <script src="https://unpkg.com/lucide@latest"></script>
    <style>
        .table-responsive { overflow-x: auto; margin: 0 -20px; padding: 0 20px; }
        .data-table { width: 100%; border-collapse: collapse; min-width: 600px; }
        .data-table th, .data-table td { padding: 12px; text-align: left; border-bottom: 1px solid var(--border); font-size: 14px; }
        .data-table th { background: var(--bg-color); color: var(--text-muted); font-weight: 600; }
        .data-table tr:hover { background: #f8fafc; }
        .form-control-standard { width: 100%; padding: 10px; border: 1px solid var(--border); border-radius: 6px; font-size: 14px; outline: none; }
        .form-control-standard:focus { border-color: var(--primary); box-shadow: 0 0 0 3px rgba(14,165,233,0.1); }
        .sticky-panel {
            position: sticky; top: 0; background: white; z-index: 10; padding-bottom: 15px;
            border-bottom: 1px solid var(--border); margin-bottom: 15px;
        }
        .checkbox-large { width: 18px; height: 18px; cursor: pointer; }
    </style>
</head>
<body>
    <div id="page-loader"><div class="loader-spinner"></div></div>
    <div class="app-container">
        <header class="app-header">
            <div style="display:flex; align-items:center; gap:15px;">
                <a href="../index.php" class="icon-btn"><i data-lucide="arrow-left"></i></a>
                <h1>Mapping Massal DUDI</h1>
            </div>
            <i data-lucide="map" style="color:var(--primary);"></i>
        </header>

        <main class="main-content">
            <?= $msg ?>
            
            <form method="POST">
                <input type="hidden" name="action" value="save_bulk">
                <?= csrf_field() ?>
                
                <div class="card sticky-panel" style="border-radius:0 0 16px 16px; margin:-20px -20px 20px -20px; padding:20px; box-shadow:0 4px 10px rgba(0,0,0,0.05);">
                    <div style="display:grid; grid-template-columns:1fr 1fr; gap:15px;">
                        <div>
                            <label style="font-size:12px; font-weight:600; color:var(--text-muted); display:block; margin-bottom:6px;">1. Pilih Perusahaan (DUDI)</label>
                            <select name="id_dudika" class="form-control-standard" required>
                                <option value="">-- Pilih Mitra DUDI --</option>
                                <?php foreach($dudis as $d): ?>
                                    <option value="<?= $d['id'] ?>"><?= htmlspecialchars($d['name']) ?> (<?= $d['jurusan'] ?: 'Umum' ?>)</option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div>
                            <label style="font-size:12px; font-weight:600; color:var(--text-muted); display:block; margin-bottom:6px;">2. Pilih Guru Pembimbing</label>
                            <select name="id_pembimbing" class="form-control-standard" required>
                                <option value="">-- Pilih Guru Sekolah --</option>
                                <?php foreach($gurus as $g): ?>
                                    <option value="<?= $g['id'] ?>"><?= htmlspecialchars($g['name']) ?> (<?= $g['jurusan'] ?: 'Umum' ?>)</option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    </div>
                    <div style="margin-top:15px; text-align:right;">
                        <button type="submit" class="btn btn-primary" onclick="return confirm('Proses mapping massal siswa yang dicentang?');"><i data-lucide="save"></i> Terapkan Penempatan</button>
                    </div>
                </div>

                <div class="card">
                    <div class="card-title"><i data-lucide="users"></i> 3. Centang Siswa</div>
                    <p style="font-size:13px; color:var(--text-muted); margin-bottom:15px;">Centang siswa yang akan ditempatkan di DUDI tersebut. Siswa yang sudah memiliki label penempatan (abu-abu) akan <b>ditimpa (dipindah)</b> jika dicentang lagi.</p>
                    
                    <div class="table-responsive">
                        <table class="data-table">
                            <thead>
                                <tr>
                                    <th width="5%" style="text-align:center;">
                                        <input type="checkbox" id="checkAll" class="checkbox-large" onclick="const checkboxes = document.querySelectorAll('.siswa-checkbox'); checkboxes.forEach(cb => cb.checked = this.checked);">
                                    </th>
                                    <th width="35%">Nama Siswa</th>
                                    <th width="15%">Jurusan</th>
                                    <th width="45%">Status Saat Ini</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if(empty($siswas)): ?>
                                <tr><td colspan="4" style="text-align:center; padding:20px; color:var(--text-muted);">Belum ada data siswa untuk TA aktif.</td></tr>
                                <?php else: foreach($siswas as $s): ?>
                                <?php $is_mapped = !empty($s['id_dudika']); ?>
                                <tr style="<?= $is_mapped ? 'background:#f1f5f9; opacity:0.8;' : '' ?>">
                                    <td style="text-align:center;">
                                        <input type="checkbox" name="siswa_ids[]" value="<?= $s['id'] ?>" class="siswa-checkbox checkbox-large">
                                    </td>
                                    <td>
                                        <div style="font-weight:600;"><?= htmlspecialchars($s['name']) ?></div>
                                        <div style="font-size:12px; color:var(--text-muted);"><?= htmlspecialchars($s['username']) ?></div>
                                    </td>
                                    <td><span class="badge bg-primary"><?= htmlspecialchars($s['jurusan']) ?></span></td>
                                    <td>
                                        <?php if($is_mapped): ?>
                                            <div style="font-size:12px; font-weight:600; color:var(--text-main);"><i data-lucide="building" style="width:12px;"></i> <?= htmlspecialchars($s['dudi_name']) ?></div>
                                            <div style="font-size:11px; color:var(--text-muted); margin-top:2px;"><i data-lucide="user" style="width:10px;"></i> Pembimbing: <?= htmlspecialchars($s['guru_name'] ?? '-') ?></div>
                                        <?php else: ?>
                                            <span style="font-size:12px; color:var(--warning); font-weight:600;"><i data-lucide="clock" style="width:12px;"></i> Belum Ditempatkan</span>
                                        <?php endif; ?>
                                    </td>
                                </tr>
                                <?php endforeach; endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </form>
        </main>
        
        <?php 
            $active_page = 'home';
            include '../includes/bottom_nav.php'; 
        ?>
    </div>
    <script>
        lucide.createIcons();
    </script>
</body>
</html>
