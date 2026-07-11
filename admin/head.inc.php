<?php
$admin_page_title = $admin_page_title ?? 'لوحة الإدارة | FleetX';
?>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <meta name="robots" content="noindex, nofollow">
  <title><?= htmlspecialchars($admin_page_title) ?></title>
  <link rel="stylesheet" href="<?= fleetx_css_href() ?>">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
  <link rel="stylesheet" href="/assets/css/admin.css">
  <script>
  document.addEventListener('DOMContentLoaded', function () {
    var btn = document.getElementById('sidebar-toggle');
    var sb = document.getElementById('admin-sidebar');
    var ov = document.getElementById('admin-sidebar-overlay');
    if (!btn || !sb) return;
    function setOpen(open) {
      sb.classList.toggle('open', open);
      if (ov) {
        ov.classList.toggle('is-visible', open);
        ov.setAttribute('aria-hidden', open ? 'false' : 'true');
      }
      document.body.classList.toggle('admin-nav-open', open);
    }
    btn.addEventListener('click', function () { setOpen(!sb.classList.contains('open')); });
    if (ov) ov.addEventListener('click', function () { setOpen(false); });
  });
  </script>