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

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $method = $_POST['payment_method'] ?? '';
    $success = false;
    $error = '';

    if ($method == 'wallet') {
        if ($wallet >= $total) {
            $_SESSION['wallet_balance'] -= $total;
            if ($db_connected) {
                $conn->begin_transaction();
                try {
                    // Update wallet balance using prepared statement
                    $wstmt = $conn->prepare("UPDATE users SET wallet_balance = wallet_balance - ? WHERE id = ?");
                    $wstmt->bind_param('di', $total, $user_id);
                    $wstmt->execute();
                    
                    // Create or update transaction
                    $check = $conn->prepare("SELECT id FROM transactions WHERE auction_id = ?");
                    $check->bind_param('i', $id);
                    $check->execute();
                    if ($check->get_result()->num_rows > 0) {
                        $upd = $conn->prepare("UPDATE transactions SET payment_status='paid', payment_method='wallet', paid_at=NOW() WHERE auction_id=?");
                        $upd->bind_param('i', $id);
                        $upd->execute();
                    } else {
                        $fee = $price * (PLATFORM_FEE_PERCENT / 100);
                        $payout = $price - $fee;
                        $ins = $conn->prepare("INSERT INTO transactions (auction_id, buyer_id, seller_id, sale_price, platform_fee, seller_payout, payment_method, payment_status, paid_at) VALUES (?,?,?,?,?,?,'wallet','paid',NOW())");
                        $ins->bind_param('iiiddd', $id, $user_id, $vehicle['seller_id'], $price, $fee, $payout);
                        $ins->execute();
                    }
                    
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
                echo "<script>alert('تم سحب المبلغ من محفظتك وإتمام الشراء بنجاح!'); window.location.href='/buyer.php?section=purchases';</script>";
                exit;
            }
        } else {
            $error = "رصيد المحفظة غير كافٍ. يرجى الشحن أولاً أو استخدام الدفع بالبطاقة.";
        }
    } else {
        // Card / Bank payment (simulated)
        if ($db_connected) {
            $conn->begin_transaction();
            try {
                $ref = 'PAY-' . strtoupper(substr(md5(uniqid()), 0, 10));
                $check = $conn->prepare("SELECT id FROM transactions WHERE auction_id = ?");
                $check->bind_param('i', $id);
                $check->execute();
                if ($check->get_result()->num_rows > 0) {
                    $upd = $conn->prepare("UPDATE transactions SET payment_status='paid', payment_method=?, payment_ref=?, paid_at=NOW() WHERE auction_id=?");
                    $upd->bind_param('ssi', $method, $ref, $id);
                    $upd->execute();
                } else {
                    $fee = $price * (PLATFORM_FEE_PERCENT / 100);
                    $payout = $price - $fee;
                    $ins = $conn->prepare("INSERT INTO transactions (auction_id, buyer_id, seller_id, sale_price, platform_fee, seller_payout, payment_method, payment_ref, payment_status, paid_at) VALUES (?,?,?,?,?,?,?,?,'paid',NOW())");
                    $ins->bind_param('iiidddss', $id, $user_id, $vehicle['seller_id'], $price, $fee, $payout, $method, $ref);
                    $ins->execute();
                }
                $conn->query("UPDATE auctions SET status='ended', winner_id=$user_id, sale_price=$price WHERE id=$id");
                
                $conn->commit();
            } catch (Exception $e) {
                $conn->rollback();
            }
        }
        echo "<script>alert('تمت عملية الدفع بنجاح!'); window.location.href='/buyer.php?section=purchases';</script>";
        exit;
    }
}

?>
<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title><?= sanitize($page_title) ?> | FleetX</title>
  <link rel="stylesheet" href="/assets/css/fleetx.css">
</head>
<body class="page-inner fx-page-shell fx-page-shell--detail">

<?php include 'includes/navbar.php'; ?>

<?php
$hero_title = 'إتمام الشراء والدفع الآمن';
$hero_desc = 'يرجى مراجعة تفاصيل الفاتورة واختيار طريقة الدفع المناسبة.';
$hero_bg = 'https://images.unsplash.com/photo-1554224155-6726b3ff858f?w=1600&q=80';
$hero_modifier = 'checkout';
include 'includes/page-hero.inc.php';
?>

<div class="container fx-page-body fx-page-body--overlap">
  
  <?php if(isset($error)): ?>
    <div style="background: var(--danger-pale); border-right: 4px solid var(--danger); padding: 16px; border-radius: var(--radius-md); color: var(--danger); margin-bottom: 24px; font-weight:700;">
      <i class="ph ph-warning-circle ph-space-left"></i> <?= sanitize($error) ?>
    </div>
  <?php endif; ?>

  <div class="fx-checkout-layout">
    
    <!-- Payment Form -->
    <div class="checkout-box">
      <h3 style="font-size: 20px; border-bottom: 1px solid var(--border-light); padding-bottom: 16px; margin-bottom: 24px; color:var(--text-dark)">الخدمات الإضافية</h3>
      
      <div style="display: flex; flex-direction: column; gap: 12px; margin-bottom: 32px;" id="extra-services">
        <label class="payment-option" style="padding:16px;">
            <div style="display: flex; align-items: center; gap: 12px;">
              <input type="checkbox" name="extra_transfer" value="1500" class="extra-service-cb" style="accent-color: var(--primary); transform: scale(1.2);">
              <div>
                <div style="font-weight: 800; color: var(--text-dark);">نقل ملكية وتأمين أساسي</div>
              </div>
            </div>
            <div style="font-weight:900; font-family:var(--font-en);">+1,500 SAR</div>
        </label>
        <label class="payment-option" style="padding:16px;">
            <div style="display: flex; align-items: center; gap: 12px;">
              <input type="checkbox" name="extra_delivery" value="500" class="extra-service-cb" style="accent-color: var(--primary); transform: scale(1.2);">
              <div>
                <div style="font-weight: 800; color: var(--text-dark);">توصيل لباب بيتك</div>
              </div>
            </div>
            <div style="font-weight:900; font-family:var(--font-en);">+500 SAR</div>
        </label>
        <label class="payment-option" style="padding:16px; border-color:#f59e0b; background:rgba(245,158,11,0.05);">
            <div style="display: flex; align-items: center; gap: 12px;">
              <input type="checkbox" name="extra_gold" value="3000" class="extra-service-cb" style="accent-color: #f59e0b; transform: scale(1.2);">
              <div>
                <div style="font-weight: 800; color: #d97706;"><i class="ph-fill ph-crown"></i> الباقة الذهبية الشاملة</div>
                <div style="font-size: 11px; color: var(--text-muted); margin-top:2px;">نقل، تأمين شامل، صيانة مجانية سنة</div>
              </div>
            </div>
            <div style="font-weight:900; font-family:var(--font-en); color:#d97706;">+3,000 SAR</div>
        </label>
      </div>

      <h3 style="font-size: 20px; border-bottom: 1px solid var(--border-light); padding-bottom: 16px; margin-bottom: 24px; color:var(--text-dark)">طريقة الدفع</h3>
      
      <form action="" method="POST">
        <div style="display: flex; flex-direction: column; gap: 16px; margin-bottom: 32px;">
          
          <label class="payment-option" onclick="document.querySelectorAll('.payment-option').forEach(e=>e.classList.remove('active')); this.classList.add('active');">
            <div style="display: flex; align-items: center; gap: 12px;">
              <input type="radio" name="payment_method" value="wallet" checked style="accent-color: var(--primary); transform: scale(1.2);">
              <div>
                <div style="font-weight: 800; color: var(--text-dark);">المحفظة الرقمية</div>
                <div style="font-size: 13px; color: var(--text-muted); margin-top: 4px;">الرصيد المتاح: <span class="font-en"><?= number_format($wallet) ?> SAR</span></div>
              </div>
            </div>
            <i class="ph-fill ph-wallet" style="font-size: 24px; color: var(--primary);"></i>
          </label>
          
          <label class="payment-option" onclick="document.querySelectorAll('.payment-option').forEach(e=>e.classList.remove('active')); this.classList.add('active');">
            <div style="display: flex; align-items: center; gap: 12px;">
              <input type="radio" name="payment_method" value="card" style="accent-color: var(--primary); transform: scale(1.2);">
              <div>
                <div style="font-weight: 800; color: var(--text-dark);">البطاقة الائتمانية / مدى</div>
                <div style="font-size: 13px; color: var(--text-muted); margin-top: 4px;">سيتم توجيهك لبوابة الدفع الآمنة</div>
              </div>
            </div>
            <i class="ph-fill ph-credit-card" style="font-size: 24px; color: var(--text-muted);"></i>
          </label>

        </div>
        
        <!-- Credit Card Form (Simulated Gateway) -->
        <div id="card-payment-form" style="display: none; background: #fff; padding: 24px; border-radius: var(--radius-md); border: 1px solid var(--border-light); margin-bottom: 32px; box-shadow: 0 4px 15px rgba(0,0,0,0.02);">
          <h4 style="font-size: 16px; font-weight: 800; margin-bottom: 16px; display: flex; align-items: center; justify-content: space-between;">
            بيانات البطاقة
            <div style="display: flex; gap: 6px;">
              <img src="https://upload.wikimedia.org/wikipedia/commons/0/04/Mastercard-logo.png" style="height: 20px;">
              <img src="https://upload.wikimedia.org/wikipedia/commons/0/04/Visa.svg" style="height: 20px;">
              <img src="https://upload.wikimedia.org/wikipedia/commons/2/2a/Mada_Logo.svg" style="height: 20px; background: #fff; border-radius: 2px;">
            </div>
          </h4>
          <div style="display: flex; flex-direction: column; gap: 16px;">
            <div>
              <label style="display: block; font-size: 13px; font-weight: 700; color: var(--text-muted); margin-bottom: 6px;">الاسم على البطاقة</label>
              <input type="text" placeholder="مثال: Ahmed Fouad" class="form-control" style="width: 100%; padding: 12px; border: 1px solid var(--border-light); border-radius: var(--radius-sm); outline: none;">
            </div>
            <div>
              <label style="display: block; font-size: 13px; font-weight: 700; color: var(--text-muted); margin-bottom: 6px;">رقم البطاقة</label>
              <input type="text" placeholder="0000 0000 0000 0000" maxlength="19" class="form-control" style="width: 100%; padding: 12px; border: 1px solid var(--border-light); border-radius: var(--radius-sm); outline: none; font-family: var(--font-en); direction: ltr; text-align: left;">
            </div>
            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 16px;">
              <div>
                <label style="display: block; font-size: 13px; font-weight: 700; color: var(--text-muted); margin-bottom: 6px;">تاريخ الانتهاء</label>
                <input type="text" placeholder="MM/YY" maxlength="5" class="form-control" style="width: 100%; padding: 12px; border: 1px solid var(--border-light); border-radius: var(--radius-sm); outline: none; font-family: var(--font-en); direction: ltr; text-align: left;">
              </div>
              <div>
                <label style="display: block; font-size: 13px; font-weight: 700; color: var(--text-muted); margin-bottom: 6px;">رمز الحماية (CVV)</label>
                <input type="text" placeholder="123" maxlength="3" class="form-control" style="width: 100%; padding: 12px; border: 1px solid var(--border-light); border-radius: var(--radius-sm); outline: none; font-family: var(--font-en); direction: ltr; text-align: left;">
              </div>
            </div>
          </div>
        </div>
        
        <button type="submit" class="btn btn-primary" style="width: 100%; height: 56px; font-size: 18px; display:flex; align-items:center; justify-content:center; gap:8px;">تأكيد الدفع <i class="ph-fill ph-lock-key"></i></button>
      </form>
    </div>

    <!-- Order Summary -->
    <div style="align-self: start; position: sticky; top: 100px;">
      <div class="summary-card">
        <h3 style="font-size: 18px; font-weight:800; margin-bottom: 24px; color:var(--text-dark)">ملخص الطلب</h3>
        
        <div style="display: flex; gap: 16px; margin-bottom: 24px; padding-bottom: 24px; border-bottom: 1px dashed var(--border-light);">
          <img src="<?= $vehicle['image_url'] ?? getCarImage($vehicle['make'] ?? '') ?>" style="width: 80px; height: 60px; border-radius: var(--radius-sm); object-fit: cover;">
          <div>
            <div style="font-weight: 800; font-size: 14px; margin-bottom: 4px; color:var(--text-dark)"><?= sanitize($vehicle['title']) ?></div>
            <div style="font-size: 12px; color: var(--text-muted);"><i class="ph ph-buildings"></i> <?= sanitize($vehicle['seller'] ?? 'بائع معتمد') ?></div>
          </div>
        </div>
        
        <div style="display: flex; justify-content: space-between; margin-bottom: 12px; color: var(--text-muted); font-weight:600;">
          <span>قيمة المركبة</span>
          <span class="font-en"><?= number_format($price) ?> SAR</span>
        </div>
        
        <div style="display: flex; justify-content: space-between; margin-bottom: 24px; color: var(--text-muted); font-weight:600;">
          <span>ضريبة القيمة المضافة (15%)</span>
          <span class="font-en"><?= number_format($vat) ?> SAR</span>
        </div>
        
        <div id="extra-services-summary" style="display:none; flex-direction:column; gap:8px; margin-bottom: 16px; padding-bottom: 16px; border-bottom: 1px dashed var(--border-light);">
          <!-- Populated by JS -->
        </div>
        
        <div style="display: flex; justify-content: space-between; padding-top: 16px; border-top: 1px solid var(--border-light);">
          <span style="font-weight: 900; font-size: 18px; color:var(--text-dark)">الإجمالي</span>
          <span style="font-size: 24px; font-weight: 900; color: var(--primary); font-family: var(--font-en);" id="total-amount-display" data-base="<?= $total ?>"><?= number_format($total) ?> SAR</span>
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
                    <div style="display: flex; justify-content: space-between; font-size:13px; color: var(--text-dark); font-weight:700;">
                      <span>+ ${name}</span>
                      <span class="font-en">${val.toString().replace(/\\B(?=(\\d{3})+(?!\\d))/g, ",")} SAR</span>
                    </div>`;
              }
          });
          
          if (hasExtras) {
              summaryDiv.style.display = 'flex';
              summaryDiv.innerHTML = summaryHTML;
          } else {
              summaryDiv.style.display = 'none';
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
              ccForm.style.display = 'block';
          } else {
              ccForm.style.display = 'none';
          }
      });
  });
  
  // Trigger initial state
  const initialSelected = document.querySelector('input[name="payment_method"]:checked');
  if(initialSelected && initialSelected.value === 'card') {
      ccForm.style.display = 'block';
  }
</script>
</body>
</html>
