# QbilHub - Next Steps

## Current Status

✅ **Python Intelligence Service** - Running at http://localhost:8000
✅ **Project Files** - All created and ready
✅ **PHP, Composer, PostgreSQL** - Installed (need PATH configuration)

## What to Do Now

### Step 1: Add Tools to PATH (REQUIRED)

You've installed PHP, Composer, and PostgreSQL, but they need to be in your system PATH.

**Quick way to check:**
```powershell
powershell -ExecutionPolicy Bypass -File check-installation.ps1
```

This script will:
- Tell you which tools are found/missing
- Show you where they're installed
- Give you the exact paths to add

### Step 2: Add to PATH

**Method 1: PowerShell (Quick)**
```powershell
# Run PowerShell as Administrator
# Replace paths with your actual installation locations

$phpPath = "C:\php"  # Or wherever PHP is installed
$composerPath = "C:\ProgramData\ComposerSetup\bin"
$postgresPath = "C:\Program Files\PostgreSQL\16\bin"  # Adjust version

# Add to PATH
$currentPath = [Environment]::GetEnvironmentVariable("Path", "Machine")
$newPath = "$currentPath;$phpPath;$composerPath;$postgresPath"
[Environment]::SetEnvironmentVariable("Path", $newPath, "Machine")

# IMPORTANT: Close and reopen your terminal after this!
```

**Method 2: GUI (Safe)**
1. Press `Win + X` → System → Advanced system settings
2. Click "Environment Variables"
3. Under "System variables", select "Path" → "Edit"
4. Click "New" and add each path:
   - PHP directory (e.g., `C:\php`)
   - Composer bin directory
   - PostgreSQL bin directory
5. Click OK
6. **Restart your terminal**

### Step 3: Verify Installation

Open a **NEW** terminal and run:
```powershell
php --version
composer --version
psql --version
```

If you see version numbers, you're good to go! ✅

### Step 4: Configure Database

```powershell
# Connect to PostgreSQL
psql -U postgres

# In the psql prompt, create database:
CREATE DATABASE qbilhub;
CREATE USER qbilhub WITH PASSWORD 'qbilhub';
GRANT ALL PRIVILEGES ON DATABASE qbilhub TO qbilhub;
\q
```

### Step 5: Install Dependencies

```powershell
cd c:\Users\MarkEllis\Documents\QbilHub

# Install PHP dependencies
composer install

# Build database schema
php bin/console doctrine:database:create --if-not-exists
php bin/console doctrine:migrations:migrate

# Install and build frontend
npm install
npm run build
```

### Step 6: Start QbilHub

**Option A: Automatic (Recommended)**
```powershell
powershell -ExecutionPolicy Bypass -File start-qbilhub.ps1
```

This will start all services in separate windows.

**Option B: Manual**

Open 3 terminal windows:

**Terminal 1: Symfony Web Server**
```bash
php -S localhost:8080 -t public
```

**Terminal 2: Message Queue Worker**
```bash
php bin/console messenger:consume async -vv
```

**Terminal 3: Python Service** (Already running ✅)

### Step 7: Access the Application

Once all services are running:

- 🌐 **Web App**: http://localhost:8080
- 📥 **Hub Inbox**: http://localhost:8080/hub/inbox
- 🤖 **API Docs**: http://localhost:8000/docs
- ❤️ **Health Check**: http://localhost:8000/health

---

## Troubleshooting

### "Command not found" errors
→ Tools not in PATH. Complete Step 2 and restart terminal.

### "Access denied" when connecting to PostgreSQL
→ Check your PostgreSQL password or use `psql -U postgres -W`

### Port 8080 already in use
→ Use different port: `php -S localhost:8081 -t public`

### Composer install fails
→ Check PHP extensions are enabled in php.ini:
   - extension=pdo_pgsql
   - extension=pgsql
   - extension=openssl
   - extension=mbstring

---

## Quick Commands Reference

```powershell
# Check installation
powershell -ExecutionPolicy Bypass -File check-installation.ps1

# Start all services
powershell -ExecutionPolicy Bypass -File start-qbilhub.ps1

# Test Python API
bash test-api.sh

# Database commands
php bin/console doctrine:database:create
php bin/console doctrine:migrations:migrate

# Build frontend
npm run build
```

---

## Need Help?

📖 **Documentation:**
- [SETUP_GUIDE.md](SETUP_GUIDE.md) - Complete setup instructions
- [README.md](README.md) - Project overview
- [IMPLEMENTATION_NOTES.md](IMPLEMENTATION_NOTES.md) - Technical details
- [TEST_RESULTS.md](TEST_RESULTS.md) - API test results

🎯 **Current Working:**
- Python Intelligence Service ✅
- API endpoints ✅
- Schema extraction ✅
- Entity resolution ✅
- Active learning ✅

🔜 **After Setup:**
- Full web interface
- Database persistence
- Real-time notifications
- Split-view mapping UI
- Hub inbox functionality
