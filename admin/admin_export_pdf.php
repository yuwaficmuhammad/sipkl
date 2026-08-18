<?php
require_once '../includes/config.php';
require_once '../vendor/autoload.php';

use Dompdf\Dompdf;
use Dompdf\Options;

checkLogin();
if(getRole() !== 'admin') {
    die("Akses ditolak. Hanya untuk Admin.");
}

if(!$conn) die("Database connection error");

$ta = $_GET['ta'] ?? $TAHUN_AJARAN_AKTIF;

// 1. Ambil Data Proyek Internal
$stmt = $conn->prepare("
    SELECT p.kode_proyek, p.judul_proyek, p.nama_klien, u.name as nama_guru, p.status_gate 
    FROM proyek_internal p 
    JOIN users u ON p.id_pembimbing_sekolah = u.id 
    WHERE p.tahun_ajaran = ?
");
$stmt->bind_param("s", $ta);
$stmt->execute();
$res_p = $stmt->get_result();
$proyeks = [];
while($row = $res_p->fetch_assoc()) $proyeks[] = $row;

// 2. Ambil Data Siswa & Penilaian
$stmt_s = $conn->prepare("
    SELECT u.username, u.name as nama_siswa, u.jurusan, d.name as nama_dudi, n.nilai_softskill, n.nilai_hardskill, n.catatan_industri 
    FROM users u 
    LEFT JOIN penilaian_dudi n ON u.id = n.id_siswa 
    LEFT JOIN penempatan_dudi pd ON pd.id_siswa = u.id AND pd.tahun_ajaran = ?
    LEFT JOIN users d ON pd.id_dudika = d.id 
    WHERE u.role = 'siswa' AND u.tahun_ajaran = ?
    ORDER BY u.jurusan ASC, u.name ASC
");
$stmt_s->bind_param("ss", $ta, $ta);
$stmt_s->execute();
$res_s = $stmt_s->get_result();
$siswas = [];
while($row = $res_s->fetch_assoc()) $siswas[] = $row;

ob_start();
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Rekapitulasi PKL - TA <?= htmlspecialchars($ta) ?></title>
    <style>
        body { font-family: 'Helvetica', 'Arial', sans-serif; font-size: 11px; color: #333; margin: 0; padding: 20px; }
        .header { text-align: center; margin-bottom: 20px; border-bottom: 2px solid #000; padding-bottom: 10px; }
        .header h2 { margin: 0 0 5px 0; font-size: 16px; text-transform: uppercase; }
        .header p { margin: 0; font-size: 11px; }
        
        h3 { font-size: 13px; margin-top: 30px; margin-bottom: 10px; color: #0f172a; border-left: 3px solid #0f172a; padding-left: 8px; }
        
        .data-table { width: 100%; border-collapse: collapse; margin-bottom: 20px; }
        .data-table th, .data-table td { border: 1px solid #666; padding: 6px; text-align: left; vertical-align: top; }
        .data-table th { background-color: #e2e8f0; font-weight: bold; text-align: center; }
        
        .signature-area { margin-top: 40px; float: right; width: 200px; text-align: center; }
        .signature-line { border-bottom: 1px solid #000; margin: 60px 0 5px 0; }
        
        .page-break { page-break-before: always; }
    </style>
</head>
<body>
    <div class="header">
        <h2>Laporan Rekapitulasi Pelaksanaan PKL (Internal & Eksternal)</h2>
        <p>SMK SALAFIYAH PATI - TAHUN AJARAN <?= htmlspecialchars($ta) ?></p>
        <p>Tanggal Cetak: <?= date('d F Y H:i') ?></p>
    </div>

    <h3>1. DATA PROYEK INTERNAL (Teaching Factory)</h3>
    <table class="data-table">
        <thead>
            <tr>
                <th width="5%">No</th>
                <th width="15%">Kode Proyek</th>
                <th width="30%">Judul Proyek</th>
                <th width="20%">Klien</th>
                <th width="15%">Guru Pembimbing</th>
                <th width="15%">Status Akhir</th>
            </tr>
        </thead>
        <tbody>
            <?php if(empty($proyeks)): ?>
            <tr><td colspan="6" style="text-align:center;">Tidak ada proyek internal di tahun ajaran ini.</td></tr>
            <?php else: $no=1; foreach($proyeks as $p): ?>
            <tr>
                <td style="text-align:center;"><?= $no++ ?></td>
                <td><?= htmlspecialchars($p['kode_proyek']) ?></td>
                <td><strong><?= htmlspecialchars($p['judul_proyek']) ?></strong></td>
                <td><?= htmlspecialchars($p['nama_klien']) ?></td>
                <td><?= htmlspecialchars($p['nama_guru']) ?></td>
                <td style="text-align:center;">Gate <?= htmlspecialchars($p['status_gate']) ?></td>
            </tr>
            <?php endforeach; endif; ?>
        </tbody>
    </table>

    <!-- Break halaman jika data proyek sangat banyak, tapi biarkan mengalir secara alami -->
    
    <h3>2. DATA SISWA & PENILAIAN DUDIKA</h3>
    <table class="data-table">
        <thead>
            <tr>
                <th width="5%">No</th>
                <th width="20%">Nama Siswa (NISN)</th>
                <th width="15%">Jurusan</th>
                <th width="20%">Industri (DUDIKA)</th>
                <th width="10%">Nilai<br>Softskill</th>
                <th width="10%">Nilai<br>Hardskill</th>
                <th width="20%">Catatan Industri</th>
            </tr>
        </thead>
        <tbody>
            <?php if(empty($siswas)): ?>
            <tr><td colspan="7" style="text-align:center;">Tidak ada siswa terdaftar di tahun ajaran ini.</td></tr>
            <?php else: $no=1; foreach($siswas as $s): ?>
            <tr>
                <td style="text-align:center;"><?= $no++ ?></td>
                <td>
                    <strong><?= htmlspecialchars($s['nama_siswa']) ?></strong><br>
                    <small><?= htmlspecialchars($s['username']) ?></small>
                </td>
                <td><?= htmlspecialchars($s['jurusan']) ?></td>
                <td><?= $s['nama_dudi'] ? htmlspecialchars($s['nama_dudi']) : '-' ?></td>
                <td style="text-align:center;"><?= $s['nilai_softskill'] ?? '-' ?></td>
                <td style="text-align:center;"><?= $s['nilai_hardskill'] ?? '-' ?></td>
                <td><?= $s['catatan_industri'] ? nl2br(htmlspecialchars($s['catatan_industri'])) : '-' ?></td>
            </tr>
            <?php endforeach; endif; ?>
        </tbody>
    </table>

    <div class="signature-area">
        <p>Pati, <?= date('d F Y') ?><br>Koordinator PKL,</p>
        <div class="signature-line"></div>
        <strong><?= htmlspecialchars($_SESSION['name']) ?></strong>
    </div>
</body>
</html>
<?php
$html = ob_get_clean();

$options = new Options();
$options->set('isHtml5ParserEnabled', true);
$options->set('isRemoteEnabled', true);

$dompdf = new Dompdf($options);
$dompdf->loadHtml($html);
$dompdf->setPaper('A4', 'landscape');
$dompdf->render();

$filename = "Laporan_PKL_TA_" . str_replace('/', '_', $ta) . ".pdf";
$dompdf->stream($filename, ["Attachment" => true]);
