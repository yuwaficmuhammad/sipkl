<?php
require_once '../includes/config.php';
require_once '../vendor/autoload.php';

use Dompdf\Dompdf;
use Dompdf\Options;

checkLogin();
if(getRole() !== 'pembimbing_dudika') {
    die("Akses ditolak.");
}

$user_id = $_SESSION['user_id'];
$nama_dudi = $_SESSION['name'];

if(!$conn) die("Database error");

$siswas = [];
$stmt = $conn->prepare("
    SELECT u.id, u.name, u.username as nisn, u.jurusan, p.nilai_softskill, p.nilai_hardskill, p.catatan_industri 
    FROM users u 
    JOIN penempatan_dudi pd ON u.id = pd.id_siswa
    LEFT JOIN penilaian_dudi p ON u.id = p.id_siswa AND p.id_dudika = ?
    WHERE u.role = 'siswa' AND pd.id_dudika = ? AND pd.tahun_ajaran = ?
    ORDER BY u.name ASC
");
$stmt->bind_param("iis", $user_id, $user_id, $TAHUN_AJARAN_AKTIF);
$stmt->execute();
$res = $stmt->get_result();
while($row = $res->fetch_assoc()) $siswas[] = $row;

ob_start();
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Rekap Penilaian DUDI - <?= htmlspecialchars($TAHUN_AJARAN_AKTIF) ?></title>
    <style>
        body { font-family: 'Helvetica', 'Arial', sans-serif; font-size: 12px; color: #333; margin: 0; padding: 20px; }
        .header { text-align: center; margin-bottom: 20px; border-bottom: 2px solid #000; padding-bottom: 10px; }
        .header h2 { margin: 0 0 5px 0; font-size: 18px; text-transform: uppercase; }
        .header p { margin: 0; font-size: 12px; }
        .info-table { width: 100%; margin-bottom: 20px; }
        .info-table td { padding: 3px 0; }
        .info-table td:first-child { width: 150px; font-weight: bold; }
        
        .data-table { width: 100%; border-collapse: collapse; margin-bottom: 30px; }
        .data-table th, .data-table td { border: 1px solid #666; padding: 8px; text-align: left; vertical-align: top; }
        .data-table th { background-color: #f0f0f0; font-weight: bold; text-align: center; }
        
        .signature-area { margin-top: 50px; float: right; width: 250px; text-align: center; }
        .signature-line { border-bottom: 1px solid #000; margin: 60px 0 5px 0; }
    </style>
</head>
<body>
    <div class="header">
        <h2>Laporan Penilaian Praktik Kerja Lapangan (PKL)</h2>
        <p>SMK SALAFIYAH PATI - TAHUN AJARAN <?= htmlspecialchars($TAHUN_AJARAN_AKTIF) ?></p>
    </div>

    <table class="info-table">
        <tr><td>Nama Industri / Instansi</td><td>: <?= htmlspecialchars($nama_dudi) ?></td></tr>
        <tr><td>Tanggal Cetak</td><td>: <?= date('d F Y') ?></td></tr>
    </table>

    <table class="data-table">
        <thead>
            <tr>
                <th width="5%">No</th>
                <th width="25%">Nama Siswa (NISN)</th>
                <th width="15%">Jurusan</th>
                <th width="12%">Nilai<br>Softskill</th>
                <th width="12%">Nilai<br>Hardskill</th>
                <th width="31%">Catatan Industri</th>
            </tr>
        </thead>
        <tbody>
            <?php if(empty($siswas)): ?>
            <tr><td colspan="6" style="text-align:center;">Belum ada siswa yang ditempatkan di DUDI ini.</td></tr>
            <?php else: $no=1; foreach($siswas as $s): 
                $belum_dinilai = is_null($s['nilai_softskill']);
            ?>
            <tr>
                <td style="text-align:center;"><?= $no++ ?></td>
                <td>
                    <strong><?= htmlspecialchars($s['name']) ?></strong><br>
                    <small><?= htmlspecialchars($s['nisn']) ?></small>
                </td>
                <td><?= htmlspecialchars($s['jurusan']) ?></td>
                <td style="text-align:center;"><?= $belum_dinilai ? '-' : $s['nilai_softskill'] ?></td>
                <td style="text-align:center;"><?= $belum_dinilai ? '-' : $s['nilai_hardskill'] ?></td>
                <td><?= $belum_dinilai ? '<em>Belum dinilai</em>' : nl2br(htmlspecialchars($s['catatan_industri'])) ?></td>
            </tr>
            <?php endforeach; endif; ?>
        </tbody>
    </table>

    <div class="signature-area">
        <p>Pati, <?= date('d F Y') ?><br>Pimpinan / Instruktur Industri,</p>
        <div class="signature-line"></div>
        <strong><?= htmlspecialchars($nama_dudi) ?></strong>
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

$filename = "Penilaian_PKL_DUDI_" . str_replace(' ', '_', $nama_dudi) . ".pdf";
$dompdf->stream($filename, ["Attachment" => true]);
