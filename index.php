<?php
require_once 'includes/config.php';
checkLogin();

$role = getRole();
$name = $_SESSION['name'];
$foto_profil = null;

if($conn) {
    $st_f = $conn->prepare("SELECT foto FROM users WHERE id = ?");
    $st_f->bind_param("i", $_SESSION['user_id']);
    $st_f->execute();
    $foto_profil = $st_f->get_result()->fetch_row()[0] ?? null;
}

// ----- MESIN NOTIFIKASI TIMELINE (DINAMIS) -----
$today = date('Y-m-d');
// $today = '2026-09-23'; // Uncomment untuk testing tgl tertentu

$notifs = [];

if(in_array($role, ['siswa', 'pembimbing_sekolah'])) {
    if($today <= $TIMELINE['gate_1']) {
        $notifs[] = ['type' => 'alert', 'icon' => 'alert-triangle', 'msg' => '<b>Gate 1 (Validasi Klien)</b> batas akhir pada ' . date('d M Y', strtotime($TIMELINE['gate_1'])) . '. Segera selesaikan MoM Klien.'];
    } else if($today <= $TIMELINE['gate_2']) {
        $notifs[] = ['type' => 'alert', 'icon' => 'alert-triangle', 'msg' => '<b>Gate 2 (Rencana Teknis)</b> batas akhir pada ' . date('d M Y', strtotime($TIMELINE['gate_2'])) . '. Lengkapi Spesifikasi Teknis.'];
    } else if($today <= $TIMELINE['gate_3']) {
        $notifs[] = ['type' => 'alert', 'icon' => 'clock', 'msg' => '<b>Gate 3 (Produksi 70%)</b> batas akhir pada ' . date('d M Y', strtotime($TIMELINE['gate_3'])) . '. Jangan lupa isi Logbook Harian!'];
    } else if($today <= $TIMELINE['gate_4']) {
        $notifs[] = ['type' => 'alert', 'icon' => 'hourglass', 'msg' => '<b>Gate 4 (UAT & Handover)</b> batas akhir pada ' . date('d M Y', strtotime($TIMELINE['gate_4'])) . '. Siapkan BAST dan Training Klien.'];
    }
    
    if($today >= $TIMELINE['pkl_eks_start'] && $today <= $TIMELINE['pkl_eks_end']) {
        $notifs[] = ['type' => 'success', 'icon' => 'building', 'msg' => 'Fase <b>PKL Eksternal</b>. Jangan lupa isi Jurnal Eksternal setiap hari kerja!'];
    }
}

if($role == 'pembimbing_dudika') {
    if($today >= '2027-02-01' && $today <= $TIMELINE['pkl_eks_end']) {
        $notifs[] = ['type' => 'danger', 'icon' => 'alert-octagon', 'msg' => 'Penarikan Siswa (18 Feb 2027) mendekat. Mohon isi <b>Form Penilaian Akhir</b>.'];
    } else {
        $notifs[] = ['type' => 'success', 'icon' => 'check-circle', 'msg' => 'Selamat datang di Panel DUDIKA. Pantau progres siswa Anda.'];
    }
}

$proyek_siswa = null;
$lulus_dudika = false;
if($role == 'siswa' && $conn) {
    // Ambil data proyek internal jika ada - JOIN efisien, tidak ada N+1
    $stmt = $conn->prepare("
        SELECT p.*, u.name as nama_pembimbing,
               ketua_u.name as nama_ketua,
               COUNT(DISTINCT tp_all.id_siswa) as jml_anggota
        FROM tim_proyek tp
        JOIN proyek_internal p ON tp.id_proyek = p.id
        JOIN users u ON p.id_pembimbing_sekolah = u.id
        LEFT JOIN tim_proyek tp_ketua ON tp_ketua.id_proyek = p.id AND tp_ketua.is_ketua = 1
        LEFT JOIN users ketua_u ON tp_ketua.id_siswa = ketua_u.id
        LEFT JOIN tim_proyek tp_all ON tp_all.id_proyek = p.id
        WHERE tp.id_siswa = ? AND p.tahun_ajaran = ?
        GROUP BY p.id, u.name, ketua_u.name
    ");
    $stmt->bind_param("is", $_SESSION['user_id'], $TAHUN_AJARAN_AKTIF);
    $stmt->execute();
    $res = $stmt->get_result();
    if($row = $res->fetch_assoc()) {
        $proyek_siswa = $row;
    }
    
    // Cek kelulusan DUDIKA
    $stmt_dudi = $conn->prepare("SELECT id FROM penilaian_dudi WHERE id_siswa = ?");
    $stmt_dudi->bind_param("i", $_SESSION['user_id']);
    $stmt_dudi->execute();
    $res_dudi = $stmt_dudi->get_result();
    if($res_dudi->fetch_assoc()) {
        $lulus_dudika = true;
    }
    
    // Handler Upload Dokumen Gate
    if($proyek_siswa && $_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['action']) && $_POST['action'] == 'upload_doc') {
        $gate = (int)$_POST['gate_target'];
        if(isset($_FILES['dokumen']) && $_FILES['dokumen']['error'] == 0) {
            // Validasi ukuran (maks 5MB)
            if($_FILES['dokumen']['size'] > 5 * 1024 * 1024) {
                $notifs[] = ['type' => 'warning', 'icon' => 'alert-triangle', 'msg' => 'File terlalu besar! Maksimal 5MB.'];
            } else {
                // Validasi MIME type sesungguhnya (bukan hanya ekstensi)
                $finfo = finfo_open(FILEINFO_MIME_TYPE);
                $mime = finfo_file($finfo, $_FILES['dokumen']['tmp_name']);
                finfo_close($finfo);
                if($mime !== 'application/pdf') {
                    $notifs[] = ['type' => 'warning', 'icon' => 'alert-triangle', 'msg' => 'Hanya file PDF yang diperbolehkan! (Terdeteksi: ' . htmlspecialchars($mime) . ')'];
                } else {
                    $new_name = "gate_{$gate}_proyek_{$proyek_siswa['id']}_" . time() . ".pdf";
                    if(move_uploaded_file($_FILES['dokumen']['tmp_name'], "uploads/" . $new_name)) {
                        $col = "doc_gate_" . $gate;
                        $stmt = $conn->prepare("UPDATE proyek_internal SET $col = ? WHERE id = ?");
                        $stmt->bind_param("si", $new_name, $proyek_siswa['id']);
                        $stmt->execute();
                        $proyek_siswa[$col] = $new_name;
                        $notifs[] = ['type' => 'success', 'icon' => 'check-circle', 'msg' => "Dokumen Gate $gate berhasil diunggah!"];
                    } else {
                        $notifs[] = ['type' => 'danger', 'icon' => 'x-circle', 'msg' => 'Gagal menyimpan file ke folder uploads/'];
                    }
                }
            }
        }
    }
}

$siswa_bimbingan = [];
if($role == 'pembimbing_dudika' && $conn) {
    // Get DUDI's jurusan
    $stmt = $conn->prepare("SELECT jurusan FROM users WHERE id = ?");
    $stmt->bind_param("i", $_SESSION['user_id']);
    $stmt->execute();
    $res = $stmt->get_result();
    $dudi_jurusan = '';
    if($r = $res->fetch_assoc()) {
        $dudi_jurusan = $r['jurusan'];
    }
    // Fetch mapped students for this DUDI
    $stmt = $conn->prepare("
        SELECT u.id, u.name 
        FROM users u 
        JOIN penempatan_dudi pd ON u.id = pd.id_siswa 
        WHERE pd.id_dudika = ? AND pd.tahun_ajaran = ? AND u.role = 'siswa'
    ");
    $stmt->bind_param("is", $_SESSION['user_id'], $TAHUN_AJARAN_AKTIF);
    $stmt->execute();
    $res = $stmt->get_result();
    while($row = $res->fetch_assoc()) {
        $siswa_bimbingan[] = $row;
    }
}

// Data untuk Admin Dashboard
$stat_siswa = 0;
$stat_proyek = 0;
$stat_dudi = 0;
$stat_gate1 = 0;
$persen_gate1 = 0;

if($role == 'admin' && $conn) {
    // Siswa Aktif
    $st = $conn->prepare("SELECT COUNT(*) FROM users WHERE role = 'siswa' AND tahun_ajaran = ?");
    $st->bind_param("s", $TAHUN_AJARAN_AKTIF);
    $st->execute();
    $stat_siswa = $st->get_result()->fetch_row()[0] ?? 0;
    
    // Proyek Internal Aktif
    $st = $conn->prepare("SELECT COUNT(*) FROM proyek_internal WHERE tahun_ajaran = ?");
    $st->bind_param("s", $TAHUN_AJARAN_AKTIF);
    $st->execute();
    $stat_proyek = $st->get_result()->fetch_row()[0] ?? 0;
    
    // Mitra DUDI
    $st = $conn->query("SELECT COUNT(*) FROM users WHERE role = 'pembimbing_dudika'");
    $stat_dudi = $st->fetch_row()[0] ?? 0;
    
    // Proyek Lulus Gate 1
    if($stat_proyek > 0) {
        $st = $conn->prepare("SELECT COUNT(*) FROM proyek_internal WHERE tahun_ajaran = ? AND status_gate >= 1");
        $st->bind_param("s", $TAHUN_AJARAN_AKTIF);
        $st->execute();
        $stat_gate1 = $st->get_result()->fetch_row()[0] ?? 0;
        $persen_gate1 = round(($stat_gate1 / $stat_proyek) * 100);
    }
}

if(isset($_GET['logout'])) {
    session_destroy();
    header("Location: auth/login.php");
    exit;
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
    <title>Dashboard - SIPKL</title>
    <link rel="icon" type="image/svg+xml" href="assets/img/favicon.svg">
    <link rel="stylesheet" href="assets/css/style_v2.css?v=<?= filemtime('assets/css/style_v2.css') ?>">
    <script src="https://unpkg.com/lucide@latest"></script>
</head>
<body>
    <div id="page-loader"><div class="loader-spinner"></div></div>
    <div class="app-container">
        <!-- Header -->
        <header class="app-header">
            <div style="display:flex; align-items:center; gap:12px; overflow: hidden; flex: 1; padding-right: 15px;">
                <?php if($foto_profil): ?>
                    <img src="uploads/profil/<?= $foto_profil ?>" style="width:40px; height:40px; border-radius:50%; object-fit:cover; border:2px solid var(--primary-light);">
                <?php else: ?>
                    <div style="width:40px; height:40px; border-radius:50%; background:var(--bg-color); color:var(--primary); display:flex; align-items:center; justify-content:center; border:2px solid var(--primary-light);">
                        <i data-lucide="user" style="width:20px; height:20px;"></i>
                    </div>
                <?php endif; ?>
                <div style="overflow:hidden;">
                    <h1 style="font-size: 16px; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; margin:0;">Halo, <?= htmlspecialchars($name) ?></h1>
                    <div style="font-size: 11px; opacity: 0.8; text-transform: uppercase; letter-spacing:0.5px; white-space: nowrap; overflow: hidden; text-overflow: ellipsis;"><?= str_replace('_', ' ', $role) ?></div>
                </div>
            </div>
            <div style="display:flex; align-items:center; gap:8px; flex-shrink:0;">
                <button id="btn-notif" class="icon-btn" type="button" title="Notifikasi" style="position:relative; background:none; border:none; cursor:pointer; padding:4px;">
                    <i data-lucide="bell"></i>
                    <span id="notif-badge" class="badge-dot" style="display:none;">0</span>
                </button>
                <a href="?logout=1" class="icon-btn" title="Logout"><i data-lucide="log-out"></i></a>
            </div>
        </header>
        
        <!-- Main Content -->
        <main class="main-content">
            
            <!-- Realtime Timeline Notifications -->
            <?php foreach($notifs as $n): ?>
                <div class="notification-banner <?= $n['type'] ?>">
                    <i data-lucide="<?= $n['icon'] ?>" style="width:18px; height:18px;"></i>
                    <div><?= $n['msg'] ?></div>
                </div>
            <?php endforeach; ?>
            
            <?php if($role == 'siswa'): ?>
            <!-- SISWA DASHBOARD -->
            <?php if(!$proyek_siswa): ?>
            <div class="card" style="text-align:center; padding:40px 20px;">
                <i data-lucide="folder-search" style="width:64px; height:64px; color:var(--warning); margin:0 auto 16px;"></i>
                <h2 style="font-size:18px; color:var(--text-main); margin-bottom:12px;">Belum Memiliki Tim</h2>
                <p style="font-size:14px; color:var(--text-muted); line-height:1.5;">Anda belum didaftarkan ke dalam proyek PKL mana pun. Hubungi Admin Pokja atau Ketua Tim Anda.</p>
            </div>
            <?php else: ?>
            <div class="card">
                <div class="card-title"><i data-lucide="target"></i> <?= htmlspecialchars($proyek_siswa['judul_proyek']) ?></div>
                <p style="font-size: 13px; color: var(--text-muted); margin-bottom: 6px;"><i data-lucide="building" style="width:14px; margin-right:4px;"></i> Klien: <?= htmlspecialchars($proyek_siswa['nama_klien']) ?></p>
                <p style="font-size: 13px; color: var(--text-muted); margin-bottom: 16px;"><i data-lucide="graduation-cap" style="width:14px; margin-right:4px;"></i> Pembimbing: <?= htmlspecialchars($proyek_siswa['nama_pembimbing']) ?></p>
                
                <div style="display:flex; justify-content:space-between; margin-bottom:16px;">
                    <?php for($i=1; $i<=4; $i++): 
                        $is_passed = $proyek_siswa['status_gate'] >= $i;
                        $is_current = $proyek_siswa['status_gate'] == ($i - 1);
                        $bg = $is_passed ? 'var(--success)' : ($is_current ? 'var(--warning)' : 'var(--border)');
                        $color = $is_passed || $is_current ? 'white' : 'var(--text-muted)';
                        $shadow = $is_passed ? '0 4px 10px rgba(16,185,129,0.3)' : ($is_current ? '0 4px 10px rgba(245,158,11,0.3)' : 'none');
                    ?>
                    <div style="text-align:center;">
                        <div style="background:<?= $bg ?>; color:<?= $color ?>; width:44px; height:44px; line-height:44px; border-radius:50%; margin:0 auto 8px; font-weight:bold; box-shadow:<?= $shadow ?>; position:relative;">
                            <?= $i ?>
                            <?php if($is_passed): ?>
                            <i data-lucide="check" style="width:16px; position:absolute; bottom:-4px; right:-4px; background:white; color:var(--success); border-radius:50%; padding:2px;"></i>
                            <?php endif; ?>
                        </div>
                        <small style="font-weight:600; color:<?= $is_passed || $is_current ? 'var(--primary)' : 'var(--text-muted)' ?>;">Gate <?= $i ?></small>
                    </div>
                    <?php endfor; ?>
                </div>
                
                <?php
                // Form Upload Dokumen Gate Berjalan
                $current_gate = $proyek_siswa['status_gate'] + 1;
                if($current_gate <= 4):
                    $doc_col = "doc_gate_" . $current_gate;
                    $has_doc = !empty($proyek_siswa[$doc_col]);
                ?>
                <div style="background:var(--bg-color); border:1px dashed var(--border); border-radius:8px; padding:12px; margin-bottom:16px;">
                    <div style="font-size:12px; font-weight:600; color:var(--text-main); margin-bottom:8px;">Dokumen Syarat Gate <?= $current_gate ?> (PDF)</div>
                    <?php if($has_doc): ?>
                        <div style="display:flex; align-items:center; gap:8px; font-size:12px; color:var(--success);">
                            <i data-lucide="file-check-2" style="width:16px;"></i> Tersimpan: <a href="uploads/<?= $proyek_siswa[$doc_col] ?>" target="_blank" style="color:var(--primary); text-decoration:underline;">Lihat Berkas</a>
                        </div>
                        <div style="font-size:11px; color:var(--text-muted); margin-top:4px;">Menunggu ACC dari Guru Pembimbing. Anda bisa <a href="#" onclick="document.getElementById('upload-form-<?= $current_gate ?>').style.display='flex'; this.style.display='none'; return false;" style="color:var(--primary);">Upload Ulang</a> jika ada revisi.</div>
                    <?php endif; ?>
                    <form id="upload-form-<?= $current_gate ?>" method="POST" enctype="multipart/form-data" style="display:<?= $has_doc ? 'none' : 'block' ?>; margin-top:12px;">
                        <input type="hidden" name="action" value="upload_doc">
                        <input type="hidden" name="gate_target" value="<?= $current_gate ?>">
                        
                        <div style="position:relative; width:100%; border:2px dashed var(--border); border-radius:8px; padding:15px; text-align:center; background:#f8fafc; transition:all 0.3s ease; margin-bottom:10px;" onmouseover="this.style.borderColor='var(--primary)'; this.style.background='#eff6ff'" onmouseout="this.style.borderColor='var(--border)'; this.style.background='#f8fafc'">
                            <input type="file" name="dokumen" id="doc_gate_<?= $current_gate ?>" accept=".pdf" required style="position:absolute; top:0; left:0; width:100%; height:100%; opacity:0; cursor:pointer;" onchange="document.getElementById('doc_name_<?= $current_gate ?>').innerText = this.files[0].name; document.getElementById('doc_name_<?= $current_gate ?>').style.color = 'var(--primary)';">
                            <i data-lucide="file-text" style="width:24px; height:24px; color:var(--text-muted); margin-bottom:4px; display:inline-block;"></i>
                            <div id="doc_name_<?= $current_gate ?>" style="font-size:12px; font-weight:600; color:var(--text-muted);">Klik / Tarik file PDF ke sini</div>
                        </div>
                        
                        <button type="submit" class="btn btn-primary" style="width:100%; padding:10px; font-size:13px; font-weight:600;"><i data-lucide="upload-cloud" style="width:16px; margin-right:4px;"></i> Upload Dokumen</button>
                    </form>
                </div>
                <?php endif; ?>
                
                <div style="display:flex; gap:10px; flex-wrap:wrap;">
                    <a href="siswa/absen.php" class="btn btn-primary" style="flex:1; background:var(--success);"><i data-lucide="camera" style="width:18px;"></i> Absen Wajah & GPS</a>
                    <a href="siswa/siswa_logbook.php" class="btn btn-primary" style="flex:1;"><i data-lucide="edit-3" style="width:18px;"></i> Isi Logbook</a>
                </div>
            </div>
            
            <div class="card">
                <div class="card-title"><i data-lucide="building-2"></i> PKL Eksternal</div>
                <p style="font-size: 13px; color: var(--text-muted); margin-bottom: 16px;">Fase eksternal industri akan dimulai pada tanggal <?= date('d M Y', strtotime($TIMELINE['pkl_eks_start'])) ?>.</p>
                <button class="btn" style="background:var(--border); color:var(--text-muted); cursor:not-allowed;" disabled><i data-lucide="lock" style="width:18px;"></i> Jurnal Eksternal Terkunci</button>
            </div>
            
            <div class="card">
                <div class="card-title"><i data-lucide="award" style="color:var(--primary);"></i> Sertifikat & Penghargaan</div>
                <p style="font-size: 13px; color: var(--text-muted); margin-bottom: 16px;">Anda berhak mendapatkan sertifikat jika telah menyelesaikan seluruh rangkaian kegiatan PKL dengan baik.</p>
                
                <div style="display:flex; flex-direction:column; gap:10px;">
                    <?php if($proyek_siswa && $proyek_siswa['status_gate'] == 4): ?>
                        <a href="siswa/sertifikat.php?tipe=internal" target="_blank" class="btn btn-primary" style="background:#0ea5e9; box-shadow:0 4px 10px rgba(14,165,233,0.3); justify-content:center; display:flex;"><i data-lucide="download" style="width:18px; margin-right:6px;"></i> Unduh Sertifikat Internal</a>
                    <?php else: ?>
                        <button class="btn" style="background:var(--border); color:var(--text-muted); cursor:not-allowed; justify-content:center; display:flex;" disabled><i data-lucide="lock" style="width:18px; margin-right:6px;"></i> Sertifikat Internal (Butuh Gate 4)</button>
                    <?php endif; ?>
                    
                    <?php if($lulus_dudika): ?>
                        <a href="siswa/sertifikat.php?tipe=eksternal" target="_blank" class="btn btn-primary" style="background:#10b981; box-shadow:0 4px 10px rgba(16,185,129,0.3); justify-content:center; display:flex;"><i data-lucide="download" style="width:18px; margin-right:6px;"></i> Unduh Sertifikat Industri</a>
                    <?php else: ?>
                        <button class="btn" style="background:var(--border); color:var(--text-muted); cursor:not-allowed; justify-content:center; display:flex;" disabled><i data-lucide="lock" style="width:18px; margin-right:6px;"></i> Sertifikat Industri (Belum Dinilai)</button>
                    <?php endif; ?>
                </div>
            </div>
            <?php endif; ?>
            
            <?php elseif($role == 'pembimbing_sekolah'): ?>
            <!-- GURU DASHBOARD -->
            <div class="card">
                <div class="card-title"><i data-lucide="shield-check"></i> Tugas Approval (Gatekeeping)</div>
                <p style="font-size: 13px; margin-bottom:16px; color:var(--text-muted);">Ada <b>2 Tim Proyek</b> yang sedang menunggu persetujuan Anda untuk Gate 3.</p>
                <a href="guru/guru_gate.php" class="btn btn-primary"><i data-lucide="list-checks" style="width:18px;"></i> Panel Gatekeeping</a>
            </div>
            
            <div class="card">
                <div class="card-title"><i data-lucide="file-text"></i> Monitoring Logbook</div>
                <p style="font-size: 13px; margin-bottom:16px; color:var(--text-muted);">Terdapat beberapa logbook siswa yang perlu Anda verifikasi (ACC).</p>
                <a href="guru/guru_logbook.php" class="btn btn-outline"><i data-lucide="check-square" style="width:18px;"></i> Periksa Logbook</a>
            </div>
            
            <div class="card">
                <div class="card-title"><i data-lucide="map-pin"></i> Pantau Kehadiran GPS</div>
                <p style="font-size: 13px; margin-bottom:16px; color:var(--text-muted);">Pantau absensi wajah dan lokasi (GPS) siswa bimbingan Anda secara *real-time*.</p>
                <a href="guru/guru_absen.php" class="btn btn-primary" style="background:var(--success);"><i data-lucide="camera" style="width:18px;"></i> Buka Peta Absensi</a>
            </div>
            
            <?php elseif($role == 'pembimbing_dudika'): ?>
            <!-- DUDI DASHBOARD -->
            <div class="card">
                <div class="card-title"><i data-lucide="users"></i> Siswa Bimbingan Industri</div>
                <div style="background:var(--bg-color); border-radius:12px; padding:12px; margin-bottom:16px;">
                    <?php if(empty($siswa_bimbingan)): ?>
                        <div style="text-align:center; padding:20px 0; color:var(--text-muted); font-size:13px;">
                            <i data-lucide="folder-search" style="width:32px; height:32px; margin-bottom:8px; opacity:0.5;"></i><br>
                            Belum ada siswa dari jurusan Anda pada tahun ajaran ini.
                        </div>
                    <?php else: ?>
                        <?php foreach($siswa_bimbingan as $sb): ?>
                        <div style="padding:8px 0; border-bottom:1px solid var(--border); display:flex; justify-content:space-between; align-items:center;">
                            <span style="font-weight:600; font-size:14px;"><i data-lucide="user" style="width:14px; margin-right:4px;"></i> <?= htmlspecialchars($sb['name']) ?></span>
                            <!-- Placeholder status jurnal, saat ini dikunci hingga fase eksternal -->
                            <span class="badge bg-border" style="color:var(--text-muted)">Menunggu Jadwal</span>
                        </div>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>
                <div style="display:flex; flex-direction:column; gap:10px;">
                    <a href="dudi/dudi_absen.php" class="btn btn-primary" style="background:var(--success);"><i data-lucide="map-pin" style="width:18px;"></i> Pantau Kehadiran Siswa (GPS)</a>
                    <a href="dudi/dudi_nilai.php" class="btn btn-primary <?= empty($siswa_bimbingan) ? 'disabled' : '' ?>" <?= empty($siswa_bimbingan) ? 'onclick="return false;" style="opacity:0.5"' : '' ?>><i data-lucide="star" style="width:18px;"></i> Input Penilaian Akhir</a>
                </div>
            </div>
            
            <?php elseif($role == 'admin'): ?>
            <!-- ADMIN POKJA DASHBOARD -->
            <div class="grid-2" style="margin-bottom:20px;">
                <div class="stat-box" style="background:linear-gradient(135deg, var(--primary) 0%, var(--primary-light) 100%); border:none;">
                    <h2 style="color:white;"><?= $stat_siswa ?></h2>
                    <small style="color:var(--accent-light);">Siswa Aktif</small>
                </div>
                <div class="stat-box">
                    <h2><?= $stat_proyek ?></h2>
                    <small>Proyek Internal</small>
                </div>
                <div class="stat-box">
                    <h2><?= $stat_dudi ?></h2>
                    <small>Mitra DUDI</small>
                </div>
                <div class="stat-box">
                    <h2><?= $persen_gate1 ?>%</h2>
                    <small>Lulus Gate 1</small>
                </div>
            </div>
            
            <style>
            .menu-grid-btn {
                display: flex;
                flex-direction: column;
                align-items: center;
                justify-content: center;
                gap: 8px;
                padding: 12px 4px;
                border-radius: 12px;
                color: white;
                text-decoration: none;
                font-size: 10px;
                font-weight: 600;
                text-align: center;
                transition: transform 0.2s;
                line-height: 1.2;
                box-shadow: 0 4px 10px rgba(0,0,0,0.1);
            }
            .menu-grid-btn:active { transform: scale(0.95); }
            .menu-grid-btn i { width: 24px; height: 24px; }
            </style>
            
            <div class="card">
                <div class="card-title"><i data-lucide="database"></i> Master Data Pokja</div>
                <div style="display:grid; grid-template-columns: repeat(4, 1fr); gap:10px;">
                    <a href="admin/admin_absen.php" class="menu-grid-btn" style="background:var(--success);">
                        <i data-lucide="map-pin"></i><span>Absensi</span>
                    </a>
                    <a href="admin/admin_dudi.php" class="menu-grid-btn" style="background:#f59e0b;">
                        <i data-lucide="briefcase"></i><span>Mitra DUDI</span>
                    </a>
                    <a href="admin/admin_mapping_dudi.php" class="menu-grid-btn" style="background:#8b5cf6;">
                        <i data-lucide="users"></i><span>Penempatan</span>
                    </a>
                    <a href="admin/admin_tahun_ajaran.php" class="menu-grid-btn" style="background:var(--primary);">
                        <i data-lucide="calendar-days"></i><span>Thn Ajaran</span>
                    </a>
                    <a href="admin/admin_timeline.php" class="menu-grid-btn" style="background:#ec4899;">
                        <i data-lucide="calendar-clock"></i><span>Timeline</span>
                    </a>
                    <a href="admin/admin_proyek.php" class="menu-grid-btn" style="background:#14b8a6;">
                        <i data-lucide="folder-git-2"></i><span>Proyek</span>
                    </a>
                    <a href="admin/admin_users.php" class="menu-grid-btn" style="background:#3b82f6;">
                        <i data-lucide="user-cog"></i><span>Pengguna</span>
                    </a>
                    <a href="admin/admin_lembaga.php" class="menu-grid-btn" style="background:#059669;">
                        <i data-lucide="building"></i><span>Profil Lembaga</span>
                    </a>
                    <a href="admin/admin_arsip.php" class="menu-grid-btn" style="background:#64748b;">
                        <i data-lucide="archive"></i><span>Arsip</span>
                    </a>
                </div>
            </div>
            <?php endif; ?>
            
        </main>
        
        <?php 
            $active_page = 'home';
            include 'includes/bottom_nav.php'; 
        ?>
    </div>
    
    <script>
        lucide.createIcons();
    </script>
</body>
</html>
