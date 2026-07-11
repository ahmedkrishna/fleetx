<?php
/**
 * @deprecated Use a full page template with fleetx.css + includes/navbar.php instead.
 * This shim keeps legacy includes working on the FleetX theme.
 */
if (defined('FLEETX_HEADER_INCLUDED')) return;
define('FLEETX_HEADER_INCLUDED', true);

require_once __DIR__ . '/../config.php';
?>
<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title><?= sanitize($page_title ?? 'FleetX') ?></title>
  <link rel="stylesheet" href="<?= fleetx_css_href() ?>">
</head>
<body class="page-inner fx-subpage-body">
<?php include __DIR__ . '/navbar.php'; ?>