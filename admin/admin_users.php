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
        $role = $_POST['role_user'];
        $jurusan = $_POST['jurusan'] !== '' ? $_POST['jurusan'] : null;
        $alamat = trim($_POST['alamat'] ?? '');
        $kontak = trim($_POST['kontak'] ?? '');
        
        $foto_name = null;
        if(isset($_FILES['foto']) && $_FILES['foto']['error'] == 0) {
            $ext = pathinfo($_FILES['foto']['name'], PATHINFO_EXTENSION);
            $foto_name = "profil_".time()."_".rand(1000,9999).".".$ext;
            if(!is_dir("../uploads/profil")) mkdir("../uploads/profil", 0777, true);
            move_uploaded_file($_FILES['foto']['tmp_name'], "../uploads/profil/" . $foto_name);
        }

        if($conn) {
            $ta = ($role == 'siswa') ? $TAHUN_AJARAN_AKTIF : null;
            $hashed = password_hash($password, PASSWORD_BCRYPT);
            $stmt = $conn->prepare("INSERT INTO users (username, password, name, role, jurusan, tahun_ajaran, alamat, kontak, foto) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)");
            $stmt->bind_param("sssssssss", $username, $hashed, $name, $role, $jurusan, $ta, $alamat, $kontak, $foto_name);
            if($stmt->execute()) {
                $msg = '<div class="notification-banner success"><i data-lucide="check-circle"></i> <span>User berhasil ditambahkan!</span></div>';
            } else {
                $msg = '<div class="notification-banner danger"><i data-lucide="alert-circle"></i> <span>Gagal. Username mungkin sudah ada.</span></div>';
            }
        } else {
            $msg = '<div class="notification-banner warning"><i data-lucide="alert-triangle"></i> <span>DB tidak terhubung.</span></div>';
        }
    } elseif($_POST['action'] == 'delete' && isset($_POST['delete_id'])) {
        $id = (int)$_POST['delete_id'];
        if($conn && $id != $_SESSION['user_id']) {
            $stmt = $conn->prepare("DELETE FROM users WHERE id = ?");
            $stmt->bind_param("i", $id);
            $stmt->execute();
        }
        header("Location: admin_users.php");
        exit;
    }
}

$users = [];
if($conn) {
    $res = $conn->query("SELECT * FROM users WHERE role != 'pembimbing_dudika' ORDER BY role, name");
    while($row = $res->fetch_assoc()) $users[] = $row;
} else {
    $users = [
        ['id' => 1, 'username' => 'admin', 'name' => 'Pak Admin Pokja', 'role' => 'admin', 'jurusan' => '-'],
        ['id' => 2, 'username' => 'guru', 'name' => 'Bu Guru Pembimbing', 'role' => 'pembimbing_sekolah', 'jurusan' => 'Busana'],
        ['id' => 3, 'username' => 'dudi', 'name' => 'Bapak Pemilik Toko', 'role' => 'pembimbing_dudika', 'jurusan' => 'PPLG'],
        ['id' => 4, 'username' => 'siswa', 'name' => 'Ahmad Siswa PPLG', 'role' => 'siswa', 'jurusan' => 'PPLG'],
    ];
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
    <title>Kelola User - Admin SIPKL</title>
    <link rel="icon" type="image/svg+xml" href="../assets/img/favicon.svg">
    <link rel="stylesheet" href="../assets/css/style.css?v=<?= filemtime('../assets/css/style.css') ?>">
    <script src="https://unpkg.com/lucide@latest"></script>
    <style>
        .table-responsive { overflow-x: auto; margin: 0 -20px; padding: 0 20px; }
        table { width: 100%; border-collapse: collapse; font-size: 13px; }
        th, td { padding: 14px 10px; border-bottom: 1px solid var(--border); text-align: left; }
        th { color: var(--text-muted); font-weight: 700; text-transform: uppercase; font-size:11px; letter-spacing:0.5px; }
        .user-row:last-child td { border-bottom: none; }
    </style>
</head>
<body>
    <div id="page-loader"><div class="loader-spinner"></div></div>
    <div class="app-container">
        <header class="app-header">
            <div style="display:flex; align-items:center; gap:15px;">
                <a href="../index.php" class="icon-btn"><i data-lucide="arrow-left"></i></a>
                <h1>Kelola Pengguna</h1>
            </div>
            <i data-lucide="users" style="color:var(--primary);"></i>
        </header>
        
        <main class="main-content">
            <?= $msg ?>
            
            <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:15px; flex-wrap:wrap; gap:10px;">
                <button onclick="openModal('modalAddUser')" class="btn btn-primary" style="width:auto; padding:10px 16px; font-size:14px; border-radius:12px;"><i data-lucide="user-plus" style="width:18px;"></i> Tambah User</button>
                <div style="display:flex; gap:8px;">
                    <a href="admin_import_siswa.php" class="btn" style="width:auto; padding:8px 12px; background:var(--primary); color:white; font-size:12px;"><i data-lucide="users" style="width:14px;"></i> Import Siswa</a>
                    <a href="admin_import_guru.php" class="btn" style="width:auto; padding:8px 12px; background:var(--success); color:white; font-size:12px;"><i data-lucide="graduation-cap" style="width:14px;"></i> Import Guru</a>
                </div>
            </div>
            
            <!-- MODAL TAMBAH USER -->
            <div class="modal-overlay" id="modalAddUser">
                <div class="modal-content">
                    <div class="modal-header">
                        <h3><i data-lucide="user-plus" style="vertical-align:middle; margin-right:8px; width:20px;"></i> Tambah User Baru</h3>
                        <button type="button" class="modal-close" onclick="closeModal('modalAddUser')"><i data-lucide="x"></i></button>
                    </div>
                    <form method="POST" enctype="multipart/form-data">
                        <input type="hidden" name="action" value="add">
                        <?= csrf_field() ?>
                        <div class="form-group form-floating">
                            <input type="text" name="name" class="form-control" id="name" placeholder=" " required>
                            <label for="name">Nama Lengkap</label>
                        </div>
                        <div class="form-group form-floating">
                            <input type="text" name="username" class="form-control" id="username" placeholder=" " required>
                            <label for="username">Username (NISN/NIP)</label>
                        </div>
                        <div class="form-group form-floating">
                            <input type="password" name="password" class="form-control" id="password" placeholder=" " required>
                            <label for="password">Password</label>
                        </div>
                        
                        <div class="grid-2">
                            <div class="form-group" style="margin-bottom:0;">
                                <label class="form-label">Role Akses</label>
                                <select name="role_user" class="form-control-standard" required>
                                    <option value="siswa">Siswa</option>
                                    <option value="pembimbing_sekolah">Guru Pembimbing</option>
                                    <option value="admin">Admin Pokja</option>
                                </select>
                            </div>
                            <div class="form-group" style="margin-bottom:0;">
                                <label class="form-label">Jurusan</label>
                                <select name="jurusan" class="form-control-standard">
                                    <option value="">-- Kosong --</option>
                                    <option value="PPLG">PPLG</option>
                                    <option value="TJKT">TJKT</option>
                                    <option value="Busana">Busana</option>
                                </select>
                            </div>
                        </div>
                        
                        <div class="grid-2" style="margin-top:15px;">
                            <div class="form-group form-floating" style="margin-bottom:0;">
                                <input type="text" name="alamat" class="form-control" id="alamat_user" placeholder=" ">
                                <label for="alamat_user">Alamat (Opsional)</label>
                            </div>
                            <div class="form-group form-floating" style="margin-bottom:0;">
                                <input type="text" name="kontak" class="form-control" id="kontak_user" placeholder=" ">
                                <label for="kontak_user">Kontak / HP (Opsional)</label>
                            </div>
                        </div>
                        
                        <div class="form-group" style="margin-top:15px;">
                            <label class="form-label">Foto Profil (Siswa/Guru)</label>
                            <input type="file" name="foto" accept="image/*" class="form-control-standard">
                        </div>
                        
                        <button type="submit" class="btn btn-primary" style="margin-top:20px;">
                            <i data-lucide="save"></i> Simpan User
                        </button>
                    </form>
                </div>
            </div>
            
            <div class="card">
                <div class="card-title"><i data-lucide="database"></i> Daftar Pengguna</div>
                <div class="table-responsive">
                    <table>
                        <thead>
                            <tr>
                                <th>Nama Lengkap & Kontak</th>
                                <th>Role & Jurusan</th>
                                <th style="text-align:right;">Aksi</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach($users as $u): ?>
                            <tr class="user-row">
                                <td>
                                    <div style="display:flex; gap:12px; align-items:center;">
                                        <?php if(!empty($u['foto'])): ?>
                                            <img src="../uploads/profil/<?= $u['foto'] ?>" style="width:40px; height:40px; border-radius:50%; object-fit:cover; border:2px solid var(--primary-light);">
                                        <?php else: ?>
                                            <div style="width:40px; height:40px; border-radius:50%; background:var(--bg-color); color:var(--text-muted); display:flex; align-items:center; justify-content:center; border:2px solid var(--border);">
                                                <i data-lucide="user" style="width:20px; height:20px;"></i>
                                            </div>
                                        <?php endif; ?>
                                        <div>
                                            <div style="font-weight:600;"><?= htmlspecialchars($u['name']) ?></div>
                                            <div style="font-size:12px; color:var(--text-muted);"><?= htmlspecialchars($u['username']) ?></div>
                                            <?php if(!empty($u['kontak'])): ?>
                                                <div style="font-size:11px; margin-top:2px; color:var(--text-main);"><i data-lucide="phone" style="width:10px; height:10px;"></i> <?= htmlspecialchars($u['kontak']) ?></div>
                                            <?php endif; ?>
                                        </div>
                                    </div>
                                </td>
                                <td>
                                    <?php
                                        $roles = ['admin' => 'Admin Pokja', 'siswa' => 'Siswa', 'pembimbing_sekolah' => 'Guru', 'pembimbing_dudika' => 'DUDI'];
                                        $r_label = $roles[$u['role']] ?? $u['role'];
                                        $r_color = ['admin'=>'var(--danger)', 'siswa'=>'var(--success)', 'pembimbing_sekolah'=>'var(--primary)', 'pembimbing_dudika'=>'var(--warning)'][$u['role']] ?? 'var(--border)';
                                    ?>
                                    <span class="badge" style="background:<?= $r_color ?>; color:white;"><?= $r_label ?></span>
                                    <span class="badge bg-border"><?= $u['jurusan'] ?? '-' ?></span>
                                </td>
                                <td style="text-align:right;">
                                    <?php if($u['id'] != $_SESSION['user_id']): ?>
                                    <form method="POST" style="display:inline;">
                                        <input type="hidden" name="action" value="delete">
                                        <input type="hidden" name="delete_id" value="<?= $u['id'] ?>">
                                        <?= csrf_field() ?>
                                        <button type="submit" onclick="return confirm('Hapus user <?= addslashes($u['name']) ?>?')" style="background:#fee2e2; border:none; color:var(--danger); padding:8px; border-radius:8px; cursor:pointer;">
                                            <i data-lucide="trash-2" style="width:16px; height:16px;"></i>
                                        </button>
                                    </form>
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
            $active_page = 'users';
            include '../includes/bottom_nav.php'; 
        ?>
    </div>
    <script>
        lucide.createIcons();
        
        function openModal(id) {
            document.getElementById(id).classList.add('active');
            document.body.style.overflow = 'hidden'; // prevent scroll
        }
        
        function closeModal(id) {
            document.getElementById(id).classList.remove('active');
            document.body.style.overflow = 'auto';
        }
    </script>
</body>
</html>
