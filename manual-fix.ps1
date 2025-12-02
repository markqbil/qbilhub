# QbilHub - Simple Manual SSL Fix

Write-Host "`n========================================" -ForegroundColor Cyan
Write-Host "   Manual SSL Certificate Fix" -ForegroundColor Cyan
Write-Host "========================================`n" -ForegroundColor Cyan

# Step 1: Download certificate with better error handling
Write-Host "Step 1: Downloading SSL certificate..." -ForegroundColor Yellow

$certPath = "cacert.pem"  # Save in current directory
$certUrl = "https://curl.se/ca/cacert.pem"

try {
    # Try with system web client (bypasses SSL)
    [Net.ServicePointManager]::SecurityProtocol = [Net.SecurityProtocolType]::Tls12
    $webClient = New-Object System.Net.WebClient
    $webClient.DownloadFile($certUrl, $certPath)
    Write-Host "✓ Certificate downloaded to: $(Get-Location)\$certPath" -ForegroundColor Green
} catch {
    Write-Host "✗ Failed to download. Trying alternative..." -ForegroundColor Yellow

    try {
        # Alternative: Download from GitHub mirror
        $altUrl = "https://raw.githubusercontent.com/bagder/ca-bundle/master/ca-bundle.crt"
        $webClient.DownloadFile($altUrl, $certPath)
        Write-Host "✓ Certificate downloaded from alternative source" -ForegroundColor Green
    } catch {
        Write-Host "✗ Download failed" -ForegroundColor Red
        Write-Host "`nManual download:" -ForegroundColor Yellow
        Write-Host "1. Open browser: https://curl.se/ca/cacert.pem" -ForegroundColor White
        Write-Host "2. Save file to: $(Get-Location)\cacert.pem" -ForegroundColor White
        Write-Host "3. Run this script again`n" -ForegroundColor White
        exit 1
    }
}

# Step 2: Tell Composer to use this certificate
Write-Host "`nStep 2: Configuring Composer..." -ForegroundColor Yellow

$certFullPath = Join-Path (Get-Location) $certPath

# Set environment variable
[Environment]::SetEnvironmentVariable("SSL_CERT_FILE", $certFullPath, "User")
$env:SSL_CERT_FILE = $certFullPath

# Also configure Composer directly
composer config --global cafile "$certFullPath"
composer config --global disable-tls false
composer config --global secure-http true

Write-Host "✓ Composer configured to use: $certFullPath" -ForegroundColor Green

# Step 3: Install dependencies
Write-Host "`nStep 3: Installing Composer dependencies..." -ForegroundColor Yellow
Write-Host "  (This may take 5-10 minutes)..." -ForegroundColor Gray

composer install --no-interaction

if ($LASTEXITCODE -eq 0) {
    Write-Host "✓ Success!" -ForegroundColor Green
} else {
    Write-Host "`n⚠ If SSL error persists, restart PowerShell and try again" -ForegroundColor Yellow
    exit 1
}

Write-Host "`n========================================" -ForegroundColor Green
Write-Host "   SSL Fixed & Dependencies Installed!" -ForegroundColor Green
Write-Host "========================================`n" -ForegroundColor Green

Write-Host "Next steps:" -ForegroundColor Yellow
Write-Host "1. Set up database:" -ForegroundColor White
Write-Host "   php bin/console doctrine:database:create" -ForegroundColor Cyan
Write-Host "   php bin/console doctrine:migrations:migrate`n" -ForegroundColor Cyan

Write-Host "2. Build frontend:" -ForegroundColor White
Write-Host "   npm install" -ForegroundColor Cyan
Write-Host "   npm run build`n" -ForegroundColor Cyan

Write-Host "3. Start app:" -ForegroundColor White
Write-Host "   .\start-qbilhub.ps1`n" -ForegroundColor Cyan
