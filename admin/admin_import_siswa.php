<?php
require_once '../includes/config.php';
checkLogin();

if(getRole() !== 'admin') {
    die("Akses ditolak. Halaman ini hanya untuk Admin Pokja PKL.");
}

$msg = '';
$uploaded_data = [];
$selected_jurusan = '';

// 1. Tangani Upload CSV
if($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['action']) && $_POST['action'] == 'upload_csv') {
    $selected_jurusan = $_POST['jurusan'] ?? '';
    
    if(empty($selected_jurusan)) {
        $msg = '<div class="notification-banner warning"><i data-lucide="alert-triangle"></i> <span>Anda harus memilih Jurusan terlebih dahulu!</span></div>';
    } else if(isset($_FILES['csv_file']) && $_FILES['csv_file']['error'] == 0) {
        $file = $_FILES['csv_file']['tmp_name'];
        if(($handle = fopen($file, "r")) !== FALSE) {
            $header = fgetcsv($handle, 1000, ",", "\"", "\\"); // PHP 8.4 support
            while (($data = fgetcsv($handle, 1000, ",", "\"", "\\")) !== FALSE) {
                // Kolom 0 = Nama Lengkap, Kolom 1 = NISN/Username
                if(isset($data[0]) && isset($data[1])) {
                    $nama = trim($data[0]);
                    $username = trim($data[1]);
                    
                    if(!empty($nama) && !empty($username) && strcasecmp($nama, 'NaT') !== 0 && strcasecmp($nama, 'NaN') !== 0) {
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

// 2. Tangani Penyimpanan Siswa yang Dicentang
if($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['action']) && $_POST['action'] == 'save_selected') {
    $jurusan_simpan = $_POST['jurusan_simpan'] ?? '';
    if(empty($jurusan_simpan)) {
        $msg = '<div class="notification-banner danger"><i data-lucide="alert-circle"></i> <span>Gagal: Jurusan hilang dari sistem. Ulangi proses.</span></div>';
    } else if(isset($_POST['selected_siswa']) && is_array($_POST['selected_siswa'])) {
        $sukses = 0;
        $gagal = 0;
        if($conn) {
            // Kita ikat dengan Tahun Ajaran Aktif secara otomatis!
            $stmt = $conn->prepare("INSERT IGNORE INTO users (username, password, name, role, jurusan, tahun_ajaran) VALUES (?, ?, ?, 'siswa', ?, ?)");
            foreach($_POST['selected_siswa'] as $index) {
                $nama = $_POST['nama'][$index] ?? '';
                $username = $_POST['username'][$index] ?? '';
                if($nama && $username) {
                    $password = password_hash($username, PASSWORD_BCRYPT); // Default: NISN sebagai password
                    $stmt->bind_param("sssss", $username, $password, $nama, $jurusan_simpan, $TAHUN_AJARAN_AKTIF);
                    if($stmt->execute() && $stmt->affected_rows > 0) {
                        $sukses++;
                    } else {
                        $gagal++;
                    }
                }
            }
            $msg = '<div class="notification-banner success"><i data-lucide="check-circle"></i> <span>Berhasil mengimpor '.$sukses.' siswa ke jurusan '.$jurusan_simpan.' (TA '.$TAHUN_AJARAN_AKTIF.'). Dilewati: '.$gagal.'.</span></div>';
        }
    } else {
        $msg = '<div class="notification-banner warning"><i data-lucide="alert-triangle"></i> <span>Tidak ada siswa yang dipilih.</span></div>';
    }
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
    <title>Import CSV Siswa - Admin SIPKL</title>
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
                <h1>Import Data Siswa</h1>
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
                    <span>Unggah file CSV absen kelas yang berisi 2 baris kolom utama: <b>Nama Siswa</b> dan <b>NISN</b>. Siswa akan otomatis terikat pada Tahun Ajaran Aktif (<?= htmlspecialchars($TAHUN_AJARAN_AKTIF) ?>).</span>
                    <div style="margin-top:8px;">
                        <a href="../templates/template_siswa.csv" download class="badge bg-primary" style="text-decoration:none; display:inline-flex; align-items:center; gap:4px; font-weight:600;"><i data-lucide="download" style="width:12px;"></i> Download Template CSV</a>
                        <a href="../templates/template_siswa.xlsx" download class="badge bg-success" style="text-decoration:none; display:inline-flex; align-items:center; gap:4px; font-weight:600;"><i data-lucide="table" style="width:12px;"></i> Download Template Excel</a>
                    </div>
                </div>
            </div>
            
            <form method="POST" enctype="multipart/form-data" class="card" style="border-left: 4px solid var(--primary);">
                <div class="card-title"><i data-lucide="upload"></i> Upload CSV Siswa</div>
                
                <div class="form-group">
                    <label class="form-label" style="font-size:12px; font-weight:600;">Jurusan / Kelas Tujuan</label>
                    <select name="jurusan" class="form-control-standard" required style="padding:12px; font-size:14px;">
                        <option value="">-- Pilih Jurusan --</option>
                        <option value="PPLG">PPLG (Pengembangan Perangkat Lunak)</option>
                        <option value="TJKT">TJKT (Teknik Jaringan Komputer)</option>
                        <option value="Busana">Busana (Tata Busana)</option>
                    </select>
                </div>
                
                <div class="form-group" style="margin-top:15px;">
                    <div style="position:relative; width:100%; border:2px dashed #94a3b8; border-radius:12px; padding:30px 20px; text-align:center; background:#f8fafc; transition:all 0.3s ease;" onmouseover="this.style.borderColor='var(--primary)'; this.style.background='#eff6ff'" onmouseout="this.style.borderColor='#94a3b8'; this.style.background='#f8fafc'">
                        <input type="file" name="csv_file" id="csv_file" accept=".csv" required style="position:absolute; top:0; left:0; width:100%; height:100%; opacity:0; cursor:pointer;" onchange="document.getElementById('file_name_display').innerText = this.files[0].name; document.getElementById('file_name_display').style.color = 'var(--primary)'; document.getElementById('file_name_display').style.fontWeight = 'bold';">
                        <i data-lucide="upload-cloud" style="width:48px; height:48px; color:var(--primary); margin-bottom:12px; display:inline-block;"></i>
                        <div style="font-weight:600; color:var(--text-main); font-size:14px; margin-bottom:4px;">Pilih File CSV Absen</div>
                        <div id="file_name_display" style="font-size:12px; color:var(--text-muted);">Ketuk area ini untuk menelusuri file</div>
                    </div>
                </div>
                <input type="hidden" name="action" value="upload_csv">
                <button type="submit" class="btn btn-primary" style="margin-top:10px;">
                    <i data-lucide="arrow-right"></i> Pratinjau Data Siswa
                </button>
            </form>
            <?php else: ?>
            <!-- Tahap 2: Filter Checkbox -->
            <form method="POST" class="card" style="border-left: 4px solid var(--success);">
                <div class="card-title" style="display:flex; justify-content:space-between; align-items:center;">
                    <span><i data-lucide="check-square"></i> Verifikasi Siswa <?= htmlspecialchars($selected_jurusan) ?></span>
                    <span class="badge bg-primary"><?= count($uploaded_data) ?> Data</span>
                </div>
                
                <p style="font-size:12px; color:var(--text-muted); margin-bottom:15px;">Semua siswa dicentang secara default. Hilangkan centang jika ada siswa yang pindah/berhenti. Sandi default: <b>siswa123</b>.</p>
                
                <div class="table-responsive" style="max-height:400px; overflow-y:auto; border:1px solid var(--border); border-radius:8px; margin-bottom:15px;">
                    <table>
                        <thead style="position:sticky; top:0; background:#f8fafc; z-index:10; box-shadow:0 1px 2px rgba(0,0,0,0.05);">
                            <tr>
                                <th style="width:40px; text-align:center;">Pilih</th>
                                <th>Informasi Siswa (Sesuai CSV)</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach($uploaded_data as $index => $s): ?>
                            <tr>
                                <td style="text-align:center;">
                                    <input type="checkbox" name="selected_siswa[]" value="<?= $index ?>" class="checkbox-large" id="check_<?= $index ?>" checked>
                                    <input type="hidden" name="nama[<?= $index ?>]" value="<?= htmlspecialchars($s['nama']) ?>">
                                    <input type="hidden" name="username[<?= $index ?>]" value="<?= htmlspecialchars($s['username']) ?>">
                                </td>
                                <td>
                                    <label for="check_<?= $index ?>" style="display:block; cursor:pointer;">
                                        <div style="font-weight:700; color:var(--primary); font-size:14px;"><?= htmlspecialchars($s['nama']) ?></div>
                                        <div style="font-size:12px; color:var(--text-muted); display:flex; align-items:center; gap:4px; margin-top:2px;">
                                            <i data-lucide="credit-card" style="width:12px;"></i> NISN: <?= htmlspecialchars($s['username']) ?> 
                                        </div>
                                    </label>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
                
                <input type="hidden" name="action" value="save_selected">
                <input type="hidden" name="jurusan_simpan" value="<?= htmlspecialchars($selected_jurusan) ?>">
                <button type="submit" class="btn btn-primary" style="background:var(--success);">
                    <i data-lucide="save"></i> Impor Siswa ke Database
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
