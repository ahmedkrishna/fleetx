<?php
$live = file_get_contents(__DIR__ . '/about_live.html');
if (!preg_match('/<!-- BUYER JOURNEY -->(.*)<\/div>\s*<!-- Footer template -->/s', $live, $m)) {
    fwrite(STDERR, "Could not extract about content\n");
    exit(1);
}
$body = trim($m[1]);
$body = preg_replace('/<div class="section-title-wrap seller" style="margin-top: 150px;">/', '<div class="section-title-wrap seller fx-about-seller-gap">', $body);

$php = <<<'PHP'
<?php
require_once 'config.php';
$hero_title = 'كيف يعمل FleetX؟';
$hero_bg = 'https://images.unsplash.com/photo-1573164713988-8665fc963095?w=1600&q=80';
$hero_back_href = '/index.php';
$hero_back_label = '← العودة للرئيسية';
$hero_modifier = 'compact';
?>
<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>كيف يعمل مزاد FleetX | شروط التداول والبيع</title>
  <link rel="stylesheet" href="/assets/css/fleetx.css">
</head>
<body class="page-inner fx-page-shell fx-page-shell--legal">

<?php include 'includes/navbar.php'; ?>
<?php include 'includes/page-hero.inc.php'; ?>

<div class="container fx-section-pad">
  <p class="fx-subpage-intro">نربط شركات تأجير السيارات بأكبر شبكة من المشترين والتجار المعتمدين بالمملكة عبر بيئة مزادات آمنة وسهلة بالكامل.</p>

PHP;

$php .= "\n" . $body . "\n</div>\n\n<?php include 'includes/footer.php'; ?>\n\n</body>\n</html>\n";
file_put_contents(dirname(__DIR__) . '/about.php', $php);
echo "Restored about.php (" . strlen($php) . " bytes)\n";