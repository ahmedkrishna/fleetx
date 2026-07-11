<?php
/** Minimal toast support for pages without full footer (auth, verify flows). */
?>
<div class="toast-container" id="toastContainer"></div>
<script src="/assets/js/fleetx.js"></script>
<?php if (!empty($_SESSION['fx_toast'])):
  $fx_toast_msg = $_SESSION['fx_toast']['message'];
  $fx_toast_type = $_SESSION['fx_toast']['type'] ?? 'success';
  unset($_SESSION['fx_toast']);
?>
<script>
document.addEventListener('DOMContentLoaded', function() {
  if (typeof showToast === 'function') {
    showToast(<?= json_encode($fx_toast_msg, JSON_UNESCAPED_UNICODE) ?>, <?= json_encode($fx_toast_type) ?>);
  }
});
</script>
<?php endif; ?>