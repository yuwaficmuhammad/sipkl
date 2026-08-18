<?php
require_once '../includes/config.php';
checkLogin();

if(getRole() !== 'admin') {
    die("Akses ditolak. Halaman ini hanya untuk Admin Pokja PKL.");
}

$msg = '';

// Proses aksi
if($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['action'])) {
    if($_POST['action'] == 'add') {
        $nama = trim($_POST['nama']);
        if($conn) {
            $stmt = $conn->prepare("INSERT INTO tahun_ajaran (nama, is_active) VALUES (?, 0)");
            $stmt->bind_param("s", $nama);
            if($stmt->execute()) {
                $msg = '<div class="notification-banner success"><i data-lucide="check-circle"></i> <span>Tahun Ajaran berhasil ditambahkan!</span></div>';
            } else {
                $msg = '<div class="notification-banner danger"><i data-lucide="alert-circle"></i> <span>Gagal. Tahun ajaran mungkin sudah ada.</span></div>';
            }
        }
    } else if($_POST['action'] == 'toggle') {
        $id = (int)$_POST['id'];
        if($conn) {
            $conn->begin_transaction();
            try {
                // Matikan semua
                $conn->query("UPDATE tahun_ajaran SET is_active = 0");
                // Aktifkan yang dipilih
                $stmt = $conn->prepare("UPDATE tahun_ajaran SET is_active = 1 WHERE id = ?");
                $stmt->bind_param("i", $id);
                $stmt->execute();
                $conn->commit();
                $msg = '<div class="notification-banner success"><i data-lucide="check-circle"></i> <span>Status Aktif berhasil dipindah!</span></div>';
            } catch(Exception $e) {
                $conn->rollback();
                $msg = '<div class="notification-banner danger"><i data-lucide="alert-circle"></i> <span>Gagal merubah status aktif.</span></div>';
            }
        }
    } else if($_POST['action'] == 'delete') {
        $id = (int)$_POST['id'];
        if($conn) {
            $stmt = $conn->prepare("DELETE FROM tahun_ajaran WHERE id = ? AND is_active = 0");
            $stmt->bind_param("i", $id);
            if($stmt->execute() && $stmt->affected_rows > 0) {
                $msg = '<div class="notification-banner success"><i data-lucide="check-circle"></i> <span>Tahun Ajaran berhasil dihapus!</span></div>';
            } else {
                $msg = '<div class="notification-banner danger"><i data-lucide="alert-circle"></i> <span>Gagal. Tahun ajaran aktif tidak bisa dihapus.</span></div>';
            }
        }
    }
}

$tahun_ajarans = [];
if($conn) {
    $res = $conn->query("SELECT * FROM tahun_ajaran ORDER BY nama DESC");
    while($row = $res->fetch_assoc()) {
        $tahun_ajarans[] = $row;
    }
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
    <title>Master Tahun Ajaran - Admin SIPKL</title>
    <link rel="icon" type="image/svg+xml" href="../assets/img/favicon.svg">
    <link rel="preconnect" href="https://fonts.googleapis.com"><link rel="preconnect" href="https://fonts.gstatic.com" crossorigin><link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Maven+Pro:wght@400;500;600;700&display=swap"><link rel="stylesheet" href="../assets/css/style_v2.css?v=<?= filemtime('../assets/css/style_v2.css') ?>">
    <script src="https://unpkg.com/lucide@latest"></script>
    <style>
        .table-responsive { overflow-x: auto; margin: 0 -20px; padding: 0 20px; }
        table { width: 100%; border-collapse: collapse; font-size: 13px; }
        th, td { padding: 12px 10px; border-bottom: 1px solid var(--border); text-align: left; }
        th { color: var(--text-muted); font-weight: 700; text-transform: uppercase; font-size:11px; }
    </style>
</head>
<body>
    <div id="page-loader"><div class="loader-spinner"></div></div>
    <div class="app-container">
        <header class="app-header">
            <div style="display:flex; align-items:center; gap:15px;">
                <a href="../index.php" class="icon-btn"><i data-lucide="arrow-left"></i></a>
                <h1>Kelola Tahun Ajaran</h1>
            </div>
            <i data-lucide="calendar-days" style="color:var(--primary);"></i>
        </header>
        
        <main class="main-content">
            <?= $msg ?>
            
            <div style="margin-bottom:15px;">
                <button onclick="openModal('modalAddTA')" class="btn btn-primary" style="width:auto; padding:10px 16px; font-size:14px; border-radius:12px;"><i data-lucide="plus-circle" style="width:18px;"></i> Tambah Tahun Ajaran</button>
            </div>
            
            <!-- MODAL TAMBAH TAHUN AJARAN -->
            <div class="modal-overlay" id="modalAddTA">
                <div class="modal-content">
                    <div class="modal-header">
                        <h3><i data-lucide="calendar-plus" style="vertical-align:middle; margin-right:8px; width:20px;"></i> Tambah Tahun Ajaran</h3>
                        <button type="button" class="modal-close" onclick="closeModal('modalAddTA')"><i data-lucide="x"></i></button>
                    </div>
                    <form method="POST">
                        <input type="hidden" name="action" value="add">
                        <div class="form-group form-floating">
                            <input type="text" name="nama" class="form-control" id="nama_ta" placeholder=" " required>
                            <label for="nama_ta">Nama Tahun Ajaran (Contoh: 2027/2028)</label>
                        </div>
                        <button type="submit" class="btn btn-primary" style="margin-top:10px;">
                            <i data-lucide="save"></i> Simpan Data
                        </button>
                    </form>
                </div>
            </div>
            
            <div class="card">
                <div class="card-title"><i data-lucide="database"></i> Daftar Tahun Ajaran</div>
                <div class="table-responsive">
                    <table>
                        <thead>
                            <tr>
                                <th>Informasi Tahun Ajaran</th>
                                <th style="text-align:right;">Aksi</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach($tahun_ajarans as $t): ?>
                            <tr>
                                <td>
                                    <div style="font-weight:700; color:<?= $t['is_active'] ? 'var(--primary)' : 'var(--text-main)' ?>; font-size:14px; margin-bottom:6px;">
                                        <?= htmlspecialchars($t['nama']) ?>
                                    </div>
                                    <?php if($t['is_active']): ?>
                                        <span style="display:inline-flex; align-items:center; gap:4px; background:#dcfce7; color:#166534; padding:6px 12px; border-radius:20px; font-size:11px; font-weight:700; border:1px solid #bbf7d0;">
                                            <i data-lucide="check-circle" style="width:14px; height:14px;"></i> AKTIF (SEKARANG)
                                        </span>
                                    <?php else: ?>
                                        <form method="POST" style="display:inline; margin:0;">
                                            <input type="hidden" name="action" value="toggle">
                                            <input type="hidden" name="id" value="<?= $t['id'] ?>">
                                            <button type="submit" style="display:inline-flex; align-items:center; background:#f8fafc; color:var(--text-muted); border:1px solid #cbd5e1; padding:6px 12px; border-radius:20px; font-size:11px; font-weight:600; cursor:pointer; transition:all 0.2s ease;" onmouseover="this.style.background='#f1f5f9'; this.style.borderColor='#94a3b8'; this.style.color='#475569'" onmouseout="this.style.background='#f8fafc'; this.style.borderColor='#cbd5e1'; this.style.color='var(--text-muted)'">
                                                Set Aktif
                                            </button>
                                        </form>
                                    <?php endif; ?>
                                </td>
                                <td style="text-align:right; vertical-align:top;">
                                    <?php if(!$t['is_active']): ?>
                                    <form method="POST" style="display:inline;" onsubmit="return confirm('Hapus master tahun ini?');">
                                        <input type="hidden" name="action" value="delete">
                                        <input type="hidden" name="id" value="<?= $t['id'] ?>">
                                        <button type="submit" style="color:var(--danger); display:inline-block; padding:8px; border-radius:8px; background:#fee2e2; border:none; cursor:pointer; vertical-align:middle;">
                                            <i data-lucide="trash-2" style="width:16px; height:16px; display:block;"></i>
                                        </button>
                                    </form>
                                    <?php else: ?>
                                        <span style="color:#cbd5e1; font-size:11px;">(Terkunci)</span>
                                    <?php endif; ?>
                                </td>
                            </tr>
                            <?php endforeach; ?>
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
