<?php
require_once '../includes/config.php';
checkLogin();

if(getRole() !== 'admin') {
    die("Akses ditolak. Halaman ini hanya untuk Admin Pokja PKL.");
}

$msg = '';

if($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['action']) && $_POST['action'] == 'update_timeline') {
    if($conn) {
        $keys = ['gate_1', 'gate_2', 'gate_3', 'gate_4', 'pkl_eks_start', 'pkl_eks_end'];
        $conn->begin_transaction();
        try {
            $stmt = $conn->prepare("UPDATE settings SET setting_value = ? WHERE setting_key = ?");
            foreach($keys as $k) {
                if(isset($_POST[$k])) {
                    $val = $_POST[$k];
                    $stmt->bind_param("ss", $val, $k);
                    $stmt->execute();
                }
            }
            $conn->commit();
            
            // Perbarui array global agar session saat ini juga update
            foreach($keys as $k) {
                if(isset($_POST[$k])) {
                    $TIMELINE[$k] = $_POST[$k];
                }
            }
            
            $msg = '<div class="notification-banner success"><i data-lucide="check-circle"></i> <span>Master Data (Timeline & Tahun Ajaran) berhasil diperbarui!</span></div>';
        } catch(Exception $e) {
            $conn->rollback();
            $msg = '<div class="notification-banner danger"><i data-lucide="alert-circle"></i> <span>Gagal: '.$e->getMessage().'</span></div>';
        }
    } else {
        $msg = '<div class="notification-banner warning"><i data-lucide="alert-triangle"></i> <span>Mode Demo: Timeline tidak tersimpan ke DB.</span></div>';
    }
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
    <title>Manajemen Timeline - Admin SIPKL</title>
    <link rel="icon" type="image/svg+xml" href="../assets/img/favicon.svg">
    <link rel="preconnect" href="https://fonts.googleapis.com"><link rel="preconnect" href="https://fonts.gstatic.com" crossorigin><link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Maven+Pro:wght@400;500;600;700&display=swap"><link rel="stylesheet" href="../assets/css/style_v2.css?v=<?= filemtime('../assets/css/style_v2.css') ?>">
    <script src="https://unpkg.com/lucide@latest" defer></script>
</head>
<body>
    <div id="page-loader"><div class="loader-spinner"></div></div>
    <div class="app-container">
        <header class="app-header">
            <div style="display:flex; align-items:center; gap:15px;">
                <a href="../index.php" class="icon-btn"><i data-lucide="arrow-left"></i></a>
                <h1>Master Data Sistem</h1>
            </div>
            <i data-lucide="calendar-clock" style="color:var(--primary);"></i>
        </header>
        
        <main class="main-content">
            <?= $msg ?>
            
            <form method="POST">
                <input type="hidden" name="action" value="update_timeline">
                
                <div class="card" style="border-left: 4px solid var(--success);">
                    <div class="card-title"><i data-lucide="shield"></i> Tenggat Waktu Gatekeeping Internal</div>
                    <div class="grid-2">
                        <div class="form-group form-floating" style="margin-bottom:0;">
                            <input type="date" name="gate_1" class="form-control" id="gate1" value="<?= $TIMELINE['gate_1'] ?>" required>
                            <label for="gate1">Gate 1 (Validasi Klien)</label>
                        </div>
                        <div class="form-group form-floating" style="margin-bottom:0;">
                            <input type="date" name="gate_2" class="form-control" id="gate2" value="<?= $TIMELINE['gate_2'] ?>" required>
                            <label for="gate2">Gate 2 (Rencana Teknis)</label>
                        </div>
                        <div class="form-group form-floating" style="margin-bottom:0; margin-top:12px;">
                            <input type="date" name="gate_3" class="form-control" id="gate3" value="<?= $TIMELINE['gate_3'] ?>" required>
                            <label for="gate3">Gate 3 (Produksi 70%)</label>
                        </div>
                        <div class="form-group form-floating" style="margin-bottom:0; margin-top:12px;">
                            <input type="date" name="gate_4" class="form-control" id="gate4" value="<?= $TIMELINE['gate_4'] ?>" required>
                            <label for="gate4">Gate 4 (Handover)</label>
                        </div>
                    </div>
                </div>
                
                <div class="card" style="border-left: 4px solid var(--accent);">
                    <div class="card-title"><i data-lucide="building-2"></i> Jadwal PKL Eksternal (Industri)</div>
                    <div class="form-group form-floating">
                        <input type="date" name="pkl_eks_start" class="form-control" id="start" value="<?= $TIMELINE['pkl_eks_start'] ?>" required>
                        <label for="start">Tanggal Berangkat Industri</label>
                    </div>
                    <div class="form-group form-floating" style="margin-bottom:0;">
                        <input type="date" name="pkl_eks_end" class="form-control" id="end" value="<?= $TIMELINE['pkl_eks_end'] ?>" required>
                        <label for="end">Tanggal Penarikan Siswa</label>
                    </div>
                </div>
                
                <button type="submit" class="btn btn-primary" style="margin-bottom:20px;">
                    <i data-lucide="save"></i> Terapkan Timeline Baru
                </button>
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
