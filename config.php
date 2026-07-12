<?php
// ============================================================
// FleetX — Car Auction Platform
// config.php - Master Configuration
// ============================================================
if (is_file(__DIR__ . '/config.local.php')) {
    require_once __DIR__ . '/config.local.php';
}

$fx_host = $_SERVER['HTTP_HOST'] ?? '';
$fx_is_local = in_array($fx_host, ['127.0.0.1', 'localhost'], true)
    || str_starts_with($fx_host, '127.0.0.1:')
    || str_starts_with($fx_host, 'localhost:');

if ($fx_is_local) {
    ini_set('display_errors', '1');
    error_reporting(E_ALL);
} else {
    ini_set('display_errors', '0');
    ini_set('log_errors', '1');
    error_reporting(E_ALL);
}

if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

// ── Database Credentials (override in config.local.php) ───
if (!defined('DB_HOST')) define('DB_HOST',    'localhost');
if (!defined('DB_USER')) define('DB_USER',    'u274391035_usr_BbBE85ay');
if (!defined('DB_PASS')) define('DB_PASS',    '*A7medfouad*');
if (!defined('DB_NAME')) define('DB_NAME',    'u274391035_db_BbBE85ay');
if (!defined('SITE_URL')) define('SITE_URL',   'https://mazadi.bearand.com');
if (!defined('SITE_NAME')) define('SITE_NAME',  'FleetX');
if (!defined('PLATFORM_FEE_PERCENT')) define('PLATFORM_FEE_PERCENT', 5);
define('FLEETX_CSS_VER', '126');

/** §5 stats background video — change URL here or override in config.local.php; empty = disabled */
if (!defined('FLEETX_STATS_BG_VIDEO')) {
    define('FLEETX_STATS_BG_VIDEO', '/assets/videos/hero-video1.mp4');
}

if (isset($_GET['lang']) && in_array($_GET['lang'], ['ar', 'en'], true)) {
    $_SESSION['fx_lang'] = $_GET['lang'];
}

function fleetx_lang(): string {
    return (($_SESSION['fx_lang'] ?? 'ar') === 'en') ? 'en' : 'ar';
}

function fleetx_t(string $key): string {
    static $map = [
        'nav_auctions' => ['ar' => 'المزادات الحية', 'en' => 'Live Auctions'],
        'nav_instant' => ['ar' => 'الشراء الفوري', 'en' => 'Instant Buy'],
        'nav_companies' => ['ar' => 'دليل الشركات', 'en' => 'Companies'],
        'nav_map' => ['ar' => 'خريطة المزادات', 'en' => 'Auction Map'],
        'nav_about' => ['ar' => 'كيف يعمل', 'en' => 'How It Works'],
        'nav_login' => ['ar' => 'دخول المنصة', 'en' => 'Sign In'],
        'nav_register' => ['ar' => 'سجل الآن', 'en' => 'Register'],
        'nav_dashboard' => ['ar' => 'لوحة التحكم', 'en' => 'Dashboard'],
        'guest_bid_login' => ['ar' => 'سجّل الدخول للمزايدة', 'en' => 'Sign in to bid'],
        'guest_fav_login' => ['ar' => 'سجّل الدخول لحفظ المفضلة', 'en' => 'Sign in to save favorites'],
        'lang_toggle' => ['ar' => 'EN', 'en' => 'عربي'],
    ];
    $lang = fleetx_lang();
    return $map[$key][$lang] ?? $key;
}

function fleetx_html_lang(): string {
    return fleetx_lang();
}

function fleetx_html_dir(): string {
    return fleetx_lang() === 'en' ? 'ltr' : 'rtl';
}

function fleetx_lang_toggle_url(): string {
    $next = fleetx_lang() === 'ar' ? 'en' : 'ar';
    $uri = $_SERVER['REQUEST_URI'] ?? '/index.php';
    $parts = parse_url($uri);
    $path = $parts['path'] ?? '/index.php';
    $query = [];
    if (!empty($parts['query'])) parse_str($parts['query'], $query);
    $query['lang'] = $next;
    return $path . '?' . http_build_query($query);
}

function fleetx_verify_otp(mysqli $conn, string $mobile, string $otp, string $purpose = 'login'): bool {
    $stmt = $conn->prepare("SELECT id, otp_code FROM otp_sessions WHERE mobile=? AND purpose=? AND is_used=0 AND expires_at > NOW() ORDER BY id DESC LIMIT 1");
    $stmt->bind_param('ss', $mobile, $purpose);
    $stmt->execute();
    $row = $stmt->get_result()->fetch_assoc();
    if (!$row || $row['otp_code'] !== $otp) return false;
    $conn->query('UPDATE otp_sessions SET is_used=1 WHERE id=' . (int)$row['id']);
    return true;
}

function fleetx_user_is_active(?array $user): bool {
    if (!$user) return false;
    return !isset($user['is_active']) || (int)$user['is_active'] === 1;
}

function fleetx_css_href(): string {
    return '/assets/css/fleetx.css?v=' . FLEETX_CSS_VER;
}

/** Show loading splash on public pages (skip admin/API/cron). */
function fleetx_show_splash(): bool {
    if (php_sapi_name() === 'cli') return false;
    $uri = $_SERVER['REQUEST_URI'] ?? '';
    $script = basename($_SERVER['PHP_SELF'] ?? '');
    $dir = basename(dirname($_SERVER['SCRIPT_NAME'] ?? ''));
    if ($dir === 'admin' || $dir === 'api' || $dir === 'cron' || $dir === 'tests') return false;
    if (str_contains($uri, '/admin/') || str_contains($uri, '/api/')) return false;
    $skip = ['payment-return.php', 'hotfix.php', 'migrate.php', 'migrate_requirements.php', 'seed.php'];
    return !in_array($script, $skip, true);
}

function fleetx_js_href(): string {
    return '/assets/js/fleetx.js?v=' . FLEETX_CSS_VER;
}

function fleetx_home_live_css_href(): string {
    return '/assets/css/home-live.css?v=' . FLEETX_CSS_VER;
}

function fleetx_polish_css_href(): string {
    return '/assets/css/homepage-polish.css?v=' . FLEETX_CSS_VER;
}

function fleetx_stats_bg_video_url(): string {
    return defined('FLEETX_STATS_BG_VIDEO') ? trim((string) FLEETX_STATS_BG_VIDEO) : '';
}

/** Default sub-page hero background — override in config.local.php */
if (!defined('FLEETX_PAGE_HERO_BG')) {
    define('FLEETX_PAGE_HERO_BG', 'https://images.unsplash.com/photo-1555215695-3004980ad54e?w=1600&q=80');
}

function fleetx_page_hero_bg_url(): string {
    return defined('FLEETX_PAGE_HERO_BG') ? trim((string) FLEETX_PAGE_HERO_BG) : '';
}

/** Per-page dark hero backgrounds — unique overlay image per sub-page */
function fleetx_subpage_hero_bg(string $page = ''): string {
    $page = $page ?: basename($_SERVER['PHP_SELF'] ?? '', '.php');
    $map = [
        'about' => 'https://images.unsplash.com/photo-1573164713988-8665fc963095?w=1600&q=80',
        'companies' => 'https://images.unsplash.com/photo-1469854523086-cc02fe5d8800?w=1600&q=80',
        'auctions' => 'https://images.unsplash.com/photo-1550355291-bbee04a92027?w=1600&q=80',
        'terms' => 'https://images.unsplash.com/photo-1450101499163-c8848c66ca85?w=1600&q=80',
        'map' => 'https://images.unsplash.com/photo-1508962914676-134849a727f0?w=1600&q=80',
        'buyer' => 'https://images.unsplash.com/photo-1618843479313-40f8afb4b4d8?w=1600&q=80',
        'seller' => 'https://images.unsplash.com/photo-1549317661-bd32c8ce0db2?w=1600&q=80',
        'inspector' => 'https://images.unsplash.com/photo-1486262715619-67b85e0b08d3?w=1600&q=80',
        'profile' => 'https://images.unsplash.com/photo-1503376712341-ea1925b4be40?w=1600&q=80',
        'wallet-topup' => 'https://images.unsplash.com/photo-1563013544-824ae1b704d3?w=1600&q=80',
        'company-profile' => 'https://images.unsplash.com/photo-1486406146926-c627a92ad1ab?w=1600&q=80',
        'event' => 'https://images.unsplash.com/photo-1555215695-3004980ad54e?w=1600&q=80',
        'vehicle-details' => 'https://images.unsplash.com/photo-1494976388531-d1058494cdd8?w=1600&q=80',
    ];
    return $map[$page] ?? fleetx_page_hero_bg_url();
}

if (!defined('GOOGLE_CLIENT_ID')) {
    define('GOOGLE_CLIENT_ID', getenv('GOOGLE_CLIENT_ID') ?: '');
}

/** WhatsApp (Taqnyat) — override in config.local.php or admin → platform_settings */
if (!defined('WHATSAPP_API_URL')) {
    define('WHATSAPP_API_URL', getenv('WHATSAPP_API_URL') ?: 'https://api.taqnyat.sa/wa/v2/messages/');
}
if (!defined('WHATSAPP_API_TOKEN')) {
    define('WHATSAPP_API_TOKEN', getenv('WHATSAPP_API_TOKEN') ?: '');
}
if (!defined('WHATSAPP_TEMPLATE_NAME')) {
    define('WHATSAPP_TEMPLATE_NAME', getenv('WHATSAPP_TEMPLATE_NAME') ?: '');
}
if (!defined('WHATSAPP_TEMPLATE_LANG')) {
    define('WHATSAPP_TEMPLATE_LANG', getenv('WHATSAPP_TEMPLATE_LANG') ?: 'ar');
}
if (!defined('WHATSAPP_OPTIN_URL')) {
    define('WHATSAPP_OPTIN_URL', getenv('WHATSAPP_OPTIN_URL') ?: 'https://api.taqnyat.sa/wa/v2/contacts/optin/');
}
if (!defined('WHATSAPP_OPTOUT_URL')) {
    define('WHATSAPP_OPTOUT_URL', getenv('WHATSAPP_OPTOUT_URL') ?: 'https://api.taqnyat.sa/wa/v2/contacts/optout/');
}

/** SMS (Taqnyat) — override in config.local.php or admin → platform_settings */
if (!defined('SMS_API_URL')) {
    define('SMS_API_URL', getenv('SMS_API_URL') ?: 'https://api.taqnyat.sa/v1/messages');
}
if (!defined('SMS_API_TOKEN')) {
    define('SMS_API_TOKEN', getenv('SMS_API_TOKEN') ?: getenv('SMS_API_KEY') ?: '');
}
if (!defined('SMS_SENDER_NAME')) {
    define('SMS_SENDER_NAME', getenv('SMS_SENDER_NAME') ?: '');
}

/** Logo asset paths — logo.png for light backgrounds, logo-dark.png for dark */
function fleetx_logo_light_src(): string {
    return '/assets/images/logo.png';
}

function fleetx_logo_dark_src(): string {
    return '/assets/images/logo-dark.png';
}

/** Detect whether the current page header uses a light or dark background */
function fleetx_logo_bg_context(): string {
    $page = basename($_SERVER['PHP_SELF'] ?? '');
    $script = $_SERVER['PHP_SELF'] ?? '';
    $is_home = ($page === 'index.php' || $page === '');
    $light_pages = ['login.php', 'register.php', 'onboarding.php', 'nafath.php', 'sanad.php'];
    if ($is_home || in_array($page, $light_pages, true)) {
        return 'light';
    }
    if (str_contains($script, '/admin/')) {
        return 'light';
    }
    return 'dark';
}

/** Single logo src for legacy img tags — pass light|dark or omit for auto */
function fleetx_logo_src(?string $bg = null): string {
    $bg = $bg ?? fleetx_logo_bg_context();
    return $bg === 'light' ? fleetx_logo_light_src() : fleetx_logo_dark_src();
}

/** Queue a toast message for the next page render (consumed in footer / toast-snippet). */
function fleetx_set_toast(string $message, string $type = 'success'): void {
    $_SESSION['fx_toast'] = ['message' => $message, 'type' => $type];
}

/** Safe post-login redirect — same-site relative paths only */
function fleetx_safe_redirect(?string $url, string $fallback = '/index.php'): string {
    $url = trim((string)$url);
    if ($url === '' || !str_starts_with($url, '/') || str_starts_with($url, '//')) {
        return $fallback;
    }
    return $url;
}

/** HTML banner when database is offline */
function fleetx_db_banner_html(): string {
    global $db_connected, $db_error_msg;
    if ($db_connected) return '';
    $msg = htmlspecialchars($db_error_msg ?: 'تعذر الاتصال بقاعدة البيانات', ENT_QUOTES, 'UTF-8');
    return '<div class="fx-db-banner" role="alert"><i class="ph ph-warning-circle"></i><span>' . $msg . ' — بعض البيانات قد لا تظهر.</span></div>';
}

/** Resolve seller company id → user id for notifications */
function fleetx_seller_user_id($conn, int $seller_company_id): int {
    if (!$conn || $seller_company_id <= 0) return 0;
    $stmt = $conn->prepare('SELECT user_id FROM seller_companies WHERE id = ? LIMIT 1');
    $stmt->bind_param('i', $seller_company_id);
    $stmt->execute();
    $row = $stmt->get_result()->fetch_assoc();
    return (int)($row['user_id'] ?? 0);
}

// ── Connect ────────────────────────────────────────────────
$conn = null;
$db_connected = false;
$db_error_msg = '';

try {
    // Disable mysqli strict error reporting temporarily for connection
    mysqli_report(MYSQLI_REPORT_OFF);
    
    $conn = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);
    if ($conn->connect_error) {
        $db_connected = false;
        $db_error_msg = 'خطأ الاتصال: ' . $conn->connect_error;
    } else {
        $db_connected = true;
        $conn->set_charset("utf8mb4");
        $conn->query("SET time_zone = '+03:00'");
        // Re-enable exceptions for queries
        mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);
    }
} catch (Throwable $e) {
    $db_connected = false;
    $db_error_msg = 'استثناء الاتصال: ' . $e->getMessage();
}

require_once __DIR__ . '/includes/integrations.php';

// ── Core Helpers ───────────────────────────────────────────
function sanitize($data) {
    return htmlspecialchars(trim($data ?? ''), ENT_QUOTES, 'UTF-8');
}

function formatPrice($amount) {
    return number_format((float)$amount, 0, '.', ',') . ' ر.س';
}

function isLoggedIn() {
    return isset($_SESSION['user_id']);
}

// Returns true if the visitor is browsing without an account (guest mode)
function isGuest() {
    return !isLoggedIn();
}

// Role-based dashboard URL
function getDashboardUrl() {
    $role = getUserRole();
    if ($role === 'admin')     return '/admin/index.php';
    if ($role === 'seller')    return '/seller.php?section=dashboard';
    if ($role === 'inspector') return '/inspector.php';
    if ($role === 'buyer')     return '/buyer.php?section=dashboard';
    return '/companies.php';
}

function getBuyerLandingUrl() {
    if (getUserRole() === 'buyer') return '/companies.php';
    return getDashboardUrl();
}

function requireLogin($redirect = '') {
    if (!isLoggedIn()) {
        $back = $redirect ?: $_SERVER['REQUEST_URI'];
        header('Location: /login.php?redirect=' . urlencode($back));
        exit;
    }
}

function requireRole($role) {
    requireLogin();
    if ($_SESSION['role'] !== $role && $_SESSION['role'] !== 'admin') {
        header('Location: /index.php');
        exit;
    }
}

function getUserRole() {
    return $_SESSION['role'] ?? 'guest';
}

function getUserId() {
    return $_SESSION['user_id'] ?? null;
}

function timeLeft($end_time) {
    if (!$end_time) return ['total' => 0, 'days' => 0, 'hours' => 0, 'mins' => 0, 'secs' => 0];
    $diff = strtotime($end_time) - time();
    if ($diff <= 0) return ['total' => 0, 'days' => 0, 'hours' => 0, 'mins' => 0, 'secs' => 0];
    return [
        'total' => $diff,
        'days'  => floor($diff / 86400),
        'hours' => floor(($diff % 86400) / 3600),
        'mins'  => floor(($diff % 3600) / 60),
        'secs'  => $diff % 60,
    ];
}

function getTimeDiff($end_time) {
    return timeLeft($end_time);
}

/**
 * Refresh auction + event end times: align lots per event, sync event row, +7d fallback when needed.
 * Returns ['events' => int, 'auctions' => int, 'fallback' => string].
 */
function fleetx_refresh_event_end_times(mysqli $conn): array {
    $events_updated = 0;
    $auctions_updated = 0;
    $fallback = date('Y-m-d H:i:s', time() + 86400 * 7);
    $horizon = date('Y-m-d H:i:s', time() + 86400 * 6);

    // Normalize lots to the earliest valid future deadline in each event (ignores +7d fallback bumps).
    $conn->query("
        UPDATE auctions a
        INNER JOIN (
            SELECT event_id, MIN(end_time) AS canon_end
            FROM auctions
            WHERE event_id IS NOT NULL
              AND status IN ('active','live')
              AND end_time > NOW()
              AND end_time <= '$horizon'
            GROUP BY event_id
        ) canon ON canon.event_id = a.event_id
        SET a.end_time = canon.canon_end,
            a.status = 'active'
        WHERE a.status IN ('active','live')
          AND a.event_id IS NOT NULL
          AND (a.end_time IS NULL OR a.end_time < NOW() OR a.end_time <> canon.canon_end)
    ");
    $auctions_updated += (int)$conn->affected_rows;

    // Extend remaining expired lots (no valid sibling deadline) via fallback.
    $conn->query("
        UPDATE auctions
        SET status = 'active', end_time = '$fallback'
        WHERE status IN ('active','live')
          AND (end_time IS NULL OR end_time < NOW())
    ");
    $auctions_updated += (int)$conn->affected_rows;

    // Sync event end_time from active lots.
    $conn->query("
        UPDATE auction_events ae
        INNER JOIN (
            SELECT event_id, MAX(end_time) AS lot_max_end
            FROM auctions
            WHERE event_id IS NOT NULL
              AND status IN ('active','live')
              AND end_time IS NOT NULL
            GROUP BY event_id
        ) lots ON lots.event_id = ae.id
        SET ae.end_time = lots.lot_max_end,
            ae.status = 'active'
        WHERE ae.status IN ('active','upcoming')
          AND (ae.end_time IS NULL OR ae.end_time <> lots.lot_max_end)
    ");
    $events_updated += (int)$conn->affected_rows;

    $conn->query("
        UPDATE auction_events
        SET end_time = '$fallback', status = 'active'
        WHERE status IN ('active','upcoming')
          AND (end_time IS NULL OR end_time < NOW())
    ");
    $events_updated += (int)$conn->affected_rows;

    return ['events' => $events_updated, 'auctions' => $auctions_updated, 'fallback' => $fallback];
}

/** Effective countdown end for an event (active lot deadline, else event row). */
function fleetx_event_countdown_end(mysqli $conn, int $event_id, ?string $event_end_time = null): string {
    $lot_max = null;
    $event_row_end = null;

    if ($event_id > 0) {
        $est = $conn->prepare('SELECT end_time FROM auction_events WHERE id = ? LIMIT 1');
        if ($est) {
            $est->bind_param('i', $event_id);
            $est->execute();
            $row = $est->get_result()->fetch_assoc();
            $est->close();
            $event_row_end = $row['end_time'] ?? null;
        }
        $horizon = date('Y-m-d H:i:s', time() + 86400 * 6);
        $lst = $conn->prepare("
            SELECT MAX(end_time) AS lot_max FROM auctions
            WHERE event_id = ? AND status IN ('active','live') AND end_time IS NOT NULL
              AND end_time > NOW() AND end_time <= ?
        ");
        if ($lst) {
            $lst->bind_param('is', $event_id, $horizon);
            $lst->execute();
            $lot_max = $lst->get_result()->fetch_assoc()['lot_max'] ?? null;
            $lst->close();
        }
        if (!$lot_max) {
            $lst2 = $conn->prepare("
                SELECT MAX(end_time) AS lot_max FROM auctions
                WHERE event_id = ? AND status IN ('active','live') AND end_time IS NOT NULL
            ");
            if ($lst2) {
                $lst2->bind_param('i', $event_id);
                $lst2->execute();
                $lot_max = $lst2->get_result()->fetch_assoc()['lot_max'] ?? null;
                $lst2->close();
            }
        }
    }

    if ($lot_max && strtotime((string)$lot_max) > time()) {
        return (string)$lot_max;
    }

    $candidates = array_filter([
        $event_end_time ? trim($event_end_time) : null,
        $event_row_end,
        $lot_max,
    ]);
    $best = '';
    $best_ts = 0;
    foreach ($candidates as $c) {
        $ts = strtotime((string)$c);
        if ($ts && $ts > $best_ts) {
            $best_ts = $ts;
            $best = (string)$c;
        }
    }
    if ($best_ts <= time()) {
        $best = date('Y-m-d H:i:s', time() + 86400 * 7);
    }
    return $best;
}

/** Normalize / validate a vehicle image URL from DB or form */
function fleetx_normalize_image_url(?string $url): string {
    $url = trim((string) $url);
    if ($url === '' || in_array(strtolower($url), ['null', 'none', 'n/a', 'undefined', '0', '-'], true)) {
        return '';
    }
    if (str_contains($url, ',')) {
        foreach (array_map('trim', explode(',', $url)) as $part) {
            $normalized = fleetx_normalize_image_url($part);
            if ($normalized !== '') {
                return $normalized;
            }
        }
        return '';
    }
    if (preg_match('#^https?://#i', $url)) {
        if (preg_match('#placeholder|dummyimage|blank\.gif|1x1|no-image|noimage#i', $url)) {
            return '';
        }
        if (preg_match('#photo-1568844293986|photo-1568605117032|photo-1503376712341#i', $url)) {
            return '';
        }
        return strlen($url) > 14 ? $url : '';
    }
    if (preg_match('#^/#', $url)) {
        return rtrim(SITE_URL, '/') . $url;
    }
    return '';
}

/** Curated Unsplash car photos — used when DB has no usable image */
function fleetx_card_fallbacks(string $type = 'live'): array {
    $shared = [
        'https://images.unsplash.com/photo-1494976388531-d1058494cdd8?w=800&q=80&auto=format',
        'https://images.unsplash.com/photo-1550355291-bbee04a92027?w=800&q=80&auto=format',
        'https://images.unsplash.com/photo-1492144534655-ae79c964c9d7?w=800&q=80&auto=format',
        'https://images.unsplash.com/photo-1580273916550-e323be2ae537?w=800&q=80&auto=format',
        'https://images.unsplash.com/photo-1618843479313-40f8afb4b4d8?w=800&q=80&auto=format',
        'https://images.unsplash.com/photo-1555215695-3004980ad54e?w=800&q=80&auto=format',
        'https://images.unsplash.com/photo-1549317661-bd32c8ce0db2?w=800&q=80&auto=format',
        'https://images.unsplash.com/photo-1621007947382-bb3c3994e3fb?w=800&q=80&auto=format',
        'https://images.unsplash.com/photo-1606664515524-ed2f786a0bd6?w=800&q=80&auto=format',
        'https://images.unsplash.com/photo-1533473359331-0135ef1b58bf?w=800&q=80&auto=format',
        'https://images.unsplash.com/photo-1502877338535-766e1452684a?w=800&q=80&auto=format',
        'https://images.unsplash.com/photo-1603584173870-7f23fdae1b7a?w=800&q=80&auto=format',
    ];
    if ($type === 'instant') {
        return array_merge([
            'https://images.unsplash.com/photo-1503376780353-7e6692767b70?w=800&q=80&auto=format',
            'https://images.unsplash.com/photo-1584345604476-8ec5e12e42dd?w=800&q=80&auto=format',
        ], $shared);
    }
    return $shared;
}

/** Reliable card image URL with make-aware and seeded fallbacks */
function fleetx_card_image(?string $url, int $seed = 0, string $type = 'live', string $make = ''): string {
    $normalized = fleetx_normalize_image_url($url);
    if ($normalized !== '') {
        return $normalized;
    }
    if (trim($make) !== '') {
        $by_make = fleetx_car_image_by_make($make);
        if ($by_make !== '') {
            return $by_make;
        }
    }
    $fallbacks = fleetx_card_fallbacks($type);
    return $fallbacks[abs($seed) % count($fallbacks)];
}

/** onerror handler for card/thumb images — chained seeded fallbacks */
function fleetx_img_onerror_handler(int $seed, string $type = 'live', string $make = ''): string {
    $fallback_img = fleetx_card_image('', $seed, $type, $make);
    $fallback_img2 = fleetx_card_image('', $seed + 5, $type, $make);
    return "var i=this;if(!i.dataset.fbx){i.dataset.fbx='1';i.src='" . htmlspecialchars($fallback_img, ENT_QUOTES) . "'}"
        . "else if(i.dataset.fbx==='1'){i.dataset.fbx='2';i.src='" . htmlspecialchars($fallback_img2, ENT_QUOTES) . "'}"
        . "else{i.onerror=null}";
}

/** Normalized vehicle thumb src + onerror pair for dashboards and cards */
function fleetx_vehicle_thumb(?string $url, int $seed, string $type = 'live', string $make = ''): array {
    return [
        'src' => fleetx_card_image($url, $seed, $type, $make),
        'onerror' => fleetx_img_onerror_handler($seed, $type, $make),
    ];
}

function getStatusLabel($status) {
    $labels = [
        'active'    => ['label' => 'نشط', 'class' => 'badge-active'],
        'live'      => ['label' => 'مباشر الآن', 'class' => 'badge-live'],
        'ended'     => ['label' => 'منتهي', 'class' => 'badge-ended'],
        'upcoming'  => ['label' => 'قادم', 'class' => 'badge-upcoming'],
        'draft'     => ['label' => 'مسودة', 'class' => 'badge-draft'],
        'cancelled' => ['label' => 'ملغي', 'class' => 'badge-ended'],
    ];
    return $labels[$status] ?? ['label' => $status, 'class' => ''];
}

function getTypeLabel($type) {
    $types = [
        'live'     => ['label' => 'مزاد مباشر', 'icon' => 'ph-gavel'],
        'instant'  => ['label' => 'شراء فوري', 'icon' => 'ph-lightning'],
        'sealed'   => ['label' => 'مزاد مغلق', 'icon' => 'ph-lock'],
        'upcoming' => ['label' => 'قادم', 'icon' => 'ph-clock'],
    ];
    return $types[$type] ?? ['label' => $type, 'icon' => 'ph-tag'];
}

function getAuctions($conn, $where = '', $params = [], $types = '', $limit = 12, $offset = 0) {
    $sql = "
        SELECT a.*, 
               v.make, v.model, v.year, v.mileage, v.color, v.fuel_type, v.transmission, v.city as vehicle_city, v.image_url,
               sc.company_name as seller_name, sc.is_verified as seller_verified,
               (SELECT COUNT(*) FROM bids b WHERE b.auction_id = a.id) as bids_count,
               (SELECT MAX(b2.amount) FROM bids b2 WHERE b2.auction_id = a.id) as highest_bid
        FROM auctions a
        JOIN vehicles v ON a.vehicle_id = v.id
        JOIN seller_companies sc ON a.seller_id = sc.id
        $where
        ORDER BY a.is_featured DESC, a.created_at DESC
        LIMIT ? OFFSET ?
    ";
    
    if (!empty($params)) {
        $stmt = $conn->prepare($sql);
        $types .= 'ii';
        array_push($params, $limit, $offset);
        $stmt->bind_param($types, ...$params);
        $stmt->execute();
        $res = $stmt->get_result();
    } else {
        $params = [$limit, $offset];
        $stmt = $conn->prepare($sql);
        $stmt->bind_param('ii', $limit, $offset);
        $stmt->execute();
        $res = $stmt->get_result();
    }
    
    $rows = [];
    if ($res) while ($row = $res->fetch_assoc()) $rows[] = $row;
    return $rows;
}

function getAuctionById($conn, $id) {
    $stmt = $conn->prepare("
        SELECT a.*, 
               v.make, v.model, v.year, v.mileage, v.color, v.fuel_type, v.transmission, v.engine_size,
               v.city as vehicle_city, v.image_url, v.images, v.description as vehicle_desc, v.condition_grade,
               v.vin, v.plate_number,
               sc.company_name as seller_name, sc.is_verified as seller_verified, sc.rating as seller_rating,
               sc.logo_url as seller_logo, sc.user_id as seller_user_id,
               (SELECT COUNT(*) FROM bids b WHERE b.auction_id = a.id) as bids_count,
               (SELECT MAX(b2.amount) FROM bids b2 WHERE b2.auction_id = a.id) as highest_bid,
               (SELECT b3.user_id FROM bids b3 WHERE b3.auction_id = a.id ORDER BY b3.amount DESC LIMIT 1) as leading_bidder_id,
               i.overall_score, i.exterior_score, i.interior_score, i.mechanical_score, i.electronics_score, i.notes as inspection_notes
        FROM auctions a
        JOIN vehicles v ON a.vehicle_id = v.id
        JOIN seller_companies sc ON a.seller_id = sc.id
        LEFT JOIN inspections i ON i.vehicle_id = v.id
        WHERE a.id = ?
    ");
    $stmt->bind_param('i', $id);
    $stmt->execute();
    $res = $stmt->get_result();
    return $res ? $res->fetch_assoc() : null;
}

function isInWatchlist($conn, $auction_id, $user_id) {
    if (!$user_id) return false;
    $stmt = $conn->prepare("SELECT 1 FROM watchlist WHERE user_id=? AND auction_id=?");
    $stmt->bind_param('ii', $user_id, $auction_id);
    $stmt->execute();
    return $stmt->get_result()->num_rows > 0;
}

function getUserFavoriteIds($conn, $user_id) {
    if (!$user_id) return [];
    if ($conn) {
        $ids = [];
        $stmt = $conn->prepare('SELECT auction_id FROM watchlist WHERE user_id = ? ORDER BY created_at DESC');
        if ($stmt) {
            $stmt->bind_param('i', $user_id);
            $stmt->execute();
            $res = $stmt->get_result();
            while ($row = $res->fetch_assoc()) {
                $ids[] = (int)$row['auction_id'];
            }
            $stmt->close();
        }
        return $ids;
    }
    return array_map('intval', $_SESSION['favorites'] ?? []);
}

function getUserFavoriteAuctions($conn, $user_id) {
    $items = [];
    if (!$user_id) return $items;

    if ($conn) {
        $stmt = $conn->prepare('
            SELECT a.*, v.make, v.model, v.year, v.image_url, v.city, v.mileage
            FROM watchlist w
            JOIN auctions a ON w.auction_id = a.id
            JOIN vehicles v ON a.vehicle_id = v.id
            WHERE w.user_id = ?
            ORDER BY w.created_at DESC
        ');
        if ($stmt) {
            $stmt->bind_param('i', $user_id);
            $stmt->execute();
            $res = $stmt->get_result();
            while ($row = $res->fetch_assoc()) {
                $items[] = $row;
            }
            $stmt->close();
        }

        $favs_cookie = isset($_COOKIE['favorites']) ? array_filter(explode(',', $_COOKIE['favorites'])) : [];
        if (!empty($favs_cookie)) {
            $existing_ids = array_column($items, 'id');
            $missing = array_diff(array_map('intval', $favs_cookie), $existing_ids);
            if (!empty($missing)) {
                $ids_str = implode(',', array_map('intval', $missing));
                $result = $conn->query("SELECT a.*, v.make, v.model, v.year, v.image_url, v.city, v.mileage
                                        FROM auctions a
                                        LEFT JOIN vehicles v ON a.vehicle_id = v.id
                                        WHERE a.id IN ($ids_str)");
                if ($result) {
                    while ($row = $result->fetch_assoc()) {
                        $items[] = $row;
                    }
                }
            }
        }
        return $items;
    }

    return [
        [
            'id' => 201, 'title' => 'فورد إكسبلورر 2023 - دفع رباعي',
            'make' => 'Ford', 'model' => 'Explorer', 'year' => 2023,
            'image_url' => '', 'city' => 'الرياض', 'mileage' => 25000,
            'current_price' => 95000, 'starting_price' => 80000,
            'type' => 'auction', 'status' => 'active',
        ],
    ];
}

function getUnreadNotifications($conn, $user_id) {
    if (!$user_id) return 0;
    $stmt = $conn->prepare("SELECT COUNT(*) as c FROM notifications WHERE user_id=? AND is_read=0");
    $stmt->bind_param('i', $user_id);
    $stmt->execute();
    $row = $stmt->get_result()->fetch_assoc();
    return $row['c'] ?? 0;
}

function createNotification($conn, $user_id, $type, $title, $message, $link = '') {
    $stmt = $conn->prepare("INSERT INTO notifications (user_id, type, title, message, link) VALUES (?,?,?,?,?)");
    $stmt->bind_param('issss', $user_id, $type, $title, $message, $link);
    $stmt->execute();
}

function logActivity($conn, $user_id, $type, $message, $meta = null) {
    if (!$conn || !$user_id) return;
    $meta_json = $meta ? json_encode($meta, JSON_UNESCAPED_UNICODE) : null;
    $stmt = $conn->prepare("INSERT INTO activity_log (user_id, type, message, meta) VALUES (?,?,?,?)");
    if ($stmt) {
        $stmt->bind_param('isss', $user_id, $type, $message, $meta_json);
        @$stmt->execute();
    }
}

function notifyUser($conn, $user_id, $type, $title, $message, $link = '', $channels = ['in_app']) {
    if (!$conn || !$user_id) return;
    createNotification($conn, $user_id, $type, $title, $message, $link);
    logActivity($conn, $user_id, $type, $message, ['link' => $link]);

    $ustmt = $conn->prepare("SELECT mobile, email FROM users WHERE id = ?");
    $ustmt->bind_param('i', $user_id);
    $ustmt->execute();
    $urow = $ustmt->get_result()->fetch_assoc();
    $mobile = $urow['mobile'] ?? '';
    $email = $urow['email'] ?? '';
    $full_message = $title . ' — ' . $message . ($link ? ' ' . (str_starts_with($link, 'http') ? $link : SITE_URL . $link) : '');

    if ($mobile) {
        if (in_array('sms', $channels, true) && fleetx_channel_enabled($conn, 'sms')) {
            sendSmsNotification($mobile, $full_message, $conn);
        }
        if (in_array('whatsapp', $channels, true) && fleetx_channel_enabled($conn, 'whatsapp')) {
            sendWhatsAppNotification($mobile, $full_message, $conn);
        }
    }
    if (in_array('email', $channels, true) && fleetx_channel_enabled($conn, 'email') && $email) {
        sendEmailNotification($email, $title, $message . ($link ? "\n$link" : ''));
    }
}

/** Low-level Taqnyat JSON HTTP helper. */
function fleetx_taqnyat_json_request(string $url, string $token, array $payload, string $method = 'POST'): array {
    $headers = ['Content-Type: application/json'];
    if ($token !== '') {
        $headers[] = 'Authorization: Bearer ' . $token;
    }
    $ch = curl_init($url);
    $opts = [
        CURLOPT_HTTPHEADER => $headers,
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_TIMEOUT => 20,
    ];
    if (strtoupper($method) === 'GET') {
        $opts[CURLOPT_HTTPGET] = true;
    } else {
        $opts[CURLOPT_CUSTOMREQUEST] = strtoupper($method);
        $opts[CURLOPT_POSTFIELDS] = json_encode($payload, JSON_UNESCAPED_UNICODE);
    }
    curl_setopt_array($ch, $opts);
    $resp = curl_exec($ch);
    $code = (int)curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $err = curl_error($ch);
    curl_close($ch);
    $decoded = json_decode((string)$resp, true);
    $ok = $code >= 200 && $code < 300;
    if (is_array($decoded)) {
        if ((int)($decoded['statusCode'] ?? 0) === 401) $ok = false;
        if (isset($decoded['statuses']) && is_array($decoded['statuses']) && count($decoded['statuses']) > 0) $ok = true;
        if ((int)($decoded['statusCode'] ?? 0) === 201) $ok = true;
    }
    return [
        'ok' => $ok,
        'http' => $code,
        'response' => substr((string)$resp, 0, 400),
        'error' => $err,
        'decoded' => is_array($decoded) ? $decoded : null,
    ];
}

function fleetx_sms_config($conn = null, array $overrides = []): array {
    $url = trim((string)(getenv('SMS_API_URL') ?: (defined('SMS_API_URL') ? SMS_API_URL : '')));
    $token = trim((string)(getenv('SMS_API_TOKEN') ?: (getenv('SMS_API_KEY') ?: (defined('SMS_API_TOKEN') ? SMS_API_TOKEN : ''))));
    $sender = trim((string)(getenv('SMS_SENDER_NAME') ?: (defined('SMS_SENDER_NAME') ? SMS_SENDER_NAME : '')));

    if ($conn && fleetx_table_exists($conn, 'platform_settings')) {
        if ($token === '') $token = trim((string)fleetx_get_setting($conn, 'sms_api_token', ''));
        if ($sender === '') $sender = trim((string)fleetx_get_setting($conn, 'sms_sender_name', ''));
        $db_url = trim((string)fleetx_get_setting($conn, 'sms_api_url', ''));
        if ($db_url !== '') $url = $db_url;
    }

    if (!empty($overrides['token'])) $token = trim((string)$overrides['token']);
    if (!empty($overrides['sender'])) $sender = trim((string)$overrides['sender']);
    if (!empty($overrides['url'])) $url = trim((string)$overrides['url']);

    if ($url === '' && $token !== '') {
        $url = 'https://api.taqnyat.sa/v1/messages';
    }

    return [
        'url' => $url,
        'token' => $token,
        'sender' => $sender,
        'configured' => ($token !== '' && $sender !== '' && $url !== ''),
    ];
}

function sendSmsNotification($mobile, $message, $conn = null, array $overrides = []) {
    $log_dir = __DIR__ . '/logs';
    if (!is_dir($log_dir)) @mkdir($log_dir, 0755, true);
    $api_mobile = fleetx_normalize_mobile_api($mobile);
    $line = date('Y-m-d H:i:s') . " [SMS] $api_mobile: $message\n";
    @file_put_contents($log_dir . '/notifications.log', $line, FILE_APPEND);

    $config = fleetx_sms_config($conn, $overrides);
    if (!$config['configured']) {
        return ['ok' => true, 'mode' => 'log_only', 'http' => 0, 'response' => ''];
    }

    $recipient = (int)$api_mobile;
    $payload = [
        'recipients' => [$recipient],
        'body' => mb_substr($message, 0, 1000),
        'sender' => $config['sender'],
    ];
    $result = fleetx_taqnyat_json_request($config['url'], $config['token'], $payload, 'POST');

    @file_put_contents($log_dir . '/notifications.log', date('Y-m-d H:i:s') . " [SMS API] HTTP {$result['http']} {$result['response']}\n", FILE_APPEND);
    if (function_exists('fx_integration_log')) {
        fx_integration_log('sms', 'api', ['mobile' => $api_mobile, 'http' => $result['http']]);
    }

    return [
        'ok' => $result['ok'],
        'mode' => 'live',
        'http' => $result['http'],
        'response' => $result['response'],
        'error' => $result['error'],
        'mobile' => $api_mobile,
    ];
}

function fleetx_ensure_whatsapp_optin_schema($conn): void {
    if (!$conn) return;
    static $done = false;
    if ($done) return;
    $done = true;
    if (fleetx_table_exists($conn, 'users') && !fx_column_exists($conn, 'users', 'whatsapp_optin')) {
        @$conn->query("ALTER TABLE users ADD COLUMN whatsapp_optin TINYINT(1) NOT NULL DEFAULT 0");
    }
    if (!fleetx_table_exists($conn, 'whatsapp_optins')) {
        @$conn->query("CREATE TABLE IF NOT EXISTS whatsapp_optins (
            id INT AUTO_INCREMENT PRIMARY KEY,
            user_id INT NULL,
            mobile VARCHAR(20) NOT NULL,
            opted_in TINYINT(1) NOT NULL DEFAULT 1,
            source VARCHAR(40) DEFAULT 'register',
            api_response VARCHAR(500) DEFAULT NULL,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            INDEX idx_mobile (mobile),
            INDEX idx_user (user_id)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");
    }
}

function fx_column_exists($conn, string $table, string $column): bool {
    if (!$conn || !fleetx_table_exists($conn, $table)) return false;
    $t = $conn->real_escape_string($table);
    $c = $conn->real_escape_string($column);
    $res = $conn->query("SHOW COLUMNS FROM `$t` LIKE '$c'");
    return $res && $res->num_rows > 0;
}

function fleetx_user_whatsapp_opted_in($conn, string $mobile, $user_id = null): bool {
    if (!$conn) return false;
    fleetx_ensure_whatsapp_optin_schema($conn);
    if ($user_id) {
        $stmt = $conn->prepare('SELECT whatsapp_optin FROM users WHERE id = ? LIMIT 1');
        $stmt->bind_param('i', $user_id);
        $stmt->execute();
        $row = $stmt->get_result()->fetch_assoc();
        if ($row) return (int)($row['whatsapp_optin'] ?? 0) === 1;
    }
    $stmt = $conn->prepare('SELECT whatsapp_optin FROM users WHERE mobile = ? LIMIT 1');
    $stmt->bind_param('s', $mobile);
    $stmt->execute();
    $row = $stmt->get_result()->fetch_assoc();
    return $row && (int)($row['whatsapp_optin'] ?? 0) === 1;
}

function fleetx_whatsapp_optin_urls($conn = null): array {
    $optin = trim((string)(getenv('WHATSAPP_OPTIN_URL') ?: (defined('WHATSAPP_OPTIN_URL') ? WHATSAPP_OPTIN_URL : '')));
    $optout = trim((string)(getenv('WHATSAPP_OPTOUT_URL') ?: (defined('WHATSAPP_OPTOUT_URL') ? WHATSAPP_OPTOUT_URL : '')));
    if ($conn && fleetx_table_exists($conn, 'platform_settings')) {
        $db_in = trim((string)fleetx_get_setting($conn, 'whatsapp_optin_url', ''));
        $db_out = trim((string)fleetx_get_setting($conn, 'whatsapp_optout_url', ''));
        if ($db_in !== '') $optin = $db_in;
        if ($db_out !== '') $optout = $db_out;
    }
    return [
        'optin' => $optin ?: 'https://api.taqnyat.sa/wa/v2/contacts/optin/',
        'optout' => $optout ?: 'https://api.taqnyat.sa/wa/v2/contacts/optout/',
    ];
}

function fleetx_whatsapp_set_optin($conn, $user_id, string $mobile, bool $opted_in, string $source = 'register', string $api_response = ''): void {
    if (!$conn) return;
    fleetx_ensure_whatsapp_optin_schema($conn);
    $flag = $opted_in ? 1 : 0;
    if ($user_id) {
        $stmt = $conn->prepare('UPDATE users SET whatsapp_optin = ? WHERE id = ?');
        $stmt->bind_param('ii', $flag, $user_id);
        $stmt->execute();
    } else {
        $stmt = $conn->prepare('UPDATE users SET whatsapp_optin = ? WHERE mobile = ?');
        $stmt->bind_param('is', $flag, $mobile);
        $stmt->execute();
    }
    $stmt = $conn->prepare('INSERT INTO whatsapp_optins (user_id, mobile, opted_in, source, api_response) VALUES (?,?,?,?,?)');
    $stmt->bind_param('isiis', $user_id, $mobile, $flag, $source, $api_response);
    $stmt->execute();
}

function fleetx_whatsapp_optin_register(string $mobile, $conn = null, $user_id = null): array {
    $api_mobile = fleetx_normalize_mobile_api($mobile);
    $wa_config = fleetx_whatsapp_config($conn);
    $urls = fleetx_whatsapp_optin_urls($conn);

    $api_result = ['ok' => false, 'http' => 0, 'response' => '', 'mode' => 'local_only'];
    if ($wa_config['configured']) {
        $payload = ['msisdn' => $api_mobile, 'phone' => $api_mobile, 'to' => $api_mobile];
        $api_result = fleetx_taqnyat_json_request($urls['optin'], $wa_config['token'], $payload, 'POST');
        $api_result['mode'] = 'live';
    }

    if ($conn) {
        fleetx_whatsapp_set_optin($conn, $user_id, $mobile, true, 'register', $api_result['response'] ?? '');
    }

    $log_dir = __DIR__ . '/logs';
    if (!is_dir($log_dir)) @mkdir($log_dir, 0755, true);
    @file_put_contents($log_dir . '/notifications.log', date('Y-m-d H:i:s') . " [WhatsApp Opt-In] $api_mobile HTTP {$api_result['http']} {$api_result['response']}\n", FILE_APPEND);
    if (function_exists('fx_integration_log')) {
        fx_integration_log('whatsapp', 'opt-in', ['mobile' => $api_mobile, 'http' => $api_result['http']]);
    }

    return [
        'ok' => true,
        'opted_in' => true,
        'mobile' => $api_mobile,
        'api' => $api_result,
    ];
}

function fleetx_whatsapp_optout(string $mobile, $conn = null, $user_id = null): array {
    $api_mobile = fleetx_normalize_mobile_api($mobile);
    $wa_config = fleetx_whatsapp_config($conn);
    $urls = fleetx_whatsapp_optin_urls($conn);

    $api_result = ['ok' => false, 'http' => 0, 'response' => '', 'mode' => 'local_only'];
    if ($wa_config['configured']) {
        $payload = ['msisdn' => $api_mobile, 'phone' => $api_mobile, 'to' => $api_mobile];
        $api_result = fleetx_taqnyat_json_request($urls['optout'], $wa_config['token'], $payload, 'POST');
        $api_result['mode'] = 'live';
    }

    if ($conn) {
        fleetx_whatsapp_set_optin($conn, $user_id, $mobile, false, 'settings', $api_result['response'] ?? '');
    }

    return [
        'ok' => true,
        'opted_in' => false,
        'mobile' => $api_mobile,
        'api' => $api_result,
    ];
}

/** Resolve WhatsApp API credentials: env → constants → platform_settings. */
function fleetx_whatsapp_config($conn = null, array $overrides = []): array {
    $url = trim((string)(getenv('WHATSAPP_API_URL') ?: (defined('WHATSAPP_API_URL') ? WHATSAPP_API_URL : '')));
    $token = trim((string)(getenv('WHATSAPP_API_TOKEN') ?: (defined('WHATSAPP_API_TOKEN') ? WHATSAPP_API_TOKEN : '')));
    $template = trim((string)(getenv('WHATSAPP_TEMPLATE_NAME') ?: (defined('WHATSAPP_TEMPLATE_NAME') ? WHATSAPP_TEMPLATE_NAME : '')));
    $lang = trim((string)(getenv('WHATSAPP_TEMPLATE_LANG') ?: (defined('WHATSAPP_TEMPLATE_LANG') ? WHATSAPP_TEMPLATE_LANG : 'ar')));

    if ($conn && fleetx_table_exists($conn, 'platform_settings')) {
        if ($token === '') $token = trim((string)fleetx_get_setting($conn, 'whatsapp_api_token', ''));
        if ($template === '') $template = trim((string)fleetx_get_setting($conn, 'whatsapp_template_name', ''));
        $db_lang = trim((string)fleetx_get_setting($conn, 'whatsapp_template_lang', ''));
        if ($db_lang !== '') $lang = $db_lang;
        $db_url = trim((string)fleetx_get_setting($conn, 'whatsapp_api_url', ''));
        if ($db_url !== '') $url = $db_url;
    }

    if (!empty($overrides['token'])) $token = trim((string)$overrides['token']);
    if (!empty($overrides['url'])) $url = trim((string)$overrides['url']);
    if (!empty($overrides['template'])) $template = trim((string)$overrides['template']);
    if (!empty($overrides['lang'])) $lang = trim((string)$overrides['lang']);

    if ($url === '' && $token !== '') {
        $url = 'https://api.taqnyat.sa/wa/v2/messages/';
    }

    return [
        'url' => $url,
        'token' => $token,
        'template' => $template,
        'lang' => $lang ?: 'ar',
        'configured' => ($token !== '' && $url !== ''),
    ];
}

/** Build Taqnyat / WhatsApp Cloud payload (template for cold outreach, text for open sessions). */
function fleetx_whatsapp_build_payload(string $api_mobile, string $message, array $config, bool $force_session = false): array {
    $body = mb_substr($message, 0, 1024);
    if (!$force_session && ($config['template'] ?? '') !== '') {
        return [
            'to' => $api_mobile,
            'type' => 'template',
            'template' => [
                'name' => $config['template'],
                'language' => ['code' => $config['lang'] ?? 'ar'],
                'components' => [[
                    'type' => 'body',
                    'parameters' => [['type' => 'text', 'text' => $body]],
                ]],
            ],
        ];
    }
    return [
        'to' => $api_mobile,
        'type' => 'text',
        'text' => ['body' => $body],
    ];
}

function sendWhatsAppNotification($mobile, $message, $conn = null, array $overrides = []) {
    $log_dir = __DIR__ . '/logs';
    if (!is_dir($log_dir)) @mkdir($log_dir, 0755, true);
    $api_mobile = fleetx_normalize_mobile_api($mobile);
    $line = date('Y-m-d H:i:s') . " [WhatsApp] $api_mobile: $message\n";
    @file_put_contents($log_dir . '/notifications.log', $line, FILE_APPEND);

    $force = !empty($overrides['force']);
    $user_id = $overrides['user_id'] ?? null;
    if (!$force && $conn && !fleetx_user_whatsapp_opted_in($conn, $mobile, $user_id)) {
        @file_put_contents($log_dir . '/notifications.log', date('Y-m-d H:i:s') . " [WhatsApp] Skipped — not opted in: $api_mobile\n", FILE_APPEND);
        return ['ok' => true, 'mode' => 'skipped_optout', 'http' => 0, 'response' => 'User not opted in', 'mobile' => $api_mobile];
    }

    $config = fleetx_whatsapp_config($conn, $overrides);
    if (!$config['configured']) {
        return ['ok' => true, 'mode' => 'log_only', 'http' => 0, 'response' => ''];
    }

    $force_session = !empty($overrides['session']);
    $payload = fleetx_whatsapp_build_payload($api_mobile, $message, $config, $force_session);
    $headers = [
        'Content-Type: application/json',
        'Authorization: Bearer ' . $config['token'],
    ];

    $ch = curl_init($config['url']);
    curl_setopt_array($ch, [
        CURLOPT_POST => true,
        CURLOPT_POSTFIELDS => json_encode($payload, JSON_UNESCAPED_UNICODE),
        CURLOPT_HTTPHEADER => $headers,
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_TIMEOUT => 20,
    ]);
    $resp = curl_exec($ch);
    $code = (int)curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $err = curl_error($ch);
    curl_close($ch);

    $snippet = substr((string)$resp, 0, 300);
    @file_put_contents($log_dir . '/notifications.log', date('Y-m-d H:i:s') . " [WhatsApp API] HTTP $code $snippet\n", FILE_APPEND);
    if (function_exists('fx_integration_log')) {
        fx_integration_log('whatsapp', 'api', ['mobile' => $api_mobile, 'http' => $code, 'template' => $config['template'] ?: 'session']);
    }

    $ok = $code >= 200 && $code < 300;
    $decoded = json_decode((string)$resp, true);
    if (is_array($decoded)) {
        $api_msg = (string)($decoded['message'] ?? '');
        if ($api_msg === '401' || str_contains(strtolower((string)($decoded['reason'] ?? '')), 'bearer')) {
            $ok = false;
        }
        if (isset($decoded['statuses']) && is_array($decoded['statuses']) && count($decoded['statuses']) > 0) {
            $ok = true;
        }
    }
    return [
        'ok' => $ok,
        'mode' => 'live',
        'http' => $code,
        'response' => $snippet,
        'error' => $err,
        'mobile' => $api_mobile,
        'template' => $config['template'],
    ];
}

/** AutoData-style market price estimate (heuristic until live API connected) */
function estimateAutoDataPrice($make, $model, $year, $mileage = 0) {
    $base_by_make = [
        'toyota' => 95000, 'تويوتا' => 95000,
        'hyundai' => 72000, 'هيونداي' => 72000,
        'kia' => 68000, 'كيا' => 68000,
        'nissan' => 75000, 'نيسان' => 75000,
        'ford' => 70000, 'فورد' => 70000,
        'mercedes' => 280000, 'مرسيدس' => 280000,
        'bmw' => 250000, 'بي ام' => 250000,
        'chevrolet' => 85000, 'شيفروليه' => 85000,
        'honda' => 80000, 'هوندا' => 80000,
    ];
    $make_key = mb_strtolower(trim($make ?? ''));
    $base = 65000;
    foreach ($base_by_make as $k => $v) {
        if (str_contains($make_key, $k) || str_contains($k, $make_key)) {
            $base = $v;
            break;
        }
    }
    $year = max(2005, min((int)$year, (int)date('Y') + 1));
    $age_factor = 1 - min(0.55, ((int)date('Y') - $year) * 0.08);
    $mileage_factor = 1 - min(0.25, max(0, ($mileage - 30000) / 200000) * 0.25);
    $mid = round($base * $age_factor * $mileage_factor);
    $spread = max(3000, round($mid * 0.08));
    return ['min' => $mid - $spread, 'max' => $mid + $spread, 'mid' => $mid];
}

function getDefaultInspectorId($conn) {
    $res = $conn->query("SELECT id FROM users WHERE role='inspector' AND is_active=1 ORDER BY id ASC LIMIT 1");
    if ($res && $row = $res->fetch_assoc()) return (int)$row['id'];
    return 6;
}

function fleetx_table_exists($conn, string $table): bool {
    if (!$conn) return false;
    $t = $conn->real_escape_string($table);
    $res = $conn->query("SHOW TABLES LIKE '$t'");
    return $res && $res->num_rows > 0;
}

function fleetx_get_setting($conn, string $key, $default = null) {
    if (!$conn || !fleetx_table_exists($conn, 'platform_settings')) return $default;
    $stmt = $conn->prepare('SELECT setting_value FROM platform_settings WHERE setting_key = ? LIMIT 1');
    $stmt->bind_param('s', $key);
    $stmt->execute();
    $row = $stmt->get_result()->fetch_assoc();
    return $row['setting_value'] ?? $default;
}

function fleetx_channel_enabled($conn, string $channel): bool {
    $key = $channel . '_enabled';
    $val = fleetx_get_setting($conn, $key, '1');
    return (string)$val !== '0' && $val !== false;
}

/** Normalize Saudi mobile for SMS/WhatsApp APIs (9665XXXXXXXX) */
function fleetx_normalize_mobile_api(string $mobile): string {
    $digits = preg_replace('/\D/', '', $mobile);
    if (str_starts_with($digits, '966')) return $digits;
    if (str_starts_with($digits, '05') && strlen($digits) === 10) {
        return '966' . substr($digits, 1);
    }
    if (str_starts_with($digits, '5') && strlen($digits) === 9) {
        return '966' . $digits;
    }
    return $digits;
}

function fleetx_login_role_for_type(string $login_type): string {
    return $login_type === 'company' ? 'seller' : 'buyer';
}

function fleetx_role_matches_login_type(string $role, string $login_type): bool {
    if ($role === 'admin') return true;
    if ($login_type === 'company') {
        return in_array($role, ['seller', 'admin'], true);
    }
    return in_array($role, ['buyer', 'inspector', 'admin'], true);
}

function getBuyerSubscription($conn, int $user_id): ?array {
    if (!$conn || $user_id <= 0 || !fleetx_table_exists($conn, 'buyer_subscriptions')) return null;
    $stmt = $conn->prepare("SELECT * FROM buyer_subscriptions WHERE user_id=? AND is_active=1 AND end_date >= CURDATE() ORDER BY end_date DESC LIMIT 1");
    $stmt->bind_param('i', $user_id);
    $stmt->execute();
    $row = $stmt->get_result()->fetch_assoc();
    return $row ?: null;
}

function buyerCanBid($conn, int $user_id): array {
    if (!$conn || $user_id <= 0) {
        return ['allowed' => false, 'reason' => 'يجب تسجيل الدخول للمزايدة', 'link' => '/login.php'];
    }
    $ustmt = $conn->prepare('SELECT role, nafath_verified, is_active FROM users WHERE id=? LIMIT 1');
    $ustmt->bind_param('i', $user_id);
    $ustmt->execute();
    $user = $ustmt->get_result()->fetch_assoc();
    if (!$user || $user['role'] !== 'buyer') {
        return ['allowed' => false, 'reason' => 'حساب المشتري غير صالح للمزايدة'];
    }
    if (!fleetx_user_is_active($user)) {
        return ['allowed' => false, 'reason' => 'حسابك بانتظار موافقة الإدارة', 'link' => '/buyer.php?section=dashboard'];
    }
    $sub = getBuyerSubscription($conn, $user_id);
    if ($sub) {
        return ['allowed' => true, 'plan' => $sub['plan']];
    }
    if (!(int)($user['nafath_verified'] ?? 0)) {
        return ['allowed' => false, 'reason' => 'يجب توثيق الهوية عبر نفاذ قبل المزايدة', 'link' => '/nafath.php'];
    }
    return ['allowed' => true, 'plan' => 'free'];
}

function fleetx_process_auto_bids($conn, int $auction_id): array {
    $placed = [];
    if (!$conn || !fleetx_table_exists($conn, 'auto_bids')) return $placed;

    for ($i = 0; $i < 20; $i++) {
        $astmt = $conn->prepare("SELECT current_price, bid_increment, status, end_time FROM auctions WHERE id=?");
        $astmt->bind_param('i', $auction_id);
        $astmt->execute();
        $auction = $astmt->get_result()->fetch_assoc();
        if (!$auction || !in_array($auction['status'], ['active', 'live'], true)) break;
        if ($auction['end_time'] && strtotime($auction['end_time']) < time()) break;

        $next = (float)$auction['current_price'] + (float)$auction['bid_increment'];
        $hstmt = $conn->prepare('SELECT user_id FROM bids WHERE auction_id=? ORDER BY amount DESC, id DESC LIMIT 1');
        $hstmt->bind_param('i', $auction_id);
        $hstmt->execute();
        $high_bidder = (int)($hstmt->get_result()->fetch_assoc()['user_id'] ?? 0);

        $abstmt = $conn->prepare("
            SELECT ab.user_id FROM auto_bids ab
            JOIN users u ON u.id = ab.user_id
            WHERE ab.auction_id=? AND ab.is_active=1 AND ab.max_amount >= ?
              AND ab.user_id != ? AND (u.is_active=1 OR u.is_active IS NULL)
            ORDER BY ab.max_amount DESC, ab.id ASC
            LIMIT 1
        ");
        $abstmt->bind_param('idi', $auction_id, $next, $high_bidder);
        $abstmt->execute();
        $auto = $abstmt->get_result()->fetch_assoc();
        if (!$auto) break;

        $result = placeBid($conn, $auction_id, (int)$auto['user_id'], $next, ['skip_auto_process' => true, 'is_auto' => true]);
        if (empty($result['success'])) break;
        $placed[] = $result;
    }
    return $placed;
}

function fleetx_platform_fee_percent($conn = null): float {
    global $conn;
    $c = $conn ?? ($GLOBALS['conn'] ?? null);
    if ($c) {
        $v = fleetx_get_setting($c, 'platform_fee_percent', null);
        if ($v !== null && $v !== '') return (float)$v;
    }
    return (float)PLATFORM_FEE_PERCENT;
}

function fleetx_inspection_fee($conn = null): float {
    global $conn;
    $c = $conn ?? ($GLOBALS['conn'] ?? null);
    if ($c) {
        $v = fleetx_get_setting($c, 'inspection_fee', null);
        if ($v !== null && $v !== '') return (float)$v;
    }
    return 100.0;
}

function sellerCanAddVehicle($conn, int $seller_company_id): array {
    if (!$conn || $seller_company_id <= 0) return ['allowed' => false, 'reason' => 'شركة غير صالحة'];
    $stmt = $conn->prepare('SELECT subscription FROM seller_companies WHERE id=? LIMIT 1');
    $stmt->bind_param('i', $seller_company_id);
    $stmt->execute();
    $row = $stmt->get_result()->fetch_assoc();
    $plan = $row['subscription'] ?? 'standard';
    $limits = ['standard' => 5, 'premium' => 50, 'enterprise' => 9999];
    $limit = $limits[$plan] ?? 5;
    $cstmt = $conn->prepare("SELECT COUNT(*) FROM vehicles WHERE seller_id=? AND status NOT IN ('withdrawn') AND created_at >= DATE_FORMAT(NOW(), '%Y-%m-01')");
    $cstmt->bind_param('i', $seller_company_id);
    $cstmt->execute();
    $count = (int)($cstmt->get_result()->fetch_row()[0] ?? 0);
    if ($count >= $limit) {
        return ['allowed' => false, 'reason' => "وصلت لحد الباقة ($limit مركبة/شهر)", 'plan' => $plan];
    }
    return ['allowed' => true, 'plan' => $plan, 'remaining' => $limit - $count];
}

function sendEmailNotification($email, $subject, $message): bool {
    if (!$email || !filter_var($email, FILTER_VALIDATE_EMAIL)) return false;
    $log_dir = __DIR__ . '/logs';
    if (!is_dir($log_dir)) @mkdir($log_dir, 0755, true);
    $line = date('Y-m-d H:i:s') . " [EMAIL] $email | $subject | $message\n";
    @file_put_contents($log_dir . '/notifications.log', $line, FILE_APPEND);
    if (getenv('SMTP_HOST') || defined('SMTP_HOST')) {
        // Production: PHPMailer / mail() with SMTP
        @mail($email, $subject, $message, "Content-Type: text/plain; charset=UTF-8\r\nFrom: FleetX <noreply@fleetx.sa>");
    }
    return true;
}

function fleetx_generate_invoice_number(): string {
    return 'FX-' . date('Ymd') . '-' . strtoupper(substr(uniqid(), -6));
}

function fleetx_zatca_qr_payload(string $seller_name, string $vat_number, string $timestamp, float $total, float $vat): string {
    // TLV base64 stub for ZATCA QR (simplified)
    $tlv = chr(1) . chr(strlen($seller_name)) . $seller_name
         . chr(2) . chr(strlen($vat_number)) . $vat_number
         . chr(3) . chr(strlen($timestamp)) . $timestamp
         . chr(4) . chr(strlen((string)$total)) . (string)$total
         . chr(5) . chr(strlen((string)$vat)) . (string)$vat;
    return base64_encode($tlv);
}

function fleetx_create_invoice($conn, int $transaction_id): ?array {
    if (!$conn || !fleetx_table_exists($conn, 'invoices')) return null;
    $stmt = $conn->prepare('SELECT t.*, u.full_name as buyer_name, u.email as buyer_email, sc.company_name, sc.vat_number FROM transactions t JOIN users u ON t.buyer_id=u.id JOIN seller_companies sc ON t.seller_id=sc.id WHERE t.id=? LIMIT 1');
    $stmt->bind_param('i', $transaction_id);
    $stmt->execute();
    $tx = $stmt->get_result()->fetch_assoc();
    if (!$tx) return null;
    $subtotal = (float)$tx['sale_price'];
    $vat = round($subtotal * 0.15, 2);
    $total = $subtotal + $vat;
    $inv_num = fleetx_generate_invoice_number();
    $qr = fleetx_zatca_qr_payload($tx['company_name'] ?? 'FleetX', $tx['vat_number'] ?? '300000000000003', date('c'), $total, $vat);
    $ins = $conn->prepare('INSERT INTO invoices (transaction_id, invoice_number, buyer_id, seller_id, subtotal, vat_amount, total, zatca_qr) VALUES (?,?,?,?,?,?,?,?)');
    $ins->bind_param('isiiddds', $transaction_id, $inv_num, $tx['buyer_id'], $tx['seller_id'], $subtotal, $vat, $total, $qr);
    if ($ins->execute()) {
        $conn->query("UPDATE transactions SET invoice_number='$inv_num', vat_amount=$vat WHERE id=$transaction_id");
        return ['invoice_number' => $inv_num, 'total' => $total, 'vat' => $vat, 'qr' => $qr];
    }
    return null;
}

function fleetx_vehicle_status_label(string $status): string {
    $labels = [
        'pending' => 'مسودة',
        'awaiting_admin' => 'بانتظار موافقة الإدارة',
        'inspection_scheduled' => 'مجدول للفحص',
        'awaiting_seller_approval' => 'بانتظار موافقتك',
        'approved' => 'معتمدة',
        'in_auction' => 'في المزاد',
        'sold' => 'مباعة',
        'withdrawn' => 'مسحوبة',
        'suspended' => 'موقوفة',
    ];
    return $labels[$status] ?? $status;
}

function getSellerCompany($conn, $user_id) {
    $stmt = $conn->prepare('SELECT * FROM seller_companies WHERE user_id = ? LIMIT 1');
    $stmt->bind_param('i', $user_id);
    $stmt->execute();
    return $stmt->get_result()->fetch_assoc();
}

function placeBid($conn, $auction_id, $user_id, $amount, array $opts = []) {
    $bid_check = buyerCanBid($conn, (int)$user_id);
    if (!$bid_check['allowed']) {
        return ['success' => false, 'error' => $bid_check['reason'], 'link' => $bid_check['link'] ?? null];
    }

    $conn->begin_transaction();
    try {
        $stmt = $conn->prepare("SELECT current_price, bid_increment, end_time, status FROM auctions WHERE id=? FOR UPDATE");
        $stmt->bind_param('i', $auction_id);
        $stmt->execute();
        $auction = $stmt->get_result()->fetch_assoc();

        if (!$auction) throw new Exception('المزاد غير موجود');
        if (!in_array($auction['status'], ['active', 'live'], true)) throw new Exception('المزاد غير نشط');
        if ($auction['end_time'] && strtotime($auction['end_time']) < time()) throw new Exception('انتهى وقت المزاد');

        $min_bid = $auction['current_price'] + $auction['bid_increment'];
        if ($amount < $min_bid) throw new Exception('الحد الأدنى للمزايدة هو ' . formatPrice($min_bid));

        $ustmt = $conn->prepare('SELECT sanad_limit, wallet_balance FROM users WHERE id=?');
        $ustmt->bind_param('i', $user_id);
        $ustmt->execute();
        $urow = $ustmt->get_result()->fetch_assoc();
        $sanad_limit = (float)($urow['sanad_limit'] ?? 0);
        if ($sanad_limit > 0 && $amount > $sanad_limit) {
            throw new Exception('المبلغ يتجاوز حد سند الأمر المعتمد (' . formatPrice($sanad_limit) . ')');
        }

        $stmt2 = $conn->prepare("INSERT INTO bids (auction_id, user_id, amount, ip_address) VALUES (?,?,?,?)");
        $ip = $_SERVER['REMOTE_ADDR'] ?? '';
        $stmt2->bind_param('iids', $auction_id, $user_id, $amount, $ip);
        $stmt2->execute();

        $new_end_time = $auction['end_time'] ?? '';
        $anti_snipe = 180;
        if ($conn) {
            $as = fleetx_get_setting($conn, 'anti_snipe_seconds', '180');
            $anti_snipe = max(60, (int)$as);
        }
        if ($auction['end_time']) {
            $remaining = strtotime($auction['end_time']) - time();
            if ($remaining < $anti_snipe) {
                $new_end_time = date('Y-m-d H:i:s', time() + $anti_snipe);
                $stmt3 = $conn->prepare('UPDATE auctions SET current_price=?, end_time=? WHERE id=?');
                $stmt3->bind_param('dsi', $amount, $new_end_time, $auction_id);
                $stmt3->execute();
            } else {
                $stmt3 = $conn->prepare('UPDATE auctions SET current_price=? WHERE id=?');
                $stmt3->bind_param('di', $amount, $auction_id);
                $stmt3->execute();
            }
        } else {
            $stmt3 = $conn->prepare('UPDATE auctions SET current_price=? WHERE id=?');
            $stmt3->bind_param('di', $amount, $auction_id);
            $stmt3->execute();
        }

        $notif_msg = 'قدم شخص آخر مزايدة بـ ' . number_format($amount) . ' ر.س';
        $notif_link = '/auction-live.php?id=' . $auction_id;
        $outbid_stmt = $conn->prepare('
            SELECT DISTINCT b.user_id FROM bids b
            WHERE b.auction_id=? AND b.user_id != ? AND b.amount < ?
        ');
        $outbid_stmt->bind_param('iid', $auction_id, $user_id, $amount);
        $outbid_stmt->execute();
        $outbid_res = $outbid_stmt->get_result();
        while ($ob = $outbid_res->fetch_assoc()) {
            notifyUser($conn, (int)$ob['user_id'], 'outbid', 'تم تجاوز مزايدتك!', $notif_msg, $notif_link, ['in_app', 'sms']);
        }

        $act_type = !empty($opts['is_auto']) ? 'auto_bid_placed' : 'bid_placed';
        logActivity($conn, $user_id, $act_type, 'قدمت مزايدة بمبلغ ' . number_format($amount) . ' ر.س', ['auction_id' => $auction_id, 'amount' => $amount]);

        $conn->commit();
        $response = [
            'success' => true,
            'new_price' => $amount,
            'new_end_time' => $new_end_time ?: null,
        ];
        if (empty($opts['skip_auto_process'])) {
            $auto = fleetx_process_auto_bids($conn, (int)$auction_id);
            if ($auto) {
                $last = $auto[count($auto) - 1];
                $response['new_price'] = $last['new_price'] ?? $response['new_price'];
                $response['new_end_time'] = $last['new_end_time'] ?? $response['new_end_time'];
                $response['auto_bids'] = count($auto);
            }
        }
        return $response;
    } catch (Exception $e) {
        $conn->rollback();
        return ['success' => false, 'error' => $e->getMessage()];
    }
}


// === RESTORED FUNCTIONS ===



// Alias for timeLeft() used by some pages
function getTimerData($end_time) {
    return timeLeft($end_time);
}

// Initialize wallet balance in session if not set
function initWalletBalance() {
    if (!isset($_SESSION['wallet_balance'])) {
        $_SESSION['wallet_balance'] = 0;
    }
    return $_SESSION['wallet_balance'];
}

// ── Car placeholder images (Unsplash) ─────────────────────
$CAR_IMAGES = [
    'toyota'   => 'https://images.unsplash.com/photo-1621007947382-bb3c3994e3fb?w=600&q=80',
    'hyundai'  => 'https://images.unsplash.com/photo-1603584173870-7f23fdae1b7a?w=600&q=80',
    'kia'      => 'https://images.unsplash.com/photo-1606664515524-ed2f786a0bd6?w=600&q=80',
    'nissan'   => 'https://images.unsplash.com/photo-1555215695-3004980ad54e?w=600&q=80',
    'ford'     => 'https://images.unsplash.com/photo-1533473359331-0135ef1b58bf?w=600&q=80',
    'mercedes' => 'https://images.unsplash.com/photo-1618843479313-40f8afb4b4d8?w=600&q=80',
    'bmw'      => 'https://images.unsplash.com/photo-1580273916550-e323be2ae537?w=600&q=80',
    'chevrolet'=> 'https://images.unsplash.com/photo-1502877338535-766e1452684a?w=600&q=80',
    'honda'    => 'https://images.unsplash.com/photo-1580273916550-e323be2ae537?w=600&q=80',
    'default'  => 'https://images.unsplash.com/photo-1549317661-bd32c8ce0db2?w=600&q=80',
];

function fleetx_car_image_by_make(string $make): string {
    global $CAR_IMAGES;
    $make_lower = mb_strtolower(trim($make));
    if ($make_lower === '') {
        return $CAR_IMAGES['default'] ?? '';
    }
    foreach ($CAR_IMAGES as $key => $url) {
        if ($key === 'default') {
            continue;
        }
        if (str_contains($make_lower, $key) || str_contains($key, $make_lower)) {
            return $url;
        }
    }
    return $CAR_IMAGES['default'] ?? '';
}

function getCarImage($make, $image_url = null) {
    return fleetx_card_image($image_url, 0, 'instant', (string) ($make ?? ''));
}

// ── Mock data for local dev (if DB not connected) ─────────
function getMockEvents() { return []; }
function countMockAuctions() { return ['live' => 0, 'instant' => 0, 'sealed' => 0, 'upcoming' => 0]; }
function getMockAuctions($limit = 9) { return []; }
?>
