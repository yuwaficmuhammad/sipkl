<?php
require_once '../includes/config.php';
checkLogin();

if(getRole() !== 'admin') {
    die("Akses ditolak. Hanya untuk Admin.");
}

if(!$conn) {
    die("Database connection error");
}

$ta = $_GET['ta'] ?? $TAHUN_AJARAN_AKTIF;

// Prepare headers for CSV download
header('Content-Type: text/csv; charset=utf-8');
header('Content-Disposition: attachment; filename=Laporan_PKL_TA_' . str_replace('/', '_', $ta) . '.csv');

$output = fopen('php://output', 'w');

// Title rows
fputcsv($output, ['REKAPITULASI DATA PKL SMK SALAFIYAH PATI']);
fputcsv($output, ['Tahun Ajaran', $ta]);
fputcsv($output, ['Tanggal Cetak', date('Y-m-d H:i:s')]);
fputcsv($output, []);

// Section 1: Proyek Internal
fputcsv($output, ['1. DATA PROYEK INTERNAL']);
fputcsv($output, ['Kode Proyek', 'Judul Proyek', 'Klien', 'Guru Pembimbing', 'Status Pencapaian']);

$stmt = $conn->prepare("
    SELECT p.kode_proyek, p.judul_proyek, p.nama_klien, u.name as nama_guru, p.status_gate 
    FROM proyek_internal p 
    JOIN users u ON p.id_pembimbing_sekolah = u.id 
    WHERE p.tahun_ajaran = ?
");
$stmt->bind_param("s", $ta);
$stmt->execute();
$res_p = $stmt->get_result();

while($row = $res_p->fetch_assoc()) {
    fputcsv($output, [
        $row['kode_proyek'], 
        $row['judul_proyek'], 
        $row['nama_klien'], 
        $row['nama_guru'], 
        'Lulus Gate ' . $row['status_gate']
    ]);
}

fputcsv($output, []);
fputcsv($output, []);

// Section 2: Siswa & Nilai DUDIKA
fputcsv($output, ['2. DATA SISWA & PENILAIAN INDUSTRI (DUDIKA)']);
fputcsv($output, ['NISN', 'Nama Siswa', 'Jurusan', 'Industri Pembimbing', 'Nilai Softskill', 'Nilai Hardskill', 'Catatan Industri']);

$stmt_s = $conn->prepare("
    SELECT u.username, u.name as nama_siswa, u.jurusan, d.name as nama_dudi, n.nilai_softskill, n.nilai_hardskill, n.catatan_industri 
    FROM users u 
    LEFT JOIN penilaian_dudi n ON u.id = n.id_siswa 
    LEFT JOIN users d ON n.id_dudika = d.id 
    WHERE u.role = 'siswa' AND u.tahun_ajaran = ?
");
$stmt_s->bind_param("s", $ta);
$stmt_s->execute();
$res_s = $stmt_s->get_result();

while($row = $res_s->fetch_assoc()) {
    fputcsv($output, [
        $row['username'], 
        $row['nama_siswa'], 
        $row['jurusan'], 
        $row['nama_dudi'] ?? '-', 
        $row['nilai_softskill'] ?? 'Belum Dinilai', 
        $row['nilai_hardskill'] ?? 'Belum Dinilai', 
        str_replace(array("\r", "\n"), " ", $row['catatan_industri'] ?? '-')
    ]);
}

fclose($output);
exit;
