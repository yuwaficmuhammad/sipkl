<?php
require_once '../includes/config.php';
checkLogin();

if(getRole() !== 'admin') {
    die("Akses ditolak. Halaman ini hanya untuk Admin Pokja PKL.");
}

$msg = '';

if($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['action'])) {
    csrf_check();
    if($_POST['action'] == 'add') {
        $username = trim($_POST['username']);
        $password = trim($_POST['password']);
        $name = trim($_POST['name']);
        $jurusan = $_POST['jurusan'] !== '' ? $_POST['jurusan'] : null;
        $alamat = trim($_POST['alamat'] ?? '');
        $kontak = trim($_POST['kontak'] ?? '');
        $pimpinan = trim($_POST['dudi_nama_pimpinan'] ?? '');
        $instruktur = trim($_POST['dudi_nama_instruktur'] ?? '');
        $no_instruktur = trim($_POST['dudi_nomor_instruktur'] ?? '');
        $latlong = trim($_POST['dudi_latlong'] ?? '');

        if($conn) {
            $hashed = password_hash($password, PASSWORD_BCRYPT);
            $stmt = $conn->prepare("INSERT INTO users (username, password, name, role, jurusan, alamat, kontak, dudi_nama_pimpinan, dudi_nama_instruktur, dudi_nomor_instruktur, dudi_latlong) VALUES (?, ?, ?, 'pembimbing_dudika', ?, ?, ?, ?, ?, ?, ?)");
            $stmt->bind_param("ssssssssss", $username, $hashed, $name, $jurusan, $alamat, $kontak, $pimpinan, $instruktur, $no_instruktur, $latlong);
            if($stmt->execute()) {
                $msg = '<div class="notification-banner success"><i data-lucide="check-circle"></i> <span>Mitra DUDI berhasil ditambahkan!</span></div>';
            } else {
                $msg = '<div class="notification-banner danger"><i data-lucide="alert-circle"></i> <span>Gagal. Username/NIB mungkin sudah ada.</span></div>';
            }
        }
    } elseif($_POST['action'] == 'delete' && isset($_POST['delete_id'])) {
        $id = (int)$_POST['delete_id'];
        if($conn) {
            // Hapus penempatan terkait DUDI ini
            $stmt_map = $conn->prepare("DELETE FROM penempatan_dudi WHERE id_dudika = ?");
            $stmt_map->bind_param("i", $id);
            $stmt_map->execute();

            $stmt = $conn->prepare("DELETE FROM users WHERE id = ? AND role = 'pembimbing_dudika'");
            $stmt->bind_param("i", $id);
            $stmt->execute();
        }
        header("Location: admin_dudi.php");
        exit;
    }
}

$dudis = [];
if($conn) {
    $res = $conn->query("SELECT * FROM users WHERE role = 'pembimbing_dudika' ORDER BY name");
    while($row = $res->fetch_assoc()) $dudis[] = $row;
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
    <title>Kelola Mitra DUDI - Admin SIPKL</title>
    <link rel="icon" type="image/svg+xml" href="../assets/img/favicon.svg">
    <link rel="stylesheet" href="../assets/css/style.css?v=<?= filemtime('../assets/css/style.css') ?>">
    <script src="https://unpkg.com/lucide@latest"></script>
    <style>
        .table-responsive { overflow-x: auto; margin: 0 -20px; padding: 0 20px; }
        .data-table { width: 100%; border-collapse: collapse; min-width: 600px; }
        .data-table th, .data-table td { padding: 12px; text-align: left; border-bottom: 1px solid var(--border); font-size: 14px; }
        .data-table th { background: var(--bg-color); color: var(--text-muted); font-weight: 600; }
        .data-table tr:hover { background: #f8fafc; }
        .form-floating { position: relative; margin-bottom: 15px; }
        .form-floating input { padding: 12px 15px; padding-top: 24px; width: 100%; }
        .form-floating label { position: absolute; left: 15px; top: 18px; font-size: 14px; color: var(--text-muted); transition: all 0.2s; pointer-events: none; }
        .form-floating input:focus + label, .form-floating input:not(:placeholder-shown) + label { top: 6px; font-size: 11px; color: var(--primary); font-weight: 600; }
        .form-control-standard { width: 100%; padding: 12px; border: 1px solid var(--border); border-radius: 8px; font-size: 14px; outline: none; transition: border-color 0.2s; }
        .form-control-standard:focus { border-color: var(--primary); box-shadow: 0 0 0 3px rgba(14,165,233,0.1); }
        .form-label { font-size: 12px; font-weight: 600; color: var(--text-muted); margin-bottom: 4px; display: block; }
    </style>
</head>
<body>
    <div id="page-loader"><div class="loader-spinner"></div></div>
    <div class="app-container">
        <header class="app-header">
            <div style="display:flex; align-items:center; gap:15px;">
                <a href="../index.php" class="icon-btn"><i data-lucide="arrow-left"></i></a>
                <h1>Mitra DUDI</h1>
            </div>
            <i data-lucide="briefcase" style="color:var(--primary);"></i>
        </header>

        <main class="main-content">
            <?= $msg ?>
            
            <div style="margin-bottom:15px;">
                <button onclick="openModal('modalAddDUDI')" class="btn btn-primary" style="width:auto; padding:10px 16px; font-size:14px; border-radius:12px;"><i data-lucide="plus-circle" style="width:18px;"></i> Tambah Mitra DUDI</button>
            </div>
            
            <!-- MODAL TAMBAH DUDI -->
            <div class="modal-overlay" id="modalAddDUDI">
                <div class="modal-content">
                    <div class="modal-header">
                        <h3><i data-lucide="plus-circle" style="vertical-align:middle; margin-right:8px; width:20px;"></i> Tambah Mitra DUDI</h3>
                        <button type="button" class="modal-close" onclick="closeModal('modalAddDUDI')"><i data-lucide="x"></i></button>
                    </div>
                    <form method="POST">
                    <input type="hidden" name="action" value="add">
                    <?= csrf_field() ?>
                    <div class="form-group form-floating">
                        <input type="text" name="name" class="form-control" id="name" placeholder=" " required>
                        <label for="name">Nama Perusahaan / Bengkel</label>
                    </div>
                    <div class="form-group form-floating">
                        <input type="text" name="username" class="form-control" id="username" placeholder=" " required>
                        <label for="username">Username (NIB/Kode DUDI)</label>
                    </div>
                    <div class="form-group form-floating">
                        <input type="password" name="password" class="form-control" id="password" placeholder=" " required>
                        <label for="password">Password Default</label>
                    </div>
                    <div class="form-group" style="margin-bottom:0;">
                        <label class="form-label">Kategori Jurusan Utama</label>
                        <select name="jurusan" class="form-control-standard">
                            <option value="">-- Semua / Umum --</option>
                            <option value="PPLG">PPLG</option>
                            <option value="TJKT">TJKT</option>
                            <option value="Busana">Busana</option>
                        </select>
                    </div>
                    <div class="form-group form-floating" style="margin-top:15px;">
                        <input type="text" name="alamat" class="form-control" id="alamat" placeholder=" ">
                        <label for="alamat">Alamat Lengkap (Opsional)</label>
                    </div>
                    <div class="form-group form-floating">
                        <input type="text" name="kontak" class="form-control" id="kontak" placeholder=" ">
                        <label for="kontak">Kontak/Telepon (Opsional)</label>
                    </div>
                    
                    <div class="grid-2" style="display:grid; grid-template-columns:1fr 1fr; gap:15px;">
                        <div class="form-group form-floating" style="margin-bottom:0;">
                            <input type="text" name="dudi_nama_pimpinan" class="form-control" id="dudi_nama_pimpinan" placeholder=" ">
                            <label for="dudi_nama_pimpinan">Nama Pimpinan (Opsional)</label>
                        </div>
                        <div class="form-group form-floating" style="margin-bottom:0;">
                            <input type="text" name="dudi_latlong" class="form-control" id="dudi_latlong" placeholder=" ">
                            <label for="dudi_latlong">Koordinat Lat,Long (Opsional)</label>
                        </div>
                    </div>
                    <div class="grid-2" style="display:grid; grid-template-columns:1fr 1fr; gap:15px; margin-top:15px;">
                        <div class="form-group form-floating" style="margin-bottom:0;">
                            <input type="text" name="dudi_nama_instruktur" class="form-control" id="dudi_nama_instruktur" placeholder=" ">
                            <label for="dudi_nama_instruktur">Nama Instruktur DUDI</label>
                        </div>
                        <div class="form-group form-floating" style="margin-bottom:0;">
                            <input type="text" name="dudi_nomor_instruktur" class="form-control" id="dudi_nomor_instruktur" placeholder=" ">
                            <label for="dudi_nomor_instruktur">No. HP Instruktur</label>
                        </div>
                    </div>
                    <button type="submit" class="btn btn-primary" style="margin-top:20px;">
                        <i data-lucide="save"></i> Simpan DUDI
                    </button>
                    </form>
                </div>
            </div>

            <!-- Tabel DUDI -->
            <div class="card">
                <div class="card-title"><i data-lucide="list"></i> Daftar Mitra DUDI</div>
                <div class="table-responsive">
                    <table class="data-table">
                        <thead>
                            <tr>
                                <th>Perusahaan</th>
                                <th>Instruktur</th>
                                <th>Kontak</th>
                                <th style="text-align:right;">Aksi</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if(empty($dudis)): ?>
                            <tr><td colspan="4" style="text-align:center; padding:20px; color:var(--text-muted);">Belum ada data DUDI.</td></tr>
                            <?php else: foreach($dudis as $d): ?>
                            <tr>
                                <td>
                                    <div style="font-weight:600;"><?= htmlspecialchars($d['name']) ?></div>
                                    <div style="font-size:12px; color:var(--text-muted);"><?= htmlspecialchars($d['alamat'] ?? '-') ?></div>
                                </td>
                                <td>
                                    <div><?= htmlspecialchars($d['dudi_nama_instruktur'] ?? '-') ?></div>
                                    <div style="font-size:12px; color:var(--text-muted);"><?= htmlspecialchars($d['dudi_nomor_instruktur'] ?? '-') ?></div>
                                </td>
                                <td>
                                    <div><?= htmlspecialchars($d['kontak'] ?? '-') ?></div>
                                    <div style="font-size:11px; margin-top:4px;"><span class="badge bg-primary"><?= $d['jurusan'] ?? 'Umum' ?></span></div>
                                </td>
                                <td style="text-align:right;">
                                    <form method="POST" style="display:inline;" onsubmit="return confirm('Hapus DUDI ini beserta data penempatannya?');">
                                        <input type="hidden" name="action" value="delete">
                                        <input type="hidden" name="delete_id" value="<?= $d['id'] ?>">
                                        <?= csrf_field() ?>
                                        <button type="submit" class="icon-btn" style="color:var(--danger); background:transparent; border:none; padding:5px; cursor:pointer;"><i data-lucide="trash-2"></i></button>
                                    </form>
                                </td>
                            </tr>
                            <?php endforeach; endif; ?>
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
