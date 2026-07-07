<?php
require_once 'config.php';
if(!isset($_SESSION['user_name'])) { header("Location: login.php"); exit; }
$page_title = "حسابي | FleetX Ultimate";
$tab = $_GET['tab'] ?? 'wallet';
?>
<?php include 'includes/header.php'; ?>

<div class="container" style="margin-top: 40px; margin-bottom: 80px;">
  
  <div style="display: flex; justify-content: space-between; align-items: flex-end; margin-bottom: 32px; padding-bottom: 24px; border-bottom: 1px solid var(--border-glass);">
    <div style="display: flex; align-items: center; gap: 24px;">
      <div style="width: 80px; height: 80px; border-radius: 50%; background: linear-gradient(135deg, var(--primary), var(--secondary)); display: flex; align-items: center; justify-content: center; font-size: 32px; font-weight: 900; color: #fff;">
        <?= mb_substr($_SESSION['user_name'], 0, 1) ?>
      </div>
      <div>
        <h1 style="font-size: 32px; margin-bottom: 4px;" class="title-gradient"><?= sanitize($_SESSION['user_name']) ?></h1>
        <p style="color: var(--primary); font-size: 14px; font-weight: 800;"><i class="ph-fill ph-check-circle"></i> حساب مشتري موثق (Nafath)</p>
      </div>
    </div>
  </div>

  <div style="display: grid; grid-template-columns: 280px 1fr; gap: 32px;">
    
    <!-- Sidebar -->
    <aside class="glass-card" style="padding: 24px; align-self: start;">
      <ul style="display: flex; flex-direction: column; gap: 8px;">
        <li><a href="?tab=wallet" class="btn" style="width: 100%; justify-content: flex-start; <?= $tab=='wallet'?'background: rgba(27, 201, 118, 0.1); color: var(--primary);':'color: var(--text-muted);' ?>"><i class="ph-fill ph-wallet" style="font-size: 20px;"></i> المحفظة والمشتريات</a></li>
        <li><a href="?tab=favorites" class="btn" style="width: 100%; justify-content: flex-start; <?= $tab=='favorites'?'background: rgba(27, 201, 118, 0.1); color: var(--primary);':'color: var(--text-muted);' ?>"><i class="ph ph-heart" style="font-size: 20px;"></i> المفضلة</a></li>
        <li><a href="?tab=bids" class="btn" style="width: 100%; justify-content: flex-start; <?= $tab=='bids'?'background: rgba(27, 201, 118, 0.1); color: var(--primary);':'color: var(--text-muted);' ?>"><i class="ph ph-gavel" style="font-size: 20px;"></i> سجل المزايدات</a></li>
        <li><a href="?tab=settings" class="btn" style="width: 100%; justify-content: flex-start; <?= $tab=='settings'?'background: rgba(27, 201, 118, 0.1); color: var(--primary);':'color: var(--text-muted);' ?>"><i class="ph ph-gear" style="font-size: 20px;"></i> الإعدادات</a></li>
        <li><a href="logout.php" class="btn" style="width: 100%; justify-content: flex-start; color: var(--danger); margin-top: 16px;"><i class="ph ph-sign-out" style="font-size: 20px;"></i> تسجيل خروج</a></li>
      </ul>
    </aside>
    
    <!-- Main Content -->
    <main style="display: flex; flex-direction: column; gap: 32px;">
      
      <?php if($tab == 'wallet'): ?>
        <!-- Wallet Glass -->
        <div class="glass-card" style="padding: 40px; position: relative; overflow: hidden;">
          <div style="position: absolute; right: -20px; top: -20px; width: 150px; height: 150px; background: var(--primary); opacity: 0.2; filter: blur(40px); border-radius: 50%;"></div>
          
          <div style="display: flex; justify-content: space-between; align-items: center; position: relative; z-index: 1;">
            <div>
              <div style="color: var(--text-muted); font-size: 16px; margin-bottom: 8px;">الرصيد المتاح (التأمين)</div>
              <div style="font-size: 48px; font-weight: 900; font-family: var(--font-en); color: #fff; line-height: 1;">
                <?= number_format($_SESSION['wallet_balance']) ?> <span style="font-size: 20px; color: var(--primary); font-family: var(--font-ar);">ر.س</span>
              </div>
            </div>
            <div style="display: flex; gap: 12px;">
              <a href="wallet-topup.php" class="btn btn-primary" style="padding: 12px 24px;"><i class="ph ph-plus"></i> شحن المحفظة</a>
              <button class="btn btn-outline" style="padding: 12px 24px;" onclick="alert('خاصية الاسترداد تتطلب مراجعة حسابك البنكي أولاً.')"><i class="ph ph-arrow-down"></i> استرداد</button>
            </div>
          </div>
        </div>

        <!-- Active Bids Overview -->
        <div class="glass-card" style="padding: 32px;">
          <h3 style="font-size: 20px; margin-bottom: 24px;">مزايدات نشطة تشارك بها</h3>
          
          <div style="padding: 24px; background: rgba(255,255,255,0.03); border: 1px solid var(--border-glass); border-radius: var(--radius-md); display: flex; justify-content: space-between; align-items: center;">
            <div style="display: flex; gap: 16px; align-items: center;">
              <div style="width: 60px; height: 60px; border-radius: var(--radius-sm); overflow: hidden;">
                <img src="https://images.unsplash.com/photo-1621007947382-bb3c3994e3fd?w=200&q=80" style="width:100%; height:100%; object-fit:cover;">
              </div>
              <div>
                <div style="font-weight: 800; font-size: 16px; color: #fff;">تويوتا كامري 2023</div>
                <div style="color: var(--primary); font-size: 13px; margin-top: 4px;">أنت صاحب أعلى سعر!</div>
              </div>
            </div>
            <div style="text-align: left;">
              <div style="font-size: 20px; font-weight: 900; font-family: var(--font-en); color: #fff;">94,000 SAR</div>
              <a href="/auction-room.php?id=1" style="color: var(--secondary); font-size: 13px; text-decoration: underline;">متابعة المزاد</a>
            </div>
          </div>
        </div>
      
      <?php elseif($tab == 'favorites'): ?>
        <div class="glass-card" style="padding: 32px;">
          <h3 style="font-size: 20px; margin-bottom: 24px;">المفضلة</h3>
          <p style="color: var(--text-muted);">لا توجد مركبات في المفضلة حالياً.</p>
        </div>
      
      <?php elseif($tab == 'bids'): ?>
        <div class="glass-card" style="padding: 32px;">
          <h3 style="font-size: 20px; margin-bottom: 24px;">سجل المزايدات والمشتريات</h3>
          <p style="color: var(--text-muted);">جميع المشتريات التي قمت بها ستظهر هنا مع الفواتير.</p>
        </div>
      
      <?php elseif($tab == 'settings'): ?>
        <div class="glass-card" style="padding: 32px;">
          <h3 style="font-size: 20px; margin-bottom: 24px;">إعدادات الحساب</h3>
          <form onsubmit="event.preventDefault(); alert('تم حفظ الإعدادات!');">
            <div class="form-group" style="margin-bottom: 16px;">
              <label style="display:block; margin-bottom:8px;">الاسم الكامل</label>
              <input type="text" class="form-control" value="<?= sanitize($_SESSION['user_name']) ?>">
            </div>
            <button class="btn btn-primary">حفظ التغييرات</button>
          </form>
        </div>
      <?php endif; ?>

    </main>

  </div>
</div>

<?php include 'includes/footer.php'; ?>
