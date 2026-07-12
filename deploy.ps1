# FleetX — upload site files to Hostinger
# Usage: .\deploy.ps1 -FtpPassword 'your-ftp-password'
param(
    [string]$FtpHost = '82.198.227.155',
    [string]$FtpUser = 'u274391035.mazadi.bearand.com',
    [string]$FtpPassword = $env:FLEETX_FTP_PASS,
    [string]$RemotePath = ''
)

$ErrorActionPreference = 'Continue'
$root = $PSScriptRoot
$failed = @()

if (-not $FtpPassword) {
    Write-Host 'Set FTP password: .\deploy.ps1 -FtpPassword "..." or $env:FLEETX_FTP_PASS'
    exit 1
}

function Upload-FtpFile {
    param([string]$Rel)
    $local = Join-Path $root $Rel
    if (-not (Test-Path $local)) {
        Write-Warning "Skip missing: $Rel"
        return
    }
    $remote = if ($RemotePath) { "$RemotePath/$($Rel.Replace('\','/'))" } else { $Rel.Replace('\','/') }
    $uri = "ftp://${FtpHost}/$remote"
    for ($attempt = 1; $attempt -le 3; $attempt++) {
        Write-Host "Uploading $Rel (attempt $attempt) ..."
        curl.exe -s --ftp-pasv --ftp-create-dirs -T $local $uri --user "${FtpUser}:${FtpPassword}"
        if ($LASTEXITCODE -eq 0) { return }
        if ($attempt -lt 3) { Start-Sleep -Seconds 2 }
    }
    Write-Warning "Failed: $Rel (exit $LASTEXITCODE)"
    $script:failed += $Rel
}

$files = @(
    'config.php',
    'index.php',
    'fx-build.php',
    '.htaccess',
    'assets/css/home-live.css',
    'assets/css/fleetx.css',
    'assets/css/splash.css',
    'assets/css/platform-ui.css',
    'assets/css/theme-restore.css',
    'assets/css/homepage-polish.css',
    'assets/css/subpage-polish.css',
    'assets/css/site-polish.css',
    'assets/css/admin.css',
    'assets/js/fleetx.js',
    'includes/navbar.php',
    'includes/splash.inc.php',
    'includes/fx-logo.inc.php',
    'includes/header.php',
    'includes/page-hero.inc.php',
    'includes/fx-auction-card.inc.php',
    'includes/fx-seller-fleet-card.inc.php',
    'includes/footer.php',
    'includes/toast-snippet.inc.php',
    'includes/dashboard/empty-state.inc.php',
    'includes/dashboard/activity-row.inc.php',
    'includes/SimpleXLSX.php',
    'login.php',
    'logout.php',
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
    'admin/settings.php',
    'admin/activity.php',
    'api/invoice.php',
    'api/auto-bid.php',
    'admin/head.inc.php',
    'admin/sidebar.inc.php',
    'assets/images/fleetxhero.png',
    'assets/images/fleetxhero-desktop.png',
    'assets/images/fleetxhero-mobile.png',
    'assets/images/logo.png',
    'assets/images/logo-dark.png',
    'auction-live.php',
    'auctions.php',
    'companies.php',
    'vehicle-details.php',
    'inspector.php',
    'event.php',
    'extend_auctions.php',
    'about.php',
    'checkout.php',
    'payment-gateway.php',
    'payment-return.php',
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
    'api/get-bids.php',
    'api/notifications.php',
    'api/buy-now.php',
    'api/watchlist.php',
    'api/export-report.php',
    'api/saved-searches.php',
    'api/otp.php',
    'api/google-login.php',
    'api/test-whatsapp.php',
    'api/test-sms.php',
    'api/e2e_helpers.php',
    'includes/fx-service-bundles.inc.php',
    'includes/integrations.php',
    'migrate_requirements.php',
    'cron/end-auctions.php',
    'tests/verify_live_ui.php',
    'tests/verify_wallet_payment.php',
    'tests/verify_whatsapp.php',
    'tests/verify_sms.php',
    'tests/e2e_end_to_end.php',
    'tests/e2e.php'
)

foreach ($rel in $files) { Upload-FtpFile $rel }

Write-Host "`nDeployed. CSS cache: FLEETX_CSS_VER in config.php"
Write-Host "Live: https://mazadi.bearand.com"
Write-Host "Build: https://mazadi.bearand.com/fx-build.php"
if ($failed.Count -gt 0) {
    Write-Warning "Failed files ($($failed.Count)):"
    $failed | ForEach-Object { Write-Warning "  $_" }
    exit 1
}