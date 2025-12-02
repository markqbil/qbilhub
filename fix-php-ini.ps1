# Fix php.ini to use the SSL certificate
# Run as Administrator

Write-Host "`n========================================" -ForegroundColor Cyan
Write-Host "   PHP Configuration Fix" -ForegroundColor Cyan
Write-Host "========================================`n" -ForegroundColor Cyan

$phpIni = "C:\Program Files\php\php.ini"
$certPath = "C:\Users\MarkEllis\Documents\QbilHub\cacert.pem"

# Check if certificate exists
if (-not (Test-Path $certPath)) {
    Write-Host "✗ Certificate not found at: $certPath" -ForegroundColor Red
    Write-Host "  Please ensure cacert.pem is in the QbilHub folder" -ForegroundColor Yellow
    exit 1
}

Write-Host "✓ Certificate found at: $certPath" -ForegroundColor Green

# Check if php.ini exists
if (-not (Test-Path $phpIni)) {
    Write-Host "✗ php.ini not found at: $phpIni" -ForegroundColor Red
    exit 1
}

Write-Host "✓ php.ini found at: $phpIni" -ForegroundColor Green

# Read current content
$content = Get-Content $phpIni -Raw

# Check if already configured
if ($content -match "curl\.cainfo.*cacert\.pem" -or $content -match "openssl\.cafile.*cacert\.pem") {
    Write-Host "`n⚠ Certificate already configured in php.ini" -ForegroundColor Yellow
    Write-Host "  Updating to new path..." -ForegroundColor Gray

    # Remove old lines
    $content = $content -replace "curl\.cainfo\s*=.*", ""
    $content = $content -replace "openssl\.cafile\s*=.*", ""
}

# Add configuration
$newConfig = @"

; SSL Certificate Configuration (QbilHub)
curl.cainfo = "$certPath"
openssl.cafile = "$certPath"
"@

try {
    $content + $newConfig | Set-Content $phpIni -Force
    Write-Host "`n✓ php.ini updated successfully!" -ForegroundColor Green
} catch {
    Write-Host "`n✗ Failed to update php.ini" -ForegroundColor Red
    Write-Host "  Error: $($_.Exception.Message)" -ForegroundColor Red
    Write-Host "`n  Please run PowerShell as Administrator and try again" -ForegroundColor Yellow
    exit 1
}

Write-Host "`n========================================" -ForegroundColor Green
Write-Host "   Configuration Complete!" -ForegroundColor Green
Write-Host "========================================`n" -ForegroundColor Green

Write-Host "Certificate path: $certPath" -ForegroundColor White
Write-Host "php.ini updated: $phpIni`n" -ForegroundColor White

Write-Host "Now try installing Composer dependencies:" -ForegroundColor Yellow
Write-Host "  composer install`n" -ForegroundColor Cyan
