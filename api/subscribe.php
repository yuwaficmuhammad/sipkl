<?php
require_once '../includes/config.php';
checkLogin();

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['error' => 'Method not allowed']);
    exit;
}

$input = json_decode(file_get_contents('php://input'), true);

if (!isset($input['endpoint']) || !isset($input['keys']['p256dh']) || !isset($input['keys']['auth'])) {
    echo json_encode(['error' => 'Invalid subscription data']);
    exit;
}

$user_id  = (int)$_SESSION['user_id'];
$endpoint = $input['endpoint'];
$p256dh   = $input['keys']['p256dh'];
$auth     = $input['keys']['auth'];

// Insert or Ignore jika endpoint sudah ada (karena user bisa punya banyak perangkat, 1 endpoint unik per perangkat)
// Tetapi MySQL unik kita set di endpoint, jadi kita insert ignore
$stmt = $conn->prepare("
    INSERT IGNORE INTO push_subscriptions (user_id, endpoint, p256dh, auth) 
    VALUES (?, ?, ?, ?)
");
$stmt->bind_param("isss", $user_id, $endpoint, $p256dh, $auth);

if ($stmt->execute()) {
    echo json_encode(['success' => true]);
} else {
    echo json_encode(['error' => 'Database error']);
}
