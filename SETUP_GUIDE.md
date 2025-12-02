# Complete Setup Guide for QbilHub

## Current Status
✅ Python Intelligence Service - RUNNING on http://localhost:8000
⏸️ Symfony Application - Needs configuration

## Step-by-Step Setup

### Step 1: Add PHP, Composer, and PostgreSQL to PATH

After installing PHP, Composer, and PostgreSQL, you need to add them to your system PATH.

#### Find Installation Locations

Common locations:
- **PHP**: `C:\php\` or `C:\tools\php82\` (if using Chocolatey)
- **Composer**: Usually installed in `C:\ProgramData\ComposerSetup\bin\` or `%APPDATA%\Composer\`
- **PostgreSQL**: `C:\Program Files\PostgreSQL\16\bin\` (version may vary)

#### Add to PATH (Windows)

**Option 1: Using PowerShell (Recommended)**
```powershell
# Run PowerShell as Administrator

# Add PHP
[Environment]::SetEnvironmentVariable("Path", $env:Path + ";C:\php", "Machine")

# Add Composer
[Environment]::SetEnvironmentVariable("Path", $env:Path + ";C:\ProgramData\ComposerSetup\bin", "Machine")

# Add PostgreSQL
[Environment]::SetEnvironmentVariable("Path", $env:Path + ";C:\Program Files\PostgreSQL\16\bin", "Machine")

# Restart your terminal after this
```

**Option 2: Using System Settings**
1. Press `Win + X` and select "System"
2. Click "Advanced system settings"
3. Click "Environment Variables"
4. Under "System variables", find "Path" and click "Edit"
5. Click "New" and add each path:
   - PHP path (e.g., `C:\php`)
   - Composer path
   - PostgreSQL bin path
6. Click "OK" to save
7. **Restart your terminal**

### Step 2: Verify Installation

Open a **new terminal** window and run:

```bash
php --version
composer --version
psql --version
```

You should see version information for each tool.

### Step 3: Install Symfony Dependencies

```bash
cd c:\Users\MarkEllis\Documents\QbilHub
composer install
```

This will install all PHP dependencies (may take a few minutes).

### Step 4: Configure Database

#### 4.1 Create PostgreSQL Database

```bash
# Connect to PostgreSQL (default password is what you set during installation)
psql -U postgres

# In psql prompt:
CREATE DATABASE qbilhub;
CREATE USER qbilhub WITH PASSWORD 'qbilhub';
GRANT ALL PRIVILEGES ON DATABASE qbilhub TO qbilhub;
\q
```

#### 4.2 Update .env.local

Create/edit `.env.local` file:

```bash
# Copy the template
cp .env .env.local
```

Edit `.env.local` and update the DATABASE_URL:
```
DATABASE_URL="postgresql://qbilhub:qbilhub@127.0.0.1:5432/qbilhub?serverVersion=15&charset=utf8"
```

### Step 5: Create Database Schema

```bash
# Create the database (if not created manually)
php bin/console doctrine:database:create --if-not-exists

# Run migrations to create tables
php bin/console doctrine:migrations:migrate --no-interaction
```

### Step 6: Install Frontend Dependencies

```bash
npm install
npm run build
```

### Step 7: Start All Services

You'll need **3 terminal windows**:

#### Terminal 1: Symfony Web Server
```bash
cd c:\Users\MarkEllis\Documents\QbilHub
php -S localhost:8080 -t public
```

#### Terminal 2: Message Queue Worker (Optional but recommended)
```bash
cd c:\Users\MarkEllis\Documents\QbilHub
php bin/console messenger:consume async -vv
```

#### Terminal 3: Python Service (Already Running ✅)
Already started at http://localhost:8000

### Step 8: Create Sample Data (Optional)

Create a simple script to add test data:

```bash
php bin/console doctrine:fixtures:load
```

Or manually via SQL or the API.

## Quick Start Script

Save this as `start-qbilhub.ps1`:

```powershell
# QbilHub Startup Script

Write-Host "Starting QbilHub Services..." -ForegroundColor Green

# Check if services are installed
$php = Get-Command php -ErrorAction SilentlyContinue
$python = Get-Command python -ErrorAction SilentlyContinue

if (-not $php) {
    Write-Host "ERROR: PHP not found in PATH" -ForegroundColor Red
    exit 1
}

if (-not $python) {
    Write-Host "ERROR: Python not found in PATH" -ForegroundColor Red
    exit 1
}

Write-Host "✓ PHP found: $($php.Version)" -ForegroundColor Green
Write-Host "✓ Python found" -ForegroundColor Green

# Start Python service in background
Write-Host "`nStarting Python Intelligence Service..." -ForegroundColor Cyan
Start-Process powershell -ArgumentList "-NoExit", "-Command", "cd python-service; .\venv\Scripts\python.exe -m uvicorn app.main:app --host 0.0.0.0 --port 8000"

# Wait for Python service
Start-Sleep -Seconds 3

# Start Symfony server
Write-Host "Starting Symfony Web Server..." -ForegroundColor Cyan
Start-Process powershell -ArgumentList "-NoExit", "-Command", "php -S localhost:8080 -t public"

# Start message worker (optional)
Write-Host "Starting Message Queue Worker..." -ForegroundColor Cyan
Start-Process powershell -ArgumentList "-NoExit", "-Command", "php bin/console messenger:consume async -vv"

Write-Host "`n========================================" -ForegroundColor Green
Write-Host "QbilHub Services Started!" -ForegroundColor Green
Write-Host "========================================" -ForegroundColor Green
Write-Host "Web Application:    http://localhost:8080" -ForegroundColor Yellow
Write-Host "Python API:         http://localhost:8000" -ForegroundColor Yellow
Write-Host "API Documentation:  http://localhost:8000/docs" -ForegroundColor Yellow
Write-Host "`nPress Ctrl+C in each window to stop services" -ForegroundColor Gray
```

Run it with:
```powershell
powershell -ExecutionPolicy Bypass -File start-qbilhub.ps1
```

## Troubleshooting

### "php: command not found"
- PHP is not in your PATH
- Restart your terminal after adding to PATH
- Verify installation: `where php` (should show path)

### "SQLSTATE[08006] could not connect to server"
- PostgreSQL is not running
- Start it: `net start postgresql-x64-16` (or your version)
- Or use Services app (Win + R, `services.msc`)

### "No such file or directory: vendor/"
- Run `composer install` first

### "npm: command not found"
- Node.js/npm not installed or not in PATH
- Already should be working based on earlier tests

### Port 8080 already in use
- Change port: `php -S localhost:8081 -t public`
- Or stop the conflicting service

## Access the Application

Once all services are running:

1. **Web Application**: http://localhost:8080
2. **Hub Inbox**: http://localhost:8080/hub/inbox
3. **API Documentation**: http://localhost:8000/docs
4. **API Health Check**: http://localhost:8000/health

## Next Steps After Setup

1. Create test users and tenants via database or API
2. Upload test documents
3. Test the mapping interface
4. Try the active learning workflow

## Need Help?

Check these files:
- [README.md](README.md) - Project overview
- [IMPLEMENTATION_NOTES.md](IMPLEMENTATION_NOTES.md) - Technical details
- [TEST_RESULTS.md](TEST_RESULTS.md) - API test results
