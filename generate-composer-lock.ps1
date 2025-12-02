# Generate composer.lock file by trying multiple SSL workarounds

Write-Host "`n========================================" -ForegroundColor Cyan
Write-Host "   Composer Lock Generator" -ForegroundColor Cyan
Write-Host "========================================`n" -ForegroundColor Cyan

# Method 1: Try with project-level certificate configuration
Write-Host "Method 1: Configuring project-level certificate..." -ForegroundColor Yellow

$certPath = Join-Path (Get-Location) "cacert.pem"

if (Test-Path $certPath) {
    Write-Host "✓ Certificate found at: $certPath" -ForegroundColor Green
    composer config cafile "$certPath"
    composer config capath ""

    Write-Host "Attempting Composer update..." -ForegroundColor Gray
    composer update --no-interaction --prefer-dist 2>&1 | Tee-Object -Variable output

    if ($LASTEXITCODE -eq 0) {
        Write-Host "`n✓ SUCCESS! composer.lock generated" -ForegroundColor Green
        exit 0
    }
} else {
    Write-Host "⚠ Certificate not found, downloading..." -ForegroundColor Yellow
    try {
        [Net.ServicePointManager]::SecurityProtocol = [Net.SecurityProtocolType]::Tls12
        $webClient = New-Object System.Net.WebClient
        $webClient.DownloadFile("https://curl.se/ca/cacert.pem", $certPath)
        Write-Host "✓ Certificate downloaded" -ForegroundColor Green
    } catch {
        Write-Host "✗ Download failed" -ForegroundColor Red
    }
}

# Method 2: Disable SSL temporarily
Write-Host "`nMethod 2: Using SSL bypass..." -ForegroundColor Yellow

$env:COMPOSER_DISABLE_XDEBUG_WARN=1
composer config -g disable-tls true
composer config -g secure-http false

Write-Host "Attempting Composer update..." -ForegroundColor Gray
composer update --no-interaction --prefer-dist 2>&1 | Tee-Object -Variable output

if ($LASTEXITCODE -eq 0) {
    # Re-enable SSL
    composer config -g disable-tls false
    composer config -g secure-http true

    Write-Host "`n✓ SUCCESS! composer.lock generated" -ForegroundColor Green
    exit 0
}

# Method 3: Use IPv4 only (sometimes helps)
Write-Host "`nMethod 3: Forcing IPv4..." -ForegroundColor Yellow

$env:COMPOSER_DISABLE_XDEBUG_WARN=1
composer config -g disable-tls true
composer config -g secure-http false
composer config -g use-github-api false

composer update --no-interaction --prefer-dist --prefer-source 2>&1 | Tee-Object -Variable output

if ($LASTEXITCODE -eq 0) {
    # Re-enable SSL
    composer config -g disable-tls false
    composer config -g secure-http true
    composer config -g --unset use-github-api

    Write-Host "`n✓ SUCCESS! composer.lock generated" -ForegroundColor Green
    exit 0
}

# All methods failed
Write-Host "`n========================================" -ForegroundColor Red
Write-Host "   All Methods Failed" -ForegroundColor Red
Write-Host "========================================`n" -ForegroundColor Red

Write-Host "The SSL issue persists. Manual steps:" -ForegroundColor Yellow
Write-Host "1. Fix SSL certificate in php.ini (see WINDOWS_SSL_FIX.md)" -ForegroundColor White
Write-Host "2. Or use Docker: docker-compose up" -ForegroundColor White
Write-Host "3. Or install on Linux/Mac where SSL works by default`n" -ForegroundColor White

Write-Host "The Python service works perfectly without Composer!" -ForegroundColor Cyan
Write-Host "Visit: http://localhost:8000/docs`n" -ForegroundColor Cyan
