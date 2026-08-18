<?php
require_once 'includes/config.php';
checkLogin();

$user_id = $_SESSION['user_id'];
$msg = '';

if($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['action'])) {
    csrf_check();
    
    if($_POST['action'] == 'update_password') {
        $old_pass = $_POST['old_password'];
        $new_pass = $_POST['new_password'];
        
        $stmt = $conn->prepare("SELECT password FROM users WHERE id = ?");
        $stmt->bind_param("i", $user_id);
        $stmt->execute();
        $row = $stmt->get_result()->fetch_assoc();
        
        if($row && password_verify($old_pass, $row['password'])) {
            if(strlen($new_pass) >= 6) {
                $hash = password_hash($new_pass, PASSWORD_BCRYPT);
                $upd = $conn->prepare("UPDATE users SET password = ? WHERE id = ?");
                $upd->bind_param("si", $hash, $user_id);
                if($upd->execute()) {
                    $msg = '<div class="notification-banner success"><i data-lucide="check-circle"></i> <span>Password berhasil diubah!</span></div>';
                }
            } else {
                $msg = '<div class="notification-banner danger"><i data-lucide="alert-triangle"></i> <span>Password baru minimal 6 karakter.</span></div>';
            }
        } else {
            $msg = '<div class="notification-banner danger"><i data-lucide="x-circle"></i> <span>Password lama tidak sesuai!</span></div>';
        }
    }
    
    if($_POST['action'] == 'update_foto') {
        if(isset($_FILES['foto']) && $_FILES['foto']['error'] == 0) {
            $ext = pathinfo($_FILES['foto']['name'], PATHINFO_EXTENSION);
            $allowed = ['jpg', 'jpeg', 'png'];
            if(in_array(strtolower($ext), $allowed)) {
                $foto_name = "profil_{$user_id}_" . time() . ".$ext";
                if(move_uploaded_file($_FILES['foto']['tmp_name'], "uploads/profil/" . $foto_name)) {
                    $upd = $conn->prepare("UPDATE users SET foto = ? WHERE id = ?");
                    $upd->bind_param("si", $foto_name, $user_id);
                    $upd->execute();
                    $msg = '<div class="notification-banner success"><i data-lucide="check-circle"></i> <span>Foto profil diperbarui!</span></div>';
                }
            } else {
                $msg = '<div class="notification-banner danger"><i data-lucide="alert-triangle"></i> <span>Hanya file JPG/PNG yang diizinkan.</span></div>';
            }
        }
    }
}

// Fetch Data
$stmt = $conn->prepare("SELECT * FROM users WHERE id = ?");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$user = $stmt->get_result()->fetch_assoc();
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
    <title>Profil Saya - SIPKL</title>
    <link rel="icon" type="image/svg+xml" href="assets/img/favicon.svg">
    <link rel="stylesheet" href="assets/css/style_v2.css?v=<?= filemtime('assets/css/style_v2.css') ?>">
    <script src="https://unpkg.com/lucide@latest"></script>
    <style>
        .profile-header {
            text-align: center;
            padding: 30px 20px;
            background: linear-gradient(135deg, var(--primary), var(--primary-light));
            margin: -20px -20px 20px -20px;
            border-radius: 0 0 24px 24px;
            color: white;
            box-shadow: 0 10px 30px rgba(15, 23, 42, 0.1);
        }
        .profile-avatar-wrap {
            position: relative;
            width: 90px;
            height: 90px;
            margin: 0 auto 15px;
        }
        .profile-avatar {
            width: 100%;
            height: 100%;
            object-fit: cover;
            border: 4px solid rgba(255,255,255,0.2);
            background: white;
        }
        .profile-avatar.no-border-radius {
            border-radius: 0 !important;
        }
        .cam-btn {
            position: absolute;
            bottom: -5px;
            right: -5px;
            background: var(--accent);
            color: white;
            border: none;
            width: 34px;
            height: 34px;
            display: flex;
            align-items: center;
            justify-content: center;
            box-shadow: 0 4px 10px rgba(0,0,0,0.2);
            cursor: pointer;
        }
    </style>
</head>
<body>
    <div id="page-loader"><div class="loader-spinner"></div></div>
    <div class="app-container">
        <header class="app-header" style="background:transparent; border-bottom:none; color:white; position:absolute; width:100%;">
            <div style="display:flex; align-items:center; gap:15px;">
                <a href="index.php" class="icon-btn" style="color:white;"><i data-lucide="arrow-left"></i></a>
                <h1 style="color:white;">Profil Pengguna</h1>
            </div>
        </header>
        
        <main class="main-content" style="padding-top:0;">
            <div class="profile-header">
                <div style="height:60px;"></div> <!-- Spacer for absolute header -->
                <div class="profile-avatar-wrap">
                    <?php if($user['foto']): ?>
                        <img src="uploads/profil/<?= $user['foto'] ?>" class="profile-avatar no-border-radius">
                    <?php else: ?>
                        <div class="profile-avatar no-border-radius" style="display:flex; align-items:center; justify-content:center; color:var(--text-muted);">
                            <i data-lucide="user" style="width:40px; height:40px;"></i>
                        </div>
                    <?php endif; ?>
                    <button type="button" class="cam-btn no-border-radius" onclick="document.getElementById('foto_upload').click()"><i data-lucide="camera" style="width:16px;"></i></button>
                    
                    <form method="POST" enctype="multipart/form-data" id="formFoto" style="display:none;">
                        <input type="hidden" name="action" value="update_foto">
                        <?= csrf_field() ?>
                        <input type="file" name="foto" id="foto_upload" accept="image/jpeg, image/png" onchange="document.getElementById('formFoto').submit()">
                    </form>
                </div>
                <h2 style="font-size:20px; font-weight:700; margin-bottom:4px;"><?= htmlspecialchars($user['name']) ?></h2>
                <div style="font-size:13px; opacity:0.8; text-transform:uppercase; letter-spacing:1px;"><?= str_replace('_', ' ', $user['role']) ?></div>
            </div>
            
            <?= $msg ?>
            
            <div class="card" style="border-left: 4px solid var(--accent);">
                <div class="card-title"><i data-lucide="info"></i> Informasi Akun</div>
                <div style="margin-bottom:12px;">
                    <div style="font-size:11px; color:var(--text-muted); font-weight:700; text-transform:uppercase;">Username</div>
                    <div style="font-size:15px; font-weight:600;"><?= htmlspecialchars($user['username']) ?></div>
                </div>
                <?php if($user['jurusan']): ?>
                <div style="margin-bottom:12px;">
                    <div style="font-size:11px; color:var(--text-muted); font-weight:700; text-transform:uppercase;">Jurusan</div>
                    <div style="font-size:15px; font-weight:600;"><?= htmlspecialchars($user['jurusan']) ?></div>
                </div>
                <?php endif; ?>
            </div>
            
            <div class="card" style="border-left: 4px solid var(--warning);">
                <div class="card-title"><i data-lucide="key"></i> Ganti Password</div>
                <form method="POST">
                    <input type="hidden" name="action" value="update_password">
                    <?= csrf_field() ?>
                    <div class="form-group form-floating">
                        <input type="password" name="old_password" class="form-control" id="old_pass" placeholder=" " required>
                        <label for="old_pass">Password Lama</label>
                    </div>
                    <div class="form-group form-floating">
                        <input type="password" name="new_password" class="form-control" id="new_pass" placeholder=" " required minlength="6">
                        <label for="new_pass">Password Baru</label>
                    </div>
                    <button type="submit" class="btn btn-primary" style="width:100%;">Perbarui Password</button>
                </form>
            </div>
            
            <a href="index.php?logout=1" class="btn" style="background:#fee2e2; color:var(--danger); width:100%; text-align:center; padding:15px; text-decoration:none; margin-top:20px; font-weight:700;">
                <i data-lucide="log-out"></i> Keluar (Log Out)
            </a>
            
        </main>
        
        <?php 
            $active_page = 'profil';
            include 'includes/bottom_nav.php'; 
        ?>
    </div>
    <script>
        lucide.createIcons();
    </script>
</body>
</html>
