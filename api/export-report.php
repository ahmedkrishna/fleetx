<?php
require_once __DIR__ . '/../config.php';
requireLogin();

$type = $_GET['type'] ?? '';
$format = $_GET['format'] ?? 'csv';
$user_id = (int)getUserId();
$role = getUserRole();

if ($format === 'pdf' && $type === 'buyer_purchases' && isset($_GET['auction_id'])) {
    header('Location: /api/invoice.php?auction_id=' . (int)$_GET['auction_id'] . '&format=pdf');
    exit;
}

if (!$db_connected) {
    http_response_code(503);
    die('Database unavailable');
}

header('Content-Type: text/csv; charset=utf-8');
header('Content-Disposition: attachment; filename="fleetx-export-' . date('Ymd') . '.csv"');
echo "\xEF\xBB\xBF";

$out = fopen('php://output', 'w');

if ($type === 'seller_payouts' && in_array($role, ['seller', 'admin'], true)) {
    $company_id = 0;
    if ($role === 'seller') {
        $co = getSellerCompany($conn, $user_id);
        $company_id = (int)($co['id'] ?? 0);
    } else {
        $company_id = (int)($_GET['seller_id'] ?? 0);
    }
    fputcsv($out, ['رقم العملية', 'التاريخ', 'المركبة', 'مبلغ البيع', 'العمولة', 'صافي المبلغ', 'الحالة']);
    if ($company_id > 0) {
        $stmt = $conn->prepare("SELECT t.*, v.make, v.model, v.year FROM transactions t JOIN auctions a ON t.auction_id=a.id JOIN vehicles v ON a.vehicle_id=v.id WHERE t.seller_id=? ORDER BY t.created_at DESC");
        $stmt->bind_param('i', $company_id);
        $stmt->execute();
        $res = $stmt->get_result();
        while ($r = $res->fetch_assoc()) {
            fputcsv($out, [
                'TXN-' . $r['id'],
                $r['created_at'],
                $r['make'] . ' ' . $r['model'] . ' ' . $r['year'],
                $r['sale_price'],
                $r['platform_fee'],
                $r['seller_payout'],
                $r['payment_status'],
            ]);
        }
    }
    fclose($out);
    exit;
}

if ($type === 'seller_bids' && in_array($role, ['seller', 'admin'], true)) {
    $company_id = 0;
    if ($role === 'seller') {
        $co = getSellerCompany($conn, $user_id);
        $company_id = (int)($co['id'] ?? 0);
    }
    fputcsv($out, ['المزاد', 'المزايد', 'المبلغ', 'التاريخ', 'الحالة']);
    if ($company_id > 0) {
        $stmt = $conn->prepare("
            SELECT a.title, u.full_name, b.amount, b.created_at, a.status
            FROM bids b
            JOIN auctions a ON b.auction_id=a.id
            JOIN users u ON b.user_id=u.id
            WHERE a.seller_id=?
            ORDER BY b.created_at DESC
        ");
        $stmt->bind_param('i', $company_id);
        $stmt->execute();
        $res = $stmt->get_result();
        while ($r = $res->fetch_assoc()) {
            fputcsv($out, [$r['title'], $r['full_name'], $r['amount'], $r['created_at'], $r['status']]);
        }
    }
    fclose($out);
    exit;
}

if ($type === 'buyer_bids' && in_array($role, ['buyer', 'admin'], true)) {
    fputcsv($out, ['المزاد', 'المركبة', 'مزايدتي', 'السعر الحالي', 'الحالة', 'التاريخ']);
    $stmt = $conn->prepare("
        SELECT a.title, CONCAT(v.make,' ',v.model,' ',v.year) as vehicle, b.amount, a.current_price, a.status, b.created_at
        FROM bids b
        JOIN auctions a ON b.auction_id=a.id
        JOIN vehicles v ON a.vehicle_id=v.id
        WHERE b.user_id=?
        ORDER BY b.created_at DESC
    ");
    $stmt->bind_param('i', $user_id);
    $stmt->execute();
    $res = $stmt->get_result();
    while ($r = $res->fetch_assoc()) {
        fputcsv($out, [$r['title'], $r['vehicle'], $r['amount'], $r['current_price'], $r['status'], $r['created_at']]);
    }
    fclose($out);
    exit;
}

if ($type === 'buyer_purchases' && in_array($role, ['buyer', 'admin'], true)) {
    fputcsv($out, ['المزاد', 'المركبة', 'سعر الشراء', 'حالة الدفع', 'التاريخ']);
    $stmt = $conn->prepare("
        SELECT a.title, CONCAT(v.make,' ',v.model,' ',v.year) as vehicle, t.sale_price, t.payment_status, t.created_at
        FROM transactions t
        JOIN auctions a ON t.auction_id=a.id
        JOIN vehicles v ON a.vehicle_id=v.id
        WHERE t.buyer_id=?
        ORDER BY t.created_at DESC
    ");
    $stmt->bind_param('i', $user_id);
    $stmt->execute();
    $res = $stmt->get_result();
    while ($r = $res->fetch_assoc()) {
        fputcsv($out, [$r['title'], $r['vehicle'], $r['sale_price'], $r['payment_status'], $r['created_at']]);
    }
    fclose($out);
    exit;
}

http_response_code(400);
fputcsv($out, ['خطأ', 'نوع التصدير غير مدعوم']);
fclose($out);