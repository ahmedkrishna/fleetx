<?php
require_once 'config.php';
requireLogin();

$id = isset($_GET['id']) ? intval($_GET['id']) : null;
if (!$id) {
    header("Location: /auctions.php");
    exit;
}

$vehicle = null;

if ($db_connected) {
    // Support both instant purchases AND auction wins
    $stmt = $conn->prepare("
        SELECT a.id, a.title, a.current_price, a.sale_price, a.seller_id, a.type, a.status, a.winner_id,
               sc.company_name as seller, v.image_url, v.make, v.model, v.year
        FROM auctions a 
        JOIN vehicles v ON a.vehicle_id = v.id 
        LEFT JOIN seller_companies sc ON a.seller_id = sc.id
        WHERE a.id = ? LIMIT 1
    ");
    if ($stmt) {
        $stmt->bind_param('i', $id);
        $stmt->execute();
        $res = $stmt->get_result();
        if ($row = $res->fetch_assoc()) {
            $vehicle = $row;
        }
    }
}

if (!$vehicle) { header("Location: /auctions.php"); exit; }

// Verify: either it's an instant purchase, or user is the winner
$user_id = (int)getUserId();
$is_instant = ($vehicle['type'] === 'instant');
$is_winner  = ((int)$vehicle['winner_id'] === $user_id);

if (!$is_instant && !$is_winner) {
    header("Location: /auctions.php?error=not_winner");
    exit;
}

$page_title = "إتمام الشراء | " . sanitize($vehicle['title'] ?: $vehicle['make'].' '.$vehicle['model'].' '.$vehicle['year']);
$price = (float)($vehicle['sale_price'] ?: $vehicle['current_price']);
$vat = $price * 0.15;
$total = $price + $vat;
$wallet = (float)($_SESSION['wallet_balance'] ?? 0);

$extra_total = 0;
$extra_services = [];
if (!empty($_POST['extra_transfer'])) { $extra_total += 1500; $extra_services[] = ['name'=>'نقل ملكية','price'=>1500]; }
if (!empty($_POST['extra_delivery'])) { $extra_total += 500; $extra_services[] = ['name'=>'توصيل','price'=>500]; }
if (!empty($_POST['extra_gold'])) { $extra_total += 3000; $extra_services[] = ['name'=>'باقة ذهبية','price'=>3000]; }
$inspection_fee = ($db_connected) ? fleetx_inspection_fee($conn) : 0;
$grand_total = $total + $extra_total + $inspection_fee;

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $method = $_POST['payment_method'] ?? '';
    $success = false;
    $error = '';
    $pay_total = $grand_total;

    if ($method == 'wallet') {
        if ($wallet >= $pay_total) {
            $_SESSION['wallet_balance'] -= $pay_total;
            if ($db_connected) {
                $conn->begin_transaction();
                try {
                    $wstmt = $conn->prepare("UPDATE users SET wallet_balance = wallet_balance - ? WHERE id = ?");
                    $wstmt->bind_param('di', $pay_total, $user_id);
                    $wstmt->execute();
                    
                    $fee_pct = fleetx_platform_fee_percent($conn);
                    $fee = $price * ($fee_pct / 100);
                    $payout = $price - $fee;
                    $extras_json = json_encode($extra_services, JSON_UNESCAPED_UNICODE);
                    $vat_amt = round($price * 0.15, 2);
                    $check = $conn->prepare("SELECT id FROM transactions WHERE auction_id = ?");
                    $check->bind_param('i', $id);
                    $check->execute();
                    $tx_id = 0;
                    if ($check->get_result()->num_rows > 0) {
                        $upd = $conn->prepare("UPDATE transactions SET payment_status='paid', payment_method='wallet', paid_at=NOW(), inspection_fee=?, extra_services=?, vat_amount=? WHERE auction_id=?");
                        $upd->bind_param('dssi', $inspection_fee, $extras_json, $vat_amt, $id);
                        $upd->execute();
                        $tx_id = (int)$conn->query("SELECT id FROM transactions WHERE auction_id=$id")->fetch_row()[0];
                    } else {
                        $ins = $conn->prepare("INSERT INTO transactions (auction_id, buyer_id, seller_id, sale_price, platform_fee, seller_payout, inspection_fee, extra_services, vat_amount, payment_method, payment_status, paid_at) VALUES (?,?,?,?,?,?,?,?,?,'wallet','paid',NOW())");
                        $ins->bind_param('iiiddddsd', $id, $user_id, $vehicle['seller_id'], $price, $fee, $payout, $inspection_fee, $extras_json, $vat_amt);
                        $ins->execute();
                        $tx_id = (int)$conn->insert_id;
                    }
                    if ($tx_id) fleetx_create_invoice($conn, $tx_id);
                    
                    // Update auction
                    $conn->query("UPDATE auctions SET status='ended', winner_id=$user_id, sale_price=$price WHERE id=$id");
                    
                    // Notify seller
                    $car_name = $vehicle['title'] ?: $vehicle['make'].' '.$vehicle['model'].' '.$vehicle['year'];
                    $seller_user = $conn->query("SELECT user_id FROM seller_companies WHERE id=".(int)$vehicle['seller_id'])->fetch_assoc();
                    if ($seller_user) {
                        createNotification($conn, $seller_user['user_id'], 'payment',
                            'تم الدفع! 💰', 
                            "تم دفع مبلغ ".formatPrice($price)." لشراء {$car_name}",
                            "/seller.php?section=payouts"
                        );
                    }
                    
                    $conn->commit();
                    $success = true;
                } catch (Exception $e) {
                    $conn->rollback();
                    $error = "حدث خطأ أثناء الدفع، حاول مرة أخرى";
                }
            } else {
                $success = true; // mock
            }
            if ($success) {
                fleetx_set_toast('تم سحب المبلغ من محفظتك وإتمام الشراء بنجاح!');
                header('Location: /buyer.php?section=purchases');
                exit;
            }
        } else {
            $error = "رصيد المحفظة غير كافٍ. يرجى الشحن أولاً أو استخدام الدفع بالبطاقة.";
        }
    } elseif (in_array($method, ['card', 'mada', 'apple_pay', 'sadad'], true)) {
        if ($db_connected && fleetx_table_exists($conn, 'payment_intents')) {
            $intent = paymentGatewayCreateIntent($conn, $id, $user_id, $pay_total, $method, [
                'extra_services' => $extra_services,
                'inspection_fee' => $inspection_fee,
            ]);
            if ($intent && !empty($intent['redirect'])) {
                header('Location: ' . $intent['redirect']);
                exit;
            }
        }
        $error = 'تعذّر بدء عملية الدفع. حاول مرة أخرى أو استخدم المحفظة.';
    } else {
        $error = 'طريقة دفع غير مدعومة.';
    }
}

?>
<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title><?= sanitize($page_title) ?> | FleetX</title>
  <link rel="stylesheet" href="<?= fleetx_css_href() ?>">
</head>
<body class="fx-home fx-page-shell fx-page-shell--checkout">

<?php include 'includes/navbar.php'; ?>

<?php
$hero_title = 'إتمام الشراء والدفع الآمن';
$hero_desc = 'يرجى مراجعة تفاصيل الفاتورة واختيار طريقة الدفع المناسبة.';
$hero_bg = 'https://images.unsplash.com/photo-1554224155-6726b3ff858f?w=1600&q=80';
$hero_eyebrow = 'الدفع الآمن';
include 'includes/page-hero.inc.php';
?>

<div class="container fx-page-body fx-page-body--overlap">
  
  <?php if(isset($error)): ?>
    <div class="fx-checkout-alert">
      <i class="ph ph-warning-circle ph-space-left"></i> <?= sanitize($error) ?>
    </div>
  <?php endif; ?>

  <div class="fx-checkout-layout">
    
    <div class="fx-checkout-box">
      <h3 class="fx-checkout-heading">الخدمات الإضافية</h3>
      
      <div class="fx-checkout-stack" id="extra-services">
        <label class="payment-option">
            <div class="fx-checkout-option-inner">
              <input type="checkbox" name="extra_transfer" value="1500" class="extra-service-cb">
              <div>
                <div class="fx-checkout-option-title">نقل ملكية وتأمين أساسي</div>
              </div>
            </div>
            <div class="fx-checkout-option-price">+1,500 SAR</div>
        </label>
        <label class="payment-option">
            <div class="fx-checkout-option-inner">
              <input type="checkbox" name="extra_delivery" value="500" class="extra-service-cb">
              <div>
                <div class="fx-checkout-option-title">توصيل لباب بيتك</div>
              </div>
            </div>
            <div class="fx-checkout-option-price">+500 SAR</div>
        </label>
        <label class="payment-option payment-option--gold">
            <div class="fx-checkout-option-inner">
              <input type="checkbox" name="extra_gold" value="3000" class="extra-service-cb">
              <div>
                <div class="fx-checkout-option-title"><i class="ph-fill ph-crown"></i> الباقة الذهبية الشاملة</div>
                <div class="fx-checkout-option-sub">نقل، تأمين شامل، صيانة مجانية سنة</div>
              </div>
            </div>
            <div class="fx-checkout-option-price fx-checkout-option-price--gold">+3,000 SAR</div>
        </label>
      </div>

      <h3 class="fx-checkout-heading">طريقة الدفع</h3>
      
      <form action="" method="POST">
        <div class="fx-checkout-stack fx-checkout-stack--payments">
          
          <label class="payment-option" onclick="document.querySelectorAll('.payment-option').forEach(e=>e.classList.remove('active')); this.classList.add('active');">
            <div class="fx-checkout-option-inner">
              <input type="radio" name="payment_method" value="wallet" checked>
              <div>
                <div class="fx-checkout-option-title">المحفظة الرقمية</div>
                <div class="fx-checkout-option-sub">الرصيد المتاح: <span class="font-en"><?= number_format($wallet) ?> SAR</span></div>
              </div>
            </div>
            <i class="ph-fill ph-wallet fx-checkout-icon fx-checkout-icon--primary"></i>
          </label>
          
          <label class="payment-option" onclick="document.querySelectorAll('.payment-option').forEach(e=>e.classList.remove('active')); this.classList.add('active');">
            <div class="fx-checkout-option-inner">
              <input type="radio" name="payment_method" value="card">
              <div>
                <div class="fx-checkout-option-title">البطاقة الائتمانية</div>
                <div class="fx-checkout-option-sub">Visa / Mastercard — بوابة دفع آمنة</div>
              </div>
            </div>
            <i class="ph-fill ph-credit-card fx-checkout-icon fx-checkout-icon--muted"></i>
          </label>

          <label class="payment-option" onclick="document.querySelectorAll('.payment-option').forEach(e=>e.classList.remove('active')); this.classList.add('active');">
            <div class="fx-checkout-option-inner">
              <input type="radio" name="payment_method" value="mada">
              <div>
                <div class="fx-checkout-option-title">مدى</div>
                <div class="fx-checkout-option-sub">بطاقات مدى السعودية</div>
              </div>
            </div>
            <i class="ph-fill ph-credit-card fx-checkout-icon fx-checkout-icon--muted"></i>
          </label>

          <label class="payment-option" onclick="document.querySelectorAll('.payment-option').forEach(e=>e.classList.remove('active')); this.classList.add('active');">
            <div class="fx-checkout-option-inner">
              <input type="radio" name="payment_method" value="apple_pay">
              <div>
                <div class="fx-checkout-option-title">Apple Pay</div>
                <div class="fx-checkout-option-sub">دفع سريع عبر Apple Pay</div>
              </div>
            </div>
            <i class="ph-fill ph-device-mobile fx-checkout-icon fx-checkout-icon--muted"></i>
          </label>

          <label class="payment-option" onclick="document.querySelectorAll('.payment-option').forEach(e=>e.classList.remove('active')); this.classList.add('active');">
            <div class="fx-checkout-option-inner">
              <input type="radio" name="payment_method" value="sadad">
              <div>
                <div class="fx-checkout-option-title">سداد</div>
                <div class="fx-checkout-option-sub">الدفع عبر نظام سداد</div>
              </div>
            </div>
            <i class="ph-fill ph-bank fx-checkout-icon fx-checkout-icon--muted"></i>
          </label>

        </div>
        
        <div id="card-payment-form" class="fx-checkout-card-form">
          <h4 class="fx-checkout-card-form__head">
            بيانات البطاقة
            <div class="fx-checkout-card-brands">
              <img src="https://upload.wikimedia.org/wikipedia/commons/0/04/Mastercard-logo.png" alt="Mastercard">
              <img src="https://upload.wikimedia.org/wikipedia/commons/0/04/Visa.svg" alt="Visa">
              <img src="https://upload.wikimedia.org/wikipedia/commons/2/2a/Mada_Logo.svg" alt="Mada">
            </div>
          </h4>
          <div class="fx-checkout-card-fields">
            <div>
              <label class="fx-checkout-field-label">الاسم على البطاقة</label>
              <input type="text" placeholder="مثال: Ahmed Fouad" class="fx-checkout-field-input">
            </div>
            <div>
              <label class="fx-checkout-field-label">رقم البطاقة</label>
              <input type="text" placeholder="0000 0000 0000 0000" maxlength="19" class="fx-checkout-field-input fx-checkout-field-input--en">
            </div>
            <div class="fx-checkout-field-grid">
              <div>
                <label class="fx-checkout-field-label">تاريخ الانتهاء</label>
                <input type="text" placeholder="MM/YY" maxlength="5" class="fx-checkout-field-input fx-checkout-field-input--en">
              </div>
              <div>
                <label class="fx-checkout-field-label">رمز الحماية (CVV)</label>
                <input type="text" placeholder="123" maxlength="3" class="fx-checkout-field-input fx-checkout-field-input--en">
              </div>
            </div>
          </div>
        </div>
        
        <button type="submit" class="btn btn-primary fx-checkout-submit">تأكيد الدفع <i class="ph-fill ph-lock-key"></i></button>
      </form>
    </div>

    <div class="fx-checkout-summary">
      <div class="fx-checkout-summary-card">
        <h3>ملخص الطلب</h3>
        
        <div class="fx-checkout-vehicle">
          <img src="<?= $vehicle['image_url'] ?? getCarImage($vehicle['make'] ?? '') ?>" alt="<?= sanitize($vehicle['title']) ?>">
          <div>
            <div class="fx-checkout-vehicle-title"><?= sanitize($vehicle['title']) ?></div>
            <div class="fx-checkout-vehicle-seller"><i class="ph ph-buildings"></i> <?= sanitize($vehicle['seller'] ?? 'بائع معتمد') ?></div>
          </div>
        </div>
        
        <div class="fx-checkout-line">
          <span>قيمة المركبة</span>
          <span class="font-en"><?= number_format($price) ?> SAR</span>
        </div>
        
        <div class="fx-checkout-line">
          <span>ضريبة القيمة المضافة (15%)</span>
          <span class="font-en"><?= number_format($vat) ?> SAR</span>
        </div>
        
        <div id="extra-services-summary" class="fx-checkout-extras-summary"></div>
        
        <div class="fx-checkout-total">
          <span class="fx-checkout-total-label">الإجمالي</span>
          <span class="fx-checkout-total-val" id="total-amount-display" data-base="<?= $total ?>"><?= number_format($total) ?> SAR</span>
        </div>
      </div>
    </div>
    
  </div>
</div>

<?php include 'includes/footer.php'; ?>

<!-- Scripts -->
<script src="/assets/js/fleetx.js"></script>
<script>
  // Add active class to first option on load
  document.querySelector('.payment-option input[type="radio"]:checked').closest('.payment-option').classList.add('active');

  const baseTotal = <?= $total ?>;
  const checkboxes = document.querySelectorAll('.extra-service-cb');
  const summaryDiv = document.getElementById('extra-services-summary');
  const totalDisplay = document.getElementById('total-amount-display');

  checkboxes.forEach(cb => {
      cb.addEventListener('change', function() {
          let currentTotal = baseTotal;
          let summaryHTML = '';
          let hasExtras = false;
          
          checkboxes.forEach(c => {
              if (c.checked) {
                  hasExtras = true;
                  let val = parseInt(c.value);
                  currentTotal += val;
                  let name = c.nextElementSibling.querySelector('div').innerText;
                  summaryHTML += `
                    <div class="fx-checkout-extra-line">
                      <span>+ ${name}</span>
                      <span class="font-en">${val.toString().replace(/\\B(?=(\\d{3})+(?!\\d))/g, ",")} SAR</span>
                    </div>`;
              }
          });
          
          if (hasExtras) {
              summaryDiv.classList.add('is-visible');
              summaryDiv.innerHTML = summaryHTML;
          } else {
              summaryDiv.classList.remove('is-visible');
          }
          
          totalDisplay.innerHTML = currentTotal.toString().replace(/\\B(?=(\\d{3})+(?!\\d))/g, ",") + ' SAR';
      });
  });
  // Show/Hide CC form
  const paymentRadios = document.querySelectorAll('input[name="payment_method"]');
  const ccForm = document.getElementById('card-payment-form');
  paymentRadios.forEach(radio => {
      radio.addEventListener('change', function() {
          if (this.value === 'card') {
              ccForm.classList.add('is-visible');
          } else {
              ccForm.classList.remove('is-visible');
          }
      });
  });
  
  const initialSelected = document.querySelector('input[name="payment_method"]:checked');
  if(initialSelected && initialSelected.value === 'card') {
      ccForm.classList.add('is-visible');
  }


</script>
</body>
</html>
