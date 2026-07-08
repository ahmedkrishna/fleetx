# FleetX — upload critical hotfix files to Hostinger
# Usage: .\deploy.ps1 -FtpPassword 'your-ftp-password'
param(
    [string]$FtpHost = '82.198.227.155',
    [string]$FtpUser = 'u274391035.mazadi.bearand.com',
    [string]$FtpPassword = $env:FLEETX_FTP_PASS,
    [string]$RemotePath = ''
)

$ErrorActionPreference = 'Stop'
$root = $PSScriptRoot

if (-not $FtpPassword) {
    Write-Host 'Set FTP password: .\deploy.ps1 -FtpPassword "..." or $env:FLEETX_FTP_PASS'
    exit 1
}

$files = @(
    'config.php',
    'login.php',
    'buyer.php',
    'seller.php',
    'dashboard.php',
    'add-auction.php',
    'add-instant.php',
    'bulk-upload.php',
    'sanad.php',
    'nafath.php',
    'register.php',
    'admin/index.php',
    'admin/users.php',
    'admin/auctions.php',
    'admin/inspections.php',
    'admin/subscriptions.php',
    'admin/approvals.php',
    'admin/head.inc.php',
    'admin/sidebar.inc.php',
    'includes/navbar.php',
    'includes/header.php',
    'includes/page-hero.inc.php',
    'includes/fx-auction-card.inc.php',
    'assets/images/fleetxhero.png',
    'assets/images/fleetxhero1.png',
    'assets/images/fleetxhero-desktop.png',
    'assets/images/fleetxhero-mobile.png',
    'includes/footer.php',
    'assets/js/fleetx.js',
    'assets/css/fleetx.css',
    'assets/css/platform-ui.css',
    'assets/css/theme-restore.css',
    'assets/css/homepage-polish.css',
    'assets/css/subpage-polish.css',
    'assets/css/site-polish.css',
    'auction-live.php',
    'auctions.php',
    'companies.php',
    'vehicle-details.php',
    'index.php',
    'login.php',
    'inspector.php',
    'event.php',
    'bulk-upload.php',
    'inspector.php',
    'about.php',
    'tests/verify_live_ui.php',
    'checkout.php',
    'wallet-topup.php',
    'onboarding.php',
    'company-profile.php',
    'inspector-report.php',
    'terms.php',
    'map.php',
    'vehicle.php',
    'profile.php',
    'instant-purchase.php',
    'api/bid.php',
    'api/place-bid.php',
    'api/toggle_favorite.php',
    'tests/e2e_end_to_end.php',
    'tests/e2e.php'
)

foreach ($rel in $files) {
    $local = Join-Path $root $rel
    if (-not (Test-Path $local)) { Write-Warning "Skip missing: $rel"; continue }
    $remote = if ($RemotePath) { "$RemotePath/$($rel.Replace('\','/'))" } else { $rel.Replace('\','/') }
    $uri = "ftp://${FtpHost}/$remote"
    Write-Host "Uploading $rel ..."
    curl.exe -s --ftp-pasv -T $local $uri --user "${FtpUser}:${FtpPassword}"
    if ($LASTEXITCODE -ne 0) { Write-Error "Failed: $rel (exit $LASTEXITCODE)" }
}

Write-Host "`nRun hotfix: https://mazadi.bearand.com/hotfix.php?key=mazad2026"
Write-Host "Or:       https://mazadi.bearand.com/update_db_sanad.php?key=mazad2026"
Write-Host "Run E2E:    php tests/e2e_end_to_end.php"