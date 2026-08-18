<?php
session_start();

// Konfigurasi Database
$host = '127.0.0.1';
$user = 'root';
$pass = ''; // Sesuaikan jika menggunakan password (misal MAMP: root)
$dbname = 'pkl_management';

// Koneksi Database dengan penanganan error
try {
    mysqli_report(MYSQLI_REPORT_STRICT | MYSQLI_REPORT_ERROR);
    $conn = new mysqli($host, $user, $pass, $dbname);
} catch (mysqli_sql_exception $e) {
    // Jika database belum dibuat, set $conn ke null (untuk keperluan testing UI/Bypass login)
    $conn = null;
    $db_error = $e->getMessage();
}

// Menentukan BASE_URL secara dinamis
$app_path = dirname(__DIR__);
$doc_root = $_SERVER['DOCUMENT_ROOT'];
$base_path = str_replace(rtrim($doc_root, '/\\'), '', $app_path);
$base_path = str_replace('\\', '/', $base_path);
$base_path = empty($base_path) ? '/' : $base_path . '/';
define('BASE_URL', $base_path);

// --- KONFIGURASI ABSENSI ---

// Fungsi Menghitung Jarak (Haversine) dalam Meter
function haversineDistance($latlong1, $latlong2) {
    if(!$latlong1 || !$latlong2) return null;
    $l1 = explode(',', $latlong1);
    $l2 = explode(',', $latlong2);
    if(count($l1) != 2 || count($l2) != 2) return null;
    
    $lat1 = floatval(trim($l1[0]));
    $lon1 = floatval(trim($l1[1]));
    $lat2 = floatval(trim($l2[0]));
    $lon2 = floatval(trim($l2[1]));
    
    $earth_radius = 6371000; // dalam meter
    
    $dLat = deg2rad($lat2 - $lat1);
    $dLon = deg2rad($lon2 - $lon1);
    
    $a = sin($dLat/2) * sin($dLat/2) + cos(deg2rad($lat1)) * cos(deg2rad($lat2)) * sin($dLon/2) * sin($dLon/2);
    $c = 2 * asin(sqrt($a));
    return round($earth_radius * $c);
}

// Fungsi Helper
function checkLogin() {
    if(!isset($_SESSION['user_id'])) {
        header("Location: " . BASE_URL . "auth/login.php");
        exit;
    }
}

function getRole() {
    return $_SESSION['role'] ?? null;
}

// CSRF Token Generator & Validator
function csrf_token() {
    if(empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

function csrf_check() {
    $token = $_POST['csrf_token'] ?? '';
    if(!hash_equals($_SESSION['csrf_token'] ?? '', $token)) {
        http_response_code(403);
        die('Permintaan tidak valid (CSRF). Silakan kembali dan coba lagi.');
    }
}

function csrf_field() {
    return '<input type="hidden" name="csrf_token" value="' . htmlspecialchars(csrf_token()) . '">';
}

// Data// Master Timeline (Fallback Default jika DB tidak tersedia)
$TIMELINE = [
    'gate_1' => '2026-08-31',
    'gate_2' => '2026-09-30',
    'gate_3' => '2026-10-31',
    'gate_4' => '2026-11-30',
    'pkl_eks_start' => '2026-12-01',
    'pkl_eks_end'   => '2027-02-18'
];

// Ambil Pengaturan Dinamis dari Database
if($conn) {
    $res = $conn->query("SELECT setting_key, setting_value FROM settings");
    if($res) {
        while($row = $res->fetch_assoc()) {
            $TIMELINE[$row['setting_key']] = $row['setting_value'];
        }
    }
}

// Definisikan Profil Lembaga
define('SEKOLAH_NAMA', $TIMELINE['sekolah_nama'] ?? 'SMK Salafiyah Pati');
define('SEKOLAH_ALAMAT', $TIMELINE['sekolah_alamat'] ?? 'Kajen, Margoyoso, Pati, Jawa Tengah');
define('SEKOLAH_LATLONG', $TIMELINE['sekolah_latlong'] ?? '-6.6669,111.0263'); // Fallback ke Pati

// Ambil Tahun Ajaran Aktif dari Database Master
$TAHUN_AJARAN_AKTIF = '2026/2027'; // Fallback
if($conn) {
    $res = $conn->query("SELECT nama FROM tahun_ajaran WHERE is_active = 1 LIMIT 1");
    if($res && $res->num_rows > 0) {
        $row = $res->fetch_assoc();
        $TAHUN_AJARAN_AKTIF = $row['nama'];
    }
}
?>
