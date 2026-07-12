<?php
$admin_page_title = $admin_page_title ?? 'لوحة الإدارة | FleetX';
?>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <meta name="robots" content="noindex, nofollow">
  <meta name="fx-build" content="<?= FLEETX_CSS_VER ?>">
  <title><?= htmlspecialchars($admin_page_title) ?></title>
  <link rel="stylesheet" href="<?= fleetx_css_href() ?>">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
  <link rel="stylesheet" href="/assets/css/admin.css?v=<?= FLEETX_CSS_VER ?>">
  <script>
  document.addEventListener('DOMContentLoaded', function () {
    var sb = document.getElementById('admin-sidebar');
    var ov = document.getElementById('admin-sidebar-overlay');
    if (!sb) return;

    var toggles = document.querySelectorAll('#sidebar-toggle, #sidebar-toggle-mobile');
    if (!toggles.length) return;

    function setOpen(open) {
      sb.classList.toggle('open', open);
      toggles.forEach(function (btn) {
        btn.setAttribute('aria-expanded', open ? 'true' : 'false');
        btn.setAttribute('aria-label', open ? 'إغلاق القائمة' : 'فتح القائمة');
      });
      if (ov) {
        ov.classList.toggle('is-visible', open);
        ov.setAttribute('aria-hidden', open ? 'false' : 'true');
      }
      document.body.classList.toggle('admin-nav-open', open);
    }

    toggles.forEach(function (btn) {
      btn.addEventListener('click', function () { setOpen(!sb.classList.contains('open')); });
    });
    if (ov) ov.addEventListener('click', function () { setOpen(false); });
    sb.querySelectorAll('.admin-nav-link').forEach(function (link) {
      link.addEventListener('click', function () { setOpen(false); });
    });
    document.addEventListener('keydown', function (e) {
      if (e.key === 'Escape' && sb.classList.contains('open')) setOpen(false);
    });
  });
  </script>