# Install Composer packages without SSL verification
# This is a workaround for SSL certificate issues

Write-Host "`n========================================" -ForegroundColor Cyan
Write-Host "   Composer Install (SSL Bypass)" -ForegroundColor Cyan
Write-Host "========================================`n" -ForegroundColor Cyan

Write-Host "Disabling SSL verification temporarily..." -ForegroundColor Yellow

# Disable SSL globally for Composer
composer config -g disable-tls true
composer config -g secure-http false

Write-Host "✓ SSL verification disabled" -ForegroundColor Green

Write-Host "`nInstalling dependencies..." -ForegroundColor Yellow
Write-Host "(This may take 5-10 minutes)`n" -ForegroundColor Gray

# Install dependencies
composer install --no-interaction --prefer-dist

if ($LASTEXITCODE -eq 0) {
    Write-Host "`n✓ Dependencies installed successfully!" -ForegroundColor Green

    # Re-enable SSL for security
    Write-Host "`nRe-enabling SSL for security..." -ForegroundColor Yellow
    composer config -g disable-tls false
    composer config -g secure-http true

    Write-Host "`n========================================" -ForegroundColor Green
    Write-Host "   Installation Complete!" -ForegroundColor Green
    Write-Host "========================================`n" -ForegroundColor Green

    Write-Host "Next steps:" -ForegroundColor Yellow
    Write-Host "1. Create database:" -ForegroundColor White
    Write-Host "   php bin/console doctrine:database:create" -ForegroundColor Cyan
    Write-Host "   php bin/console doctrine:migrations:migrate`n" -ForegroundColor Cyan

    Write-Host "2. Build frontend:" -ForegroundColor White
    Write-Host "   npm install && npm run build`n" -ForegroundColor Cyan

    Write-Host "3. Start application:" -ForegroundColor White
    Write-Host "   .\start-qbilhub.ps1`n" -ForegroundColor Cyan
} else {
    Write-Host "`n✗ Installation failed" -ForegroundColor Red
    Write-Host "Please check the errors above`n" -ForegroundColor Yellow
}
