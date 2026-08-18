<?php
require_once '../includes/config.php';
checkLogin();

if(getRole() !== 'admin') {
    die("Akses ditolak. Halaman ini khusus Admin Pokja.");
}

$msg = '';

if($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['action']) && $_POST['action'] == 'update_lembaga') {
    csrf_check();
    
    $fields = [
        'sekolah_nama' => $_POST['sekolah_nama'],
        'sekolah_alamat' => $_POST['sekolah_alamat'],
        'sekolah_latlong' => $_POST['sekolah_latlong']
    ];
    
    if($conn) {
        $stmt = $conn->prepare("INSERT INTO settings (setting_key, setting_value) VALUES (?, ?) ON DUPLICATE KEY UPDATE setting_value = ?");
        foreach($fields as $k => $v) {
            $stmt->bind_param("sss", $k, $v, $v);
            $stmt->execute();
        }
        
        $msg = '<div class="notification-banner success"><i data-lucide="check-circle"></i> <span>Profil Lembaga berhasil diperbarui!</span></div>';
        
        // Refresh local array
        foreach($fields as $k => $v) {
            $TIMELINE[$k] = $v;
        }
    }
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
    <title>Profil Lembaga - Admin SIPKL</title>
    <link rel="icon" type="image/svg+xml" href="../assets/img/favicon.svg">
    <link rel="stylesheet" href="../assets/css/style.css?v=<?= filemtime('../assets/css/style.css') ?>">
    <script src="https://unpkg.com/lucide@latest"></script>
</head>
<body>
    <div id="page-loader"><div class="loader-spinner"></div></div>
    <div class="app-container">
        <header class="app-header">
            <div style="display:flex; align-items:center; gap:15px;">
                <a href="../index.php" class="icon-btn"><i data-lucide="arrow-left"></i></a>
                <h1>Profil Lembaga</h1>
            </div>
            <i data-lucide="building" style="color:var(--primary);"></i>
        </header>
        
        <main class="main-content">
            <?= $msg ?>
            
            <form method="POST">
                <input type="hidden" name="action" value="update_lembaga">
                <?= csrf_field() ?>
                
                <div class="card" style="border-left: 4px solid var(--primary);">
                    <div class="card-title"><i data-lucide="landmark"></i> Informasi Sekolah</div>
                    <div class="form-group form-floating">
                        <input type="text" name="sekolah_nama" class="form-control" id="sekolah_nama" value="<?= htmlspecialchars($TIMELINE['sekolah_nama'] ?? 'SMK Salafiyah Pati') ?>" required>
                        <label for="sekolah_nama">Nama Lembaga Pendidikan</label>
                    </div>
                    <div class="form-group form-floating">
                        <textarea name="sekolah_alamat" class="form-control" id="sekolah_alamat" required style="height:100px;"><?= htmlspecialchars($TIMELINE['sekolah_alamat'] ?? 'Kajen, Margoyoso, Pati, Jawa Tengah') ?></textarea>
                        <label for="sekolah_alamat">Alamat Lengkap</label>
                    </div>
                </div>
                
                <div class="card" style="border-left: 4px solid var(--success);">
                    <div class="card-title"><i data-lucide="map-pin"></i> Konfigurasi Geofencing (GPS)</div>
                    <div style="font-size:12px; color:var(--text-muted); margin-bottom:15px;">
                        Koordinat ini akan menjadi titik pusat 0 meter untuk fitur validasi radius absensi bagi siswa yang ditugaskan pada **Proyek Internal Sekolah**.
                    </div>
                    <div class="form-group form-floating">
                        <input type="text" name="sekolah_latlong" class="form-control" id="sekolah_latlong" value="<?= htmlspecialchars($TIMELINE['sekolah_latlong'] ?? '-6.6669,111.0263') ?>" required>
                        <label for="sekolah_latlong">Koordinat Lat,Long (Contoh: -6.6669,111.0263)</label>
                    </div>
                    <a href="https://www.google.com/maps" target="_blank" class="btn btn-outline" style="font-size:13px; padding:10px;"><i data-lucide="external-link" style="width:16px;"></i> Cari Koordinat di Google Maps</a>
                </div>
                
                <button type="submit" class="btn btn-primary" style="margin-bottom:20px;">
                    <i data-lucide="save"></i> Simpan Profil Lembaga
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
        window.onload = function() {
            document.getElementById('page-loader').style.opacity = '0';
            setTimeout(() => document.getElementById('page-loader').style.display = 'none', 300);
        }
    </script>
</body>
</html>
