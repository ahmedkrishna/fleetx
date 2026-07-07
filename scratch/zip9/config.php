<?php
// ============================================================
// FleetX Platform — Configuration & Database
// Hostinger MySQL: u274391035_fleetx
// ============================================================
ini_set('display_errors', 0);
error_reporting(E_ALL);

if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

// ── Database Credentials (Hostinger) ──────────────────────
define('DB_HOST', 'localhost');
define('DB_USER', 'u274391035_123456');
define('DB_PASS', '*A7medfouad*');
define('DB_NAME', 'u274391035_fleetx');
define('SITE_URL', 'https://mazadi.bearand.com');
define('SITE_NAME', 'FleetX');

// ── Connect ────────────────────────────────────────────────
$conn = null;
$db_connected = false;
try {
    $conn = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);
    if (!$conn->connect_error) {
        $db_connected = true;
        $conn->set_charset("utf8mb4");
        $conn->query("SET time_zone = '+03:00'"); // KSA
    }
} catch (Exception $e) {
    $db_connected = false;
}

// ── Helpers ────────────────────────────────────────────────
function formatPrice($amount) {
    return number_format((float)$amount, 0, '.', ',') . ' ر.س';
}

function sanitize($data) {
    return htmlspecialchars(trim($data ?? ''), ENT_QUOTES, 'UTF-8');
}

function timeLeft($end_time) {
    $diff = strtotime($end_time) - time();
    if ($diff <= 0) return ['days'=>0,'hours'=>0,'mins'=>0,'secs'=>0,'total'=>0];
    return [
        'days'  => floor($diff / 86400),
        'hours' => floor(($diff % 86400) / 3600),
        'mins'  => floor(($diff % 3600) / 60),
        'secs'  => $diff % 60,
        'total' => $diff
    ];
}

function isLoggedIn() {
    return isset($_SESSION['user_id']);
}

function requireLogin() {
    if (!isLoggedIn()) {
        header('Location: /login.php?redirect=' . urlencode($_SERVER['REQUEST_URI']));
        exit;
    }
}

function getUserRole() {
    return $_SESSION['role'] ?? 'guest';
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
function getMockEvents() {
    $now = time();
    $events = [
        [
            'id' => 1, 'seller_id' => 1,
            'title' => 'مزاد الوطنية للسيارات الفاخرة والأساطيل',
            'description' => 'المزاد الكبرى لشركة الوطنية لتصفية أسطول سيارات تويوتا وهيونداي وتوسان الفاخرة للربع الأول من عام 2026.',
            'brochure_pdf' => '/assets/docs/watania_brochure.pdf',
            'status' => 'active', 'start_time' => date('Y-m-d H:i:s', $now - 86400),
            'end_time' => date('Y-m-d H:i:s', $now + 18000), // 5 hours
            'seller' => 'الوطنية للتأجير'
        ],
        [
            'id' => 2, 'seller_id' => 2,
            'title' => 'مزاد تصفية أسطول بدجت السنوي',
            'description' => 'المزاد السنوي الخاص بشركة بدجت لتصفية أسطول سيارات كيا ومرسيدس وشيفروليه وهيونداي المستعملة مع كفالة الفحص المعتمد.',
            'brochure_pdf' => '/assets/docs/budget_brochure.pdf',
            'status' => 'active', 'start_time' => date('Y-m-d H:i:s', $now - 7200),
            'end_time' => date('Y-m-d H:i:s', $now + 86400), // 1 day
            'seller' => 'بدجت السعودية'
        ],
        [
            'id' => 3, 'seller_id' => 3,
            'title' => 'مزاد الوفاق لتأجير السيارات - المنطقة الوسطى',
            'description' => 'مزاد تصفية أسطول شركة يلو (الوفاق) لسيارات السيدان والاقتصادية الموديلات من 2021 إلى 2024.',
            'brochure_pdf' => '/assets/docs/watania_brochure.pdf',
            'status' => 'active', 'start_time' => date('Y-m-d H:i:s', $now - 3600 * 12),
            'end_time' => date('Y-m-d H:i:s', $now + 172800), // 2 days
            'seller' => 'يلو لتأجير السيارات'
        ],
        [
            'id' => 4, 'seller_id' => 4,
            'title' => 'مزاد أسطول ذيب لسيارات السيدان والدفع الرباعي',
            'description' => 'مزاد شركة ذيب لتصفية مجموعة من السيارات العائلية وسيارات الدفع الرباعي الممتازة المفحوصة.',
            'brochure_pdf' => '/assets/docs/budget_brochure.pdf',
            'status' => 'active', 'start_time' => date('Y-m-d H:i:s', $now - 3600),
            'end_time' => date('Y-m-d H:i:s', $now + 10800), // 3 hours
            'seller' => 'ذيب لتأجير السيارات'
        ],
        [
            'id' => 5, 'seller_id' => 5,
            'title' => 'مزاد هرتز السعودية للسيارات الاقتصادية',
            'description' => 'مزاد تصفية هرتز للسيارات الصغيرة والمتوسطة ذات الاستهلاك الاقتصادي للوقود.',
            'brochure_pdf' => '/assets/docs/watania_brochure.pdf',
            'status' => 'active', 'start_time' => date('Y-m-d H:i:s', $now - 3600 * 24 * 2),
            'end_time' => date('Y-m-d H:i:s', $now + 345600), // 4 days
            'seller' => 'هرتز السعودية'
        ],
        [
            'id' => 6, 'seller_id' => 6,
            'title' => 'مزاد المفتاح لتصفية السيارات المستعملة',
            'description' => 'المزاد العام لشركة المفتاح لتصفية أسطولها من سيارات هيونداي وتويوتا في الرياض وجدة.',
            'brochure_pdf' => '/assets/docs/budget_brochure.pdf',
            'status' => 'active', 'start_time' => date('Y-m-d H:i:s', $now - 3600 * 6),
            'end_time' => date('Y-m-d H:i:s', $now + 43200), // 12 hours
            'seller' => 'المفتاح لتأجير السيارات'
        ],
        [
            'id' => 7, 'seller_id' => 7,
            'title' => 'مزاد الأفضل لتأجير السيارات - المنطقة الغربية',
            'description' => 'مزاد شركة الأفضل لتصفية أسطول سيارات شيفروليه ونيسان وجي إم سي بالمنطقة الغربية.',
            'brochure_pdf' => '/assets/docs/watania_brochure.pdf',
            'status' => 'active', 'start_time' => date('Y-m-d H:i:s', $now),
            'end_time' => date('Y-m-d H:i:s', $now + 518400), // 6 days
            'seller' => 'الأفضل لتأجير السيارات'
        ],
        [
            'id' => 8, 'seller_id' => 8,
            'title' => 'مزاد أسطول سيكست للسيارات العائلية والفاخرة',
            'description' => 'تصفية أسطول سيكست للسيارات الألمانية والسيارات ذات الحجم العائلي الفسيح.',
            'brochure_pdf' => '/assets/docs/budget_brochure.pdf',
            'status' => 'active', 'start_time' => date('Y-m-d H:i:s', $now - 3600 * 4),
            'end_time' => date('Y-m-d H:i:s', $now + 259200), // 3 days
            'seller' => 'سيكست السعودية'
        ],
        [
            'id' => 9, 'seller_id' => 9,
            'title' => 'مزاد تصفية سيارات شركة هانكو',
            'description' => 'مزاد عام على سيارات هانكو المستعملة المفحوصة والمعتمدة تحت إشراف الفحص الفني الخاص بالمنصة.',
            'brochure_pdf' => '/assets/docs/watania_brochure.pdf',
            'status' => 'active', 'start_time' => date('Y-m-d H:i:s', $now - 12000),
            'end_time' => date('Y-m-d H:i:s', $now + 28800), // 8 hours
            'seller' => 'هانكو لتأجير السيارات'
        ],
        [
            'id' => 10, 'seller_id' => 10,
            'title' => 'مزاد الفرسان للسيارات المستعملة والفاخرة',
            'description' => 'تصفية أسطول الفرسان من السيارات الفاخرة والمميزة ذات المواصفات العالية.',
            'brochure_pdf' => '/assets/docs/budget_brochure.pdf',
            'status' => 'active', 'start_time' => date('Y-m-d H:i:s', $now - 3600 * 48),
            'end_time' => date('Y-m-d H:i:s', $now + 432000), // 5 days
            'seller' => 'الفرسان لتأجير السيارات'
        ],
        [
            'id' => 11, 'seller_id' => 11,
            'title' => 'مزاد شركة أوتو ستار للسيارات المستردة',
            'description' => 'مزاد مميز للسيارات المستعملة بحالة ممتازة التابعة لشركة أوتو ستار بالمنطقة الشرقية والوسطى.',
            'brochure_pdf' => '/assets/docs/watania_brochure.pdf',
            'status' => 'active', 'start_time' => date('Y-m-d H:i:s', $now - 3600 * 24),
            'end_time' => date('Y-m-d H:i:s', $now + 777600), // 9 days
            'seller' => 'أوتو ستار'
        ],
        [
            'id' => 12, 'seller_id' => 12,
            'title' => 'مزاد أساطيل الجهات الحكومية والمؤسسات الموحد',
            'description' => 'المزاد الحكومي الموحد لتصفية أساطيل بعض الوزارات والمؤسسات الرسمية بتفاصيل فحص دقيقة.',
            'brochure_pdf' => '/assets/docs/budget_brochure.pdf',
            'status' => 'active', 'start_time' => date('Y-m-d H:i:s', $now),
            'end_time' => date('Y-m-d H:i:s', $now + 864000), // 10 days
            'seller' => 'منصة أساطيل الموحدة'
        ],
        [
            'id' => 13, 'seller_id' => 13,
            'title' => 'مزاد الشركة المتحدة للسيارات المستعملة',
            'description' => 'مزاد خاص لتصفية سيارات جيب ودودج وكرايسلر التابعة للشركة المتحدة للسيارات.',
            'brochure_pdf' => '/assets/docs/watania_brochure.pdf',
            'status' => 'active', 'start_time' => date('Y-m-d H:i:s', $now - 7200),
            'end_time' => date('Y-m-d H:i:s', $now + 86400), // 24 hours
            'seller' => 'المتحدة للسيارات'
        ],
        [
            'id' => 14, 'seller_id' => 14,
            'title' => 'مزاد أسطول شركة رينت إيه كار - الشرقية',
            'description' => 'تصفية أسطول شركة رينت إيه كار بالدمام والخبر لسيارات كيا وهيونداي وتويوتا.',
            'brochure_pdf' => '/assets/docs/budget_brochure.pdf',
            'status' => 'active', 'start_time' => date('Y-m-d H:i:s', $now - 3600 * 24 * 3),
            'end_time' => date('Y-m-d H:i:s', $now + 604800), // 7 days
            'seller' => 'رينت إيه كار الشرقية'
        ],
        [
            'id' => 15, 'seller_id' => 15,
            'title' => 'مزاد تصفية سيارات شركة يلو الحديثة 2024',
            'description' => 'مزاد تصفية أسطول شركة يلو لسيارات موديل 2024 لغرض التجديد السنوي للأسطول.',
            'brochure_pdf' => '/assets/docs/watania_brochure.pdf',
            'status' => 'active', 'start_time' => date('Y-m-d H:i:s', $now - 7200),
            'end_time' => date('Y-m-d H:i:s', $now + 14400), // 4 hours
            'seller' => 'يلو لتأجير السيارات'
        ]
    ];

    // Count vehicles in each event
    $auctions = getMockAuctions(200); // get all mock auctions to count
    foreach ($events as &$ev) {
        $ev['lot_count'] = count(array_filter($auctions, function($a) use ($ev) {
            return $a['event_id'] == $ev['id'];
        }));
    }
    return $events;
}

function getMockAuctions($limit = 9) {
    $now = time();
    $mock = [
        ['id'=>1,'event_id'=>1,'title'=>'تويوتا كامري 2.5L Prestige 2023','make'=>'Toyota','model'=>'Camry','year'=>2023,'mileage'=>45200,'fuel_type'=>'بنزين','transmission'=>'أوتوماتيك','city'=>'الرياض','current_price'=>85000,'bid_count'=>23,'end_time'=>date('Y-m-d H:i:s',$now+14400),'type'=>'live','image_url'=>'https://images.unsplash.com/photo-1621007947382-bb3c3994e3fb?w=600&q=80','seller'=>'الوطنية للتأجير', 'is_featured'=>true],
        ['id'=>2,'event_id'=>1,'title'=>'هيونداي توسان 2.0 AWD 2023','make'=>'Hyundai','model'=>'Tucson','year'=>2023,'mileage'=>28100,'fuel_type'=>'بنزين','transmission'=>'أوتوماتيك','city'=>'جدة','current_price'=>94000,'bid_count'=>31,'end_time'=>date('Y-m-d H:i:s',$now+18000),'type'=>'live','image_url'=>'https://images.unsplash.com/photo-1583121274602-3e2820c69888?w=600&q=80','seller'=>'الوطنية للتأجير', 'is_featured'=>false],
        ['id'=>3,'event_id'=>2,'title'=>'كيا سبورتاج 1.6T 2022','make'=>'Kia','model'=>'Sportage','year'=>2022,'mileage'=>38400,'fuel_type'=>'بنزين','transmission'=>'أوتوماتيك','city'=>'الدمام','current_price'=>78500,'bid_count'=>12,'end_time'=>date('Y-m-d H:i:s',$now+86400),'type'=>'live','image_url'=>'https://images.unsplash.com/photo-1606664515524-ed2f786a0bd6?w=600&q=80','seller'=>'بدجت السعودية', 'is_featured'=>true],
        ['id'=>4,'event_id'=>1,'title'=>'نيسان باترول 5.6 V8 2021','make'=>'Nissan','model'=>'Patrol','year'=>2021,'mileage'=>62800,'fuel_type'=>'بنزين','transmission'=>'أوتوماتيك','city'=>'الدمام','current_price'=>153000,'bid_count'=>8,'end_time'=>date('Y-m-d H:i:s',$now+10800),'type'=>'live','image_url'=>'https://images.unsplash.com/photo-1555215695-3004980ad54e?w=600&q=80','seller'=>'الوطنية للتأجير', 'is_featured'=>false],
        ['id'=>5,'event_id'=>1,'title'=>'تويوتا راف 4 2.5L 2023','make'=>'Toyota','model'=>'RAV4','year'=>2023,'mileage'=>22600,'fuel_type'=>'هجين','transmission'=>'أوتوماتيك','city'=>'الرياض','current_price'=>112000,'bid_count'=>19,'end_time'=>date('Y-m-d H:i:s',$now+3600),'type'=>'live','image_url'=>'https://images.unsplash.com/photo-1584345604476-8ec5e12e42dd?w=600&q=80','seller'=>'الوطنية للتأجير'],
        ['id'=>6,'event_id'=>2,'title'=>'مرسيدس E200 2021','make'=>'Mercedes','model'=>'E200','year'=>2021,'mileage'=>44700,'fuel_type'=>'بنزين','transmission'=>'أوتوماتيك','city'=>'الرياض','current_price'=>198000,'bid_count'=>5,'end_time'=>date('Y-m-d H:i:s',$now+86400),'type'=>'sealed','image_url'=>'https://images.unsplash.com/photo-1618843479313-40f8afb4b4d8?w=600&q=80','seller'=>'بدجت السعودية'],
        ['id'=>7,'event_id'=>1,'title'=>'فورد إكسبلورر 2022','make'=>'Ford','model'=>'Explorer','year'=>2022,'mileage'=>71200,'fuel_type'=>'بنزين','transmission'=>'أوتوماتيك','city'=>'جدة','current_price'=>132000,'bid_count'=>14,'end_time'=>date('Y-m-d H:i:s',$now+14400),'type'=>'live','image_url'=>'https://images.unsplash.com/photo-1533473359331-0135ef1b58bf?w=600&q=80','seller'=>'الوطنية للتأجير'],
        ['id'=>8,'event_id'=>2,'title'=>'هوندا أكورد 1.5T 2022','make'=>'Honda','model'=>'Accord','year'=>2022,'mileage'=>51300,'fuel_type'=>'بنزين','transmission'=>'أوتوماتيك','city'=>'الرياض','current_price'=>68000,'bid_count'=>27,'end_time'=>null,'type'=>'instant','image_url'=>'https://images.unsplash.com/photo-1580273916550-e323be2ae537?w=600&q=80','seller'=>'بدجت السعودية'],
        ['id'=>9,'event_id'=>1,'title'=>'BMW X5 3.0T 2022','make'=>'BMW','model'=>'X5','year'=>2022,'mileage'=>38000,'fuel_type'=>'بنزين','transmission'=>'أوتوماتيك','city'=>'الرياض','current_price'=>245000,'bid_count'=>9,'end_time'=>date('Y-m-d H:i:s',$now+18000),'type'=>'live','image_url'=>'https://images.unsplash.com/photo-1549317661-bd32c8ce0db2?w=600&q=80','seller'=>'الوطنية للتأجير'],
        
        // Additional mock data for Events 3 to 15 to enable 30+ vehicles
        ['id'=>10,'event_id'=>3,'title'=>'هيونداي إلنترا 1.6L 2022','make'=>'Hyundai','model'=>'Elantra','year'=>2022,'mileage'=>58200,'fuel_type'=>'بنزين','transmission'=>'أوتوماتيك','city'=>'الرياض','current_price'=>55000,'bid_count'=>18,'end_time'=>date('Y-m-d H:i:s',$now+172800),'type'=>'live','image_url'=>'https://images.unsplash.com/photo-1583121274602-3e2820c69888?w=600&q=80','seller'=>'يلو لتأجير السيارات'],
        ['id'=>11,'event_id'=>3,'title'=>'تويوتا يارس 1.5L 2023','make'=>'Toyota','model'=>'Yaris','year'=>2023,'mileage'=>34100,'fuel_type'=>'بنزين','transmission'=>'أوتوماتيك','city'=>'الرياض','current_price'=>48000,'bid_count'=>11,'end_time'=>date('Y-m-d H:i:s',$now+170000),'type'=>'live','image_url'=>'https://images.unsplash.com/photo-1621007947382-bb3c3994e3fb?w=600&q=80','seller'=>'يلو لتأجير السيارات'],
        ['id'=>12,'event_id'=>4,'title'=>'تويوتا كامري LE 2022','make'=>'Toyota','model'=>'Camry','year'=>2022,'mileage'=>61000,'fuel_type'=>'بنزين','transmission'=>'أوتوماتيك','city'=>'جدة','current_price'=>73000,'bid_count'=>15,'end_time'=>date('Y-m-d H:i:s',$now+10800),'type'=>'live','image_url'=>'https://images.unsplash.com/photo-1621007947382-bb3c3994e3fb?w=600&q=80','seller'=>'ذيب لتأجير السيارات'],
        ['id'=>13,'event_id'=>4,'title'=>'هيونداي سنتافي 2.5L 2021','make'=>'Hyundai','model'=>'SantaFe','year'=>2021,'mileage'=>78200,'fuel_type'=>'بنزين','transmission'=>'أوتوماتيك','city'=>'جدة','current_price'=>82000,'bid_count'=>7,'end_time'=>date('Y-m-d H:i:s',$now+10000),'type'=>'live','image_url'=>'https://images.unsplash.com/photo-1583121274602-3e2820c69888?w=600&q=80','seller'=>'ذيب لتأجير السيارات'],
        ['id'=>14,'event_id'=>5,'title'=>'تويوتا كورولا 1.6L 2022','make'=>'Toyota','model'=>'Corolla','year'=>2022,'mileage'=>52100,'fuel_type'=>'بنزين','transmission'=>'أوتوماتيك','city'=>'الدمام','current_price'=>58000,'bid_count'=>14,'end_time'=>date('Y-m-d H:i:s',$now+345600),'type'=>'live','image_url'=>'https://images.unsplash.com/photo-1621007947382-bb3c3994e3fb?w=600&q=80','seller'=>'هرتز السعودية'],
        ['id'=>15,'event_id'=>5,'title'=>'كيا سيراتو 1.6L 2023','make'=>'Kia','model'=>'Cerato','year'=>2023,'mileage'=>29400,'fuel_type'=>'بنزين','transmission'=>'أوتوماتيك','city'=>'الدمام','current_price'=>61000,'bid_count'=>10,'end_time'=>date('Y-m-d H:i:s',$now+340000),'type'=>'live','image_url'=>'https://images.unsplash.com/photo-1606664515524-ed2f786a0bd6?w=600&q=80','seller'=>'هرتز السعودية'],
        ['id'=>16,'event_id'=>6,'title'=>'هيونداي أكسنت 1.4L 2022','make'=>'Hyundai','model'=>'Accent','year'=>2022,'mileage'=>48200,'fuel_type'=>'بنزين','transmission'=>'أوتوماتيك','city'=>'الرياض','current_price'=>42000,'bid_count'=>16,'end_time'=>date('Y-m-d H:i:s',$now+43200),'type'=>'live','image_url'=>'https://images.unsplash.com/photo-1583121274602-3e2820c69888?w=600&q=80','seller'=>'المفتاح لتأجير السيارات'],
        ['id'=>17,'event_id'=>7,'title'=>'نيسان ألتيما 2.5L 2021','make'=>'Nissan','model'=>'Altima','year'=>2021,'mileage'=>69000,'fuel_type'=>'بنزين','transmission'=>'أوتوماتيك','city'=>'مكة المكرمة','current_price'=>64000,'bid_count'=>13,'end_time'=>date('Y-m-d H:i:s',$now+518400),'type'=>'live','image_url'=>'https://images.unsplash.com/photo-1555215695-3004980ad54e?w=600&q=80','seller'=>'الأفضل لتأجير السيارات'],
        ['id'=>18,'event_id'=>8,'title'=>'BMW 520i 2.0T 2022','make'=>'BMW','model'=>'520i','year'=>2022,'mileage'=>41200,'fuel_type'=>'بنزين','transmission'=>'أوتوماتيك','city'=>'الرياض','current_price'=>168000,'bid_count'=>21,'end_time'=>date('Y-m-d H:i:s',$now+259200),'type'=>'live','image_url'=>'https://images.unsplash.com/photo-1580273916550-e323be2ae537?w=600&q=80','seller'=>'سيكست السعودية'],
        ['id'=>19,'event_id'=>9,'title'=>'تويوتا هايلاندر هجين 2022','make'=>'Toyota','model'=>'Highlander','year'=>2022,'mileage'=>49600,'fuel_type'=>'هجين','transmission'=>'أوتوماتيك','city'=>'المدينة المنورة','current_price'=>118000,'bid_count'=>25,'end_time'=>date('Y-m-d H:i:s',$now+28800),'type'=>'live','image_url'=>'https://images.unsplash.com/photo-1584345604476-8ec5e12e42dd?w=600&q=80','seller'=>'هانكو لتأجير السيارات'],
        ['id'=>20,'event_id'=>10,'title'=>'مرسيدس C200 2022','make'=>'Mercedes','model'=>'C200','year'=>2022,'mileage'=>32000,'fuel_type'=>'بنزين','transmission'=>'أوتوماتيك','city'=>'الرياض','current_price'=>185000,'bid_count'=>12,'end_time'=>date('Y-m-d H:i:s',$now+432000),'type'=>'live','image_url'=>'https://images.unsplash.com/photo-1618843479313-40f8afb4b4d8?w=600&q=80','seller'=>'الفرسان لتأجير السيارات'],
        ['id'=>21,'event_id'=>11,'title'=>'كيا أوبتيما 2.4L 2020','make'=>'Kia','model'=>'Optima','year'=>2020,'mileage'=>82400,'fuel_type'=>'بنزين','transmission'=>'أوتوماتيك','city'=>'الدمام','current_price'=>49000,'bid_count'=>9,'end_time'=>date('Y-m-d H:i:s',$now+777600),'type'=>'live','image_url'=>'https://images.unsplash.com/photo-1606664515524-ed2f786a0bd6?w=600&q=80','seller'=>'أوتو ستار'],
        ['id'=>22,'event_id'=>12,'title'=>'تويوتا لاندكروزر V8 2020','make'=>'Toyota','model'=>'LandCruiser','year'=>2020,'mileage'=>115000,'fuel_type'=>'بنزين','transmission'=>'أوتوماتيك','city'=>'الرياض','current_price'=>195000,'bid_count'=>34,'end_time'=>date('Y-m-d H:i:s',$now+864000),'type'=>'live','image_url'=>'https://images.unsplash.com/photo-1533473359331-0135ef1b58bf?w=600&q=80','seller'=>'منصة أساطيل الموحدة'],
        ['id'=>23,'event_id'=>13,'title'=>'جيب جراند شيروكي 2021','make'=>'Jeep','model'=>'GrandCherokee','year'=>2021,'mileage'=>56200,'fuel_type'=>'بنزين','transmission'=>'أوتوماتيك','city'=>'الرياض','current_price'=>110000,'bid_count'=>16,'end_time'=>date('Y-m-d H:i:s',$now+86400),'type'=>'live','image_url'=>'https://images.unsplash.com/photo-1549317661-bd32c8ce0db2?w=600&q=80','seller'=>'المتحدة للسيارات'],
        ['id'=>24,'event_id'=>14,'title'=>'كيا سيلتوس 1.6L 2022','make'=>'Kia','model'=>'Seltos','year'=>2022,'mileage'=>38100,'fuel_type'=>'بنزين','transmission'=>'أوتوماتيك','city'=>'الدمام','current_price'=>62000,'bid_count'=>8,'end_time'=>date('Y-m-d H:i:s',$now+604800),'type'=>'live','image_url'=>'https://images.unsplash.com/photo-1606664515524-ed2f786a0bd6?w=600&q=80','seller'=>'رينت إيه كار الشرقية'],
        ['id'=>25,'event_id'=>15,'title'=>'هيونداي توسان Smart 2024','make'=>'Hyundai','model'=>'Tucson','year'=>2024,'mileage'=>14200,'fuel_type'=>'بنزين','transmission'=>'أوتوماتيك','city'=>'جدة','current_price'=>89000,'bid_count'=>22,'end_time'=>date('Y-m-d H:i:s',$now+14400),'type'=>'live','image_url'=>'https://images.unsplash.com/photo-1583121274602-3e2820c69888?w=600&q=80','seller'=>'يلو لتأجير السيارات'],
        ['id'=>26,'event_id'=>15,'title'=>'تويوتا كامري Hybrid 2024','make'=>'Toyota','model'=>'Camry','year'=>2024,'mileage'=>18900,'fuel_type'=>'هجين','transmission'=>'أوتوماتيك','city'=>'الرياض','current_price'=>96000,'bid_count'=>27,'end_time'=>date('Y-m-d H:i:s',$now+14400),'type'=>'live','image_url'=>'https://images.unsplash.com/photo-1621007947382-bb3c3994e3fb?w=600&q=80','seller'=>'يلو لتأجير السيارات'],
        ['id'=>27,'event_id'=>14,'title'=>'تويوتا فورتشنر 2.7L 2022','make'=>'Toyota','model'=>'Fortuner','year'=>2022,'mileage'=>67000,'fuel_type'=>'بنزين','transmission'=>'أوتوماتيك','city'=>'الدمام','current_price'=>98000,'bid_count'=>19,'end_time'=>date('Y-m-d H:i:s',$now+604800),'type'=>'live','image_url'=>'https://images.unsplash.com/photo-1533473359331-0135ef1b58bf?w=600&q=80','seller'=>'رينت إيه كار الشرقية'],
        ['id'=>28,'event_id'=>13,'title'=>'دودج تشارجر GT 2021','make'=>'Dodge','model'=>'Charger','year'=>2021,'mileage'=>49200,'fuel_type'=>'بنزين','transmission'=>'أوتوماتيك','city'=>'جدة','current_price'=>94000,'bid_count'=>21,'end_time'=>date('Y-m-d H:i:s',$now+86400),'type'=>'live','image_url'=>'https://images.unsplash.com/photo-1549317661-bd32c8ce0db2?w=600&q=80','seller'=>'المتحدة للسيارات'],
        ['id'=>29,'event_id'=>12,'title'=>'تويوتا هيلوكس غمارتين 2021','make'=>'Toyota','model'=>'Hilux','year'=>2021,'mileage'=>92100,'fuel_type'=>'ديزل','transmission'=>'يدوي','city'=>'الدمام','current_price'=>78000,'bid_count'=>15,'end_time'=>date('Y-m-d H:i:s',$now+864000),'type'=>'live','image_url'=>'https://images.unsplash.com/photo-1533473359331-0135ef1b58bf?w=600&q=80','seller'=>'منصة أساطيل الموحدة'],
        ['id'=>30,'event_id'=>11,'title'=>'هوندا سيفيك 1.5T 2022','make'=>'Honda','model'=>'Civic','year'=>2022,'mileage'=>38400,'fuel_type'=>'بنزين','transmission'=>'أوتوماتيك','city'=>'جدة','current_price'=>76000,'bid_count'=>14,'end_time'=>date('Y-m-d H:i:s',$now+777600),'type'=>'live','image_url'=>'https://images.unsplash.com/photo-1580273916550-e323be2ae537?w=600&q=80','seller'=>'أوتو ستار']
    ];

    
    // Generate extra instant purchase cars dynamically
    $extra_instant_cars = [];
    $makes = ['Toyota' => ['Camry', 'Corolla', 'Yaris', 'RAV4'], 'Hyundai' => ['Elantra', 'Sonata', 'Tucson', 'Accent'], 'Kia' => ['Sportage', 'Cerato', 'Optima', 'Seltos'], 'Nissan' => ['Sunny', 'Altima', 'Patrol']];
    $images = array (
  0 => 'https://images.unsplash.com/photo-1555215695-3004980ad54e?w=800&q=80',
  1 => 'https://images.unsplash.com/photo-1583121274602-3e2820c69888?w=800&q=80',
  2 => 'https://images.unsplash.com/photo-1621007947382-bb3c3994e3fb?w=800&q=80',
  3 => 'https://images.unsplash.com/photo-1549317661-bd32c8ce0db2?w=800&q=80',
  4 => 'https://images.unsplash.com/photo-1618843479313-40f8afb4b4d8?w=800&q=80',
  5 => 'https://images.unsplash.com/photo-1606664515524-ed2f786a0bd6?w=800&q=80',
  6 => 'https://images.unsplash.com/photo-1580273916550-e323be2ae537?w=800&q=80',
);
    $cities = ['الرياض', 'جدة', 'الدمام', 'مكة المكرمة', 'المدينة المنورة'];
    $sellers = ['الوطنية للتأجير', 'بدجت السعودية', 'يلو لتأجير السيارات', 'ذيب لتأجير السيارات', 'سيكست السعودية'];
    $fuel_types = ['بنزين', 'بنزين', 'بنزين', 'هجين'];
    
    $base_id = count($mock) + 1;
    for ($i = 0; $i < 60; $i++) {
        $make_keys = array_keys($makes);
        $make = $make_keys[array_rand($make_keys)];
        $model = $makes[$make][array_rand($makes[$make])];
        $year = rand(2018, 2024);
        $mileage = rand(10000, 150000);
        $city = $cities[array_rand($cities)];
        $seller = $sellers[array_rand($sellers)];
        $price = rand(20, 150) * 1000;
        
        $mock[] = [
            'id' => $base_id + $i,
            'event_id' => rand(1, 15),
            'title' => "$make $model $year",
            'make' => $make,
            'model' => $model,
            'year' => $year,
            'mileage' => $mileage,
            'fuel_type' => $fuel_types[array_rand($fuel_types)],
            'transmission' => 'أوتوماتيك',
            'city' => $city,
            'current_price' => $price,
            'starting_price' => $price,
            'bid_count' => 0,
            'end_time' => date('Y-m-d H:i:s', $now + rand(86400, 86400 * 14)),
            'type' => 'instant',
            'image_url' => $images[array_rand($images)],
            'seller' => $seller
        ];
    }


    // Merge session mock auctions
    if (isset($_SESSION['mock_auctions']) && is_array($_SESSION['mock_auctions'])) {
        $mock = array_merge($_SESSION['mock_auctions'], $mock);
    }

    // Merge custom live bids
    if (isset($_SESSION['mock_bids']) && is_array($_SESSION['mock_bids'])) {
        foreach ($_SESSION['mock_bids'] as $bid) {
            foreach ($mock as &$a) {
                if ($a['id'] == $bid['auction_id'] && $bid['amount'] > $a['current_price']) {
                    $a['current_price'] = $bid['amount'];
                    $a['bid_count']++;
                }
            }
        }
    }

    return array_slice($mock, 0, $limit);
}
?>
