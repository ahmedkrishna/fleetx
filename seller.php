<?php
require_once 'config.php';
requireLogin();
if (!in_array(getUserRole(), ['seller', 'admin'], true)) {
    header('Location: ' . getDashboardUrl());
    exit;
}

$section = isset($_GET['section']) ? sanitize($_GET['section']) : 'dashboard';
$user_name = $_SESSION['user_name'] ?? 'مستخدم';
$user_id = $_SESSION['user_id'] ?? 0;
$role = getUserRole();

// Get seller company info
$company = null;
if ($db_connected) {
    $stmt = $conn->prepare('SELECT * FROM seller_companies WHERE user_id = ?');
    $stmt->bind_param('i', $user_id);
    $stmt->execute();
    $company = $stmt->get_result()->fetch_assoc();
}
if (!$company && $db_connected && getUserRole() === 'seller') {
    $cname = $user_name . ' للتأجير';
    $cr = 'CR-' . rand(100000, 999999);
    $ins = $conn->prepare('INSERT INTO seller_companies (user_id, company_name, cr_number, fleet_size, is_verified) VALUES (?, ?, ?, 10, 1)');
    $ins->bind_param('iss', $user_id, $cname, $cr);
    if ($ins->execute()) {
        $company = [
            'id' => $conn->insert_id,
            'company_name' => $cname,
            'cr_number' => $cr,
            'subscription' => 'standard',
            'rating' => 0,
            'total_auctions' => 0,
            'is_verified' => 1,
        ];
    }
}
if (!$company && $db_connected && getUserRole() !== 'admin') {
    header('Location: /register.php?type=company&complete=1');
    exit;
}
if (!$company) {
    $company = [
        'id' => 0,
        'company_name' => 'شركة ' . $user_name,
        'cr_number' => '',
        'subscription' => 'standard',
        'rating' => 0,
        'total_auctions' => 0,
        'is_verified' => 0,
    ];
}
$company_id = (int)($company['id'] ?? 0);

// Handle settings POST
$settings_msg = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST' && $section === 'settings') {
    $company_name = sanitize($_POST['company_name'] ?? '');
    $cr_number = sanitize($_POST['cr_number'] ?? '');
    $city = sanitize($_POST['city'] ?? '');
    $phone = sanitize($_POST['phone'] ?? '');
    $email = sanitize($_POST['email'] ?? '');
    $personal_name = sanitize($_POST['personal_name'] ?? '');

    if ($db_connected) {
        $stmt = $conn->prepare('UPDATE seller_companies SET company_name=?, cr_number=?, city=?, phone=?, email=? WHERE user_id=?');
        $stmt->bind_param('sssssi', $company_name, $cr_number, $city, $phone, $email, $user_id);
        if ($stmt->execute()) {
            $settings_msg = 'success';
            $company['company_name'] = $company_name;
            $company['cr_number'] = $cr_number;
            $company['city'] = $city;
            $company['phone'] = $phone;
            $company['email'] = $email;
        } else {
            $settings_msg = 'error';
        }
        if ($personal_name) {
            $stmt2 = $conn->prepare('UPDATE users SET full_name=? WHERE id=?');
            $stmt2->bind_param('si', $personal_name, $user_id);
            $stmt2->execute();
            $_SESSION['user_name'] = $personal_name;
            $user_name = $personal_name;
        }
    } else {
        // Mock save
        $settings_msg = 'success';
        $company['company_name'] = $company_name ?: $company['company_name'];
        $company['cr_number'] = $cr_number ?: $company['cr_number'];
        $company['city'] = $city ?: $company['city'];
        $company['phone'] = $phone ?: $company['phone'];
        $company['email'] = $email ?: $company['email'];
        if ($personal_name) {
            $_SESSION['user_name'] = $personal_name;
            $user_name = $personal_name;
        }
    }
}

// Upload company legal documents
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['upload_document']) && $section === 'settings') {
    $doc_type = in_array($_POST['doc_type'] ?? '', ['cr','vat','istimara','insurance','other'], true) ? $_POST['doc_type'] : 'other';
    if ($db_connected && $company_id && isset($_FILES['doc_file']) && $_FILES['doc_file']['error'] === UPLOAD_ERR_OK) {
        $dir = __DIR__ . '/uploads/documents/';
        if (!is_dir($dir)) mkdir($dir, 0755, true);
        $ext = strtolower(pathinfo($_FILES['doc_file']['name'], PATHINFO_EXTENSION));
        if (in_array($ext, ['pdf','jpg','jpeg','png'], true)) {
            $fname = 'doc_' . $company_id . '_' . time() . '.' . $ext;
            if (move_uploaded_file($_FILES['doc_file']['tmp_name'], $dir . $fname) && fleetx_table_exists($conn, 'company_documents')) {
                $url = '/uploads/documents/' . $fname;
                $dstmt = $conn->prepare('INSERT INTO company_documents (seller_id, doc_type, file_url) VALUES (?,?,?)');
                $dstmt->bind_param('iss', $company_id, $doc_type, $url);
                $dstmt->execute();
                notifyUser($conn, 1, 'system', 'مستند جديد للمراجعة', 'رفعت شركة ' . $company['company_name'] . ' مستنداً قانونياً', '/admin/approvals.php');
                fleetx_set_toast('تم رفع المستند — بانتظار مراجعة الإدارة');
            }
        }
    }
    header('Location: ?section=settings');
    exit;
}

// Handle Push to Inspection (admin gate — no auto-assign)
if (isset($_GET['push_inspection'])) {
    $vehicle_id = intval($_GET['push_inspection']);
    if ($db_connected && $vehicle_id > 0 && !empty($company_id)) {
        $insp_fee = fleetx_inspection_fee($conn);
        $conn->begin_transaction();
        try {
            $wstmt = $conn->prepare('SELECT wallet_balance, user_id FROM seller_companies sc JOIN users u ON sc.user_id=u.id WHERE sc.id=?');
            $wstmt->bind_param('i', $company_id);
            $wstmt->execute();
            $wrow = $wstmt->get_result()->fetch_assoc();
            $seller_user_id = (int)($wrow['user_id'] ?? $user_id);
            $wallet = (float)($wrow['wallet_balance'] ?? 0);
            if ($insp_fee > 0 && $wallet < $insp_fee) {
                throw new Exception('insufficient_wallet');
            }
            $stmt = $conn->prepare("UPDATE vehicles SET status='awaiting_admin' WHERE id=? AND seller_id=? AND status IN ('pending','withdrawn','suspended')");
            $stmt->bind_param('ii', $vehicle_id, $company_id);
            $stmt->execute();
            if ($stmt->affected_rows === 0) throw new Exception('invalid');

            $stmt2 = $conn->prepare("INSERT INTO inspections (vehicle_id, inspector_id, status, inspection_date) VALUES (?, NULL, 'awaiting_admin', CURDATE()) ON DUPLICATE KEY UPDATE status='awaiting_admin', inspector_id=NULL, admin_approved=0, seller_approved=NULL");
            $stmt2->bind_param('i', $vehicle_id);
            $stmt2->execute();

            if ($insp_fee > 0 && $seller_user_id) {
                $fee_stmt = $conn->prepare('UPDATE users SET wallet_balance = wallet_balance - ? WHERE id=?');
                $fee_stmt->bind_param('di', $insp_fee, $seller_user_id);
                $fee_stmt->execute();
            }

            $conn->commit();
            notifyUser($conn, 1, 'system', 'طلب فحص جديد', 'قامت شركة ' . $company['company_name'] . ' بطلب فحص مركبة — بانتظار موافقة الإدارة', '/admin/approvals.php', ['in_app', 'email']);
            $fee_msg = $insp_fee > 0 ? ' (تم خصم ' . number_format($insp_fee) . ' ر.س رسوم الفحص)' : '';
            fleetx_set_toast('تم إرسال طلب الفحص — بانتظار موافقة الإدارة' . $fee_msg);
            header('Location: ?section=fleet&msg=pushed');
            exit;
        } catch (Exception $e) {
            $conn->rollback();
            if ($e->getMessage() === 'insufficient_wallet') {
                fleetx_set_toast('رصيد المحفظة غير كافٍ لرسوم الفحص (' . number_format(fleetx_inspection_fee($conn)) . ' ر.س)', 'error');
                header('Location: ?section=wallet&msg=insufficient');
                exit;
            }
        }
    }
}

// Suspend / unsuspend vehicle
if (isset($_GET['suspend'])) {
    $vid = (int)$_GET['suspend'];
    if ($db_connected && $vid > 0 && $company_id) {
        $stmt = $conn->prepare("UPDATE vehicles SET status='suspended' WHERE id=? AND seller_id=? AND status NOT IN ('sold','in_auction')");
        $stmt->bind_param('ii', $vid, $company_id);
        $stmt->execute();
        fleetx_set_toast('تم إيقاف المركبة مؤقتاً');
        header('Location: ?section=fleet');
        exit;
    }
}
if (isset($_GET['unsuspend'])) {
    $vid = (int)$_GET['unsuspend'];
    if ($db_connected && $vid > 0 && $company_id) {
        $stmt = $conn->prepare("UPDATE vehicles SET status='pending' WHERE id=? AND seller_id=? AND status='suspended'");
        $stmt->bind_param('ii', $vid, $company_id);
        $stmt->execute();
        fleetx_set_toast('تم إعادة تفعيل المركبة');
        header('Location: ?section=fleet');
        exit;
    }
}

// Seller approve / reject completed inspection
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['inspection_action'])) {
    $insp_id = (int)($_POST['inspection_id'] ?? 0);
    $action = $_POST['inspection_action'];
    if ($db_connected && $insp_id > 0 && $company_id) {
        $chk = $conn->prepare("SELECT i.id, i.vehicle_id, v.make, v.model, v.year FROM inspections i JOIN vehicles v ON i.vehicle_id=v.id WHERE i.id=? AND v.seller_id=? AND i.status='completed' AND i.seller_approved IS NULL");
        $chk->bind_param('ii', $insp_id, $company_id);
        $chk->execute();
        $insp = $chk->get_result()->fetch_assoc();
        if ($insp) {
            if ($action === 'approve') {
                $conn->query("UPDATE inspections SET seller_approved=1 WHERE id=" . (int)$insp_id);
                $conn->query("UPDATE vehicles SET status='approved' WHERE id=" . (int)$insp['vehicle_id']);
                fleetx_set_toast('تم اعتماد تقرير الفحص — يمكنك نشر المركبة في المزاد');
            } elseif ($action === 'reject') {
                $conn->query("UPDATE inspections SET seller_approved=0, status='rejected' WHERE id=" . (int)$insp_id);
                $conn->query("UPDATE vehicles SET status='withdrawn' WHERE id=" . (int)$insp['vehicle_id']);
                fleetx_set_toast('تم رفض تقرير الفحص');
            }
        }
        header('Location: ?section=reports');
        exit;
    }
}

// Seller accept / reject auction result
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['auction_decision'])) {
    $auction_id = (int)($_POST['auction_id'] ?? 0);
    $decision = $_POST['auction_decision'];
    if ($db_connected && $auction_id > 0 && $company_id) {
        $astmt = $conn->prepare("SELECT a.*, v.id as vid, v.make, v.model, v.year FROM auctions a JOIN vehicles v ON a.vehicle_id=v.id WHERE a.id=? AND a.seller_id=? AND a.status='ended' AND a.seller_decision='pending'");
        $astmt->bind_param('ii', $auction_id, $company_id);
        $astmt->execute();
        $auc = $astmt->get_result()->fetch_assoc();
        if ($auc) {
            $car_name = $auc['title'] ?: $auc['make'] . ' ' . $auc['model'] . ' ' . $auc['year'];
            if ($decision === 'accept' && $auc['winner_id']) {
                $conn->begin_transaction();
                try {
                    $fee = (float)$auc['sale_price'] * (PLATFORM_FEE_PERCENT / 100);
                    $payout = (float)$auc['sale_price'] - $fee;
                    $chk = $conn->prepare('SELECT id FROM transactions WHERE auction_id=?');
                    $chk->bind_param('i', $auction_id);
                    $chk->execute();
                    if ($chk->get_result()->num_rows === 0) {
                        $tx = $conn->prepare("INSERT INTO transactions (auction_id, buyer_id, seller_id, sale_price, platform_fee, seller_payout, payment_status) VALUES (?,?,?,?,?,?,'pending')");
                        $tx->bind_param('iiiddd', $auction_id, $auc['winner_id'], $company_id, $auc['sale_price'], $fee, $payout);
                        $tx->execute();
                    }
                    $conn->query("UPDATE auctions SET seller_decision='accepted' WHERE id=$auction_id");
                    $conn->query("UPDATE vehicles SET status='sold' WHERE id=" . (int)$auc['vid']);
                    notifyUser($conn, (int)$auc['winner_id'], 'auction_won', 'تم قبول البيع', "وافق البائع على بيع {$car_name}. أكمل الدفع الآن.", '/checkout.php?id=' . $auction_id, ['in_app', 'sms']);
                    $conn->commit();
                    fleetx_set_toast('تم قبول نتيجة المزاد');
                } catch (Exception $e) {
                    $conn->rollback();
                }
            } elseif ($decision === 'reject') {
                $conn->query("UPDATE auctions SET seller_decision='rejected' WHERE id=$auction_id");
                if ($auc['winner_id']) {
                    notifyUser($conn, (int)$auc['winner_id'], 'auction_end', 'رفض البائع البيع', "لم يقبل البائع بيع {$car_name}.", '/auctions.php');
                }
                fleetx_set_toast('تم رفض نتيجة المزاد');
            }
        }
        header('Location: ?section=results');
        exit;
    }
}

// Publish approved vehicle to auction
if (isset($_GET['publish_auction'])) {
    $vehicle_id = intval($_GET['publish_auction']);
    if ($db_connected && $vehicle_id > 0 && !empty($company_id)) {
        $vstmt = $conn->prepare("SELECT * FROM vehicles WHERE id=? AND seller_id=? AND status='approved' LIMIT 1");
        $vstmt->bind_param('ii', $vehicle_id, $company_id);
        $vstmt->execute();
        $veh = $vstmt->get_result()->fetch_assoc();
        if ($veh) {
            $exists = $conn->prepare("SELECT id FROM auctions WHERE vehicle_id=? AND status IN ('active','live','draft') LIMIT 1");
            $exists->bind_param('i', $vehicle_id);
            $exists->execute();
            if ($exists->get_result()->num_rows === 0) {
                $title = $veh['make'] . ' ' . $veh['model'] . ' ' . $veh['year'];
                $start = date('Y-m-d H:i:s');
                $end = date('Y-m-d H:i:s', strtotime('+48 hours'));
                $price = floatval($veh['autodata_price_min'] ?? 50000);
                $ins = $conn->prepare("INSERT INTO auctions (vehicle_id, seller_id, title, type, status, starting_price, current_price, bid_increment, start_time, end_time, admin_approved) VALUES (?,?,?,'live','draft',?,?,500,?,?,0)");
                $ins->bind_param('iissdss', $vehicle_id, $company_id, $title, $price, $price, $start, $end);
                $ins->execute();
                notifyUser($conn, 1, 'system', 'مزاد جديد بانتظار الموافقة', 'طلب نشر مزاد: ' . $title, '/admin/approvals.php');
                fleetx_set_toast('تم إرسال المزاد للموافقة — سيتم نشره بعد مراجعة الإدارة');
            }
            header('Location: ?section=fleet&msg=published');
            exit;
        }
    }
}

// Handle Delete Vehicle
if (isset($_GET['delete'])) {
    $del_id = intval($_GET['delete']);
    if ($db_connected && $del_id > 0 && !empty($company_id)) {
        $stmt = $conn->prepare("DELETE FROM vehicles WHERE id=? AND seller_id=?");
        $stmt->bind_param('ii', $del_id, $company_id);
        $stmt->execute();
        header('Location: ?section=fleet&msg=deleted');
        exit;
    }
}

// Get fleet data for fleet + dashboard sections (all vehicles, with optional auction)
$fleet_auctions = [];
$total_sales = 0;
$fleet_count = 0;
if ($db_connected && !empty($company_id)) {
    $stmt = $conn->prepare("
        SELECT v.id as vehicle_id, v.make, v.model, v.year, v.city, v.mileage, v.image_url,
               v.status as v_status, v.autodata_price_min, v.autodata_price_max,
               a.id, a.title, a.current_price, a.starting_price, a.status, a.type,
               (SELECT COUNT(*) FROM bids WHERE auction_id=a.id) as bid_count,
               (SELECT MAX(amount) FROM bids WHERE auction_id=a.id) as top_bid
        FROM vehicles v
        LEFT JOIN auctions a ON a.vehicle_id = v.id AND a.status NOT IN ('ended','cancelled')
        WHERE v.seller_id = ?
        ORDER BY v.created_at DESC
    ");
    $stmt->bind_param('i', $company_id);
    $stmt->execute();
    $result = $stmt->get_result();
    while ($row = $result->fetch_assoc()) {
        $fleet_auctions[] = $row;
    }
    $fleet_count = count($fleet_auctions);
    // Get total sales
    $stmt2 = $conn->prepare('SELECT COALESCE(SUM(sale_price), 0) FROM transactions WHERE seller_id=? AND payment_status="paid"');
    $stmt2->bind_param('i', $company_id);
    $stmt2->execute();
    $total_sales = $stmt2->get_result()->fetch_row()[0];
    // Get pending dues
    $stmt3 = $conn->prepare('SELECT COALESCE(SUM(seller_payout), 0) FROM transactions WHERE seller_id=? AND payment_status="pending"');
    if ($stmt3) {
        $stmt3->bind_param('i', $company_id);
        $stmt3->execute();
        $pending_dues = $stmt3->get_result()->fetch_row()[0];
    } else {
        $pending_dues = 0;
    }
} else {
    $pending_dues = 0;
}

// Seller dashboard analytics
$seller_chart_labels = [];
$seller_chart_sales = [];
$seller_activities = [];
$active_auctions_count = 0;
$pending_inspections = 0;

if ($db_connected && !empty($company_id)) {
    for ($m = 5; $m >= 0; $m--) {
        $month_start = date('Y-m-01', strtotime("-$m months"));
        $month_end = date('Y-m-t', strtotime("-$m months"));
        $seller_chart_labels[] = date('M Y', strtotime($month_start));
        $cstmt = $conn->prepare("SELECT COALESCE(SUM(sale_price),0) FROM transactions WHERE seller_id=? AND payment_status='paid' AND paid_at BETWEEN ? AND ?");
        $month_end_sql = $month_end . ' 23:59:59';
        $cstmt->bind_param('iss', $company_id, $month_start, $month_end_sql);
        $cstmt->execute();
        $seller_chart_sales[] = (float)($cstmt->get_result()->fetch_row()[0] ?? 0);
    }

    $ac = $conn->prepare("SELECT COUNT(*) FROM auctions WHERE seller_id=? AND status IN ('active','live')");
    $ac->bind_param('i', $company_id);
    $ac->execute();
    $active_auctions_count = (int)$ac->get_result()->fetch_row()[0];

    $pi = $conn->prepare("SELECT COUNT(*) FROM vehicles WHERE seller_id=? AND status='pending'");
    $pi->bind_param('i', $company_id);
    $pi->execute();
    $pending_inspections = (int)$pi->get_result()->fetch_row()[0];

    $act = $conn->prepare("
        SELECT 'bid' as src, b.amount as val, b.created_at, CONCAT('مزايدة جديدة على ', COALESCE(a.title, v.make)) as msg
        FROM bids b
        JOIN auctions a ON b.auction_id = a.id
        JOIN vehicles v ON a.vehicle_id = v.id
        WHERE a.seller_id = ?
        UNION ALL
        SELECT 'inspection', i.overall_score, i.created_at, CONCAT('طلب فحص ', v.make, ' ', v.model) as msg
        FROM inspections i JOIN vehicles v ON i.vehicle_id = v.id WHERE v.seller_id = ?
        ORDER BY created_at DESC LIMIT 8
    ");
    if ($act) {
        $act->bind_param('ii', $company_id, $company_id);
        $act->execute();
        $ares = $act->get_result();
        while ($ar = $ares->fetch_assoc()) $seller_activities[] = $ar;
    }
}

// Also fetch draft/pending vehicles (not yet in auctions) for fleet management
$draft_vehicles = [];
if ($db_connected) {
    $dstmt = $conn->prepare("SELECT v.* FROM vehicles v WHERE v.seller_id=? AND v.id NOT IN (SELECT vehicle_id FROM auctions WHERE seller_id=?) ORDER BY v.created_at DESC");
    if ($dstmt) {
        $dstmt->bind_param('ii', $company_id, $company_id);
        $dstmt->execute();
        $dres = $dstmt->get_result();
        while ($dr = $dres->fetch_assoc()) {
            $draft_vehicles[] = $dr;
        }
    }
}

// Subscription data
$plans = [
    'standard' => [
        'name' => 'الباقة الأساسية',
        'price' => 'مجاناً',
        'price_num' => 0,
        'color' => '#64748b',
        'icon' => 'ph-package',
        'features' => ['حتى 5 مركبات شهرياً', 'تقارير أساسية', 'دعم بالبريد الإلكتروني', 'عمولة 3% على المبيعات']
    ],
    'premium' => [
        'name' => 'الباقة المتقدمة',
        'price' => '999 ر.س/شهر',
        'price_num' => 999,
        'color' => '#0ea5e9',
        'icon' => 'ph-crown',
        'features' => ['حتى 50 مركبة شهرياً', 'تقارير تفصيلية وتحليلات', 'دعم أولوية 24/7', 'عمولة 1.5% على المبيعات', 'شارة بائع متميز', 'ترويج في الصفحة الرئيسية']
    ],
    'enterprise' => [
        'name' => 'باقة المؤسسات',
        'price' => 'تواصل معنا',
        'price_num' => -1,
        'color' => '#1bc976',
        'icon' => 'ph-buildings',
        'features' => ['مركبات غير محدودة', 'مدير حساب مخصص', 'API ربط مباشر', 'عمولة مخفضة 0.5%', 'تقارير مخصصة وبيانات حية', 'أولوية القوائم والعرض', 'تدريب فريق العمل']
    ]
];
$current_plan = $company['subscription'] ?? 'premium';
// Inspection reports
$reports = [];
if ($db_connected) {
    $stmt = $conn->prepare("SELECT i.*, v.make, v.model, v.year, v.vin, u.full_name AS inspector_name FROM inspections i JOIN vehicles v ON i.vehicle_id = v.id LEFT JOIN users u ON i.inspector_id = u.id WHERE v.seller_id = ? ORDER BY i.inspection_date DESC, i.id DESC LIMIT 20");
    if ($stmt) {
        $stmt->bind_param('i', $company_id);
        $stmt->execute();
        $res = $stmt->get_result();
        while ($r = $res->fetch_assoc()) {
            $reports[] = [
                'id' => $r['id'],
                'vehicle' => $r['make'].' '.$r['model'].' '.$r['year'],
                'vin' => $r['vin'],
                'date' => isset($r['inspection_date']) ? date('Y-m-d', strtotime($r['inspection_date'])) : date('Y-m-d'),
                'inspector' => $r['inspector_name'] ?? 'مفتش المنصة',
                'exterior' => $r['exterior_score'] ?? 90,
                'interior' => $r['interior_score'] ?? 90,
                'mechanical' => $r['mechanical_score'] ?? 90,
                'overall' => round((($r['exterior_score'] ?? 90) + ($r['interior_score'] ?? 90) + ($r['mechanical_score'] ?? 90) + ($r['electronics_score'] ?? 90)) / 4),
                'status' => $r['status'] ?? 'pending',
                'seller_approved' => $r['seller_approved'] ?? null,
                'report_pdf' => $r['report_pdf'] ?? '',
            ];
        }
    }
}

$seller_bids = [];
$pending_auction_results = [];
if ($db_connected && $company_id) {
    $bstmt = $conn->prepare("
        SELECT b.amount, b.created_at, u.full_name as bidder_name, a.id as auction_id, a.title, a.status,
               v.make, v.model, v.year
        FROM bids b
        JOIN auctions a ON b.auction_id=a.id
        JOIN users u ON b.user_id=u.id
        JOIN vehicles v ON a.vehicle_id=v.id
        WHERE a.seller_id=?
        ORDER BY b.created_at DESC LIMIT 50
    ");
    if ($bstmt) {
        $bstmt->bind_param('i', $company_id);
        $bstmt->execute();
        $bres = $bstmt->get_result();
        while ($br = $bres->fetch_assoc()) $seller_bids[] = $br;
    }
    $rstmt = $conn->prepare("
        SELECT a.*, u.full_name as winner_name, v.make, v.model, v.year
        FROM auctions a
        LEFT JOIN users u ON a.winner_id=u.id
        JOIN vehicles v ON a.vehicle_id=v.id
        WHERE a.seller_id=? AND a.status='ended' AND a.seller_decision='pending' AND a.winner_id IS NOT NULL
        ORDER BY a.end_time DESC
    ");
    if ($rstmt) {
        $rstmt->bind_param('i', $company_id);
        $rstmt->execute();
        $rres = $rstmt->get_result();
        while ($rr = $rres->fetch_assoc()) $pending_auction_results[] = $rr;
    }
}

?>
<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>لوحة البائع | FleetX</title>
  <link rel="stylesheet" href="<?= fleetx_css_href() ?>">
  </head>
<body class="fx-home fx-page-shell fx-page-shell--seller">
<?php include 'includes/navbar.php'; ?>

<?php
$hero_title_html = sanitize($company['company_name']);
if (!empty($company['is_verified'])) {
    $hero_title_html .= ' <span class="verified-badge"><i class="ph-fill ph-seal-check"></i> بائع موثق</span>';
}
$hero_desc = 'إدارة كاملة لأسطولك ومزاداتك ومستحقاتك المالية';
$hero_bg = 'https://images.unsplash.com/photo-1549317661-bd32c8ce0db2?w=1600&q=80';
$hero_eyebrow = 'لوحة البائع';
$hero_meta_html = '<span class="fx-page-hero__chip"><i class="ph-fill ph-car"></i> ' . (int)$fleet_count . ' مركبة معروضة</span>'
    . '<span class="fx-page-hero__chip"><i class="ph-fill ph-currency-circle-dollar"></i> ' . number_format((float)$total_sales) . ' ر.س مبيعات</span>'
    . '<span class="fx-page-hero__chip fx-page-hero__chip--accent"><i class="ph-fill ph-gavel"></i> ' . (int)($active_auctions_count ?? 0) . ' مزاد نشط</span>';
$hero_actions_html = '';
$hero_modifier = 'dashboard';
$hero_extra_class = 'fx-page-hero--seller';
include 'includes/page-hero.inc.php';
?>

<div class="container fx-page-body fx-page-body--overlap fx-seller-page">
  <div class="fx-seller-layout">

  <!-- ── SIDEBAR ── -->
  <aside class="fx-profile-sidebar fx-profile-sidebar--home fx-seller-sidebar">
    <div class="fx-seller-profile">
      <div class="fx-seller-avatar"><i class="ph-fill ph-buildings"></i></div>
      <div class="fx-seller-company-name"><?= sanitize($company['company_name']) ?></div>
      <span class="subscription-badge sub-<?= $current_plan ?>">
        <i class="ph-fill ph-crown"></i>
        <?= $plans[$current_plan]['name'] ?? 'الباقة المتقدمة' ?>
      </span>
      <?php if ($company['is_verified']): ?>
      <div class="fx-seller-verified">
        <i class="ph-fill ph-seal-check"></i> حساب موثق ومعتمد
      </div>
      <?php endif; ?>
    </div>
    <ul class="fx-profile-nav fx-seller-nav">
      <li><a href="?section=dashboard" class="<?= $section==='dashboard'?'active':'' ?>"><i class="ph ph-chart-bar"></i> لوحة التحكم</a></li>
      <li><a href="?section=fleet" class="<?= $section==='fleet'?'active':'' ?>"><i class="ph ph-car"></i> أسطولي المعروض</a></li>
      <li><a href="?section=bids" class="<?= $section==='bids'?'active':'' ?>"><i class="ph ph-gavel"></i> المزايدات الواردة</a></li>
      <li><a href="?section=results" class="<?= $section==='results'?'active':'' ?>"><i class="ph ph-handshake"></i> نتائج المزادات</a></li>
      <li class="fx-seller-nav-label">إضافة إعلان</li>
      <li><a href="/add-auction.php" class="fx-seller-nav-sub <?= $section==='add_auction'?'active':'' ?>"><i class="ph ph-gavel fx-icon-primary"></i> جدولة مزاد مباشر</a></li>
      <li><a href="/add-auction.php?type=instant" class="fx-seller-nav-sub"><i class="ph ph-lightning fx-icon-warning"></i> بيع فوري</a></li>
      <li><a href="/bulk-upload.php" class="fx-seller-nav-sub"><i class="ph ph-upload-simple fx-icon-purple"></i> رفع مجمّع Excel</a></li>
      <li><a href="?section=payouts" class="<?= $section==='payouts'?'active':'' ?>"><i class="ph ph-money"></i> المستحقات المالية</a></li>
      <li><a href="?section=wallet" class="<?= $section==='wallet'?'active':'' ?>"><i class="ph ph-wallet"></i> المحفظة</a></li>
      <li><a href="?section=reports" class="<?= $section==='reports'?'active':'' ?>"><i class="ph ph-clipboard-text"></i> تقارير الفحص</a></li>
      <li><a href="?section=subscription" class="<?= $section==='subscription'?'active':'' ?>"><i class="ph ph-crown"></i> الباقة والاشتراك</a></li>
      <li><a href="?section=settings" class="<?= $section==='settings'?'active':'' ?>"><i class="ph ph-gear"></i> إعدادات الحساب</a></li>
      <li><a href="/logout.php" class="danger"><i class="ph ph-sign-out"></i> تسجيل خروج</a></li>
    </ul>
  </aside>

  <!-- ── MAIN CONTENT ── -->
  <main class="fx-seller-main">
    <div class="fx-dash-mobile-profile fx-dash-mobile-profile--seller">
      <div class="fx-dash-mobile-profile__avatar"><i class="ph-fill ph-buildings"></i></div>
      <div>
        <div class="fx-dash-mobile-profile__name"><?= sanitize($company['company_name']) ?></div>
        <div class="fx-dash-mobile-profile__meta">
          <?= sanitize($plans[$current_plan]['name'] ?? 'الباقة المتقدمة') ?>
          <?php if (!empty($company['is_verified'])): ?> · موثق<?php endif; ?>
        </div>
      </div>
    </div>
    <div class="fx-dash-mobile-nav">
      <select onchange="if(this.value) window.location.href=this.value" aria-label="قائمة لوحة البائع">
        <option value="">انتقل إلى قسم...</option>
        <option value="?section=dashboard" <?= $section==='dashboard'?'selected':'' ?>>لوحة التحكم</option>
        <option value="?section=fleet" <?= $section==='fleet'?'selected':'' ?>>أسطولي المعروض</option>
        <option value="/add-auction.php">جدولة مزاد مباشر</option>
        <option value="/add-auction.php?type=instant">بيع فوري</option>
        <option value="/bulk-upload.php">رفع مجمّع Excel</option>
        <option value="?section=bids" <?= $section==='bids'?'selected':'' ?>>المزايدات الواردة</option>
        <option value="?section=results" <?= $section==='results'?'selected':'' ?>>نتائج المزادات</option>
        <option value="?section=payouts" <?= $section==='payouts'?'selected':'' ?>>المستحقات المالية</option>
        <option value="?section=wallet" <?= $section==='wallet'?'selected':'' ?>>المحفظة</option>
        <option value="?section=reports" <?= $section==='reports'?'selected':'' ?>>تقارير الفحص</option>
        <option value="?section=subscription" <?= $section==='subscription'?'selected':'' ?>>الباقة والاشتراك</option>
        <option value="?section=settings" <?= $section==='settings'?'selected':'' ?>>إعدادات الحساب</option>
      </select>
    </div>

    <?php
    // Nafath Check for Seller
    $nafath_verified = $_SESSION['nafath_verified'] ?? 0;
    if ($db_connected && !$nafath_verified) {
        $nst = $conn->prepare("SELECT nafath_verified FROM users WHERE id = ?");
        if($nst) {
            $nst->bind_param('i', $user_id); $nst->execute();
            if ($row = $nst->get_result()->fetch_assoc()) {
                $nafath_verified = $row['nafath_verified'];
                $_SESSION['nafath_verified'] = $nafath_verified;
            }
        }
    }
    ?>
    <?php if (!$nafath_verified): ?>
      <div class="fx-dash-alert fx-dash-alert--danger">
        <div class="fx-dash-alert__body">
          <i class="ph-fill ph-warning-circle"></i>
          <span>حسابك كبائع غير موثق في نفاذ. نرجو التوثيق لتتمكن من إضافة المزادات وعرض سياراتك للبيع.</span>
        </div>
        <a href="/nafath.php" class="btn btn-primary fx-dash-alert__btn">توثيق الآن</a>
      </div>
    <?php else: ?>
      <div class="fx-dash-alert fx-dash-alert--success">
        <i class="ph-fill ph-check-circle"></i>
        <span>الشركة موثقة عبر النفاذ الوطني</span>
      </div>
    <?php endif; ?>    <!-- ══════════════════════════════════════════════ -->
    <!-- SECTION: DASHBOARD                            -->
    <!-- ══════════════════════════════════════════════ -->
    <?php if ($section === 'dashboard'): ?>

    <div class="seller-header-bar fx-seller-card">
      <h1 class="seller-section-title"><i class="ph-fill ph-chart-bar fx-icon-primary"></i> لوحة التحكم</h1>
      <div class="fx-seller-actions-top">
        <a href="/add-auction.php" class="btn-action-top btn-action-top--live">
          <i class="ph ph-gavel"></i> مزاد مباشر
        </a>
        <a href="/add-auction.php?type=instant" class="btn-action-top btn-action-top--instant">
          <i class="ph ph-lightning"></i> بيع فوري
        </a>
        <a href="/bulk-upload.php" class="btn-action-top btn-action-top--bulk">
          <i class="ph ph-upload-simple"></i> رفع Excel
        </a>
      </div>
    </div>

    <div class="stats-grid fx-seller-stats">
      <div class="stat-card primary">
        <div class="stat-card-icon"><i class="ph-fill ph-car"></i></div>
        <div class="stat-card-label">مركبات معروضة</div>
        <div class="stat-card-value"><?= $fleet_count ?></div>
      </div>
      <div class="stat-card blue">
        <div class="stat-card-icon"><i class="ph-fill ph-currency-circle-dollar"></i></div>
        <div class="stat-card-label">إجمالي المبيعات</div>
        <div class="stat-card-value"><?= number_format($total_sales) ?> <span class="unit">ر.س</span></div>
      </div>
      <div class="stat-card warning">
        <div class="stat-card-icon"><i class="ph-fill ph-hourglass-medium"></i></div>
        <div class="stat-card-label">مستحقات معلقة</div>
        <div class="stat-card-value"><?= number_format($pending_dues) ?> <span class="unit">ر.س</span></div>
      </div>
      <div class="stat-card purple">
        <div class="stat-card-icon"><i class="ph-fill ph-gavel"></i></div>
        <div class="stat-card-label">مزادات نشطة</div>
        <div class="stat-card-value"><?= $active_auctions_count ?? 0 ?> <span class="unit">مزاد</span></div>
      </div>
    </div>

    <div class="activity-card fx-seller-card fx-seller-card--chart">
      <h3 class="activity-title"><i class="ph-fill ph-chart-line-up fx-icon-primary"></i> المبيعات الشهرية (ر.س)</h3>
      <canvas id="sellerSalesChart" height="80"></canvas>
    </div>

    <?php if (!empty($fleet_auctions)): ?>
    <div class="activity-card fx-seller-card">
      <div class="fx-seller-dash-fleet-head">
        <h3 class="activity-title"><i class="ph-fill ph-car fx-icon-primary"></i> أحدث المركبات المعروضة</h3>
        <a href="?section=fleet" class="fx-seller-dash-fleet-link">عرض الأسطول <i class="ph ph-arrow-left"></i></a>
      </div>
      <div class="fleet-grid fx-seller-dash-fleet-grid">
        <?php foreach (array_slice($fleet_auctions, 0, 4) as $idx => $car):
          $fx_seller_card_compact = true;
          include 'includes/fx-seller-fleet-card.inc.php';
        endforeach; ?>
      </div>
    </div>
    <?php endif; ?>

    <div class="activity-card fx-seller-card">
      <h3 class="activity-title"><i class="ph-fill ph-clock-counter-clockwise fx-icon-primary"></i> آخر النشاطات</h3>
      <ul class="activity-list">
        <?php if (empty($seller_activities)): ?>
        <li class="activity-item activity-item--empty">لا يوجد نشاط بعد — أضف مركباتك وابدأ المزادات</li>
        <?php else: foreach ($seller_activities as $act):
          $is_bid = ($act['src'] ?? '') === 'bid';
          $icon = $is_bid ? 'ph-gavel' : 'ph-clipboard-text';
          $tone = $is_bid ? 'bid' : 'inspection';
        ?>
        <li class="activity-item">
          <div class="activity-icon activity-icon--<?= $tone ?>"><i class="ph-fill <?= $icon ?>"></i></div>
          <div class="activity-info">
            <h4><?= sanitize($act['msg'] ?? 'نشاط') ?></h4>
            <p><?= sanitize($act['created_at'] ?? '') ?></p>
          </div>
          <?php if ($is_bid): ?>
          <div class="activity-amount activity-amount--bid"><?= number_format($act['val'] ?? 0) ?> <span>ر.س</span></div>
          <?php endif; ?>
        </li>
        <?php endforeach; endif; ?>
      </ul>
      <?php if (($pending_inspections ?? 0) > 0): ?>
      <a href="?section=fleet" class="fx-seller-fleet-link">
        <?= $pending_inspections ?> مركبة بانتظار الفحص — عرض الأسطول
      </a>
      <?php endif; ?>
    </div>
    <script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.1/dist/chart.umd.min.js"></script>
    <script>
    (function(){
      const el = document.getElementById('sellerSalesChart');
      if (!el || typeof Chart === 'undefined') return;
      new Chart(el, {
        type: 'bar',
        data: {
          labels: <?= json_encode($seller_chart_labels ?? [], JSON_UNESCAPED_UNICODE) ?>,
          datasets: [{ label: 'المبيعات', data: <?= json_encode($seller_chart_sales ?? []) ?>, backgroundColor: 'rgba(27,201,118,0.7)', borderRadius: 8 }]
        },
        options: { responsive: true, plugins: { legend: { display: false } }, scales: { y: { beginAtZero: true } } }
      });
    })();
    </script>

    <!-- ══════════════════════════════════════════════ -->
    <!-- SECTION: FLEET                                -->
    <!-- ══════════════════════════════════════════════ -->
    <?php elseif ($section === 'fleet'): ?>

    <div class="seller-header-bar fx-seller-card">
      <h1 class="seller-section-title"><i class="ph-fill ph-car fx-icon-primary"></i> أسطولي المعروض</h1>
      <a href="/add-auction.php" class="btn-action-top"><i class="ph ph-plus"></i> إضافة مركبة</a>
    </div>

    <?php if (empty($fleet_auctions)): ?>
    <div class="seller-empty fx-seller-card">
      <div class="fx-seller-empty-icon">
        <i class="ph-fill ph-car"></i>
      </div>
      <h3>لا توجد مركبات معروضة حالياً</h3>
      <p>ابدأ بإضافة مركباتك لعرضها في المزادات وجذب المشترين.</p>
      <a href="/add-auction.php" class="btn btn-primary" style="margin-top:24px; border-radius:30px; padding:12px 30px; font-weight:800;">أضف أول مركبة</a>
    </div>
    <?php else: ?>
    <div class="fleet-grid">
      <?php foreach (array_slice($fleet_auctions, 0, 12) as $idx => $car):
        $fx_seller_card_compact = false;
        include 'includes/fx-seller-fleet-card.inc.php';
      endforeach; ?>
    </div>
    <?php endif; ?>

    <!-- ══════════════════════════════════════════════ -->
    <!-- SECTION: INTEGRATION                          -->
    <!-- ══════════════════════════════════════════════ -->
    <?php elseif ($section === 'integration'): ?>

    <div class="seller-header-bar fx-seller-card">
      <h1 class="seller-section-title"><i class="ph-fill ph-plugs fx-icon-primary"></i> الربط الخارجي وجلب البيانات</h1>
    </div>

    <div class="stat-card fx-seller-card fx-seller-integration-card">
        <h3 style="margin-bottom: 15px;">استدعاء بيانات المركبة آلياً</h3>
        <p style="color: var(--text-muted); margin-bottom: 20px;">
            أدخل رقم الشاسيه (VIN) أو رقم اللوحة لجلب بيانات المركبة كاملة من مركز المعلومات الوطني (موجز/علم) لتسهيل عملية إضافة المركبة.
        </p>
        
        <div class="fx-integration-row">
            <div class="fx-integration-field">
                <label class="fx-integration-label">نوع البحث</label>
                <select id="integrationType" class="form-control fx-integration-input">
                    <option value="vin">رقم الشاسيه (VIN)</option>
                    <option value="plate">رقم اللوحة</option>
                </select>
            </div>
            <div class="fx-integration-field fx-integration-field--wide">
                <label class="fx-integration-label">القيمة (رقم الشاسيه أو اللوحة)</label>
                <div class="fx-integration-actions">
                    <input type="text" id="integrationValue" class="form-control fx-integration-input" placeholder="مثال: 1HGCM82633A..." />
                    <button type="button" class="btn btn-primary fx-integration-btn" onclick="fetchVehicleData()">
                        <i class="ph ph-magnifying-glass"></i> جلب البيانات
                    </button>
                </div>
            </div>
        </div>

        <div id="integrationResult" style="display: none; background: #f8fafc; border: 1px solid #e2e8f0; border-radius: var(--radius-lg); padding: 20px; margin-top: 20px;">
            <h4 style="margin-bottom: 15px; color: #0ea5e9; display: flex; align-items: center; gap: 8px;">
                <i class="ph-fill ph-check-circle"></i> تم العثور على المركبة
            </h4>
            <div class="stats-grid fx-seller-stats fx-seller-stats--3">
                <div style="background: white; padding: 15px; border-radius: 8px; box-shadow: 0 2px 4px rgba(0,0,0,0.05);">
                    <span style="display:block; font-size:12px; color:gray; margin-bottom:4px;">الشركة والموديل</span>
                    <strong id="fetchedMakeModel" style="font-size:16px;">--</strong>
                </div>
                <div style="background: white; padding: 15px; border-radius: 8px; box-shadow: 0 2px 4px rgba(0,0,0,0.05);">
                    <span style="display:block; font-size:12px; color:gray; margin-bottom:4px;">سنة الصنع</span>
                    <strong id="fetchedYear" style="font-size:16px;">--</strong>
                </div>
                <div style="background: white; padding: 15px; border-radius: 8px; box-shadow: 0 2px 4px rgba(0,0,0,0.05);">
                    <span style="display:block; font-size:12px; color:gray; margin-bottom:4px;">اللون</span>
                    <strong id="fetchedColor" style="font-size:16px;">--</strong>
                </div>
            </div>
            
            <a href="/add-auction.php?autofill=1" class="btn btn-primary" style="width: 100%; text-align: center; justify-content: center; padding: 14px; border-radius: var(--radius-md); font-weight: bold;">
                <i class="ph ph-plus-circle"></i> إرسال البيانات وإضافة الإعلان
            </a>
        </div>
    </div>
    
    <script>
    function fetchVehicleData() {
        const val = document.getElementById('integrationValue').value;
        if (!val) {
            if (typeof showToast === 'function') showToast('الرجاء إدخال رقم الشاسيه أو اللوحة أولاً', 'warning');
            return;
        }
        
        // Simulate API Loading
        const btn = event.currentTarget;
        const originalText = btn.innerHTML;
        btn.innerHTML = '<i class="ph ph-spinner ph-spin"></i> جاري الجلب...';
        btn.disabled = true;
        
        setTimeout(() => {
            btn.innerHTML = originalText;
            btn.disabled = false;
            
            // Mock fetched data
            document.getElementById('fetchedMakeModel').innerText = 'تويوتا - كامري';
            document.getElementById('fetchedYear').innerText = '2023';
            document.getElementById('fetchedColor').innerText = 'أبيض لؤلؤي';
            
            document.getElementById('integrationResult').style.display = 'block';
            
            // Save in localStorage for the next page to pick up
            localStorage.setItem('fleetx_autofill', JSON.stringify({
                make: 'تويوتا',
                model: 'كامري',
                year: '2023',
                color: 'أبيض',
                vin: val,
                mileage: '24000',
                specs: 'سعودي'
            }));
            
        }, 1500);
    }
    </script>

    <!-- ══════════════════════════════════════════════ -->
    <!-- SECTION: PAYOUTS                              -->
    <!-- ══════════════════════════════════════════════ -->
    <?php elseif ($section === 'payouts'): 
      $payout_available = 0;
      $payout_total_paid = 0;
      $payout_pending = 0;
      $payout_fees = 0;
      $payouts = [];

      if ($db_connected && isset($company_id)) {
          $stmt_payouts = $conn->prepare("SELECT t.*, v.make, v.model, v.year FROM transactions t JOIN auctions a ON t.auction_id = a.id JOIN vehicles v ON a.vehicle_id = v.id WHERE t.seller_id = ? ORDER BY t.created_at DESC LIMIT 20");
          if ($stmt_payouts) {
              $stmt_payouts->bind_param('i', $company_id);
              $stmt_payouts->execute();
              $res = $stmt_payouts->get_result();
              while ($r = $res->fetch_assoc()) {
                  $payouts[] = [
                      'id' => 'TXN-' . date('Ymd', strtotime($r['created_at'])) . '-' . str_pad($r['id'], 3, '0', STR_PAD_LEFT),
                      'date' => date('Y-m-d', strtotime($r['created_at'])),
                      'vehicle' => $r['make'].' '.$r['model'].' '.$r['year'],
                      'amount' => $r['sale_price'],
                      'commission' => $r['platform_fee'],
                      'net' => $r['seller_payout'],
                      'status' => ($r['payment_status'] === 'paid' ? 'completed' : 'pending')
                  ];
              }
          }
          // Available Balance (paid payouts)
          $stmt_av = $conn->prepare('SELECT COALESCE(SUM(seller_payout), 0) FROM transactions WHERE seller_id=? AND payment_status="paid"');
          if ($stmt_av) {
              $stmt_av->bind_param('i', $company_id);
              $stmt_av->execute();
              $payout_available = $stmt_av->get_result()->fetch_row()[0];
              $payout_total_paid = $payout_available;
          }
          // Pending Balance
          $stmt_pe = $conn->prepare('SELECT COALESCE(SUM(seller_payout), 0) FROM transactions WHERE seller_id=? AND payment_status="pending"');
          if ($stmt_pe) {
              $stmt_pe->bind_param('i', $company_id);
              $stmt_pe->execute();
              $payout_pending = $stmt_pe->get_result()->fetch_row()[0];
          }
          // Fees Withheld
          $stmt_fe = $conn->prepare('SELECT COALESCE(SUM(platform_fee), 0) FROM transactions WHERE seller_id=? AND (payment_status="paid" OR payment_status="pending")');
          if ($stmt_fe) {
              $stmt_fe->bind_param('i', $company_id);
              $stmt_fe->execute();
              $payout_fees = $stmt_fe->get_result()->fetch_row()[0];
          }
      }
    ?>

    <div class="seller-header-bar fx-seller-card">
      <h1 class="seller-section-title"><i class="ph-fill ph-money fx-icon-primary"></i> سجل ودفعات المبيعات</h1>
    </div>

    <div class="payout-hero fx-seller-payout-hero">
      <i class="ph-fill ph-wallet payout-hero-bg"></i>
      <div style="position:relative; z-index:2;">
        <div class="payout-hero-label">رصيد المبيعات المتاح للسحب</div>
        <div class="payout-hero-amount"><?= number_format($payout_available) ?> <span class="cur">ر.س</span></div>
        <div class="payout-hero-note">تاريخ الصرف القادم: 8 يونيو 2026 • بنك الرياض الحساب ****4521</div>
      </div>
      <button class="payout-btn-transfer" style="position:relative; z-index:2;">
        <i class="ph ph-bank" style="font-size:20px; color:#fff;"></i> طلب تحويل الرصيد
      </button>
    </div>

    <!-- Summary Cards -->
    <div class="stats-grid fx-seller-stats fx-seller-stats--3">
      <div class="stat-card primary">
        <div class="stat-card-icon"><i class="ph-fill ph-check-circle"></i></div>
        <div class="stat-card-label">إجمالي المدفوعات المستلمة</div>
        <div class="stat-card-value"><?= number_format($payout_total_paid) ?> <span class="unit">ر.س</span></div>
      </div>
      <div class="stat-card warning">
        <div class="stat-card-icon"><i class="ph-fill ph-hourglass-medium"></i></div>
        <div class="stat-card-label">دفعات معلقة قيد التحقق</div>
        <div class="stat-card-value"><?= number_format($payout_pending) ?> <span class="unit">ر.س</span></div>
      </div>
      <div class="stat-card blue">
        <div class="stat-card-icon"><i class="ph-fill ph-percent"></i></div>
        <div class="stat-card-label">رسوم المنصة والضرائب المستقطعة</div>
        <div class="stat-card-value"><?= number_format($payout_fees) ?> <span class="unit">ر.س</span></div>
      </div>
    </div>

    <div class="payout-table-wrap fx-seller-card fx-table-scroll">
      <div class="payout-table-header" style="display:flex; justify-content:space-between; align-items:center;">
          <span>سجل التحويلات والمعاملات</span>
          <a href="/api/export-report.php?type=seller_payouts" class="btn btn-outline btn-sm"><i class="ph ph-download-simple"></i> تصدير CSV</a>
      </div>
      <table class="payout-table">
        <thead>
          <tr>
            <th>رقم العملية</th>
            <th>التاريخ</th>
            <th>المركبة</th>
            <th>مبلغ البيع</th>
            <th>العمولة</th>
            <th>صافي المبلغ</th>
            <th>الحالة</th>
          </tr>
        </thead>
        <tbody>
          <?php foreach ($payouts as $tx): ?>
          <tr>
            <td style="font-family:var(--font-en); font-weight:700; color:var(--text-muted); font-size:12px;"><?= $tx['id'] ?></td>
            <td><?= $tx['date'] ?></td>
            <td style="font-weight:700;"><?= $tx['vehicle'] ?></td>
            <td style="font-family:var(--font-en); font-weight:800;"><?= number_format($tx['amount']) ?> <span style="font-family:var(--font-ar); font-size:11px;">ر.س</span></td>
            <td style="font-family:var(--font-en); color:#f43f5e; font-weight:700;">-<?= number_format($tx['commission']) ?></td>
            <td style="font-family:var(--font-en); font-weight:900; color:var(--primary);"><?= number_format($tx['net']) ?></td>
            <td><span class="payout-status <?= $tx['status'] ?>"><?= $tx['status'] === 'completed' ? 'مكتمل' : 'قيد المعالجة' ?></span></td>
          </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
    </div>

    <!-- ══════════════════════════════════════════════ -->
    <!-- SECTION: WALLET                               -->
    <!-- ══════════════════════════════════════════════ -->
    <?php elseif ($section === 'wallet'):
      $seller_wallet = 0;
      if ($db_connected) {
          $wst = $conn->prepare('SELECT wallet_balance FROM users WHERE id=?');
          $wst->bind_param('i', $user_id);
          $wst->execute();
          $seller_wallet = (float)($wst->get_result()->fetch_assoc()['wallet_balance'] ?? 0);
          $_SESSION['wallet_balance'] = $seller_wallet;
      }
      $insp_fee = fleetx_inspection_fee($conn);
    ?>

    <div class="seller-header-bar fx-seller-card">
      <h1 class="seller-section-title"><i class="ph-fill ph-wallet fx-icon-primary"></i> المحفظة الرقمية</h1>
    </div>

    <div class="wallet-grid" style="margin-bottom:24px;">
      <div class="wallet-balance-card">
        <i class="ph-fill ph-wallet bg-icon" style="color:#fff;"></i>
        <h4 class="wallet-balance-label">رصيد المحفظة</h4>
        <div class="wallet-balance-amount"><?= number_format($seller_wallet) ?> <span>ر.س</span></div>
        <div class="fx-wallet-card-actions">
          <a href="/wallet-topup.php" class="wallet-btn">شحن الرصيد <i class="ph ph-plus"></i></a>
        </div>
      </div>
      <div class="wallet-verify-card">
        <div class="verify-icon"><i class="ph ph-magnifying-glass"></i></div>
        <h4 class="fx-wallet-verify-title">رسوم الفحص</h4>
        <p class="fx-wallet-verify-desc">كل طلب فحص يخصم <strong><?= number_format($insp_fee) ?> ر.س</strong> من محفظتك عند الإرسال للإدارة.</p>
      </div>
    </div>

    <?php if (isset($_GET['msg']) && $_GET['msg'] === 'insufficient'): ?>
    <div class="fx-checkout-alert" style="margin-bottom:20px;">
      <i class="ph ph-warning-circle"></i> رصيد المحفظة غير كافٍ لرسوم الفحص (<?= number_format($insp_fee) ?> ر.س). يرجى الشحن أولاً.
    </div>
    <?php endif; ?>

    <div class="fx-seller-card" style="padding:20px;">
      <h3 style="font-weight:800;margin-bottom:12px;">ملاحظات</h3>
      <ul style="color:var(--text-muted);line-height:1.8;padding-right:20px;">
        <li>تُستخدم المحفظة لدفع رسوم الفحص عند إرسال المركبات للتفتيش.</li>
        <li>مستحقات المبيعات تظهر في قسم <a href="?section=payouts">المستحقات المالية</a>.</li>
      </ul>
    </div>

    <!-- ══════════════════════════════════════════════ -->
    <!-- SECTION: REPORTS                              -->
    <!-- ══════════════════════════════════════════════ -->
    <?php elseif ($section === 'reports'): ?>

    <div class="seller-header-bar fx-seller-card">
      <h1 class="seller-section-title"><i class="ph-fill ph-clipboard-text fx-icon-primary"></i> تقارير الفحص</h1>
    </div>

    <?php foreach ($reports as $report):
      $scoreClass = function($s) {
        if ($s >= 90) return 'score-excellent';
        if ($s >= 80) return 'score-good';
        if ($s >= 70) return 'score-fair';
        return 'score-poor';
      };
    ?>
    <div class="report-card fx-seller-card">
      <div class="report-card-header">
        <div>
          <div class="report-vehicle-name"><?= sanitize($report['vehicle']) ?></div>
          <div class="report-vin">VIN: <?= $report['vin'] ?></div>
        </div>
        <span class="report-status-badge report-<?= $report['status'] ?>">
          <?php
            if ($report['status'] === 'completed' && $report['seller_approved'] === null) echo 'بانتظار موافقتك';
            elseif ($report['seller_approved'] === 1) echo 'معتمد';
            elseif ($report['seller_approved'] === 0) echo 'مرفوض';
            else echo $report['status'] === 'completed' ? 'مكتمل' : 'قيد المراجعة';
          ?>
        </span>
      </div>
      <div class="report-scores">
        <div class="report-score-item">
          <div class="report-score-label">الهيكل الخارجي</div>
          <div class="report-score-circle <?= $scoreClass($report['exterior']) ?>"><?= $report['exterior'] ?></div>
        </div>
        <div class="report-score-item">
          <div class="report-score-label">المقصورة الداخلية</div>
          <div class="report-score-circle <?= $scoreClass($report['interior']) ?>"><?= $report['interior'] ?></div>
        </div>
        <div class="report-score-item">
          <div class="report-score-label">الحالة الميكانيكية</div>
          <div class="report-score-circle <?= $scoreClass($report['mechanical']) ?>"><?= $report['mechanical'] ?></div>
        </div>
        <div class="report-score-item">
          <div class="report-score-label">التقييم الإجمالي</div>
          <div class="report-score-circle <?= $scoreClass($report['overall']) ?>"><?= $report['overall'] ?></div>
        </div>
      </div>
      <div class="report-meta">
        <span><i class="ph ph-user" style="font-size:16px; color:var(--text-muted);"></i> <?= sanitize($report['inspector']) ?></span>
        <span><i class="ph ph-calendar" style="font-size:16px; color:var(--text-muted);"></i> <?= $report['date'] ?></span>
        <?php if (!empty($report['report_pdf'])): ?>
        <a href="<?= sanitize($report['report_pdf']) ?>" target="_blank" style="color:var(--primary); font-weight:800; margin-right:auto; display:inline-flex; align-items:center; gap:5px;">
          <i class="ph ph-file-pdf" style="font-size:16px; color:var(--primary);"></i> تحميل التقرير
        </a>
        <?php endif; ?>
      </div>
      <?php if ($report['status'] === 'completed' && $report['seller_approved'] === null): ?>
      <div style="display:flex;gap:10px;margin-top:16px;padding-top:16px;border-top:1px solid var(--border-light);">
        <form method="POST" style="flex:1;">
          <input type="hidden" name="inspection_id" value="<?= (int)$report['id'] ?>">
          <button type="submit" name="inspection_action" value="approve" class="btn btn-primary" style="width:100%;">اعتماد التقرير ونشر المركبة</button>
        </form>
        <form method="POST">
          <input type="hidden" name="inspection_id" value="<?= (int)$report['id'] ?>">
          <button type="submit" name="inspection_action" value="reject" class="btn btn-outline" style="color:var(--danger);">رفض التقرير</button>
        </form>
      </div>
      <?php endif; ?>
    </div>
    <?php endforeach; ?>

    <!-- ══════════════════════════════════════════════ -->
    <!-- SECTION: BIDS                                 -->
    <!-- ══════════════════════════════════════════════ -->
    <?php elseif ($section === 'bids'): ?>

    <div class="seller-header-bar fx-seller-card" style="display:flex;justify-content:space-between;align-items:center;">
      <h1 class="seller-section-title"><i class="ph-fill ph-gavel fx-icon-primary"></i> المزايدات الواردة</h1>
      <a href="/api/export-report.php?type=seller_bids" class="btn btn-outline btn-sm"><i class="ph ph-download-simple"></i> تصدير CSV</a>
    </div>
    <div class="payout-table-wrap fx-seller-card fx-table-scroll">
      <table class="payout-table">
        <thead><tr><th>المزاد</th><th>المركبة</th><th>المزايد</th><th>المبلغ</th><th>التاريخ</th><th>الحالة</th></tr></thead>
        <tbody>
          <?php if (empty($seller_bids)): ?>
          <tr><td colspan="6" style="text-align:center;color:var(--text-muted);">لا توجد مزايدات بعد</td></tr>
          <?php else: foreach ($seller_bids as $sb): ?>
          <tr>
            <td><?= sanitize($sb['title'] ?? '') ?></td>
            <td><?= sanitize(($sb['make'] ?? '') . ' ' . ($sb['model'] ?? '') . ' ' . ($sb['year'] ?? '')) ?></td>
            <td><?= sanitize($sb['bidder_name'] ?? '') ?></td>
            <td style="font-weight:800;"><?= number_format($sb['amount']) ?> ر.س</td>
            <td><?= date('Y-m-d H:i', strtotime($sb['created_at'])) ?></td>
            <td><?= sanitize($sb['status'] ?? '') ?></td>
          </tr>
          <?php endforeach; endif; ?>
        </tbody>
      </table>
    </div>

    <!-- ══════════════════════════════════════════════ -->
    <!-- SECTION: AUCTION RESULTS                      -->
    <!-- ══════════════════════════════════════════════ -->
    <?php elseif ($section === 'results'): ?>

    <div class="seller-header-bar fx-seller-card">
      <h1 class="seller-section-title"><i class="ph-fill ph-handshake fx-icon-primary"></i> نتائج المزادات — قبول أو رفض</h1>
    </div>
    <?php if (empty($pending_auction_results)): ?>
    <div class="seller-empty fx-seller-card"><p>لا توجد نتائج مزاد بانتظار قرارك.</p></div>
    <?php else: foreach ($pending_auction_results as $par): ?>
    <div class="report-card fx-seller-card">
      <div class="report-card-header">
        <div>
          <div class="report-vehicle-name"><?= sanitize($par['title'] ?: $par['make'].' '.$par['model'].' '.$par['year']) ?></div>
          <div class="report-vin">الفائز: <?= sanitize($par['winner_name'] ?? '—') ?> · <?= number_format($par['sale_price'] ?? $par['current_price']) ?> ر.س</div>
        </div>
      </div>
      <div style="display:flex;gap:10px;margin-top:12px;">
        <form method="POST" style="flex:1;">
          <input type="hidden" name="auction_id" value="<?= (int)$par['id'] ?>">
          <button type="submit" name="auction_decision" value="accept" class="btn btn-primary" style="width:100%;">قبول البيع</button>
        </form>
        <form method="POST">
          <input type="hidden" name="auction_id" value="<?= (int)$par['id'] ?>">
          <button type="submit" name="auction_decision" value="reject" class="btn btn-outline" style="color:var(--danger);">رفض البيع</button>
        </form>
      </div>
    </div>
    <?php endforeach; endif; ?>

    <!-- ══════════════════════════════════════════════ -->
    <!-- SECTION: SUBSCRIPTION                         -->
    <!-- ══════════════════════════════════════════════ -->
    <?php elseif ($section === 'subscription'): ?>

    <div class="seller-header-bar fx-seller-card">
      <h1 class="seller-section-title"><i class="ph-fill ph-crown fx-icon-primary"></i> الباقة والاشتراك</h1>
    </div>

    <div class="plans-grid">
      <?php foreach ($plans as $key => $plan):
        $is_current = ($key === $current_plan);
      ?>
      <div class="plan-card <?= $is_current ? 'current-plan' : '' ?>">
        <div class="plan-icon" style="background: <?= $plan['color'] ?>15; color: <?= $plan['color'] ?>;">
          <i class="ph-fill <?= $plan['icon'] ?>"></i>
        </div>
        <div class="plan-name"><?= $plan['name'] ?></div>
        <div class="plan-price" style="color: <?= $plan['color'] ?>;">
          <?php if ($plan['price_num'] === 0): ?>
            مجاناً
          <?php elseif ($plan['price_num'] === -1): ?>
            <span style="font-size:20px; font-family:var(--font-ar);">تواصل معنا</span>
          <?php else: ?>
            <?= number_format($plan['price_num']) ?> <span class="monthly">ر.س/شهر</span>
          <?php endif; ?>
        </div>
        <ul class="plan-features">
          <?php foreach ($plan['features'] as $feat): ?>
          <li><i class="ph-fill ph-check-circle" style="color:<?= $plan['color'] ?>; flex-shrink:0;"></i> <?= $feat ?></li>
          <?php endforeach; ?>
        </ul>
        <?php if ($is_current): ?>
          <button class="plan-btn plan-btn-current"><i class="ph-fill ph-check" style="color:var(--primary); font-size:18px;"></i> باقتك الحالية</button>
        <?php elseif ($plan['price_num'] === -1): ?>
          <button class="plan-btn plan-btn-contact"><i class="ph ph-phone" style="color:#fff; font-size:18px;"></i> تواصل مع المبيعات</button>
        <?php else: ?>
          <button class="plan-btn plan-btn-upgrade"><i class="ph ph-rocket-launch" style="color:#fff; font-size:18px;"></i> ترقية الآن</button>
        <?php endif; ?>
      </div>
      <?php endforeach; ?>
    </div>

    <!-- ══════════════════════════════════════════════ -->
    <!-- SECTION: SETTINGS                             -->
    <!-- ══════════════════════════════════════════════ -->
    <?php elseif ($section === 'settings'): ?>

    <div class="seller-header-bar fx-seller-card">
      <h1 class="seller-section-title"><i class="ph-fill ph-gear fx-icon-primary"></i> إعدادات الحساب</h1>
    </div>

    <?php if ($settings_msg === 'success'): ?>
    <div class="alert-msg alert-success">
      <i class="ph-fill ph-check-circle" style="font-size:20px; color:#10b981;"></i> تم حفظ التعديلات بنجاح
    </div>
    <?php elseif ($settings_msg === 'error'): ?>
    <div class="alert-msg alert-error">
      <i class="ph-fill ph-warning" style="font-size:20px; color:#f43f5e;"></i> حدث خطأ أثناء الحفظ، يرجى المحاولة مرة أخرى
    </div>
    <?php endif; ?>

    <form method="POST" action="?section=settings">
      <!-- Company Info -->
      <div class="settings-section">
        <h3 class="settings-title"><i class="ph-fill ph-buildings" style="color:var(--primary); font-size:22px;"></i> معلومات الشركة</h3>
        <div class="form-grid">
          <div class="form-group">
            <label>اسم الشركة</label>
            <input type="text" name="company_name" value="<?= sanitize($company['company_name']) ?>" placeholder="أدخل اسم الشركة">
          </div>
          <div class="form-group">
            <label>رقم السجل التجاري</label>
            <input type="text" name="cr_number" value="<?= sanitize($company['cr_number']) ?>" placeholder="1010XXXXXX" style="font-family:var(--font-en);">
          </div>
          <div class="form-group">
            <label>المدينة</label>
            <select name="city">
              <option value="">اختر المدينة</option>
              <?php
              $cities = ['الرياض', 'جدة', 'الدمام', 'مكة المكرمة', 'المدينة المنورة', 'الخبر', 'بريدة', 'تبوك', 'أبها', 'حائل'];
              foreach ($cities as $c):
              ?>
              <option value="<?= $c ?>" <?= ($company['city'] ?? '') === $c ? 'selected' : '' ?>><?= $c ?></option>
              <?php endforeach; ?>
            </select>
          </div>
          <div class="form-group">
            <label>رقم الهاتف</label>
            <input type="tel" name="phone" value="<?= sanitize($company['phone'] ?? '') ?>" placeholder="05XXXXXXXX" style="font-family:var(--font-en); direction:ltr; text-align:right;">
          </div>
          <div class="form-group full">
            <label>البريد الإلكتروني</label>
            <input type="email" name="email" value="<?= sanitize($company['email'] ?? '') ?>" placeholder="email@company.com" style="font-family:var(--font-en); direction:ltr; text-align:right;">
          </div>
        </div>
      </div>

      <!-- Personal Info -->
      <div class="settings-section">
        <h3 class="settings-title"><i class="ph-fill ph-user" style="color:var(--primary); font-size:22px;"></i> المعلومات الشخصية</h3>
        <div class="form-grid">
          <div class="form-group">
            <label>الاسم الكامل</label>
            <input type="text" name="personal_name" value="<?= sanitize($user_name) ?>" placeholder="أدخل اسمك الكامل">
          </div>
          <div class="form-group">
            <label>البريد الإلكتروني للحساب</label>
            <input type="email" value="<?= sanitize($_SESSION['email'] ?? $company['email'] ?? '') ?>" disabled style="font-family:var(--font-en); direction:ltr; text-align:right; opacity:0.6; cursor:not-allowed;">
          </div>
          <div class="form-group">
            <label>كلمة المرور الجديدة</label>
            <input type="password" name="new_password" placeholder="اتركه فارغاً إذا لم تريد التغيير">
          </div>
          <div class="form-group">
            <label>تأكيد كلمة المرور</label>
            <input type="password" name="confirm_password" placeholder="أعد إدخال كلمة المرور الجديدة">
          </div>
        </div>
      </div>

      <!-- Bank Info -->
      <div class="settings-section">
        <h3 class="settings-title"><i class="ph-fill ph-bank" style="color:var(--primary); font-size:22px;"></i> معلومات الحساب البنكي</h3>
        <div class="form-grid">
          <div class="form-group">
            <label>اسم البنك</label>
            <select name="bank_name">
              <option value="">اختر البنك</option>
              <option value="alrajhi" selected>مصرف الراجحي</option>
              <option value="alinma">بنك الإنماء</option>
              <option value="snb">البنك الأهلي السعودي</option>
              <option value="riyad">بنك الرياض</option>
              <option value="sab">بنك ساب</option>
              <option value="albilad">بنك البلاد</option>
            </select>
          </div>
          <div class="form-group">
            <label>رقم الآيبان (IBAN)</label>
            <input type="text" name="iban" value="SA44 2000 0001 2345 6789 1234" placeholder="SA..." style="font-family:var(--font-en); direction:ltr; text-align:right;">
          </div>
        </div>
      </div>

      <?php
      $company_docs = [];
      if ($db_connected && $company_id && fleetx_table_exists($conn, 'company_documents')) {
          $dres = $conn->prepare('SELECT * FROM company_documents WHERE seller_id=? ORDER BY uploaded_at DESC');
          $dres->bind_param('i', $company_id);
          $dres->execute();
          $dr = $dres->get_result();
          while ($row = $dr->fetch_assoc()) $company_docs[] = $row;
      }
      ?>
      <div class="settings-section">
        <h3 class="settings-title"><i class="ph-fill ph-file-text" style="color:var(--primary); font-size:22px;"></i> المستندات القانونية</h3>
        <form method="POST" enctype="multipart/form-data" action="?section=settings" style="margin-bottom:16px;">
          <input type="hidden" name="upload_document" value="1">
          <div class="form-grid">
            <div class="form-group">
              <label>نوع المستند</label>
              <select name="doc_type">
                <option value="cr">سجل تجاري</option>
                <option value="vat">شهادة ضريبة</option>
                <option value="istimara">استمارة</option>
                <option value="insurance">تأمين</option>
                <option value="other">أخرى</option>
              </select>
            </div>
            <div class="form-group">
              <label>الملف (PDF/صورة)</label>
              <input type="file" name="doc_file" accept=".pdf,.jpg,.jpeg,.png" required>
            </div>
          </div>
          <button type="submit" class="btn btn-outline btn-sm">رفع مستند</button>
        </form>
        <?php if ($company_docs): ?>
        <ul style="list-style:none;padding:0;">
          <?php foreach ($company_docs as $doc): ?>
          <li style="padding:8px 0;border-bottom:1px solid var(--border-light);display:flex;justify-content:space-between;gap:10px;">
            <span><?= sanitize($doc['doc_type']) ?> — <a href="<?= sanitize($doc['file_url']) ?>" target="_blank">عرض</a></span>
            <span style="font-size:12px;color:<?= $doc['admin_approved']?'var(--primary)':'#f59e0b' ?>;"><?= $doc['admin_approved']?'معتمد':'بانتظار المراجعة' ?></span>
          </li>
          <?php endforeach; ?>
        </ul>
        <?php endif; ?>
      </div>

      <button type="submit" class="settings-submit">
        <i class="ph ph-floppy-disk" style="font-size:20px; color:#fff;"></i> حفظ التعديلات
      </button>
    </form>

    <?php endif; ?>

  </main>
  </div>
</div>

<?php include 'includes/footer.php'; ?>
</body>
</html>
