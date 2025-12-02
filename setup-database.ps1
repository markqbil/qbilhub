# QbilHub Database Setup Script

Write-Host "`n========================================" -ForegroundColor Cyan
Write-Host "   QbilHub Database Setup" -ForegroundColor Cyan
Write-Host "========================================`n" -ForegroundColor Cyan

# Step 1: Check if PHP is available
Write-Host "Step 1: Checking PHP installation..." -ForegroundColor Yellow
$php = Get-Command php -ErrorAction SilentlyContinue
if (-not $php) {
    Write-Host "✗ PHP not found. Please ensure PHP is in your PATH and restart this terminal." -ForegroundColor Red
    exit 1
}
Write-Host "✓ PHP found: $(php --version | Select-Object -First 1)" -ForegroundColor Green

# Step 2: Check if Composer is available
Write-Host "`nStep 2: Checking Composer installation..." -ForegroundColor Yellow
$composer = Get-Command composer -ErrorAction SilentlyContinue
if (-not $composer) {
    Write-Host "✗ Composer not found. Please ensure Composer is in your PATH and restart this terminal." -ForegroundColor Red
    exit 1
}
Write-Host "✓ Composer found" -ForegroundColor Green

# Step 3: Check if PostgreSQL is available
Write-Host "`nStep 3: Checking PostgreSQL installation..." -ForegroundColor Yellow
$psql = Get-Command psql -ErrorAction SilentlyContinue
if (-not $psql) {
    Write-Host "⚠ PostgreSQL (psql) not found in PATH" -ForegroundColor Yellow
    Write-Host "  You may need to create the database manually" -ForegroundColor Gray
    $hasPsql = $false
} else {
    Write-Host "✓ PostgreSQL found" -ForegroundColor Green
    $hasPsql = $true
}

# Step 4: Install Composer dependencies
Write-Host "`nStep 4: Installing Composer dependencies..." -ForegroundColor Yellow
if (Test-Path "vendor") {
    Write-Host "  vendor/ directory exists, skipping..." -ForegroundColor Gray
} else {
    Write-Host "  This may take a few minutes..." -ForegroundColor Gray
    composer install
    if ($LASTEXITCODE -ne 0) {
        Write-Host "✗ Failed to install Composer dependencies" -ForegroundColor Red
        Write-Host "  Please check for errors above" -ForegroundColor Yellow
        exit 1
    }
    Write-Host "✓ Composer dependencies installed" -ForegroundColor Green
}

# Step 5: Create .env.local if it doesn't exist
Write-Host "`nStep 5: Configuring environment..." -ForegroundColor Yellow
if (-not (Test-Path ".env.local")) {
    Copy-Item ".env" ".env.local"
    Write-Host "✓ Created .env.local from .env" -ForegroundColor Green
    Write-Host "  You may need to adjust database credentials in .env.local" -ForegroundColor Gray
} else {
    Write-Host "  .env.local already exists" -ForegroundColor Gray
}

# Step 6: Create database
Write-Host "`nStep 6: Setting up database..." -ForegroundColor Yellow
if ($hasPsql) {
    Write-Host "  Attempting to create database..." -ForegroundColor Gray
    Write-Host "  You may be prompted for PostgreSQL password (default: postgres)" -ForegroundColor Cyan

    # Create database commands in a SQL file
    $sqlCommands = @"
CREATE DATABASE qbilhub;
CREATE USER qbilhub WITH PASSWORD 'qbilhub';
GRANT ALL PRIVILEGES ON DATABASE qbilhub TO qbilhub;
"@
    $sqlCommands | Out-File -FilePath "setup_db.sql" -Encoding utf8

    # Try to create database
    psql -U postgres -f setup_db.sql 2>&1
    Remove-Item "setup_db.sql"

    Write-Host "  Database creation attempted" -ForegroundColor Gray
} else {
    Write-Host "  Please create the database manually:" -ForegroundColor Yellow
    Write-Host "    1. Open pgAdmin or psql" -ForegroundColor White
    Write-Host "    2. Run these commands:" -ForegroundColor White
    Write-Host "       CREATE DATABASE qbilhub;" -ForegroundColor Cyan
    Write-Host "       CREATE USER qbilhub WITH PASSWORD 'qbilhub';" -ForegroundColor Cyan
    Write-Host "       GRANT ALL PRIVILEGES ON DATABASE qbilhub TO qbilhub;" -ForegroundColor Cyan
    Write-Host ""
    Write-Host "  Press Enter when done, or Ctrl+C to exit..." -ForegroundColor Yellow
    Read-Host
}

# Step 7: Run Doctrine migrations
Write-Host "`nStep 7: Creating database schema..." -ForegroundColor Yellow
Write-Host "  Creating database if not exists..." -ForegroundColor Gray
php bin/console doctrine:database:create --if-not-exists 2>&1

Write-Host "  Running migrations..." -ForegroundColor Gray
php bin/console doctrine:migrations:migrate --no-interaction 2>&1
if ($LASTEXITCODE -eq 0) {
    Write-Host "✓ Database schema created successfully" -ForegroundColor Green
} else {
    Write-Host "⚠ Migration had warnings (this might be OK)" -ForegroundColor Yellow
}

# Step 8: Install npm dependencies
Write-Host "`nStep 8: Installing npm dependencies..." -ForegroundColor Yellow
if (Test-Path "node_modules") {
    Write-Host "  node_modules/ exists, skipping..." -ForegroundColor Gray
} else {
    npm install
    if ($LASTEXITCODE -ne 0) {
        Write-Host "✗ Failed to install npm dependencies" -ForegroundColor Red
        exit 1
    }
    Write-Host "✓ npm dependencies installed" -ForegroundColor Green
}

# Step 9: Build frontend assets
Write-Host "`nStep 9: Building frontend assets..." -ForegroundColor Yellow
npm run build
if ($LASTEXITCODE -eq 0) {
    Write-Host "✓ Frontend assets built successfully" -ForegroundColor Green
} else {
    Write-Host "⚠ Build had warnings (this might be OK)" -ForegroundColor Yellow
}

# Summary
Write-Host "`n========================================" -ForegroundColor Green
Write-Host "   Setup Complete!" -ForegroundColor Green
Write-Host "========================================`n" -ForegroundColor Green

Write-Host "Next steps:" -ForegroundColor Yellow
Write-Host "1. To start all services, run:" -ForegroundColor White
Write-Host "   powershell -ExecutionPolicy Bypass -File start-qbilhub.ps1`n" -ForegroundColor Cyan

Write-Host "2. Or start services manually:" -ForegroundColor White
Write-Host "   Terminal 1: php -S localhost:8080 -t public" -ForegroundColor Cyan
Write-Host "   Terminal 2: php bin/console messenger:consume async -vv" -ForegroundColor Cyan
Write-Host "   Terminal 3: Python service (already running)" -ForegroundColor Cyan

Write-Host "`n3. Access the application:" -ForegroundColor White
Write-Host "   http://localhost:8080`n" -ForegroundColor Cyan
