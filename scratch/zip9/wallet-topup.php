<?php
require_once 'config.php';
$page_title = "شحن المحفظة | FleetX";

if(!isset($_SESSION['user_name'])) {
    header("Location: login.php");
    exit;
}

if($_SERVER['REQUEST_METHOD'] == 'POST') {
    $amount = intval($_POST['amount'] ?? 0);
    if($amount > 0) {
        $_SESSION['wallet_balance'] += $amount;
        echo "<script>alert('تم شحن المحفظة بنجاح بمبلغ $amount ريال!'); window.location.href='profile.php';</script>";
        exit;
    }
}
?>
<?php include 'includes/header.php'; ?>

<div class="container" style="margin-top: 40px; margin-bottom: 80px; max-width: 600px;">
  
  <div style="text-align: center; margin-bottom: 40px;">
    <h1 style="font-size: 32px; margin-bottom: 16px;">شحن المحفظة الرقمية</h1>
    <p style="color: var(--text-muted);">قم بإيداع مبالغ في محفظتك لاستخدامها كتأمين لدخول المزادات الحية أو للشراء الفوري.</p>
  </div>

  <div class="glass-card" style="padding: 40px;">
    
    <div style="display: flex; justify-content: space-between; align-items: center; padding: 24px; background: rgba(27, 201, 118, 0.05); border: 1px solid var(--primary); border-radius: var(--radius-md); margin-bottom: 32px;">
      <div>
        <div style="color: var(--text-muted); font-size: 14px; margin-bottom: 4px;">الرصيد الحالي</div>
        <div style="font-weight: 900; font-size: 28px; color: #fff; font-family: var(--font-en);"><?= number_format($_SESSION['wallet_balance']) ?> <span style="font-size: 14px; font-family: var(--font-ar); color: var(--primary);">ر.س</span></div>
      </div>
      <i class="ph-fill ph-wallet" style="font-size: 48px; color: var(--primary); opacity: 0.5;"></i>
    </div>

    <form action="" method="POST">
      <h3 style="font-size: 18px; margin-bottom: 16px;">اختر باقة الشحن</h3>
      
      <div style="display: grid; grid-template-columns: 1fr 1fr 1fr; gap: 16px; margin-bottom: 24px;">
        <label style="border: 1px solid var(--border-glass); padding: 16px; border-radius: var(--radius-sm); text-align: center; cursor: pointer;" onclick="document.querySelectorAll('label').forEach(e=>e.style.borderColor='var(--border-glass)'); this.style.borderColor='var(--primary)'; document.getElementById('custom_amount').value = 5000;">
          <input type="radio" name="pkg" style="display: none;">
          <div style="font-weight: 800; font-family: var(--font-en); color: #fff; font-size: 20px;">5,000</div>
          <div style="font-size: 12px; color: var(--text-muted);">ر.س</div>
        </label>
        
        <label style="border: 1px solid var(--border-glass); padding: 16px; border-radius: var(--radius-sm); text-align: center; cursor: pointer;" onclick="document.querySelectorAll('label').forEach(e=>e.style.borderColor='var(--border-glass)'); this.style.borderColor='var(--primary)'; document.getElementById('custom_amount').value = 10000;">
          <input type="radio" name="pkg" style="display: none;">
          <div style="font-weight: 800; font-family: var(--font-en); color: #fff; font-size: 20px;">10,000</div>
          <div style="font-size: 12px; color: var(--text-muted);">ر.س</div>
        </label>
        
        <label style="border: 1px solid var(--border-glass); padding: 16px; border-radius: var(--radius-sm); text-align: center; cursor: pointer;" onclick="document.querySelectorAll('label').forEach(e=>e.style.borderColor='var(--border-glass)'); this.style.borderColor='var(--primary)'; document.getElementById('custom_amount').value = 50000;">
          <input type="radio" name="pkg" style="display: none;">
          <div style="font-weight: 800; font-family: var(--font-en); color: #fff; font-size: 20px;">50,000</div>
          <div style="font-size: 12px; color: var(--text-muted);">ر.س</div>
        </label>
      </div>

      <div style="margin-bottom: 32px;">
        <label style="display: block; font-weight: 700; color: var(--text-muted); margin-bottom: 8px;">أو أدخل مبلغاً مخصصاً</label>
        <input type="number" id="custom_amount" name="amount" class="form-control" style="font-size: 20px; font-family: var(--font-en); font-weight: 800;" placeholder="0" required>
      </div>

      <button type="submit" class="btn btn-primary" style="width: 100%; height: 56px; font-size: 18px;">المتابعة للدفع <i class="ph-fill ph-credit-card"></i></button>
      <a href="/profile.php" class="btn btn-outline" style="width: 100%; height: 50px; margin-top: 12px;">إلغاء</a>
    </form>
    
  </div>
</div>

<?php include 'includes/footer.php'; ?>
