<?php
require_once '../includes/config.php';
require_once '../vendor/autoload.php';

use Minishlink\WebPush\WebPush;
use Minishlink\WebPush\Subscription;

/**
 * Mengirim Web Push Notification
 * 
 * @param array $user_ids Array of user ID yang akan dikirim (bisa satu atau banyak)
 * @param string $title Judul Notifikasi
 * @param string $message Isi Pesan
 * @param string $url URL tujuan jika notif di klik
 * @return array Hasil pengiriman (sukses/gagal)
 */
function sendWebPush($user_ids, $title, $message, $url = '/') {
    global $conn;
    
    if (empty($user_ids)) return [];
    if (!is_array($user_ids)) $user_ids = [$user_ids];

    // Ambil semua subscription milik user_ids
    $in  = str_repeat('?,', count($user_ids) - 1) . '?';
    $sql = "SELECT id, endpoint, p256dh, auth FROM push_subscriptions WHERE user_id IN ($in)";
    $stmt = $conn->prepare($sql);
    $types = str_repeat('i', count($user_ids));
    $stmt->bind_param($types, ...$user_ids);
    $stmt->execute();
    $subs = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);

    if (empty($subs)) return ['status' => 'no_subscriptions'];

    // Siapkan otentikasi VAPID
    $auth = [
        'VAPID' => [
            'subject' => 'mailto:admin@sipkl.com', // ganti dengan email valid admin
            'publicKey' => VAPID_PUBLIC_KEY,
            'privateKey' => VAPID_PRIVATE_KEY,
        ],
    ];

    $webPush = new WebPush($auth);
    $payload = json_encode([
        'title' => $title,
        'body' => $message,
        'url' => BASE_URL . ltrim($url, '/')
    ]);

    $results = [];
    $toDelete = [];

    // Queue semua notifikasi
    foreach ($subs as $sub) {
        $subscription = Subscription::create([
            'endpoint' => $sub['endpoint'],
            'publicKey' => $sub['p256dh'],
            'authToken' => $sub['auth'],
        ]);
        
        $webPush->queueNotification($subscription, $payload);
    }

    // Kirim & proses hasilnya
    foreach ($webPush->flush() as $report) {
        $endpoint = $report->getRequest()->getUri()->__toString();
        
        if ($report->isSuccess()) {
            $results[] = ['endpoint' => $endpoint, 'success' => true];
        } else {
            $results[] = ['endpoint' => $endpoint, 'success' => false, 'reason' => $report->getReason()];
            // Jika expired/unsubscribed, hapus dari DB
            if ($report->isSubscriptionExpired()) {
                $toDelete[] = $endpoint;
            }
        }
    }

    // Hapus subscription yang sudah mati
    if (!empty($toDelete)) {
        $inDel = str_repeat('?,', count($toDelete) - 1) . '?';
        $delStmt = $conn->prepare("DELETE FROM push_subscriptions WHERE endpoint IN ($inDel)");
        $delTypes = str_repeat('s', count($toDelete));
        $delStmt->bind_param($delTypes, ...$toDelete);
        $delStmt->execute();
    }

    return ['status' => 'sent', 'details' => $results];
}
