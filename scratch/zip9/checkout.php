<?php
require_once 'config.php';
$id = $_GET['id'] ?? null;

// Require login
if(!isset($_SESSION['user_name'])) {
    header("Location: login.php");
    exit;
}

$cars = getMockCars();
$vehicle = null;
foreach($cars as $c) {
    if($c['id'] == $id && $c['type'] == 'instant') { $vehicle = $c; break; }
}
if(!$vehicle) { header("Location: auctions.php"); exit; }

$page_title = "إتمام الشراء | " . sanitize($vehicle['title']);
$price = $vehicle['price'];
$vat = $price * 0.15;
$total = $price + $vat;
$wallet = $_SESSION['wallet_balance'] ?? 0;

if($_SERVER['REQUEST_METHOD'] == 'POST') {
    $method = $_POST['payment_method'] ?? '';
    if($method == 'wallet') {
        if($wallet >= $total) {
            $_SESSION['wallet_balance'] -= $total;
            echo "<script>alert('تم سحب المبلغ من محفظتك وإتمام الشراء بنجاح!'); window.location.href='profile.php';</script>";
            exit;
        } else {
            $error = "رصيد المحفظة غير كافٍ. يرجى الشحن أولاً أو استخدام الدفع بالبطاقة.";
        }
    } else {
        echo "<script>alert('تمت عملية الدفع بنجاح من خلال البطاقة!'); window.location.href='profile.php';</script>";
        exit;
    }
}
?>
<?php include 'includes/header.php'; ?>

<div class="container" style="margin-top: 40px; margin-bottom: 80px;">
  <h1 style="font-size: 32px; margin-bottom: 8px;">إتمام الشراء والدفع الآمن</h1>
  <p style="color: var(--text-muted); margin-bottom: 32px;">يرجى مراجعة تفاصيل الفاتورة واختيار طريقة الدفع المناسبة.</p>
  
  <?php if(isset($error)): ?>
    <div style="background: rgba(239, 68, 68, 0.1); border: 1px solid var(--danger); padding: 16px; border-radius: var(--radius-md); color: var(--danger); margin-bottom: 24px;">
      <?= $error ?>
    </div>
  <?php endif; ?>

  <div style="display: grid; grid-template-columns: 1fr 380px; gap: 32px;">
    
    <!-- Payment Form -->
    <div class="glass-card" style="padding: 32px;">
      <h3 style="font-size: 20px; border-bottom: 1px solid var(--border-glass); padding-bottom: 16px; margin-bottom: 24px;">طريقة الدفع</h3>
      
      <form action="" method="POST">
        <div style="display: flex; flex-direction: column; gap: 16px; margin-bottom: 32px;">
          
          <label style="border: 1px solid var(--border-glass); padding: 20px; border-radius: var(--radius-md); cursor: pointer; display: flex; align-items: center; justify-content: space-between; transition: var(--transition);" onmouseover="this.style.borderColor='var(--primary)'" onmouseout="if(!this.querySelector('input').checked) this.style.borderColor='var(--border-glass)'" onclick="document.querySelectorAll('label').forEach(e=>e.style.borderColor='var(--border-glass)'); this.style.borderColor='var(--primary)';">
            <div style="display: flex; align-items: center; gap: 12px;">
              <input type="radio" name="payment_method" value="wallet" checked style="accent-color: var(--primary); transform: scale(1.2);">
              <div>
                <div style="font-weight: 800; color: #fff;">المحفظة الرقمية</div>
                <div style="font-size: 13px; color: var(--text-muted); margin-top: 4px;">الرصيد المتاح: <span class="font-en"><?= number_format($wallet) ?> SAR</span></div>
              </div>
            </div>
            <i class="ph-fill ph-wallet" style="font-size: 24px; color: var(--primary);"></i>
          </label>
          
          <label style="border: 1px solid var(--border-glass); padding: 20px; border-radius: var(--radius-md); cursor: pointer; display: flex; align-items: center; justify-content: space-between; transition: var(--transition);" onmouseover="this.style.borderColor='var(--primary)'" onmouseout="if(!this.querySelector('input').checked) this.style.borderColor='var(--border-glass)'" onclick="document.querySelectorAll('label').forEach(e=>e.style.borderColor='var(--border-glass)'); this.style.borderColor='var(--primary)';">
            <div style="display: flex; align-items: center; gap: 12px;">
              <input type="radio" name="payment_method" value="card" style="accent-color: var(--primary); transform: scale(1.2);">
              <div>
                <div style="font-weight: 800; color: #fff;">البطاقة الائتمانية / مدى</div>
                <div style="font-size: 13px; color: var(--text-muted); margin-top: 4px;">سيتم توجيهك لبوابة الدفع الآمنة</div>
              </div>
            </div>
            <i class="ph-fill ph-credit-card" style="font-size: 24px; color: var(--text-muted);"></i>
          </label>

        </div>
        
        <button type="submit" class="btn btn-primary" style="width: 100%; height: 56px; font-size: 18px;">تأكيد الدفع <i class="ph-fill ph-lock-key"></i></button>
      </form>
    </div>

    <!-- Order Summary -->
    <div style="align-self: start; position: sticky; top: 100px;">
      <div class="glass-card" style="padding: 24px;">
        <h3 style="font-size: 18px; margin-bottom: 24px;">ملخص الطلب</h3>
        
        <div style="display: flex; gap: 12px; margin-bottom: 24px; padding-bottom: 24px; border-bottom: 1px dashed var(--border-glass);">
          <img src="<?= $vehicle['img'] ?>" style="width: 80px; height: 60px; border-radius: var(--radius-sm); object-fit: cover;">
          <div>
            <div style="font-weight: 800; font-size: 14px; margin-bottom: 4px;"><?= sanitize($vehicle['title']) ?></div>
            <div style="font-size: 12px; color: var(--text-muted);"><i class="ph ph-buildings"></i> <?= sanitize($vehicle['seller']) ?></div>
          </div>
        </div>
        
        <div style="display: flex; justify-content: space-between; margin-bottom: 12px; color: var(--text-muted);">
          <span>قيمة المركبة</span>
          <span class="font-en"><?= number_format($price) ?> SAR</span>
        </div>
        
        <div style="display: flex; justify-content: space-between; margin-bottom: 24px; color: var(--text-muted);">
          <span>ضريبة القيمة المضافة (15%)</span>
          <span class="font-en"><?= number_format($vat) ?> SAR</span>
        </div>
        
        <div style="display: flex; justify-content: space-between; padding-top: 16px; border-top: 1px solid var(--border-glass);">
          <span style="font-weight: 800; font-size: 18px;">الإجمالي</span>
          <span style="font-size: 24px; font-weight: 900; color: var(--primary); font-family: var(--font-en);"><?= number_format($total) ?> SAR</span>
        </div>
      </div>
    </div>
    
  </div>
</div>

<?php include 'includes/footer.php'; ?>
