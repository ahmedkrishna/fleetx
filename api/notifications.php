<?php
/**
 * api/notifications.php
 * FleetX — Real-time Notifications API
 * Returns JSON notifications for the current logged-in user
 * Also handles mark-as-read actions
 *
 * DB columns: id, user_id, type, title, message, is_read, link, created_at
 */
require_once '../config.php';
header('Content-Type: application/json; charset=UTF-8');

if (!isLoggedIn()) {
    echo json_encode(['success' => false, 'error' => 'غير مصرح']);
    exit;
}

$user_id = (int)$_SESSION['user_id'];
$action  = $_GET['action'] ?? 'list';

// ── Mark all as read ─────────────────────────────────────
if ($action === 'mark_read') {
    if ($db_connected) {
        $stmt = $conn->prepare("UPDATE notifications SET is_read = 1 WHERE user_id = ?");
        $stmt->bind_param('i', $user_id);
        $stmt->execute();
    }
    echo json_encode(['success' => true]);
    exit;
}

// ── Mark single notification as read ─────────────────────
if ($action === 'mark_one' && isset($_GET['id'])) {
    $nid = (int)$_GET['id'];
    if ($db_connected) {
        $stmt = $conn->prepare("UPDATE notifications SET is_read = 1 WHERE id = ? AND user_id = ?");
        $stmt->bind_param('ii', $nid, $user_id);
        $stmt->execute();
    }
    echo json_encode(['success' => true]);
    exit;
}

// ── List notifications ────────────────────────────────────
$notifications = [];
$unread_count  = 0;

if ($db_connected) {
    // Fetch last 10 notifications — using correct DB column names: message, link
    $stmt = $conn->prepare("
        SELECT id, type, title, message, is_read, link, created_at
        FROM notifications
        WHERE user_id = ?
        ORDER BY created_at DESC
        LIMIT 10
    ");
    $stmt->bind_param('i', $user_id);
    $stmt->execute();
    $res = $stmt->get_result();
    while ($row = $res->fetch_assoc()) {
        // Map DB columns to API response keys for the frontend
        $notifications[] = [
            'id'         => (int)$row['id'],
            'type'       => $row['type'],
            'title'      => $row['title'],
            'body'       => $row['message'] ?? '',
            'is_read'    => (int)$row['is_read'],
            'link'       => $row['link'] ?? '',
            'created_at' => $row['created_at'],
        ];
    }

    // Count unread
    $cstmt = $conn->prepare("SELECT COUNT(*) FROM notifications WHERE user_id = ? AND is_read = 0");
    $cstmt->bind_param('i', $user_id);
    $cstmt->execute();
    $cstmt->bind_result($unread_count);
    $cstmt->fetch();
    $cstmt->close();

} else {
    // Fallback mock data
    $notifications = [
        ['id'=>1,'type'=>'outbid','title'=>'تجاوزت مزايدتك','body'=>'قام شخص آخر بمزايدة أعلى على هوندا CR-V 2022 بمبلغ 55,500 ر.س','is_read'=>0,'link'=>'/auction-live.php?id=12','created_at'=>date('Y-m-d H:i:s', strtotime('-5 minutes'))],
        ['id'=>2,'type'=>'system','title'=>'تمت الموافقة على سيارتك','body'=>'تم اعتماد تويوتا كامري 2022 وهي الآن جاهزة للنشر في المزاد','is_read'=>0,'link'=>'/seller.php?section=fleet','created_at'=>date('Y-m-d H:i:s', strtotime('-1 hour'))],
        ['id'=>3,'type'=>'auction_won','title'=>'🎉 فزت بالمزاد!','body'=>'تهانينا! فزت بمزاد هيونداي إلنترا 2023 بمبلغ 68,000 ر.س','is_read'=>1,'link'=>'/checkout.php?id=7','created_at'=>date('Y-m-d H:i:s', strtotime('-1 day'))],
        ['id'=>4,'type'=>'system','title'=>'طلب فحص جديد','body'=>'تم إرسال تويوتا لاند كروزر 2021 للفحص','is_read'=>1,'link'=>'/seller.php?section=reports','created_at'=>date('Y-m-d H:i:s', strtotime('-2 days'))],
        ['id'=>5,'type'=>'payment','title'=>'تم تحويل المبلغ','body'=>'تم تحويل 85,000 ر.س إلى حسابكم البنكي المسجل','is_read'=>1,'link'=>'/seller.php?section=payouts','created_at'=>date('Y-m-d H:i:s', strtotime('-3 days'))],
    ];
    $unread_count = 2;
}

// Format relative time
function timeAgo($datetime) {
    $diff = time() - strtotime($datetime);
    if ($diff < 60)    return 'الآن';
    if ($diff < 3600)  return 'قبل ' . floor($diff/60) . ' دقيقة';
    if ($diff < 86400) return 'قبل ' . floor($diff/3600) . ' ساعة';
    if ($diff < 604800) return 'قبل ' . floor($diff/86400) . ' يوم';
    return date('Y/m/d', strtotime($datetime));
}

foreach ($notifications as &$n) {
    $n['time_ago'] = timeAgo($n['created_at']);
}

echo json_encode([
    'success'       => true,
    'unread_count'  => (int)$unread_count,
    'notifications' => $notifications,
]);
