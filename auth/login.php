<?php
require_once '../includes/config.php';

if(isset($_SESSION['user_id'])) {
    header("Location: index.php");
    exit;
}

$error = '';

if($_SERVER['REQUEST_METHOD'] == 'POST') {
    $username = $_POST['username'] ?? '';
    $password = $_POST['password'] ?? '';

    if($conn) {
        $stmt = $conn->prepare("SELECT id, username, name, role, password FROM users WHERE username = ?");
        $stmt->bind_param("s", $username);
        $stmt->execute();
        $res = $stmt->get_result();
        
        if($user = $res->fetch_assoc()) {
            // Dukung password lama (plain) dan baru (bcrypt)
            $valid = password_verify($password, $user['password']) 
                     || ($user['password'] === $password); // fallback plain text
            if($valid) {
                $_SESSION['user_id'] = $user['id'];
                $_SESSION['role'] = $user['role'];
                $_SESSION['name'] = $user['name'];
                // Upgrade ke hash jika masih plain text
                if($user['password'] === $password && $conn) {
                    $new_hash = password_hash($password, PASSWORD_BCRYPT);
                    $upd = $conn->prepare("UPDATE users SET password = ? WHERE id = ?");
                    $upd->bind_param("si", $new_hash, $user['id']);
                    $upd->execute();
                }
                header("Location: ../index.php");
                exit;
            }
        }
        $error = 'Username atau Password salah!';
    } else {
        $error = 'Koneksi Database Gagal!';
    }
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
    <title>Login - SIPKL Premium</title>
    <link rel="icon" type="image/svg+xml" href="../assets/img/favicon.svg">
    <link rel="stylesheet" href="../assets/css/style.css">
    <!-- SVG Renderer yang Sangat Cepat -->
    <script src="https://unpkg.com/lucide@latest"></script>
    <style>
        .login-container {
            flex: 1;
            display: flex;
            flex-direction: column;
            justify-content: center;
            padding: 24px;
            background: linear-gradient(135deg, var(--bg-color) 0%, #e2e8f0 100%);
        }
        .logo-box {
            text-align: center;
            margin-bottom: 40px;
        }
        .logo-box .lucide {
            width: 48px;
            height: 48px;
            color: var(--accent);
            margin-bottom: 16px;
        }
        .logo-box h1 {
            color: var(--primary);
            font-size: 28px;
            margin-bottom: 8px;
            letter-spacing: -1px;
        }
        .logo-box p {
            color: var(--text-muted);
            font-size: 15px;
        }
    </style>
</head>
<body>
    <div id="page-loader"><div class="loader-spinner"></div></div>
    <div class="app-container">
        <div class="login-container">
            <div class="logo-box">
                <i data-lucide="graduation-cap"></i>
                <h1>SIPKL Salafiyah</h1>
                <p>Sistem Informasi Praktik Kerja Lapangan</p>
            </div>
            
            <div class="card" style="box-shadow: var(--shadow-md); border:none;">
                <?php if($error): ?>
                    <div class="notification-banner danger" style="margin-bottom: 20px;">
                        <i data-lucide="alert-circle"></i>
                        <span><?= htmlspecialchars($error) ?></span>
                    </div>
                <?php endif; ?>
                
                <form method="POST">
                    <div class="form-group form-floating">
                        <input type="text" name="username" class="form-control" id="username" placeholder=" " required>
                        <label for="username">NISN / NIP / Username</label>
                    </div>
                    
                    <div class="form-group form-floating">
                        <input type="password" name="password" class="form-control" id="password" placeholder=" " required>
                        <label for="password">Password</label>
                    </div>
                    
                    <button type="submit" class="btn btn-primary" style="margin-top: 10px;">
                        Masuk Sistem <i data-lucide="arrow-right" style="width:18px;"></i>
                    </button>
                </form>
                
                <div style="margin-top: 24px; text-align: center;">
                    <span style="font-size:12px; color:var(--text-muted); font-weight:600; text-transform:uppercase; letter-spacing:1px;">Login</span>
                    <div style="font-size:12px; color:var(--text-muted); margin-top:8px; line-height:1.8;">
                        Gunakan akun yang telah didaftarkan oleh Admin.<br>
                        Hubungi Admin Pokja jika lupa password.
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <script>
        // Render Icons
        lucide.createIcons();
    </script>
    
    <!-- SWEETALERT2 CDN & SMART SCRIPTS -->
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <script>
    document.addEventListener('DOMContentLoaded', () => {
        const loader = document.getElementById('page-loader');
        if(loader) {
            setTimeout(() => {
                loader.style.opacity = '0';
                setTimeout(() => loader.style.display = 'none', 300);
            }, 150);
        }
        
        const banner = document.querySelector('.notification-banner');
        if(banner && banner.innerText.trim() !== '') {
            let isSuccess = banner.classList.contains('success');
            let isWarning = banner.classList.contains('warning');
            let isDanger = banner.classList.contains('danger');
            
            if(isSuccess || isWarning || isDanger) {
                banner.style.display = 'none'; 
                let icon = isSuccess ? 'success' : (isWarning ? 'warning' : 'error');
                let title = isSuccess ? 'Berhasil!' : (isWarning ? 'Perhatian' : 'Gagal Login!');
                
                Swal.fire({
                    icon: icon, title: title, text: banner.innerText.trim(),
                    confirmButtonColor: '#0ea5e9', confirmButtonText: 'Tutup'
                });
            }
        }
    });
    </script>
</body>
</html>
