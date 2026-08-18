<?php
require_once '../includes/config.php';
checkLogin();

header('Content-Type: application/json');
$me = $_SESSION['user_id'];
$role = getRole();
$action = $_GET['action'] ?? $_POST['action'] ?? 'count';

if (!$conn) { echo json_encode(['error' => 'DB tidak tersedia']); exit; }

// ─── COUNT ───────────────────────────────────────────────────────────────────
if ($action === 'count') {
    $stmt = $conn->prepare("
        SELECT COUNT(*) FROM notifikasi
        WHERE is_read = 0
          AND (id_user = ? OR (id_user IS NULL AND (target_role = ? OR target_role = 'all')))
    ");
    $stmt->bind_param("is", $me, $role);
    $stmt->execute();
    $count = $stmt->get_result()->fetch_row()[0];
    echo json_encode(['count' => (int)$count]);
    exit;
}

// ─── LIST ─────────────────────────────────────────────────────────────────────
if ($action === 'list') {
    $stmt = $conn->prepare("
        SELECT id, judul, pesan, is_read, created_at
        FROM notifikasi
        WHERE id_user = ? OR (id_user IS NULL AND (target_role = ? OR target_role = 'all'))
        ORDER BY created_at DESC
        LIMIT 20
    ");
    $stmt->bind_param("is", $me, $role);
    $stmt->execute();
    $rows = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    echo json_encode($rows);
    exit;
}

// ─── READ ONE ─────────────────────────────────────────────────────────────────
if ($action === 'read' && $_SERVER['REQUEST_METHOD'] === 'POST') {
    $id = (int)($_POST['id'] ?? 0);
    $stmt = $conn->prepare("UPDATE notifikasi SET is_read = 1 WHERE id = ? AND (id_user = ? OR id_user IS NULL)");
    $stmt->bind_param("ii", $id, $me);
    $stmt->execute();
    echo json_encode(['ok' => true]);
    exit;
}

// ─── READ ALL ────────────────────────────────────────────────────────────────
if ($action === 'read_all' && $_SERVER['REQUEST_METHOD'] === 'POST') {
    $stmt = $conn->prepare("
        UPDATE notifikasi SET is_read = 1
        WHERE is_read = 0
          AND (id_user = ? OR (id_user IS NULL AND (target_role = ? OR target_role = 'all')))
    ");
    $stmt->bind_param("is", $me, $role);
    $stmt->execute();
    echo json_encode(['ok' => true]);
    exit;
}

echo json_encode(['error' => 'action tidak dikenal']);
