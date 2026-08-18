<?php
require_once '../includes/config.php';
require_once '../vendor/autoload.php';

use Dompdf\Dompdf;
use Dompdf\Options;

checkLogin();
if(getRole() !== 'pembimbing_sekolah') {
    die("Akses ditolak.");
}

$user_id = $_SESSION['user_id'];
$nama_guru = $_SESSION['name'];
$filter_tgl = $_GET['tanggal'] ?? date('Y-m-d');

if(!$conn) die("Database error");

// Ambil data absensi
$absensi = [];
$stmt = $conn->prepare("
    SELECT a.*, u.name as nama_siswa, u.jurusan, u.username
    FROM absensi_siswa a
    JOIN users u ON a.id_siswa = u.id
    JOIN tim_proyek tp ON tp.id_siswa = a.id_siswa
    JOIN proyek_internal p ON tp.id_proyek = p.id
    WHERE a.tanggal = ? AND p.id_pembimbing_sekolah = ? AND a.tipe_pkl = 'internal'
    ORDER BY u.name ASC
");
$stmt->bind_param("si", $filter_tgl, $user_id);
$stmt->execute();
$res = $stmt->get_result();
while($row = $res->fetch_assoc()) $absensi[] = $row;

// Mulai HTML untuk PDF
ob_start();
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Rekap Absensi - <?= htmlspecialchars($filter_tgl) ?></title>
    <style>
        body { font-family: 'Helvetica', 'Arial', sans-serif; font-size: 12px; color: #333; margin: 0; padding: 20px; }
        .header { text-align: center; margin-bottom: 20px; border-bottom: 2px solid #000; padding-bottom: 10px; }
        .header h2 { margin: 0 0 5px 0; font-size: 18px; text-transform: uppercase; }
        .header p { margin: 0; font-size: 12px; }
        .info-table { width: 100%; margin-bottom: 20px; }
        .info-table td { padding: 3px 0; }
        .info-table td:first-child { width: 120px; font-weight: bold; }
        
        .data-table { width: 100%; border-collapse: collapse; margin-bottom: 30px; }
        .data-table th, .data-table td { border: 1px solid #666; padding: 8px; text-align: left; }
        .data-table th { background-color: #f0f0f0; font-weight: bold; text-align: center; }
        
        .status-hadir { color: green; font-weight: bold; }
        .status-izin { color: orange; font-weight: bold; }
        .status-sakit { color: blue; font-weight: bold; }
        .status-alpa { color: red; font-weight: bold; }
        
        .signature-area { margin-top: 50px; float: right; width: 250px; text-align: center; }
        .signature-line { border-bottom: 1px solid #000; margin: 60px 0 5px 0; }
    </style>
</head>
<body>
    <div class="header">
        <h2>Laporan Harian Absensi Siswa PKL Internal</h2>
        <p>SMK SALAFIYAH PATI</p>
    </div>

    <table class="info-table">
        <tr><td>Tanggal</td><td>: <?= date('d F Y', strtotime($filter_tgl)) ?></td></tr>
        <tr><td>Guru Pembimbing</td><td>: <?= htmlspecialchars($nama_guru) ?></td></tr>
    </table>

    <table class="data-table">
        <thead>
            <tr>
                <th width="5%">No</th>
                <th width="30%">Nama Siswa / NISN</th>
                <th width="15%">Waktu Datang</th>
                <th width="15%">Waktu Pulang</th>
                <th width="15%">Status Datang</th>
                <th width="20%">Keterangan Tambahan</th>
            </tr>
        </thead>
        <tbody>
            <?php if(empty($absensi)): ?>
            <tr><td colspan="6" style="text-align:center;">Tidak ada data absensi di tanggal ini.</td></tr>
            <?php else: $no=1; foreach($absensi as $a): 
                $status = strtolower($a['status_datang']);
                $class = "status-".$status;
            ?>
            <tr>
                <td style="text-align:center;"><?= $no++ ?></td>
                <td>
                    <strong><?= htmlspecialchars($a['nama_siswa']) ?></strong><br>
                    <small><?= htmlspecialchars($a['username']) ?> - <?= htmlspecialchars($a['jurusan']) ?></small>
                </td>
                <td style="text-align:center;"><?= $a['waktu_datang'] ? date('H:i', strtotime($a['waktu_datang'])) : '-' ?></td>
                <td style="text-align:center;"><?= $a['waktu_pulang'] ? date('H:i', strtotime($a['waktu_pulang'])) : '-' ?></td>
                <td style="text-align:center;" class="<?= $class ?>"><?= strtoupper($status) ?></td>
                <td>
                    <?= $a['catatan'] ? htmlspecialchars($a['catatan']) : '-' ?>
                </td>
            </tr>
            <?php endforeach; endif; ?>
        </tbody>
    </table>

    <div class="signature-area">
        <p>Pati, <?= date('d F Y', strtotime($filter_tgl)) ?><br>Guru Pembimbing,</p>
        <div class="signature-line"></div>
        <strong><?= htmlspecialchars($nama_guru) ?></strong>
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
$dompdf->setPaper('A4', 'portrait');
$dompdf->render();

$filename = "Absensi_PKL_" . $filter_tgl . ".pdf";
$dompdf->stream($filename, ["Attachment" => true]);
