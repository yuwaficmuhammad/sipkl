<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>403 - Akses Ditolak</title>
    <?php
        $base = './';
        if(file_exists('../../includes/config.php')) $base = '../../';
        elseif(file_exists('../includes/config.php')) $base = '../';
    ?>
    <link rel="icon" type="image/svg+xml" href="<?= $base ?>assets/img/favicon.svg">
    <link rel="stylesheet" href="<?= $base ?>assets/css/style_v2.css">
    <script src="https://unpkg.com/lucide@latest" defer></script>
    <style>
        body {
            display: flex;
            align-items: center;
            justify-content: center;
            height: 100vh;
            background-color: var(--bg-color);
            margin: 0;
            font-family: 'Inter', sans-serif;
        }
        .error-container {
            text-align: center;
            background: white;
            padding: 50px 40px;
            border-radius: 20px;
            box-shadow: 0 10px 30px rgba(0,0,0,0.05);
            max-width: 400px;
            width: 90%;
        }
        .error-icon {
            color: var(--danger);
            width: 80px;
            height: 80px;
            margin-bottom: 20px;
            opacity: 0.9;
            filter: drop-shadow(0 4px 10px rgba(239,68,68,0.3));
        }
        .error-code {
            font-size: 60px;
            font-weight: 800;
            color: var(--text-main);
            margin: 0;
            line-height: 1;
            letter-spacing: -2px;
        }
        .error-title {
            font-size: 20px;
            font-weight: 600;
            color: var(--text-main);
            margin: 15px 0 10px 0;
        }
        .error-desc {
            font-size: 14px;
            color: var(--text-muted);
            margin-bottom: 30px;
            line-height: 1.5;
        }
        .btn-back {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            background: var(--text-main);
            color: white;
            padding: 12px 24px;
            border-radius: 50px;
            text-decoration: none;
            font-weight: 600;
            font-size: 14px;
            transition: all 0.2s ease;
        }
        .btn-back:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(0,0,0,0.2);
        }
    </style>
</head>
<body>
    <div class="error-container">
        <i data-lucide="shield-alert" class="error-icon"></i>
        <h1 class="error-code">403</h1>
        <h2 class="error-title">Akses Ditolak!</h2>
        <p class="error-desc">Maaf, Anda tidak memiliki izin (*role*) yang cukup untuk melihat atau mengakses halaman ini.</p>
        <a href="<?= $base ?>index.php" class="btn-back">
            <i data-lucide="home" style="width:18px;"></i> Kembali ke Beranda
        </a>
    </div>
    <script>lucide.createIcons();</script>
</body>
</html>
