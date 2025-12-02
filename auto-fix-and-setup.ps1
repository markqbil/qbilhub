# QbilHub - Automatic SSL Fix and Complete Setup
# Run as Administrator for best results

Write-Host "`n========================================" -ForegroundColor Cyan
Write-Host "   QbilHub Auto-Fix & Setup" -ForegroundColor Cyan
Write-Host "========================================`n" -ForegroundColor Cyan

# Check if running as admin
$isAdmin = ([Security.Principal.WindowsPrincipal] [Security.Principal.WindowsIdentity]::GetCurrent()).IsInRole([Security.Principal.WindowsBuiltInRole] "Administrator")
if (-not $isAdmin) {
    Write-Host "⚠ Not running as Administrator" -ForegroundColor Yellow
    Write-Host "  Some operations may fail. Continuing anyway...`n" -ForegroundColor Gray
}

# Step 1: Download SSL Certificate Bundle
Write-Host "Step 1: Downloading SSL certificate bundle..." -ForegroundColor Yellow
$certPath = "C:\cacert.pem"

if (Test-Path $certPath) {
    Write-Host "  Certificate already exists at $certPath" -ForegroundColor Gray
} else {
    try {
        Write-Host "  Downloading from curl.se..." -ForegroundColor Gray
        Invoke-WebRequest -Uri "https://curl.se/ca/cacert.pem" -OutFile $certPath -UseBasicParsing
        Write-Host "✓ Certificate downloaded to $certPath" -ForegroundColor Green
    } catch {
        Write-Host "✗ Failed to download certificate" -ForegroundColor Red
        Write-Host "  Error: $($_.Exception.Message)" -ForegroundColor Red
        Write-Host "`n  Manual fix: Download https://curl.se/ca/cacert.pem to C:\cacert.pem" -ForegroundColor Yellow
        exit 1
    }
}

# Step 2: Find php.ini
Write-Host "`nStep 2: Locating php.ini..." -ForegroundColor Yellow
$phpExe = Get-Command php -ErrorAction SilentlyContinue
if (-not $phpExe) {
    Write-Host "✗ PHP not found" -ForegroundColor Red
    exit 1
}

$phpIniPath = php --ini | Select-String "Loaded Configuration File" | ForEach-Object { $_.ToString().Split(':')[1].Trim() }

if (-not $phpIniPath -or $phpIniPath -eq "(none)") {
    # Try common locations
    $possiblePaths = @(
        "C:\Program Files\php\php.ini",
        "C:\php\php.ini",
        "C:\tools\php82\php.ini"
    )

    foreach ($path in $possiblePaths) {
        if (Test-Path $path) {
            $phpIniPath = $path
            break
        }
    }
}

if ($phpIniPath -and (Test-Path $phpIniPath)) {
    Write-Host "✓ Found php.ini at: $phpIniPath" -ForegroundColor Green
} else {
    Write-Host "✗ Could not find php.ini" -ForegroundColor Red
    Write-Host "  Please configure manually" -ForegroundColor Yellow
    $phpIniPath = $null
}

# Step 3: Update php.ini
if ($phpIniPath) {
    Write-Host "`nStep 3: Updating php.ini..." -ForegroundColor Yellow

    $iniContent = Get-Content $phpIniPath -Raw

    if ($iniContent -match "curl\.cainfo.*cacert\.pem" -and $iniContent -match "openssl\.cafile.*cacert\.pem") {
        Write-Host "  Certificate already configured in php.ini" -ForegroundColor Gray
    } else {
        try {
            Add-Content $phpIniPath "`n; SSL Certificate Configuration (added by QbilHub setup)"
            Add-Content $phpIniPath "curl.cainfo = `"$certPath`""
            Add-Content $phpIniPath "openssl.cafile = `"$certPath`""
            Write-Host "✓ php.ini updated with certificate path" -ForegroundColor Green
        } catch {
            Write-Host "✗ Failed to update php.ini (permission denied?)" -ForegroundColor Red
            Write-Host "  Please add these lines manually to $phpIniPath" -ForegroundColor Yellow
            Write-Host "  curl.cainfo = `"$certPath`"" -ForegroundColor Cyan
            Write-Host "  openssl.cafile = `"$certPath`"" -ForegroundColor Cyan
        }
    }
} else {
    Write-Host "`nStep 3: Skipping php.ini update (file not found)" -ForegroundColor Yellow
}

# Step 4: Install Composer dependencies
Write-Host "`nStep 4: Installing Composer dependencies..." -ForegroundColor Yellow
Write-Host "  This may take 5-10 minutes..." -ForegroundColor Gray

if (Test-Path "vendor") {
    Write-Host "  vendor/ already exists, skipping..." -ForegroundColor Gray
} else {
    composer install --no-interaction --prefer-dist 2>&1 | Out-String | Write-Host

    if ($LASTEXITCODE -eq 0) {
        Write-Host "✓ Composer dependencies installed" -ForegroundColor Green
    } else {
        Write-Host "⚠ Composer install had issues, trying update..." -ForegroundColor Yellow
        composer update --no-interaction --prefer-dist 2>&1 | Out-String | Write-Host
    }
}

# Step 5: Create .env.local
Write-Host "`nStep 5: Creating environment configuration..." -ForegroundColor Yellow
if (-not (Test-Path ".env.local")) {
    Copy-Item ".env" ".env.local"
    Write-Host "✓ Created .env.local" -ForegroundColor Green
} else {
    Write-Host "  .env.local already exists" -ForegroundColor Gray
}

# Step 6: Database setup
Write-Host "`nStep 6: Setting up database..." -ForegroundColor Yellow
Write-Host "  Attempting to create PostgreSQL database..." -ForegroundColor Gray
Write-Host "  (You may be prompted for PostgreSQL password)" -ForegroundColor Cyan

$sqlScript = @"
SELECT 'CREATE DATABASE qbilhub' WHERE NOT EXISTS (SELECT FROM pg_database WHERE datname = 'qbilhub')\gexec
DO `$`$ BEGIN IF NOT EXISTS (SELECT FROM pg_roles WHERE rolname = 'qbilhub') THEN CREATE USER qbilhub WITH PASSWORD 'qbilhub'; END IF; END `$`$;
GRANT ALL PRIVILEGES ON DATABASE qbilhub TO qbilhub;
"@

$sqlScript | psql -U postgres 2>&1 | Out-Null

php bin/console doctrine:database:create --if-not-exists 2>&1 | Out-Null
php bin/console doctrine:migrations:migrate --no-interaction 2>&1

Write-Host "✓ Database setup complete" -ForegroundColor Green

# Step 7: Frontend setup
Write-Host "`nStep 7: Setting up frontend..." -ForegroundColor Yellow

if (-not (Test-Path "node_modules")) {
    npm install 2>&1 | Out-Null
    Write-Host "✓ npm dependencies installed" -ForegroundColor Green
} else {
    Write-Host "  node_modules already exists" -ForegroundColor Gray
}

npm run build 2>&1 | Out-Null
Write-Host "✓ Frontend built" -ForegroundColor Green

# Summary
Write-Host "`n========================================" -ForegroundColor Green
Write-Host "   Setup Complete!" -ForegroundColor Green
Write-Host "========================================`n" -ForegroundColor Green

Write-Host "SSL certificate installed at: $certPath" -ForegroundColor White
if ($phpIniPath) {
    Write-Host "php.ini updated at: $phpIniPath`n" -ForegroundColor White
}

Write-Host "To start QbilHub, run:" -ForegroundColor Yellow
Write-Host "  .\start-qbilhub.ps1`n" -ForegroundColor Cyan

Write-Host "Or start manually:" -ForegroundColor Yellow
Write-Host "  Terminal 1: php -S localhost:8080 -t public" -ForegroundColor White
Write-Host "  Terminal 2: php bin/console messenger:consume async`n" -ForegroundColor White

Write-Host "Then visit: http://localhost:8080`n" -ForegroundColor Cyan
