<?php
use Dompdf\Dompdf;
use Dompdf\Options;
require_once '../includes/config.php';
checkLogin();

if(getRole() !== 'siswa') {
    die("Akses ditolak. Hanya siswa yang dapat mencetak sertifikat.");
}

$tipe = $_GET['tipe'] ?? '';
$format = $_GET['format'] ?? 'html';

if($tipe !== 'internal' && $tipe !== 'eksternal') {
    die("Tipe sertifikat tidak valid.");
}

$user_id = $_SESSION['user_id'];
$nama_siswa = $_SESSION['name'];
$username = '';
$jurusan = '';

if(!$conn) {
    header("Location: ../index.php");
    exit;
}

// Ambil info dasar siswa
$stmt = $conn->prepare("SELECT username, jurusan FROM users WHERE id = ?");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$res = $stmt->get_result();
if($s = $res->fetch_assoc()){
    $username = $s['username'];
    $jurusan = $s['jurusan'];
}

$judul_sertifikat = "";
$pesan = "";
$penandatangan = "";
$jabatan_penandatangan = "";
$nilai_rata = null;

if($tipe == 'internal') {
    // Validasi Gate 4
    $stmt = $conn->prepare("
        SELECT p.judul_proyek, p.nama_klien, p.status_gate, u.name as nama_guru 
        FROM tim_proyek tp 
        JOIN proyek_internal p ON tp.id_proyek = p.id 
        JOIN users u ON p.id_pembimbing_sekolah = u.id 
        WHERE tp.id_siswa = ?
    ");
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $res = $stmt->get_result();
    $proyek = $res->fetch_assoc();
    
    if(!$proyek || $proyek['status_gate'] < 4) {
        die("Anda belum memenuhi syarat Gate 4 untuk mengunduh Sertifikat Internal.");
    }
    
    $judul_sertifikat = "SERTIFIKAT KOMPETENSI INTERNAL";
    $pesan = "Telah berhasil menyelesaikan Praktik Kerja Lapangan (PKL) Internal pada proyek <b>" . htmlspecialchars($proyek['judul_proyek']) . "</b> (Klien: " . htmlspecialchars($proyek['nama_klien']) . ") dengan predikat LULUS seluruh tahapan verifikasi kualitas (Gate 4).";
    $penandatangan = $proyek['nama_guru'];
    $jabatan_penandatangan = "Guru Pembimbing PKL";
    
} else if ($tipe == 'eksternal') {
    // Validasi Penilaian DUDI
    $stmt = $conn->prepare("
        SELECT n.nilai_softskill, n.nilai_hardskill, u.name as nama_dudi 
        FROM penilaian_dudi n 
        JOIN users u ON n.id_dudika = u.id 
        WHERE n.id_siswa = ?
    ");
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $res = $stmt->get_result();
    $dudi = $res->fetch_assoc();
    
    if(!$dudi) {
        die("Anda belum dinilai oleh industri. Sertifikat belum tersedia.");
    }
    
    $nilai_rata = ($dudi['nilai_softskill'] + $dudi['nilai_hardskill']) / 2;
    $predikat = "SANGAT BAIK";
    if($nilai_rata < 85) $predikat = "BAIK";
    if($nilai_rata < 70) $predikat = "CUKUP";
    
    $judul_sertifikat = "SERTIFIKAT PRAKTIK INDUSTRI";
    $pesan = "Telah melaksanakan dan menyelesaikan Praktik Kerja Lapangan (PKL) Eksternal di <b>" . htmlspecialchars($dudi['nama_dudi']) . "</b> dengan predikat <b>" . $predikat . "</b> (Rata-rata Nilai: " . number_format($nilai_rata, 1) . ").";
    $penandatangan = $dudi['nama_dudi'];
    $jabatan_penandatangan = "Pimpinan Industri / Instruktur";
}

// Mulai buffer output HTML
ob_start();
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Sertifikat <?= ucfirst($tipe) ?> - <?= htmlspecialchars($nama_siswa) ?></title>
    <style>
        @page { size: A4 landscape; margin: 0; }
        body { margin: 0; padding: 0; display: flex; justify-content: center; align-items: center; min-height: 100vh; background: #52525b; font-family: 'Times New Roman', serif; }
        .certificate-wrapper { width: 297mm; height: 210mm; padding: 40px; background: #fff; box-shadow: 0 10px 30px rgba(0,0,0,0.1); position: relative; background-image: url('../assets/img/cert-bg.png'); background-size: cover; background-position: center; box-sizing: border-box; overflow: hidden; }
        .certificate-border { position: absolute; top: 10mm; left: 10mm; right: 10mm; bottom: 10mm; border: 4mm double #0f172a; }
        .certificate-inner-border { position: absolute; top: 16mm; left: 16mm; right: 16mm; bottom: 16mm; border: 1px solid #334155; }
        .certificate-content { position: relative; z-index: 10; text-align: center; display: flex; flex-direction: column; justify-content: center; height: 100%; }
        
        .logo-placeholder { width: 80px; height: 80px; background: #e2e8f0; border-radius: 50%; margin: 0 auto 20px; display: flex; justify-content: center; align-items: center; color: #64748b; font-family: sans-serif; font-weight: bold; border: 2px solid #cbd5e1; }
        
        .title { font-size: 36px; font-weight: bold; color: #0f172a; letter-spacing: 2px; margin-bottom: 5px; }
        .subtitle { font-size: 16px; color: #475569; letter-spacing: 4px; margin-bottom: 30px; text-transform: uppercase; }
        
        .presented-to { font-size: 14px; font-style: italic; color: #64748b; margin-bottom: 15px; }
        .student-name { font-size: 42px; font-weight: bold; color: #0284c7; margin-bottom: 5px; font-family: 'Georgia', serif; }
        .student-nisn { font-size: 16px; color: #334155; margin-bottom: 30px; letter-spacing: 1px; }
        
        .description { font-size: 18px; color: #1e293b; line-height: 1.6; max-width: 80%; margin: 0 auto 40px; }
        
        .signature-area { display: flex; justify-content: space-between; max-width: 70%; margin: 0 auto; margin-top: 30px; }
        .signature-box { text-align: center; width: 200px; }
        .signature-line { border-bottom: 1px solid #0f172a; margin-top: 50px; margin-bottom: 5px; }
        .signature-name { font-weight: bold; font-size: 16px; color: #0f172a; }
        .signature-title { font-size: 12px; color: #64748b; }
        
        .watermark { position: absolute; top: 50%; left: 50%; transform: translate(-50%, -50%) rotate(-30deg); font-size: 150px; color: rgba(0,0,0,0.03); font-weight: bold; white-space: nowrap; pointer-events: none; z-index: 1; text-transform: uppercase; }
        
        @media print {
            body { background: white; }
            .certificate-wrapper { box-shadow: none; width: 100%; height: 100%; }
            .action-buttons { display: none; }
        }
        
        .action-buttons { position: fixed; top: 20px; right: 20px; display: flex; gap: 10px; z-index: 100; }
        .btn-print { background: #0ea5e9; color: white; border: none; padding: 12px 24px; border-radius: 8px; font-size: 16px; font-weight: bold; font-family: sans-serif; cursor: pointer; box-shadow: 0 4px 6px rgba(0,0,0,0.1); }
        .btn-print:hover { background: #0284c7; }
        .btn-back { background: #64748b; text-decoration: none; padding: 12px 24px; border-radius: 8px; color: white; display: inline-flex; align-items: center; gap: 5px; }
        <?php if($format === 'pdf'): ?>
        body { background: white !important; }
        .action-buttons { display: none !important; }
        .certificate-wrapper { box-shadow: none !important; width: 100% !important; height: 100% !important; padding: 30px !important; }
        <?php endif; ?>
    </style>
</head>
<body>

    <?php if($format !== 'pdf'): ?>
    <div class="action-buttons">
        <a href="../index.php" class="btn-back"><svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="m15 18-6-6 6-6"/></svg> Kembali</a>
        <a href="?tipe=<?= $tipe ?>&format=pdf" class="btn-print" style="text-decoration:none; display:inline-block;">Unduh PDF Murni</a>
        <button class="btn-print" style="background:#6366f1;" onclick="window.print()">Cetak Biasa (Browser)</button>
    </div>
    <?php endif; ?>

    <div class="certificate-wrapper">
        <div class="certificate-border"></div>
        <div class="certificate-inner-border"></div>
        <div class="watermark"><?= htmlspecialchars($jurusan) ?></div>
        
        <div class="certificate-content">
            <img src="../assets/img/logo-sekolah.png" alt="Logo" style="height:80px; object-fit:contain; margin-bottom: 20px;" onerror="this.src='data:image/svg+xml;utf8,<svg xmlns=\'http://www.w3.org/2000/svg\' width=\'80\' height=\'80\' viewBox=\'0 0 24 24\' fill=\'none\' stroke=\'%230ea5e9\' stroke-width=\'2\' stroke-linecap=\'round\' stroke-linejoin=\'round\'><path d=\'M22 10v6M2 10l10-5 10 5-10 5z\'/><path d=\'M6 12v5c3 3 9 3 12 0v-5\'/></svg>'">
            <div class="title"><?= $judul_sertifikat ?></div>
            <div class="subtitle">SMK SALAFIYAH PATI</div>
            
            <div class="presented-to">Sertifikat ini diberikan kepada:</div>
            <div class="student-name"><?= htmlspecialchars($nama_siswa) ?></div>
            <div class="student-nisn">NISN: <?= htmlspecialchars($username) ?> | Jurusan: <?= htmlspecialchars($jurusan) ?></div>
            
            <div class="description">
                <?= $pesan ?>
            </div>
            
            <div class="signature-area">
                <div class="signature-box">
                    <div style="font-size: 14px; color: #475569; margin-bottom: 10px;">Pati, <?= date('d F Y') ?></div>
                    <div class="signature-line"></div>
                    <div class="signature-name"><?= htmlspecialchars($penandatangan) ?></div>
                    <div class="signature-title"><?= $jabatan_penandatangan ?></div>
                </div>
                <div class="signature-box">
                    <div style="font-size: 14px; color: #475569; margin-bottom: 10px;">Mengetahui,</div>
                    <div class="signature-line"></div>
                    <div class="signature-name">Kepala Sekolah</div>
                    <div class="signature-title">SMK Salafiyah Pati</div>
                </div>
            </div>
        </div>
    </div>
    
    <script>
        // Opsional: Otomatis memicu dialog print saat halaman dimuat
        // window.onload = function() { window.print(); }
    </script>
</body>
</html>
<?php
$html = ob_get_clean();

if($format === 'pdf') {
    require_once '../vendor/autoload.php';

    $options = new Options();
    $options->set('isHtml5ParserEnabled', true);
    $options->set('isRemoteEnabled', true); // Agar bisa load gambar

    $dompdf = new Dompdf($options);
    $dompdf->loadHtml($html);
    $dompdf->setPaper('A4', 'landscape');
    $dompdf->render();
    
    $filename = "Sertifikat_" . ucfirst($tipe) . "_" . str_replace(' ', '_', $nama_siswa) . ".pdf";
    $dompdf->stream($filename, ["Attachment" => true]);
    exit;
} else {
    echo $html;
}
?>
