<?php
require_once '../includes/config.php';
checkLogin();

if(getRole() !== 'admin') {
    die("Akses ditolak. Halaman ini hanya untuk Admin Pokja PKL.");
}

$msg = '';
$uploaded_data = [];

// 1. Tangani Upload CSV
if($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['action']) && $_POST['action'] == 'upload_csv') {
    if(isset($_FILES['csv_file']) && $_FILES['csv_file']['error'] == 0) {
        $file = $_FILES['csv_file']['tmp_name'];
        if(($handle = fopen($file, "r")) !== FALSE) {
            $header = fgetcsv($handle, 1000, ",", "\"", "\\"); // Parameter escape untuk PHP 8.4
            while (($data = fgetcsv($handle, 1000, ",", "\"", "\\")) !== FALSE) {
                // Asumsi CSV Python kita: Kolom 0 = Nama Lengkap, Kolom 1 = Username
                if(isset($data[0]) && isset($data[1])) {
                    $nama = trim($data[0]);
                    $username = trim($data[1]);
                    
                    if(!empty($nama) && strcasecmp($nama, 'NaT') !== 0 && strcasecmp($nama, 'NaN') !== 0) {
                        $uploaded_data[] = [
                            'nama' => $nama,
                            'username' => $username
                        ];
                    }
                }
            }
            fclose($handle);
        }
    } else {
        $msg = '<div class="notification-banner danger"><i data-lucide="alert-circle"></i> <span>Gagal mengunggah file CSV.</span></div>';
    }
}

// 2. Tangani Penyimpanan Guru yang Dicentang
if($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['action']) && $_POST['action'] == 'save_selected') {
    if(isset($_POST['selected_gurus']) && is_array($_POST['selected_gurus'])) {
        $sukses = 0;
        $gagal = 0;
        if($conn) {
            $stmt = $conn->prepare("INSERT IGNORE INTO users (username, password, name, role) VALUES (?, ?, ?, 'pembimbing_sekolah')");
            foreach($_POST['selected_gurus'] as $index) {
                $nama = $_POST['nama'][$index] ?? '';
                $username = $_POST['username'][$index] ?? '';
                if($nama && $username) {
                    $password = password_hash($username, PASSWORD_BCRYPT); // Default: Username sebagai password
                    $stmt->bind_param("sss", $username, $password, $nama);
                    if($stmt->execute() && $stmt->affected_rows > 0) {
                        $sukses++;
                    } else {
                        $gagal++;
                    }
                }
            }
            $msg = '<div class="notification-banner success"><i data-lucide="check-circle"></i> <span>Berhasil menambahkan '.$sukses.' guru sebagai pembimbing. (Dilewati: '.$gagal.')</span></div>';
        }
    } else {
        $msg = '<div class="notification-banner warning"><i data-lucide="alert-triangle"></i> <span>Tidak ada guru yang dipilih.</span></div>';
    }
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
    <title>Import CSV Guru - Admin SIPKL</title>
    <link rel="icon" type="image/svg+xml" href="../assets/img/favicon.svg">
    <link rel="stylesheet" href="../assets/css/style_v2.css?v=<?= filemtime('../assets/css/style_v2.css') ?>">
    <script src="https://unpkg.com/lucide@latest"></script>
    <style>
        .table-responsive { overflow-x: auto; margin: 0 -20px; padding: 0 20px; }
        table { width: 100%; border-collapse: collapse; font-size: 13px; }
        th, td { padding: 12px 10px; border-bottom: 1px solid var(--border); text-align: left; }
        th { color: var(--text-muted); font-weight: 700; text-transform: uppercase; font-size:11px; }
        .checkbox-large { width: 18px; height: 18px; cursor: pointer; accent-color: var(--primary); }
    </style>
</head>
<body>
    <div id="page-loader"><div class="loader-spinner"></div></div>
    <div class="app-container">
        <header class="app-header">
            <div style="display:flex; align-items:center; gap:15px;">
                <a href="admin_users.php" class="icon-btn"><i data-lucide="arrow-left"></i></a>
                <h1>Import Data Guru</h1>
            </div>
            <i data-lucide="file-spreadsheet" style="color:var(--success);"></i>
        </header>
        
        <main class="main-content">
            <?= $msg ?>
            
            <?php if(empty($uploaded_data)): ?>
            <!-- Tahap 1: Upload Form -->
            <div class="notification-banner" style="background:#e0f2fe; color:#0369a1; border-left:4px solid #0284c7;">
                <i data-lucide="info"></i>
                <div style="flex:1;">
                    <span>Unggah file CSV yang berisi 2 kolom utama: <b>Nama Guru</b> dan <b>Username (NIP)</b>.</span>
                    <div style="margin-top:8px;">
                        <a href="../templates/template_guru.csv" download class="badge bg-primary" style="text-decoration:none; display:inline-flex; align-items:center; gap:4px; font-weight:600;"><i data-lucide="download" style="width:12px;"></i> Download Template CSV</a>
                        <a href="../templates/template_guru.xlsx" download class="badge bg-success" style="text-decoration:none; display:inline-flex; align-items:center; gap:4px; font-weight:600;"><i data-lucide="table" style="width:12px;"></i> Download Template Excel</a>
                    </div>
                </div>
            </div>
            
            <form method="POST" enctype="multipart/form-data" class="card" style="border-left: 4px solid var(--primary);">
                <div class="card-title"><i data-lucide="upload"></i> Upload File CSV</div>
                <div class="form-group">
                    <div style="position:relative; width:100%; border:2px dashed #94a3b8; border-radius:12px; padding:30px 20px; text-align:center; background:#f8fafc; transition:all 0.3s ease;" onmouseover="this.style.borderColor='var(--primary)'; this.style.background='#eff6ff'" onmouseout="this.style.borderColor='#94a3b8'; this.style.background='#f8fafc'">
                        <input type="file" name="csv_file" id="csv_file" accept=".csv" required style="position:absolute; top:0; left:0; width:100%; height:100%; opacity:0; cursor:pointer;" onchange="document.getElementById('file_name_display').innerText = this.files[0].name; document.getElementById('file_name_display').style.color = 'var(--primary)'; document.getElementById('file_name_display').style.fontWeight = 'bold';">
                        <i data-lucide="upload-cloud" style="width:48px; height:48px; color:var(--primary); margin-bottom:12px; display:inline-block;"></i>
                        <div style="font-weight:600; color:var(--text-main); font-size:14px; margin-bottom:4px;">Pilih File CSV Guru</div>
                        <div id="file_name_display" style="font-size:12px; color:var(--text-muted);">Ketuk area ini untuk menelusuri file</div>
                    </div>
                </div>
                <input type="hidden" name="action" value="upload_csv">
                <button type="submit" class="btn btn-primary" style="margin-top:10px;">
                    <i data-lucide="arrow-right"></i> Baca Data CSV
                </button>
            </form>
            <?php else: ?>
            <!-- Tahap 2: Filter Checkbox -->
            <form method="POST" class="card" style="border-left: 4px solid var(--success);">
                <div class="card-title" style="display:flex; justify-content:space-between; align-items:center;">
                    <span><i data-lucide="check-square"></i> Pilih Guru Pembimbing</span>
                    <span class="badge bg-primary"><?= count($uploaded_data) ?> Data</span>
                </div>
                
                <p style="font-size:12px; color:var(--text-muted); margin-bottom:15px;">Centang HANYA guru-guru yang akan bertugas membimbing PKL. Username akan digenerate otomatis. Password default: <b>guru123</b>.</p>
                
                <div class="table-responsive" style="max-height:400px; overflow-y:auto; border:1px solid var(--border); border-radius:8px; margin-bottom:15px;">
                    <table>
                        <thead style="position:sticky; top:0; background:#f8fafc; z-index:10; box-shadow:0 1px 2px rgba(0,0,0,0.05);">
                            <tr>
                                <th style="width:40px; text-align:center;">Pilih</th>
                                <th>Informasi Guru (Sesuai CSV)</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach($uploaded_data as $index => $guru): ?>
                            <tr>
                                <td style="text-align:center;">
                                    <input type="checkbox" name="selected_gurus[]" value="<?= $index ?>" class="checkbox-large" id="check_<?= $index ?>">
                                    <input type="hidden" name="nama[<?= $index ?>]" value="<?= htmlspecialchars($guru['nama']) ?>">
                                    <input type="hidden" name="username[<?= $index ?>]" value="<?= htmlspecialchars($guru['username']) ?>">
                                </td>
                                <td>
                                    <label for="check_<?= $index ?>" style="display:block; cursor:pointer;">
                                        <div style="font-weight:700; color:var(--primary); font-size:14px;"><?= htmlspecialchars($guru['nama']) ?></div>
                                        <div style="font-size:12px; color:var(--text-muted); display:flex; align-items:center; gap:4px; margin-top:2px;">
                                            <i data-lucide="at-sign" style="width:12px;"></i> <?= htmlspecialchars($guru['username']) ?> 
                                        </div>
                                    </label>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
                
                <input type="hidden" name="action" value="save_selected">
                <button type="submit" class="btn btn-primary" style="background:var(--success);">
                    <i data-lucide="save"></i> Simpan Pilihan ke Database
                </button>
            </form>
            <?php endif; ?>
            
        </main>
        
        <?php 
            $active_page = 'users';
            include '../includes/bottom_nav.php'; 
        ?>
    </div>
    <script>
        lucide.createIcons();
    </script>
</body>
</html>
