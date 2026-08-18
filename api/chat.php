<?php
require_once '../includes/config.php';
checkLogin();

header('Content-Type: application/json');
$me = (int)$_SESSION['user_id'];
$action = $_GET['action'] ?? $_POST['action'] ?? '';

if (!$conn) { echo json_encode(['error' => 'DB tidak tersedia']); exit; }

// ─── CONTACTS ─────────────────────────────────────────────────────────────────
// Semua user yang pernah berinteraksi (punya pesan), plus semua user lain
if ($action === 'contacts') {
    // Ambil semua user kecuali diri sendiri, beserta jumlah pesan belum dibaca dari mereka
    $stmt = $conn->prepare("
        SELECT
            u.id,
            u.name,
            u.role,
            u.foto,
            COALESCE(unread.cnt, 0) AS unread_count,
            last_msg.message AS last_message,
            last_msg.created_at AS last_at
        FROM users u
        LEFT JOIN (
            SELECT from_user_id, COUNT(*) AS cnt
            FROM chat_messages
            WHERE to_user_id = ? AND is_read = 0
            GROUP BY from_user_id
        ) unread ON unread.from_user_id = u.id
        LEFT JOIN (
            SELECT m1.message, m1.created_at,
                   CASE WHEN m1.from_user_id = ? THEN m1.to_user_id ELSE m1.from_user_id END AS partner_id
            FROM chat_messages m1
            INNER JOIN (
                SELECT MAX(id) as max_id
                FROM chat_messages
                WHERE from_user_id = ? OR to_user_id = ?
                GROUP BY CASE WHEN from_user_id = ? THEN to_user_id ELSE from_user_id END
            ) m2 ON m1.id = m2.max_id
        ) last_msg ON last_msg.partner_id = u.id
        WHERE u.id != ?
        ORDER BY last_at DESC, u.name ASC
    ");
    $stmt->bind_param("iiiiii", $me, $me, $me, $me, $me, $me);
    $stmt->execute();
    $rows = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    echo json_encode($rows);
    exit;
}

// ─── MESSAGES (polling) ────────────────────────────────────────────────────────
if ($action === 'messages') {
    $with = (int)($_GET['with'] ?? 0);
    $after = (int)($_GET['after'] ?? 0); // last message id untuk efisiensi polling

    if (!$with) { echo json_encode([]); exit; }

    // Tandai pesan dari 'with' ke 'me' sebagai dibaca
    $upd = $conn->prepare("UPDATE chat_messages SET is_read = 1 WHERE from_user_id = ? AND to_user_id = ? AND is_read = 0");
    $upd->bind_param("ii", $with, $me);
    $upd->execute();

    if ($after > 0) {
        // Hanya ambil pesan baru (untuk polling)
        $stmt = $conn->prepare("
            SELECT id, from_user_id, to_user_id, message, is_read, created_at
            FROM chat_messages
            WHERE ((from_user_id = ? AND to_user_id = ?) OR (from_user_id = ? AND to_user_id = ?))
              AND id > ?
            ORDER BY id ASC
        ");
        $stmt->bind_param("iiiii", $me, $with, $with, $me, $after);
    } else {
        // Load awal: 50 pesan terakhir
        $stmt = $conn->prepare("
            SELECT id, from_user_id, to_user_id, message, is_read, created_at
            FROM chat_messages
            WHERE (from_user_id = ? AND to_user_id = ?) OR (from_user_id = ? AND to_user_id = ?)
            ORDER BY id DESC
            LIMIT 50
        ");
        $stmt->bind_param("iiii", $me, $with, $with, $me);
    }

    $stmt->execute();
    $rows = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    if ($after <= 0) $rows = array_reverse($rows); // kronologis
    echo json_encode($rows);
    exit;
}

// ─── SEND ─────────────────────────────────────────────────────────────────────
if ($action === 'send' && $_SERVER['REQUEST_METHOD'] === 'POST') {
    $to  = (int)($_POST['to_user_id'] ?? 0);
    $msg = trim($_POST['message'] ?? '');

    if (!$to || $msg === '' || $to === $me) {
        echo json_encode(['error' => 'Data tidak valid']); exit;
    }
    if (mb_strlen($msg) > 2000) {
        echo json_encode(['error' => 'Pesan terlalu panjang']); exit;
    }

    // Pastikan user tujuan ada
    $chk = $conn->prepare("SELECT id FROM users WHERE id = ?");
    $chk->bind_param("i", $to);
    $chk->execute();
    if (!$chk->get_result()->fetch_row()) {
        echo json_encode(['error' => 'User tidak ditemukan']); exit;
    }

    $stmt = $conn->prepare("INSERT INTO chat_messages (from_user_id, to_user_id, message) VALUES (?, ?, ?)");
    $stmt->bind_param("iis", $me, $to, $msg);
    $stmt->execute();
    $new_id = $conn->insert_id;

    // --- KIRIM WEB PUSH NOTIFICATION ---
    require_once '../includes/push.php';
    $sender_name = $_SESSION['name'] ?? 'Seseorang';
    // Hanya ambil text awal untuk preview
    $preview = mb_strlen($msg) > 40 ? mb_substr($msg, 0, 40) . '...' : $msg;
    sendWebPush($to, "Pesan baru dari $sender_name", $preview, "/chat/index.php");

    echo json_encode(['ok' => true, 'id' => $new_id, 'created_at' => date('Y-m-d H:i:s')]);
    exit;
}

// ─── UNREAD COUNT ─────────────────────────────────────────────────────────────
if ($action === 'unread_count') {
    $stmt = $conn->prepare("SELECT COUNT(*) FROM chat_messages WHERE to_user_id = ? AND is_read = 0");
    $stmt->bind_param("i", $me);
    $stmt->execute();
    $count = $stmt->get_result()->fetch_row()[0];
    echo json_encode(['count' => (int)$count]);
    exit;
}

echo json_encode(['error' => 'action tidak dikenal']);
