<?php
require_once '../includes/config.php';
checkLogin();

if(getRole() !== 'siswa') {
    die("Akses ditolak.");
}

// Blokir akses jika bukan dari perangkat mobile
$user_agent = $_SERVER['HTTP_USER_AGENT'] ?? '';
$is_mobile = preg_match('/Mobile|Android|BlackBerry|iPhone|Windows Phone/i', $user_agent);

if(!$is_mobile) {
    die('<!DOCTYPE html><html lang="id"><head><meta name="viewport" content="width=device-width, initial-scale=1.0"><title>Akses Ditolak</title><link rel="stylesheet" href="../assets/css/style.css?v=<?= filemtime('../assets/css/style.css') ?>"></head><body style="display:flex;align-items:center;justify-content:center;height:100vh;background:var(--bg-color);margin:0;"><div style="text-align:center;padding:40px;background:white;border-radius:16px;box-shadow:0 10px 30px rgba(0,0,0,0.05);max-width:350px;"><div style="font-size:48px;margin-bottom:10px;">📱</div><h2 style="margin:0 0 10px 0;color:var(--text-main);">Gunakan HP Anda</h2><p style="color:var(--text-muted);font-size:14px;line-height:1.5;margin-bottom:20px;">Fitur absensi wajah dan lokasi hanya dapat dibuka melalui *browser* di perangkat *mobile* (HP) untuk memastikan keaslian lokasi GPS.</p><a href="../index.php" style="display:inline-block;padding:10px 20px;background:var(--primary);color:white;text-decoration:none;border-radius:8px;font-weight:600;font-size:14px;">Kembali ke Dasbor</a></div></body></html>');
}

$msg = '';
$user_id = $_SESSION['user_id'];
$hari_ini = date('Y-m-d');
$waktu_sekarang = date('H:i:s');
$foto_profil = null;

// Cari tahu tipe PKL siswa (Internal / Eksternal) & Foto Profil
$tipe_pkl = '';
if($conn) {
    // Foto Profil
    $st_f = $conn->prepare("SELECT foto FROM users WHERE id = ?");
    $st_f->bind_param("i", $user_id);
    $st_f->execute();
    $foto_profil = $st_f->get_result()->fetch_row()[0] ?? null;
    // Cek Internal
    $cek_int = $conn->prepare("SELECT id FROM tim_proyek WHERE id_siswa = ?");
    $cek_int->bind_param("i", $user_id);
    $cek_int->execute();
    if($cek_int->get_result()->fetch_assoc()) $tipe_pkl = 'internal';
    
    // Cek Eksternal
    $cek_eks = $conn->prepare("
        SELECT u.alamat as latlong 
        FROM penempatan_dudi pd 
        JOIN users u ON pd.id_dudika = u.id 
        WHERE pd.id_siswa = ? AND pd.tahun_ajaran = ?
    ");
    $cek_eks->bind_param("is", $user_id, $TAHUN_AJARAN_AKTIF);
    $cek_eks->execute();
    $res_eks = $cek_eks->get_result();
    if($row_eks = $res_eks->fetch_assoc()) {
        $tipe_pkl = 'eksternal';
        $target_latlong = $row_eks['latlong']; // Asumsi dudi menyimpan latlong di kolom alamat/latlong
    }
}

if(empty($tipe_pkl)) {
    die('<!DOCTYPE html><html lang="id"><head><meta name="viewport" content="width=device-width, initial-scale=1.0"><title>Belum Ada Penempatan</title><link rel="stylesheet" href="../assets/css/style.css?v=<?= filemtime('../assets/css/style.css') ?>"></head><body style="display:flex;align-items:center;justify-content:center;height:100vh;background:var(--bg-color);margin:0;"><div style="text-align:center;padding:40px;background:white;border-radius:16px;box-shadow:0 10px 30px rgba(0,0,0,0.05);max-width:350px;"><div style="font-size:48px;margin-bottom:10px;">🛑</div><h2 style="margin:0 0 10px 0;color:var(--text-main);">Belum Penempatan</h2><p style="color:var(--text-muted);font-size:14px;line-height:1.5;margin-bottom:20px;">Anda belum ditempatkan di Proyek Internal maupun Mitra DUDI. Hubungi Admin Pokja.</p><a href="../index.php" style="display:inline-block;padding:10px 20px;background:var(--primary);color:white;text-decoration:none;border-radius:8px;font-weight:600;font-size:14px;">Kembali</a></div></body></html>');
}

// Cek status absen hari ini
$absen_datang = null;
$absen_pulang = null;
if($conn) {
    $stmt = $conn->prepare("SELECT * FROM absensi_siswa WHERE id_siswa = ? AND tanggal = ?");
    $stmt->bind_param("is", $user_id, $hari_ini);
    $stmt->execute();
    $res = $stmt->get_result();
    if($row = $res->fetch_assoc()) {
        $absen_datang = $row['waktu_datang'];
        $absen_pulang = $row['waktu_pulang'];
    }
}

// Proses Absen
if($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['action'])) {
    csrf_check();
    $action = $_POST['action'];
    $latlong = trim($_POST['latlong'] ?? '');
    $is_wajah_valid = isset($_POST['is_wajah_valid']) ? (int)$_POST['is_wajah_valid'] : 0;
    
    // Perhitungan Lokasi
    if ($tipe_pkl == 'internal') {
        $target_lokasi = SEKOLAH_LATLONG;
        $jarak_meter = haversineDistance($latlong, $target_lokasi);
        $is_lokasi_valid = ($jarak_meter !== null && $jarak_meter <= 100) ? 1 : 0;
    } else {
        $jarak_meter = null;
        $is_lokasi_valid = 1; // Selalu valid karena bisa tugas luar
    }
    
    // Upload Foto
    $foto_name = null;
    if(isset($_FILES['foto']) && $_FILES['foto']['error'] == 0) {
        $ext = pathinfo($_FILES['foto']['name'], PATHINFO_EXTENSION);
        $foto_name = "absen_".$user_id."_".time().".".$ext;
        move_uploaded_file($_FILES['foto']['tmp_name'], "../uploads/absensi/" . $foto_name);
    }
    
    if($latlong == '' || !$foto_name) {
        $msg = '<div class="notification-banner danger"><span>Pastikan GPS menyala dan foto wajah berhasil diambil!</span></div>';
    } else {
        if($action == 'datang' && !$absen_datang) {
            $stmt = $conn->prepare("INSERT INTO absensi_siswa (id_siswa, tanggal, tipe_pkl, waktu_datang, foto_datang, latlong_datang, is_wajah_valid, is_lokasi_valid, jarak_meter, status) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, 'Hadir')");
            $stmt->bind_param("isssssiii", $user_id, $hari_ini, $tipe_pkl, $waktu_sekarang, $foto_name, $latlong, $is_wajah_valid, $is_lokasi_valid, $jarak_meter);
            if($stmt->execute()) {
                $jarak_txt = ($jarak_meter !== null) ? "Jarak: {$jarak_meter}m" : "Tugas Luar";
                $msg = '<div class="notification-banner success"><span>Berhasil Absen Datang! ('.$jarak_txt.')</span></div>';
                $absen_datang = $waktu_sekarang;
            }
        } elseif($action == 'pulang' && $absen_datang && !$absen_pulang) {
            $stmt = $conn->prepare("UPDATE absensi_siswa SET waktu_pulang = ?, foto_pulang = ?, latlong_pulang = ?, is_wajah_valid = ?, is_lokasi_valid = ?, jarak_meter = ? WHERE id_siswa = ? AND tanggal = ?");
            $stmt->bind_param("sssiiiis", $waktu_sekarang, $foto_name, $latlong, $is_wajah_valid, $is_lokasi_valid, $jarak_meter, $user_id, $hari_ini);
            if($stmt->execute()) {
                $jarak_txt = ($jarak_meter !== null) ? "Jarak: {$jarak_meter}m" : "Tugas Luar";
                $msg = '<div class="notification-banner success"><span>Berhasil Absen Pulang! ('.$jarak_txt.')</span></div>';
                $absen_pulang = $waktu_sekarang;
            }
        }
    }
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
    <title>Absensi PKL</title>
    <link rel="icon" type="image/svg+xml" href="../assets/img/favicon.svg">
    <link rel="stylesheet" href="../assets/css/style.css?v=<?= filemtime('../assets/css/style.css') ?>">
    <script src="https://unpkg.com/lucide@latest"></script>
    <script src="../assets/js/face-api.min.js"></script>
    <style>
        .absen-card { background: white; border-radius: 16px; padding: 25px 20px; box-shadow: 0 4px 15px rgba(0,0,0,0.05); text-align: center; margin-bottom: 20px; }
        .camera-btn { 
            position: relative; display: inline-flex; align-items: center; justify-content: center;
            width: 100px; height: 100px; border-radius: 50%; background: #eff6ff; color: var(--primary);
            margin: 0 auto 15px; cursor: pointer; border: 3px solid #dbeafe; transition: all 0.3s;
        }
        .camera-btn:active { transform: scale(0.95); background: #dbeafe; }
        .camera-btn input[type="file"] { position: absolute; width: 100%; height: 100%; opacity: 0; cursor: pointer; }
        .preview-img { width: 100px; height: 100px; border-radius: 50%; object-fit: cover; display: none; position: absolute; top: -3px; left: -3px; border: 3px solid var(--primary); }
        .map-status { font-size: 12px; color: var(--text-muted); display: flex; align-items: center; justify-content: center; gap: 5px; margin-bottom: 20px; }
        .btn-absen { width: 100%; padding: 14px; font-size: 16px; font-weight: 700; border-radius: 50px; opacity: 0.5; pointer-events: none; transition: all 0.3s; }
        .btn-absen.ready { opacity: 1; pointer-events: auto; }
        .time-badge { display: inline-block; padding: 4px 12px; background: #f1f5f9; border-radius: 50px; font-size: 12px; font-weight: 600; color: var(--text-main); margin-bottom: 10px; }
    </style>
</head>
<body style="background:var(--bg-color);">
    <div class="app-container">
        <header class="app-header">
            <div style="display:flex; align-items:center; gap:15px;">
                <a href="../index.php" class="icon-btn"><i data-lucide="arrow-left"></i></a>
                <h1>Live Absensi PKL</h1>
            </div>
        </header>

        <main class="main-content">
            <?= $msg ?>
            
            <div style="text-align:center; margin-bottom:20px;">
                <div style="font-size:14px; font-weight:600; color:var(--text-main);"><?= date('l, d F Y') ?></div>
                <div style="font-size:32px; font-weight:800; color:var(--primary); font-variant-numeric: tabular-nums;" id="clock"><?= date('H:i:s') ?></div>
                <div style="font-size:12px; color:var(--text-muted); margin-top:5px;">Mode: PKL <?= ucfirst($tipe_pkl) ?></div>
            </div>
            
            <div id="ai-status" style="text-align:center; margin-bottom:15px; font-size:12px; color:var(--warning); font-weight:600;"><i class="loader-spinner" style="width:12px; height:12px; border-width:2px;"></i> Memuat AI Face Recognition...</div>
            
            <?php if($foto_profil): ?>
                <img src="../uploads/profil/<?= $foto_profil ?>" id="ref-img" style="display:none;" crossorigin="anonymous">
            <?php else: ?>
                <div class="notification-banner warning" style="margin-bottom:15px;">Anda belum memasang Foto Profil. Absensi tetap bisa dilakukan namun verifikasi wajah akan dilewati (Tidak Valid).</div>
            <?php endif; ?>

            <!-- ABSEN DATANG -->
            <div class="absen-card" style="<?= $absen_datang ? 'opacity:0.6; filter:grayscale(1); pointer-events:none;' : '' ?>">
                <h3 style="margin:0 0 15px 0; font-size:16px;">Absen Datang</h3>
                <?php if($absen_datang): ?>
                    <div class="time-badge"><i data-lucide="check-circle" style="width:12px; color:var(--success);"></i> Tersimpan: <?= $absen_datang ?></div>
                <?php else: ?>
                    <form method="POST" enctype="multipart/form-data" id="form-datang">
                        <input type="hidden" name="action" value="datang">
                        <?= csrf_field() ?>
                        <input type="hidden" name="latlong" id="latlong-datang" required>
                        <input type="hidden" name="is_wajah_valid" id="wajah-datang" value="0">
                        
                        <div class="camera-btn" id="cam-btn-datang">
                            <i data-lucide="camera" style="width:32px; height:32px;"></i>
                            <input type="file" name="foto" accept="image/*" capture="user" required id="file-datang">
                            <img src="" class="preview-img" id="preview-datang">
                        </div>
                        
                        <div class="map-status" id="map-status-datang">
                            <i data-lucide="map-pin" style="width:14px;"></i> <span>Mencari Lokasi GPS...</span>
                        </div>
                        
                        <button type="submit" class="btn btn-primary btn-absen" id="btn-submit-datang">Kirim Absen Datang</button>
                    </form>
                <?php endif; ?>
            </div>

            <!-- ABSEN PULANG -->
            <div class="absen-card" style="<?= (!$absen_datang || $absen_pulang) ? 'opacity:0.6; filter:grayscale(1); pointer-events:none;' : '' ?>">
                <h3 style="margin:0 0 15px 0; font-size:16px;">Absen Pulang</h3>
                <?php if($absen_pulang): ?>
                    <div class="time-badge"><i data-lucide="check-circle" style="width:12px; color:var(--success);"></i> Tersimpan: <?= $absen_pulang ?></div>
                <?php else: ?>
                    <form method="POST" enctype="multipart/form-data" id="form-pulang">
                        <input type="hidden" name="action" value="pulang">
                        <?= csrf_field() ?>
                        <input type="hidden" name="latlong" id="latlong-pulang" required>
                        <input type="hidden" name="is_wajah_valid" id="wajah-pulang" value="0">
                        
                        <div class="camera-btn" id="cam-btn-pulang">
                            <i data-lucide="camera" style="width:32px; height:32px;"></i>
                            <input type="file" name="foto" accept="image/*" capture="user" required id="file-pulang">
                            <img src="" class="preview-img" id="preview-pulang">
                        </div>
                        
                        <div class="map-status" id="map-status-pulang">
                            <i data-lucide="map-pin" style="width:14px;"></i> <span>Mencari Lokasi GPS...</span>
                        </div>
                        
                        <button type="submit" class="btn btn-primary btn-absen" id="btn-submit-pulang" style="background:var(--warning);">Kirim Absen Pulang</button>
                    </form>
                <?php endif; ?>
            </div>
            
        </main>
    </div>
    
    <script>
        lucide.createIcons();
        
        // Live Clock
        setInterval(() => {
            const d = new Date();
            document.getElementById('clock').innerText = d.toTimeString().split(' ')[0];
        }, 1000);

        // Geolocation Engine
        let currentLatLong = "";
        function initGPS(statusElId, submitBtnId, latlongInputId) {
            const statusEl = document.getElementById(statusElId);
            const submitBtn = document.getElementById(submitBtnId);
            const latlongInput = document.getElementById(latlongInputId);
            
            if (!statusEl) return;

            if ("geolocation" in navigator) {
                navigator.geolocation.getCurrentPosition(
                    (position) => {
                        currentLatLong = position.coords.latitude + "," + position.coords.longitude;
                        latlongInput.value = currentLatLong;
                        statusEl.innerHTML = `<i data-lucide="check" style="width:14px; color:var(--success);"></i> <span style="color:var(--success);">GPS Akurat: ${currentLatLong.substring(0,15)}...</span>`;
                        lucide.createIcons();
                        checkReady(submitBtnId, latlongInputId, submitBtnId.replace('btn-submit', 'file'));
                    },
                    (error) => {
                        statusEl.innerHTML = `<i data-lucide="alert-triangle" style="width:14px; color:var(--danger);"></i> <span style="color:var(--danger);">Akses GPS Ditolak / Gagal.</span>`;
                        lucide.createIcons();
                        alert("Gagal mendapatkan lokasi. Harap nyalakan GPS (Lokasi) pada HP Anda dan izinkan browser mengaksesnya.");
                    },
                    { enableHighAccuracy: true, timeout: 10000, maximumAge: 0 }
                );
            } else {
                statusEl.innerHTML = "Browser tidak mendukung GPS.";
            }
        }

        function checkReady(btnId, latId, fileId) {
            const btn = document.getElementById(btnId);
            const lat = document.getElementById(latId).value;
            const file = document.getElementById(fileId).files.length;
            if(lat !== "" && file > 0) {
                btn.classList.add('ready');
            }
        }

        // Setup File Inputs & Face AI
        let modelsLoaded = false;
        let refDescriptor = null;
        
        async function loadAI() {
            try {
                await Promise.all([
                    faceapi.nets.ssdMobilenetv1.loadFromUri('../assets/models'),
                    faceapi.nets.faceLandmark68Net.loadFromUri('../assets/models'),
                    faceapi.nets.faceRecognitionNet.loadFromUri('../assets/models')
                ]);
                modelsLoaded = true;
                const aiStatus = document.getElementById('ai-status');
                
                const refImg = document.getElementById('ref-img');
                if(refImg) {
                    aiStatus.innerHTML = `<i class="loader-spinner" style="width:12px; height:12px; border-width:2px;"></i> Mengekstrak Wajah Profil...`;
                    const results = await faceapi.detectSingleFace(refImg).withFaceLandmarks().withFaceDescriptor();
                    if(results) {
                        refDescriptor = results.descriptor;
                        aiStatus.innerHTML = `<i data-lucide="check-circle" style="width:14px; color:var(--success);"></i> <span style="color:var(--success);">AI Ready. Wajah Profil Dikenali.</span>`;
                        lucide.createIcons();
                    } else {
                        aiStatus.innerHTML = `<i data-lucide="alert-circle" style="width:14px; color:var(--danger);"></i> <span style="color:var(--danger);">AI Gagal mengenali wajah di Foto Profil Anda!</span>`;
                        lucide.createIcons();
                    }
                } else {
                    aiStatus.innerHTML = `<i data-lucide="alert-triangle" style="width:14px; color:var(--warning);"></i> <span style="color:var(--warning);">Foto Profil Kosong. AI Mati.</span>`;
                    lucide.createIcons();
                }
            } catch(e) {
                console.error(e);
                document.getElementById('ai-status').innerHTML = "Gagal memuat AI Module.";
            }
        }
        loadAI();

        ['datang', 'pulang'].forEach(tipe => {
            const fileInput = document.getElementById('file-' + tipe);
            const preview = document.getElementById('preview-' + tipe);
            const btnSubmit = document.getElementById('btn-submit-' + tipe);
            
            if(fileInput) {
                fileInput.addEventListener('change', async function() {
                    if(this.files && this.files[0]) {
                        const reader = new FileReader();
                        reader.onload = async function(e) {
                            preview.src = e.target.result;
                            preview.style.display = 'block';
                            
                            // Matikan tombol saat proses AI
                            btnSubmit.classList.remove('ready');
                            const originalBtnText = btnSubmit.innerText;
                            btnSubmit.innerText = "Menganalisis Wajah...";
                            
                            // Jalankan AI Jika Model & Ref sudah siap
                            if(modelsLoaded && refDescriptor) {
                                try {
                                    const imgEl = new Image();
                                    imgEl.src = e.target.result;
                                    await new Promise(r => imgEl.onload = r); // tunggu gambar load di memory
                                    
                                    const results = await faceapi.detectSingleFace(imgEl).withFaceLandmarks().withFaceDescriptor();
                                    if(results) {
                                        const distance = faceapi.euclideanDistance(refDescriptor, results.descriptor);
                                        if(distance < 0.6) { // Threshold standar
                                            document.getElementById('wajah-' + tipe).value = "1";
                                            // alert("Wajah Cocok! Distance: " + distance.toFixed(2));
                                        } else {
                                            document.getElementById('wajah-' + tipe).value = "0";
                                            alert("Peringatan: Wajah Anda terlihat berbeda dari Foto Profil. Sistem akan menandai kehadiran ini untuk direview oleh pembimbing.");
                                        }
                                    } else {
                                        alert("Peringatan: Wajah tidak terdeteksi dengan jelas di foto selfie. Sistem akan menandai kehadiran ini untuk direview oleh pembimbing.");
                                    }
                                } catch(err) {
                                    console.error("AI Error:", err);
                                }
                            }
                            
                            btnSubmit.innerText = originalBtnText;
                            checkReady('btn-submit-' + tipe, 'latlong-' + tipe, 'file-' + tipe);
                        }
                        reader.readAsDataURL(this.files[0]);
                    }
                });
                // Init GPS for active card
                initGPS('map-status-' + tipe, 'btn-submit-' + tipe, 'latlong-' + tipe);
            }
        });
    </script>
</body>
</html>
