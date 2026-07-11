<?php
require_once 'config.php';

$is_guest = !isLoggedIn();

$companies = [];
if ($db_connected) {
    $query = "
        SELECT sc.*,
               (SELECT COUNT(*) FROM auctions a WHERE a.seller_id = sc.id AND a.status IN ('live','active')) as active_count,
               (SELECT COUNT(*) FROM auctions a WHERE a.seller_id = sc.id AND a.status = 'live') as live_count,
               (SELECT COUNT(*) FROM auctions a WHERE a.seller_id = sc.id) as total_count,
               (SELECT COUNT(*) FROM vehicles v WHERE v.seller_id = sc.id AND v.status = 'approved') as approved_vehicles
        FROM seller_companies sc
        WHERE sc.is_verified = 1 OR sc.id > 0
        ORDER BY live_count DESC, active_count DESC, sc.rating DESC
    ";
    $res = $conn->query($query);
    if ($res) { while ($r = $res->fetch_assoc()) { $companies[] = $r; } }
}

if (empty($companies)) {
    $companies = [
        ['id'=>1,'company_name'=>'الوطنية للتأجير','city'=>'الرياض','rating'=>4.9,'active_count'=>8,'live_count'=>3,'total_count'=>420,'approved_vehicles'=>12,'is_verified'=>1],
        ['id'=>2,'company_name'=>'بدجت السعودية','city'=>'جدة','rating'=>4.7,'active_count'=>5,'live_count'=>2,'total_count'=>280,'approved_vehicles'=>7,'is_verified'=>1],
        ['id'=>3,'company_name'=>'هيرتز السعودية','city'=>'الدمام','rating'=>4.8,'active_count'=>4,'live_count'=>1,'total_count'=>190,'approved_vehicles'=>5,'is_verified'=>1],
        ['id'=>4,'company_name'=>'أوروبكار المملكة','city'=>'الرياض','rating'=>4.6,'active_count'=>3,'live_count'=>0,'total_count'=>150,'approved_vehicles'=>3,'is_verified'=>1],
        ['id'=>5,'company_name'=>'سيكست السعودية','city'=>'مكة المكرمة','rating'=>4.5,'active_count'=>2,'live_count'=>0,'total_count'=>95,'approved_vehicles'=>4,'is_verified'=>1],
        ['id'=>6,'company_name'=>'تفيد للإيجار','city'=>'المدينة المنورة','rating'=>4.4,'active_count'=>1,'live_count'=>0,'total_count'=>60,'approved_vehicles'=>2,'is_verified'=>1],
    ];
}

$cities = array_unique(array_filter(array_column($companies, 'city')));
$live_total = array_sum(array_column($companies, 'live_count'));
$active_total = array_sum(array_column($companies, 'active_count'));
$vehicles_total = array_sum(array_column($companies, 'total_count'));

$hero_title = 'دليل الشركات المعتمدة';
$hero_eyebrow = 'شركاء FleetX';
$hero_desc = 'شركات تأجير موثّقة • تقارير فحص معتمدة • أسعار شفافة';
$hero_bg = 'https://images.unsplash.com/photo-1469854523086-cc02fe5d8800?w=1600&q=80';
$hero_bottom_html = '';
?>
<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>دليل الشركات المعتمدة | FleetX</title>
  <meta name="description" content="تصفح شركات التأجير المعتمدة على FleetX">
  <meta name="fx-build" content="<?= FLEETX_CSS_VER ?>">
  <link rel="stylesheet" href="<?= fleetx_css_href() ?>">
</head>
<body class="fx-home fx-page-shell fx-page-shell--companies" data-fx-build="<?= FLEETX_CSS_VER ?>">
<?php include 'includes/navbar.php'; ?>
<?php include 'includes/page-hero.inc.php'; ?>

<div class="fx-companies-layout container fx-page-body--overlap">
  <div class="fx-companies-hero-stats reveal">
    <div class="fx-companies-stat-card">
      <i class="ph-fill ph-buildings"></i>
      <strong class="font-en"><?= count($companies) ?>+</strong>
      <span>شركة معتمدة</span>
    </div>
    <div class="fx-companies-stat-card">
      <i class="ph-fill ph-broadcast"></i>
      <strong class="font-en"><?= $live_total ?></strong>
      <span>مزاد مباشر</span>
    </div>
    <div class="fx-companies-stat-card">
      <i class="ph-fill ph-gavel"></i>
      <strong class="font-en"><?= $active_total ?></strong>
      <span>مزاد نشط</span>
    </div>
    <div class="fx-companies-stat-card">
      <i class="ph-fill ph-car"></i>
      <strong class="font-en"><?= number_format($vehicles_total) ?></strong>
      <span>إجمالي المركبات</span>
    </div>
  </div>

  <div class="fx-companies-panel reveal">
    <div class="fx-companies-panel__chips">
      <div class="filter-chips">
        <button type="button" class="chip active" onclick="filterChip(this,'all')"><i class="ph-fill ph-squares-four"></i> الكل</button>
        <button type="button" class="chip" onclick="filterChip(this,'live')"><i class="ph-fill ph-broadcast"></i> مباشر الآن</button>
        <button type="button" class="chip" onclick="filterChip(this,'active')"><i class="ph-fill ph-gavel"></i> نشط</button>
        <button type="button" class="chip" onclick="filterChip(this,'top')"><i class="ph-fill ph-star"></i> الأعلى تقييماً</button>
      </div>
    </div>

    <div class="fx-companies-panel__body">
      <div class="fx-companies-search-panel">
        <div class="fx-companies-search">
          <div class="search-input-wrap">
            <i class="ph ph-magnifying-glass"></i>
            <input class="search-input" type="text" id="searchInput" placeholder="ابحث عن شركة..." oninput="filterCompanies()">
          </div>
          <select class="filter-select" id="cityFilter" onchange="filterCompanies()">
            <option value="">كل المدن</option>
            <?php foreach ($cities as $city): ?>
            <option value="<?= htmlspecialchars($city) ?>"><?= htmlspecialchars($city) ?></option>
            <?php endforeach; ?>
          </select>
        </div>
      </div>

      <?php if ($is_guest): ?>
      <div class="guest-banner fx-guest-banner">
        <p><i class="ph-fill ph-info"></i> أنت تتصفح كزائر. سجّل الدخول للمشاركة في المزادات</p>
        <a href="/login.php?type=trader" class="btn btn-primary btn--pill">سجّل الدخول</a>
      </div>
      <?php endif; ?>

      <div class="fx-companies-toolbar">
        <h2><i class="ph-fill ph-buildings"></i> الشركات المتاحة</h2>
        <span class="fx-companies-count" id="companyCount"><?= count($companies) ?> شركة</span>
      </div>

      <div class="companies-grid fx-company-grid fx-company-grid--modern" id="companiesGrid">
        <?php foreach ($companies as $comp):
          $has_live = intval($comp['live_count']) > 0;
          $has_active = intval($comp['active_count']) > 0;
          $card_cls = $has_live ? 'has-live' : ($has_active ? 'has-active' : 'dimmed');
          $initial = mb_substr($comp['company_name'], 0, 1, 'UTF-8');
          $logo = trim($comp['logo_url'] ?? '');
          $has_logo = $logo !== '' && (str_starts_with($logo, 'http') || str_starts_with($logo, '/'));
          $rating = floatval($comp['rating'] ?? 4.5);
          $cta_text = $has_live ? 'دخول المزاد المباشر' : ($has_active ? 'عرض المزادات' : 'عرض السيارات');
        ?>
        <a href="/company-profile.php?id=<?= $comp['id'] ?>"
           class="fx-company-card fx-company-card--v2 <?= $card_cls ?>"
           data-name="<?= htmlspecialchars($comp['company_name']) ?>"
           data-city="<?= htmlspecialchars($comp['city'] ?? '') ?>"
           data-live="<?= intval($comp['live_count']) ?>"
           data-active="<?= intval($comp['active_count']) ?>"
           data-rating="<?= $rating ?>">

          <div class="fx-company-card__banner" aria-hidden="true">
            <div class="fx-company-card__banner-glow"></div>
          </div>

          <div class="fx-company-card__main">
            <div class="fx-company-card__identity">
              <div class="fx-company-card__avatar">
                <?php if ($has_logo): ?>
                  <img src="<?= htmlspecialchars($logo) ?>" alt="<?= htmlspecialchars($comp['company_name']) ?>">
                <?php else: ?>
                  <span class="fx-company-card__initial"><?= htmlspecialchars($initial) ?></span>
                <?php endif; ?>
              </div>
              <div class="fx-company-card__info">
                <div class="fx-company-card__badges">
                  <?php if ($has_live): ?>
                  <span class="fx-live-pill"><span class="fx-live-pill__dot"></span> مباشر</span>
                  <?php endif; ?>
                  <?php if (!empty($comp['is_verified'])): ?>
                  <span class="fx-verified-pill"><i class="ph-fill ph-seal-check"></i> موثّق</span>
                  <?php endif; ?>
                </div>
                <h3 class="fx-company-card__name"><?= htmlspecialchars($comp['company_name']) ?></h3>
                <div class="fx-company-card__meta">
                  <span><i class="ph ph-map-pin"></i> <?= htmlspecialchars($comp['city'] ?? 'المملكة') ?></span>
                  <span><i class="ph-fill ph-star"></i> <span class="font-en"><?= number_format($rating, 1) ?></span></span>
                </div>
              </div>
            </div>

            <div class="fx-company-card__metrics">
              <div class="fx-company-card__metric">
                <strong class="font-en"><?= intval($comp['live_count']) ?></strong>
                <span>مباشر</span>
              </div>
              <div class="fx-company-card__metric">
                <strong class="font-en"><?= intval($comp['active_count']) ?></strong>
                <span>نشط</span>
              </div>
              <div class="fx-company-card__metric">
                <strong class="font-en"><?= number_format(intval($comp['total_count'])) ?></strong>
                <span>إجمالي</span>
              </div>
            </div>

            <span class="fx-company-card__cta"><?= $cta_text ?> <i class="ph ph-arrow-left"></i></span>
          </div>
        </a>
        <?php endforeach; ?>
      </div>

      <div id="emptyState" class="fx-panel-first empty-state fx-empty-state-panel" hidden>
        <i class="ph ph-magnifying-glass"></i>
        <h3>لا توجد نتائج</h3>
        <p>جرّب بحثاً مختلفاً أو غيّر الفلتر</p>
      </div>
    </div>
  </div>
</div>

<?php include 'includes/footer.php'; ?>
<script>
let activeChip = 'all';
function filterChip(btn, type) {
  document.querySelectorAll('.fx-companies-panel__chips .chip').forEach(c => c.classList.remove('active'));
  btn.classList.add('active');
  activeChip = type;
  filterCompanies();
}
function filterCompanies() {
  const q = document.getElementById('searchInput').value.trim().toLowerCase();
  const city = document.getElementById('cityFilter').value;
  const cards = document.querySelectorAll('.fx-company-card');
  let visible = 0;
  cards.forEach(card => {
    const name = (card.dataset.name || '').toLowerCase();
    const cCity = card.dataset.city || '';
    const live = parseInt(card.dataset.live) || 0;
    const active = parseInt(card.dataset.active) || 0;
    const rating = parseFloat(card.dataset.rating) || 0;
    let show = true;
    if (q && !name.includes(q)) show = false;
    if (city && cCity !== city) show = false;
    if (activeChip === 'live' && live === 0) show = false;
    if (activeChip === 'active' && active === 0) show = false;
    if (activeChip === 'top' && rating < 4.5) show = false;
    card.style.display = show ? '' : 'none';
    if (show) visible++;
  });
  document.getElementById('companyCount').textContent = visible + ' شركة';
  const empty = document.getElementById('emptyState');
  if (empty) empty.hidden = visible !== 0;
}
</script>
</body>
</html>