# QbilHub Startup Script for Windows PowerShell

Write-Host "`n========================================" -ForegroundColor Cyan
Write-Host "   QbilHub Service Launcher" -ForegroundColor Cyan
Write-Host "========================================`n" -ForegroundColor Cyan

# Check if services are installed
Write-Host "Checking dependencies..." -ForegroundColor Yellow

$php = Get-Command php -ErrorAction SilentlyContinue
$composer = Get-Command composer -ErrorAction SilentlyContinue
$python = Get-Command python -ErrorAction SilentlyContinue
$npm = Get-Command npm -ErrorAction SilentlyContinue

$allGood = $true

if (-not $php) {
    Write-Host "✗ PHP not found in PATH" -ForegroundColor Red
    Write-Host "  Please add PHP to your system PATH and restart terminal" -ForegroundColor Gray
    $allGood = $false
} else {
    Write-Host "✓ PHP found: $(php --version | Select-Object -First 1)" -ForegroundColor Green
}

if (-not $composer) {
    Write-Host "✗ Composer not found in PATH" -ForegroundColor Red
    $allGood = $false
} else {
    Write-Host "✓ Composer found" -ForegroundColor Green
}

if (-not $python) {
    Write-Host "✗ Python not found in PATH" -ForegroundColor Red
    $allGood = $false
} else {
    Write-Host "✓ Python found" -ForegroundColor Green
}

if (-not $npm) {
    Write-Host "✗ npm not found in PATH" -ForegroundColor Red
    $allGood = $false
} else {
    Write-Host "✓ npm found" -ForegroundColor Green
}

if (-not $allGood) {
    Write-Host "`nPlease install missing dependencies and add them to PATH." -ForegroundColor Red
    Write-Host "See SETUP_GUIDE.md for instructions." -ForegroundColor Yellow
    exit 1
}

Write-Host "`nChecking if vendor directory exists..." -ForegroundColor Yellow
if (-not (Test-Path "vendor")) {
    Write-Host "Installing Composer dependencies (this may take a few minutes)..." -ForegroundColor Yellow
    composer install
    if ($LASTEXITCODE -ne 0) {
        Write-Host "Failed to install Composer dependencies" -ForegroundColor Red
        exit 1
    }
}

Write-Host "`nChecking if node_modules exists..." -ForegroundColor Yellow
if (-not (Test-Path "node_modules")) {
    Write-Host "Installing npm dependencies..." -ForegroundColor Yellow
    npm install
    if ($LASTEXITCODE -ne 0) {
        Write-Host "Failed to install npm dependencies" -ForegroundColor Red
        exit 1
    }
}

Write-Host "`n========================================" -ForegroundColor Green
Write-Host "   Starting QbilHub Services" -ForegroundColor Green
Write-Host "========================================`n" -ForegroundColor Green

# Start Python service in new window
Write-Host "1. Starting Python Intelligence Service on port 8000..." -ForegroundColor Cyan
$pythonPath = Get-Location
Start-Process powershell -ArgumentList "-NoExit", "-Command", "cd '$pythonPath\python-service'; .\venv\Scripts\python.exe -m uvicorn app.main:app --host 0.0.0.0 --port 8000"

# Wait for Python service to start
Write-Host "   Waiting for Python service to start..." -ForegroundColor Gray
Start-Sleep -Seconds 3

# Test if Python service is running
try {
    $response = Invoke-WebRequest -Uri "http://localhost:8000/health" -UseBasicParsing -TimeoutSec 2
    Write-Host "   ✓ Python service is running" -ForegroundColor Green
} catch {
    Write-Host "   ⚠ Python service may not be running yet" -ForegroundColor Yellow
}

# Start Symfony server in new window
Write-Host "`n2. Starting Symfony Web Server on port 8080..." -ForegroundColor Cyan
Start-Process powershell -ArgumentList "-NoExit", "-Command", "cd '$pythonPath'; php -S localhost:8080 -t public"

# Wait a moment
Start-Sleep -Seconds 2

# Optionally start message worker
Write-Host "`n3. Starting Message Queue Worker (optional)..." -ForegroundColor Cyan
Start-Process powershell -ArgumentList "-NoExit", "-Command", "cd '$pythonPath'; php bin/console messenger:consume async -vv"

Write-Host "`n========================================" -ForegroundColor Green
Write-Host "   QbilHub Services Started!" -ForegroundColor Green
Write-Host "========================================" -ForegroundColor Green

Write-Host "`nAccess Points:" -ForegroundColor Yellow
Write-Host "  • Web Application:    http://localhost:8080" -ForegroundColor White
Write-Host "  • Hub Inbox:          http://localhost:8080/hub/inbox" -ForegroundColor White
Write-Host "  • Python API:         http://localhost:8000" -ForegroundColor White
Write-Host "  • API Documentation:  http://localhost:8000/docs" -ForegroundColor White

Write-Host "`nTo stop services:" -ForegroundColor Gray
Write-Host "  Close the PowerShell windows or press Ctrl+C in each window" -ForegroundColor Gray

Write-Host "`nNote: 3 new PowerShell windows have been opened." -ForegroundColor Cyan
Write-Host "      Keep them running for the services to work.`n" -ForegroundColor Cyan
