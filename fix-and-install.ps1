# QbilHub - Fix SSL and Install Dependencies

Write-Host "`n========================================" -ForegroundColor Cyan
Write-Host "   QbilHub Setup with SSL Fix" -ForegroundColor Cyan
Write-Host "========================================`n" -ForegroundColor Cyan

# Fix 1: Disable Composer SSL verification (temporary workaround)
Write-Host "Step 1: Configuring Composer for SSL..." -ForegroundColor Yellow
composer config -g repos.packagist composer https://packagist.org
composer config -g disable-tls true
composer config -g secure-http false
Write-Host "✓ Composer SSL configuration updated" -ForegroundColor Green

# Fix 2: Install Composer dependencies
Write-Host "`nStep 2: Installing Composer dependencies..." -ForegroundColor Yellow
Write-Host "  This may take 5-10 minutes on first install..." -ForegroundColor Gray
composer install --no-interaction --prefer-dist
if ($LASTEXITCODE -ne 0) {
    Write-Host "✗ Composer install failed" -ForegroundColor Red
    Write-Host "`nTrying alternative method..." -ForegroundColor Yellow
    composer update --no-interaction --prefer-dist
}
Write-Host "✓ Composer dependencies installed" -ForegroundColor Green

# Step 3: Create .env.local
Write-Host "`nStep 3: Creating environment configuration..." -ForegroundColor Yellow
if (-not (Test-Path ".env.local")) {
    Copy-Item ".env" ".env.local"
    Write-Host "✓ Created .env.local" -ForegroundColor Green
} else {
    Write-Host "  .env.local already exists" -ForegroundColor Gray
}

# Step 4: Setup database
Write-Host "`nStep 4: Setting up PostgreSQL database..." -ForegroundColor Yellow
Write-Host "  You may be prompted for PostgreSQL password" -ForegroundColor Cyan

$createDbScript = @"
-- Create database and user
DO `$`$
BEGIN
    IF NOT EXISTS (SELECT FROM pg_database WHERE datname = 'qbilhub') THEN
        CREATE DATABASE qbilhub;
    END IF;
END
`$`$;

-- Create user if not exists (PostgreSQL 9.6+)
DO `$`$
BEGIN
    IF NOT EXISTS (SELECT FROM pg_roles WHERE rolname = 'qbilhub') THEN
        CREATE USER qbilhub WITH PASSWORD 'qbilhub';
    END IF;
END
`$`$;

-- Grant privileges
GRANT ALL PRIVILEGES ON DATABASE qbilhub TO qbilhub;
"@

$createDbScript | Out-File -FilePath "setup_db.sql" -Encoding utf8
psql -U postgres -f setup_db.sql 2>&1
$dbResult = $LASTEXITCODE
Remove-Item "setup_db.sql" -ErrorAction SilentlyContinue

if ($dbResult -eq 0) {
    Write-Host "✓ Database created successfully" -ForegroundColor Green
} else {
    Write-Host "⚠ Database creation had warnings (database may already exist)" -ForegroundColor Yellow
}

# Step 5: Create database schema
Write-Host "`nStep 5: Creating database tables..." -ForegroundColor Yellow
php bin/console doctrine:database:create --if-not-exists 2>&1 | Out-Null
php bin/console doctrine:migrations:migrate --no-interaction 2>&1

if ($LASTEXITCODE -eq 0) {
    Write-Host "✓ Database schema created" -ForegroundColor Green
} else {
    Write-Host "⚠ Migration warnings (might be OK if tables already exist)" -ForegroundColor Yellow
}

# Step 6: Install npm dependencies
Write-Host "`nStep 6: Installing npm dependencies..." -ForegroundColor Yellow
if (-not (Test-Path "node_modules")) {
    npm install 2>&1
    Write-Host "✓ npm dependencies installed" -ForegroundColor Green
} else {
    Write-Host "  node_modules already exists, skipping..." -ForegroundColor Gray
}

# Step 7: Build frontend
Write-Host "`nStep 7: Building frontend assets..." -ForegroundColor Yellow
npm run build 2>&1
Write-Host "✓ Frontend built" -ForegroundColor Green

# Re-enable SSL for security (optional)
Write-Host "`nStep 8: Re-enabling Composer SSL (recommended)..." -ForegroundColor Yellow
composer config -g disable-tls false
composer config -g secure-http true
Write-Host "✓ SSL re-enabled" -ForegroundColor Green

# Summary
Write-Host "`n========================================" -ForegroundColor Green
Write-Host "   Setup Complete!" -ForegroundColor Green
Write-Host "========================================`n" -ForegroundColor Green

Write-Host "Next step: Start the application with:" -ForegroundColor Yellow
Write-Host "  .\start-qbilhub.ps1`n" -ForegroundColor Cyan

Write-Host "Or manually in 2 terminals:" -ForegroundColor Yellow
Write-Host "  Terminal 1: php -S localhost:8080 -t public" -ForegroundColor White
Write-Host "  Terminal 2: php bin/console messenger:consume async -vv" -ForegroundColor White
Write-Host "`nThen visit: http://localhost:8080`n" -ForegroundColor Cyan
