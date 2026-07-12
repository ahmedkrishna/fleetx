<?php
/**
 * Integration layer — env-driven stubs for Nafath, Sanad, AutoData, SMS.
 * Set keys in config.local.php or server environment.
 */
if (!defined('NAFATH_API_URL')) define('NAFATH_API_URL', getenv('NAFATH_API_URL') ?: '');
if (!defined('NAFATH_API_KEY')) define('NAFATH_API_KEY', getenv('NAFATH_API_KEY') ?: '');
if (!defined('SANAD_API_URL')) define('SANAD_API_URL', getenv('SANAD_API_URL') ?: '');
if (!defined('SANAD_API_KEY')) define('SANAD_API_KEY', getenv('SANAD_API_KEY') ?: '');
if (!defined('AUTODATA_API_URL')) define('AUTODATA_API_URL', getenv('AUTODATA_API_URL') ?: '');
if (!defined('AUTODATA_API_KEY')) define('AUTODATA_API_KEY', getenv('AUTODATA_API_KEY') ?: '');
if (!defined('SMS_API_KEY')) define('SMS_API_KEY', getenv('SMS_API_KEY') ?: '');
if (!defined('WHATSAPP_API_TOKEN')) define('WHATSAPP_API_TOKEN', getenv('WHATSAPP_API_TOKEN') ?: '');

function fx_integration_log(string $channel, string $message, array $meta = []): void {
    $log_dir = __DIR__ . '/../logs';
    if (!is_dir($log_dir)) @mkdir($log_dir, 0755, true);
    $line = date('Y-m-d H:i:s') . " [$channel] $message";
    if ($meta) $line .= ' ' . json_encode($meta, JSON_UNESCAPED_UNICODE);
    @file_put_contents($log_dir . '/integrations.log', $line . "\n", FILE_APPEND);
}

function nafathRequestVerification(string $national_id, string $mobile): array {
    if (NAFATH_API_URL && NAFATH_API_KEY) {
        fx_integration_log('nafath', 'API call', ['national_id' => $national_id, 'mobile' => $mobile]);
        // Production: POST to NAFATH_API_URL with NAFATH_API_KEY
    }
    return [
        'success' => true,
        'mode' => NAFATH_API_URL ? 'live' : 'sandbox',
        'reference' => 'NAF-' . strtoupper(substr(md5($national_id . $mobile), 0, 10)),
        'message' => 'تم إرسال طلب التحقق عبر نفاذ',
    ];
}

function sanadCheckLimit(string $national_id): array {
    if (SANAD_API_URL && SANAD_API_KEY) {
        fx_integration_log('sanad', 'API call', ['national_id' => $national_id]);
    }
    return [
        'success' => true,
        'mode' => SANAD_API_URL ? 'live' : 'sandbox',
        'limit' => 500000,
    ];
}

function autodataFetchPrice(string $make, string $model, int $year, int $mileage = 0): ?array {
    if (AUTODATA_API_URL && AUTODATA_API_KEY) {
        fx_integration_log('autodata', 'API call', compact('make', 'model', 'year', 'mileage'));
        // Production: fetch from AutoData API
    }
    return null;
}

function smsSend(string $mobile, string $message): bool {
    if (SMS_API_KEY) {
        fx_integration_log('sms', 'API send', ['mobile' => $mobile]);
        // Production: Taqnyat / Unifonic
    }
    return sendSmsNotification($mobile, $message);
}

function whatsappSend(string $mobile, string $message, $conn = null, array $overrides = []): array {
    if (WHATSAPP_API_TOKEN) {
        fx_integration_log('whatsapp', 'API send', ['mobile' => $mobile]);
    }
    return sendWhatsAppNotification($mobile, $message, $conn, $overrides);
}

if (!defined('PAYMENT_GATEWAY_URL')) define('PAYMENT_GATEWAY_URL', getenv('PAYMENT_GATEWAY_URL') ?: '');

function paymentGatewayCreateIntent(mysqli $conn, int $auction_id, int $buyer_id, float $amount, string $method, array $meta = []): ?array {
    if (!fleetx_table_exists($conn, 'payment_intents')) return null;
    $ref = 'FXP-' . strtoupper(substr(md5(uniqid((string)$buyer_id, true)), 0, 12));
    $extras = json_encode($meta['extra_services'] ?? [], JSON_UNESCAPED_UNICODE);
    $insp = (float)($meta['inspection_fee'] ?? 0);
    $stmt = $conn->prepare('INSERT INTO payment_intents (reference, auction_id, buyer_id, amount, method, extra_services, inspection_fee) VALUES (?,?,?,?,?,?,?)');
    $stmt->bind_param('siidssd', $ref, $auction_id, $buyer_id, $amount, $method, $extras, $insp);
    if (!$stmt->execute()) return null;
    $return_url = SITE_URL . '/payment-return.php?ref=' . urlencode($ref);
    $cancel_url = SITE_URL . '/checkout.php?id=' . $auction_id . '&cancelled=1';
    if (PAYMENT_GATEWAY_URL) {
        fx_integration_log('payment', 'redirect live gateway', ['ref' => $ref, 'method' => $method]);
        $redirect = PAYMENT_GATEWAY_URL . '?ref=' . urlencode($ref) . '&amount=' . $amount . '&return=' . urlencode($return_url);
    } else {
        $redirect = '/payment-gateway.php?ref=' . urlencode($ref);
    }
    return ['reference' => $ref, 'redirect' => $redirect, 'return_url' => $return_url, 'cancel_url' => $cancel_url];
}

/** Wallet top-up payment intent (auction_id = 0, purpose = wallet_topup). */
function paymentGatewayCreateWalletIntent(mysqli $conn, int $buyer_id, float $amount, string $method): ?array {
    if (!fleetx_table_exists($conn, 'payment_intents') || $amount <= 0) return null;
    $ref = 'FXW-' . strtoupper(substr(md5(uniqid((string)$buyer_id, true)), 0, 12));
    $extras = '[]';
    $insp = 0.0;
    $auction_id = 0;
    $purpose = 'wallet_topup';

    $has_purpose = false;
    $col = $conn->query("SHOW COLUMNS FROM payment_intents LIKE 'purpose'");
    if ($col && $col->num_rows > 0) $has_purpose = true;

    if ($has_purpose) {
        $stmt = $conn->prepare('INSERT INTO payment_intents (reference, auction_id, buyer_id, amount, method, purpose, extra_services, inspection_fee) VALUES (?,?,?,?,?,?,?,?)');
        $stmt->bind_param('siidsssd', $ref, $auction_id, $buyer_id, $amount, $method, $purpose, $extras, $insp);
    } else {
        $stmt = $conn->prepare('INSERT INTO payment_intents (reference, auction_id, buyer_id, amount, method, extra_services, inspection_fee) VALUES (?,?,?,?,?,?,?)');
        $stmt->bind_param('siidssd', $ref, $auction_id, $buyer_id, $amount, $method, $extras, $insp);
    }
    if (!$stmt->execute()) return null;

    $return_url = SITE_URL . '/payment-return.php?ref=' . urlencode($ref);
    if (PAYMENT_GATEWAY_URL) {
        fx_integration_log('payment', 'wallet topup live gateway', ['ref' => $ref, 'amount' => $amount]);
        $redirect = PAYMENT_GATEWAY_URL . '?ref=' . urlencode($ref) . '&amount=' . $amount . '&return=' . urlencode($return_url);
    } else {
        $redirect = '/payment-gateway.php?ref=' . urlencode($ref);
    }
    return ['reference' => $ref, 'redirect' => $redirect, 'return_url' => $return_url, 'cancel_url' => '/wallet-topup.php?cancelled=1'];
}