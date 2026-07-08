<?php
// ============================================================
// MAZADI - Car Auction Platform
// config.php - Master Configuration
// ============================================================
ini_set('display_errors', 1);
error_reporting(E_ALL);

if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

// ── Database Credentials ──────────────────────────────────
define('DB_HOST',    'localhost');
define('DB_USER',    'u274391035_usr_BbBE85ay');
define('DB_PASS',    '*A7medfouad*');
define('DB_NAME',    'u274391035_db_BbBE85ay');
define('SITE_URL',   'https://mazadi.bearand.com');
define('SITE_NAME',  'FleetX');
define('PLATFORM_FEE_PERCENT', 5);
define('FLEETX_CSS_VER', '20');

function fleetx_css_href(): string {
    return '/assets/css/fleetx.css?v=' . FLEETX_CSS_VER;
}

/** Navbar/header logo: logo.png on homepage only, logo-dark.png everywhere else */
function fleetx_logo_src(): string {
    $page = basename($_SERVER['PHP_SELF'] ?? '');
    $is_home = ($page === 'index.php' || $page === '');
    return '/assets/images/' . ($is_home ? 'logo.png' : 'logo-dark.png');
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
               sc.logo_url as seller_logo,
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

    if (in_array('sms', $channels, true) || in_array('whatsapp', $channels, true)) {
        $ustmt = $conn->prepare("SELECT mobile FROM users WHERE id = ?");
        $ustmt->bind_param('i', $user_id);
        $ustmt->execute();
        $urow = $ustmt->get_result()->fetch_assoc();
        $mobile = $urow['mobile'] ?? '';
        if ($mobile) {
            if (in_array('sms', $channels, true)) sendSmsNotification($mobile, $message);
            if (in_array('whatsapp', $channels, true)) sendWhatsAppNotification($mobile, $message);
        }
    }
}

function sendSmsNotification($mobile, $message) {
    $log_dir = __DIR__ . '/logs';
    if (!is_dir($log_dir)) @mkdir($log_dir, 0755, true);
    $line = date('Y-m-d H:i:s') . " [SMS] $mobile: $message\n";
    @file_put_contents($log_dir . '/notifications.log', $line, FILE_APPEND);
    // Production: integrate Taqnyat / Unifonic API here using SMS_API_KEY env
    return true;
}

function sendWhatsAppNotification($mobile, $message) {
    $log_dir = __DIR__ . '/logs';
    if (!is_dir($log_dir)) @mkdir($log_dir, 0755, true);
    $line = date('Y-m-d H:i:s') . " [WhatsApp] $mobile: $message\n";
    @file_put_contents($log_dir . '/notifications.log', $line, FILE_APPEND);
    // Production: integrate WhatsApp Business API here
    return true;
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

function getSellerCompany($conn, $user_id) {
    $stmt = $conn->prepare('SELECT * FROM seller_companies WHERE user_id = ? LIMIT 1');
    $stmt->bind_param('i', $user_id);
    $stmt->execute();
    return $stmt->get_result()->fetch_assoc();
}

function placeBid($conn, $auction_id, $user_id, $amount) {
    $conn->begin_transaction();
    try {
        // Lock auction row
        $stmt = $conn->prepare("SELECT current_price, bid_increment, end_time, status FROM auctions WHERE id=? FOR UPDATE");
        $stmt->bind_param('i', $auction_id);
        $stmt->execute();
        $auction = $stmt->get_result()->fetch_assoc();
        
        if (!$auction) throw new Exception('المزاد غير موجود');
        if (!in_array($auction['status'], ['active', 'live'], true)) throw new Exception('المزاد غير نشط');
        if ($auction['end_time'] && strtotime($auction['end_time']) < time()) throw new Exception('انتهى وقت المزاد');
        
        $min_bid = $auction['current_price'] + $auction['bid_increment'];
        if ($amount < $min_bid) throw new Exception("الحد الأدنى للمزايدة هو " . formatPrice($min_bid));
        
        // Insert bid
        $stmt2 = $conn->prepare("INSERT INTO bids (auction_id, user_id, amount, ip_address) VALUES (?,?,?,?)");
        $ip = $_SERVER['REMOTE_ADDR'] ?? '';
        $stmt2->bind_param('iids', $auction_id, $user_id, $amount, $ip);
        $stmt2->execute();
        
        // Update current price
        $stmt3 = $conn->prepare("UPDATE auctions SET current_price=? WHERE id=?");
        $stmt3->bind_param('di', $amount, $auction_id);
        $stmt3->execute();
        
        logActivity($conn, $user_id, 'bid_placed', 'قدمت مزايدة بمبلغ ' . number_format($amount) . ' ر.س', ['auction_id' => $auction_id, 'amount' => $amount]);

        $conn->commit();
        return ['success' => true, 'new_price' => $amount];
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
    'hyundai'  => 'https://images.unsplash.com/photo-1568844293986-ca9c5c6f8b8a?w=600&q=80',
    'kia'      => 'https://images.unsplash.com/photo-1606664515524-ed2f786a0bd6?w=600&q=80',
    'nissan'   => 'https://images.unsplash.com/photo-1555215695-3004980ad54e?w=600&q=80',
    'ford'     => 'https://images.unsplash.com/photo-1533473359331-0135ef1b58bf?w=600&q=80',
    'mercedes' => 'https://images.unsplash.com/photo-1618843479313-40f8afb4b4d8?w=600&q=80',
    'bmw'      => 'https://images.unsplash.com/photo-1580273916550-e323be2ae537?w=600&q=80',
    'chevrolet'=> 'https://images.unsplash.com/photo-1502877338535-766e1452684a?w=600&q=80',
    'honda'    => 'https://images.unsplash.com/photo-1580273916550-e323be2ae537?w=600&q=80',
    'default'  => 'https://images.unsplash.com/photo-1549317661-bd32c8ce0db2?w=600&q=80',
];

function getCarImage($make, $image_url = null) {
    global $CAR_IMAGES;
    if ($image_url && is_string($image_url) && str_starts_with(trim($image_url), 'http')) {
        return trim($image_url);
    }
    $make_lower = mb_strtolower($make ?? '');
    foreach ($CAR_IMAGES as $key => $url) {
        if (str_contains($make_lower, $key) || str_contains($key, $make_lower)) return $url;
    }
    return $CAR_IMAGES['default'];
}

// ── Mock data for local dev (if DB not connected) ─────────
function getMockEvents() { return []; }
function countMockAuctions() { return ['live' => 0, 'instant' => 0, 'sealed' => 0, 'upcoming' => 0]; }
function getMockAuctions($limit = 9) { return []; }
?>
