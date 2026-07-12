<?php
require_once 'config.php';
requireLogin();

if ($_SERVER['REQUEST_METHOD'] !== 'POST' || empty($_POST['confirm'])) {
    header('Location: /auctions.php');
    exit;
}

$ref = trim($_POST['ref'] ?? '');
$uid = (int)getUserId();
if (!$ref || !$db_connected) {
    fleetx_set_toast('فشلت عملية الدفع', 'error');
    header('Location: /auctions.php');
    exit;
}

$stmt = $conn->prepare("SELECT * FROM payment_intents WHERE reference=? AND buyer_id=? AND status='pending' LIMIT 1");
$stmt->bind_param('si', $ref, $uid);
$stmt->execute();
$intent = $stmt->get_result()->fetch_assoc();
if (!$intent) {
    fleetx_set_toast('جلسة الدفع غير صالحة', 'error');
    header('Location: /buyer.php?section=purchases');
    exit;
}

$purpose = $intent['purpose'] ?? 'purchase';
$is_wallet = ($purpose === 'wallet_topup' || (int)($intent['auction_id'] ?? 0) === 0);

if ($is_wallet) {
    $amount = (float)$intent['amount'];
    $method = $intent['method'] ?? 'mada';
    $conn->begin_transaction();
    try {
        $conn->query("UPDATE payment_intents SET status='completed', completed_at=NOW() WHERE reference='" . $conn->real_escape_string($ref) . "'");
        $wstmt = $conn->prepare('UPDATE users SET wallet_balance = wallet_balance + ? WHERE id = ?');
        $wstmt->bind_param('di', $amount, $uid);
        $wstmt->execute();
        $_SESSION['wallet_balance'] = ($_SESSION['wallet_balance'] ?? 0) + $amount;
        notifyUser($conn, $uid, 'payment', 'تم شحن المحفظة ✓', 'تم إضافة ' . number_format($amount) . ' ر.س إلى محفظتك عبر ' . $method . '.', '/buyer.php?section=wallet', ['in_app', 'whatsapp', 'sms']);
        $conn->commit();
        fleetx_set_toast('تم شحن محفظتك بنجاح بمبلغ ' . number_format($amount) . ' ر.س');
        $back = getUserRole() === 'seller' ? '/seller.php?section=wallet&topup=1' : '/buyer.php?section=wallet&topup=1';
        header('Location: ' . $back);
        exit;
    } catch (Throwable $e) {
        $conn->rollback();
        $conn->query("UPDATE payment_intents SET status='failed' WHERE reference='" . $conn->real_escape_string($ref) . "'");
        fleetx_set_toast('حدث خطأ أثناء شحن المحفظة', 'error');
        header('Location: /wallet-topup.php');
        exit;
    }
}

$auction_id = (int)$intent['auction_id'];
$pay_total = (float)$intent['amount'];
$inspection_fee = (float)($intent['inspection_fee'] ?? 0);
$extra_services = json_decode($intent['extra_services'] ?? '[]', true) ?: [];

$astmt = $conn->prepare("SELECT a.*, v.make, v.model, v.year FROM auctions a JOIN vehicles v ON a.vehicle_id=v.id WHERE a.id=? LIMIT 1");
$astmt->bind_param('i', $auction_id);
$astmt->execute();
$vehicle = $astmt->get_result()->fetch_assoc();
if (!$vehicle) {
    fleetx_set_toast('المزاد غير موجود', 'error');
    header('Location: /auctions.php');
    exit;
}

$price = (float)($vehicle['sale_price'] ?: $vehicle['current_price']);
$conn->begin_transaction();
try {
    $conn->query("UPDATE payment_intents SET status='completed', completed_at=NOW() WHERE reference='" . $conn->real_escape_string($ref) . "'");

    $fee_pct = fleetx_platform_fee_percent($conn);
    $fee = $price * ($fee_pct / 100);
    $payout = $price - $fee;
    $extras_json = json_encode($extra_services, JSON_UNESCAPED_UNICODE);
    $vat_amt = round($price * 0.15, 2);
    $method = $intent['method'];

    $check = $conn->prepare('SELECT id FROM transactions WHERE auction_id=?');
    $check->bind_param('i', $auction_id);
    $check->execute();
    $tx_id = 0;
    if ($check->get_result()->num_rows > 0) {
        $upd = $conn->prepare("UPDATE transactions SET payment_status='paid', payment_method=?, payment_ref=?, paid_at=NOW(), inspection_fee=?, extra_services=?, vat_amount=? WHERE auction_id=?");
        $upd->bind_param('ssdsdi', $method, $ref, $inspection_fee, $extras_json, $vat_amt, $auction_id);
        $upd->execute();
        $tx_id = (int)$conn->query("SELECT id FROM transactions WHERE auction_id=$auction_id")->fetch_row()[0];
    } else {
        $ins = $conn->prepare("INSERT INTO transactions (auction_id, buyer_id, seller_id, sale_price, platform_fee, seller_payout, inspection_fee, extra_services, vat_amount, payment_method, payment_ref, payment_status, paid_at) VALUES (?,?,?,?,?,?,?,?,?,?,?,'paid',NOW())");
        $ins->bind_param('iiiddddsdss', $auction_id, $uid, (int)$vehicle['seller_id'], $price, $fee, $payout, $inspection_fee, $extras_json, $vat_amt, $method, $ref);
        $ins->execute();
        $tx_id = (int)$conn->insert_id;
    }
    if ($tx_id) fleetx_create_invoice($conn, $tx_id);

    $conn->query("UPDATE auctions SET status='ended', winner_id=$uid, sale_price=$price WHERE id=$auction_id");

    $car_name = $vehicle['title'] ?? ($vehicle['make'] . ' ' . $vehicle['model'] . ' ' . $vehicle['year']);
    $seller_user = $conn->query('SELECT user_id FROM seller_companies WHERE id=' . (int)$vehicle['seller_id'])->fetch_assoc();
    notifyUser($conn, $uid, 'payment', 'تم الدفع بنجاح ✓', 'تم دفع ' . formatPrice($price) . " لشراء {$car_name}. شكراً لثقتك بـ FleetX.", '/buyer.php?section=purchases', ['in_app', 'whatsapp', 'sms']);
    if ($seller_user) {
        notifyUser($conn, (int)$seller_user['user_id'], 'payment', 'تم الدفع! 💰', 'تم دفع مبلغ ' . formatPrice($price) . " لشراء {$car_name}", '/seller.php?section=payouts', ['in_app', 'whatsapp']);
    }

    $conn->commit();
    fleetx_set_toast('تمت عملية الدفع بنجاح عبر ' . $method . '!');
    header('Location: /buyer.php?section=purchases');
    exit;
} catch (Throwable $e) {
    $conn->rollback();
    $conn->query("UPDATE payment_intents SET status='failed' WHERE reference='" . $conn->real_escape_string($ref) . "'");
    fleetx_set_toast('حدث خطأ أثناء إتمام الدفع', 'error');
    header('Location: /checkout.php?id=' . $auction_id);
    exit;
}