<?php
require_once __DIR__ . '/../config.php';
requireLogin();

$auction_id = (int)($_GET['auction_id'] ?? 0);
$format = $_GET['format'] ?? 'html';
$user_id = (int)getUserId();

if (!$db_connected || !$auction_id) {
    http_response_code(400);
    die('معرف غير صالح');
}

$role = getUserRole();
$stmt = $conn->prepare("
    SELECT t.*, a.title, v.make, v.model, v.year,
           u.full_name as buyer_name, u.mobile as buyer_mobile, u.email as buyer_email,
           sc.company_name, sc.vat_number, sc.cr_number, sc.user_id as seller_user_id
    FROM transactions t
    JOIN auctions a ON t.auction_id=a.id
    JOIN vehicles v ON a.vehicle_id=v.id
    JOIN users u ON t.buyer_id=u.id
    JOIN seller_companies sc ON t.seller_id=sc.id
    WHERE t.auction_id=?
    LIMIT 1
");
$stmt->bind_param('i', $auction_id);
$stmt->execute();
$tx = $stmt->get_result()->fetch_assoc();
if (!$tx) {
    http_response_code(404);
    die('لم يتم العثور على المعاملة');
}
$allowed = ($role === 'admin')
    || ((int)$tx['buyer_id'] === $user_id)
    || ((int)$tx['seller_user_id'] === $user_id);
if (!$allowed) {
    http_response_code(403);
    die('غير مصرح');
}

$inv = null;
if (fleetx_table_exists($conn, 'invoices')) {
    $istmt = $conn->prepare('SELECT * FROM invoices WHERE transaction_id=? LIMIT 1');
    $istmt->bind_param('i', $tx['id']);
    $istmt->execute();
    $inv = $istmt->get_result()->fetch_assoc();
}
if (!$inv) {
    $created = fleetx_create_invoice($conn, (int)$tx['id']);
    if ($created) {
        $istmt = $conn->prepare('SELECT * FROM invoices WHERE transaction_id=? LIMIT 1');
        $istmt->bind_param('i', $tx['id']);
        $istmt->execute();
        $inv = $istmt->get_result()->fetch_assoc();
    }
}

$subtotal = (float)$tx['sale_price'];
$vat = (float)($tx['vat_amount'] ?? round($subtotal * 0.15, 2));
$total = $subtotal + $vat;
$inv_num = $inv['invoice_number'] ?? $tx['invoice_number'] ?? fleetx_generate_invoice_number();
$car = $tx['title'] ?: $tx['make'].' '.$tx['model'].' '.$tx['year'];

if ($format === 'pdf') {
    header('Content-Type: text/html; charset=utf-8');
    header('Content-Disposition: inline; filename="invoice-'.$inv_num.'.html"');
} else {
    header('Content-Type: text/html; charset=utf-8');
}
?>
<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
  <meta charset="UTF-8">
  <title>فاتورة <?= htmlspecialchars($inv_num) ?></title>
  <style>
    body{font-family:Tahoma,Arial,sans-serif;padding:40px;color:#1e293b}
    .head{display:flex;justify-content:space-between;border-bottom:2px solid #1bc976;padding-bottom:16px;margin-bottom:24px}
    table{width:100%;border-collapse:collapse;margin-top:20px}
    th,td{border:1px solid #e2e8f0;padding:10px;text-align:right}
    .total{font-size:20px;font-weight:bold;color:#1bc976}
    @media print{.no-print{display:none}}
  </style>
</head>
<body>
  <div class="head">
    <div><h1>FleetX</h1><p>فاتورة ضريبية مبسطة</p></div>
    <div style="text-align:left"><strong><?= htmlspecialchars($inv_num) ?></strong><br><?= date('Y-m-d') ?></div>
  </div>
  <p><strong>البائع:</strong> <?= sanitize($tx['company_name']) ?> | الرقم الضريبي: <?= sanitize($tx['vat_number'] ?? '—') ?></p>
  <p><strong>المشتري:</strong> <?= sanitize($tx['buyer_name']) ?> | <?= sanitize($tx['buyer_mobile']) ?></p>
  <table>
    <thead><tr><th>الوصف</th><th>المبلغ (ر.س)</th></tr></thead>
    <tbody>
      <tr><td><?= sanitize($car) ?></td><td><?= number_format($subtotal, 2) ?></td></tr>
      <tr><td>ضريبة القيمة المضافة 15%</td><td><?= number_format($vat, 2) ?></td></tr>
      <tr><td class="total">الإجمالي</td><td class="total"><?= number_format($total, 2) ?></td></tr>
    </tbody>
  </table>
  <?php if (!empty($inv['zatca_qr'])): ?>
  <p style="margin-top:24px;font-size:12px;color:#64748b;">رمز ZATCA (TLV): <code dir="ltr"><?= htmlspecialchars(substr($inv['zatca_qr'], 0, 48)) ?>...</code></p>
  <?php endif; ?>
  <button class="no-print" onclick="window.print()" style="margin-top:24px;padding:10px 20px;background:#1bc976;border:none;border-radius:8px;color:#fff;cursor:pointer;">طباعة / حفظ PDF</button>
</body>
</html>