# QbilHub - Getting Help

## You've completed Step 3 (PATH configuration) ✅

Now let's complete the remaining steps!

---

## Quick Solution (Recommended)

Open **PowerShell** (not bash) in this directory and run:

```powershell
powershell -ExecutionPolicy Bypass -File setup-database.ps1
```

This script will automatically:
- ✓ Check that PHP, Composer are available
- ✓ Install all Composer dependencies (`composer install`)
- ✓ Create database configuration (`.env.local`)
- ✓ Set up PostgreSQL database
- ✓ Run database migrations
- ✓ Install npm dependencies
- ✓ Build frontend assets

**If you get prompted for PostgreSQL password**, use the password you set during PostgreSQL installation (default is often "postgres").

---

## After setup-database.ps1 completes

Start all services with:

```powershell
powershell -ExecutionPolicy Bypass -File start-qbilhub.ps1
```

This will open 3 windows:
1. Python Intelligence Service (port 8000)
2. Symfony Web Server (port 8080)
3. Message Queue Worker

Then visit: **http://localhost:8080**

---

## Manual Step-by-Step (If you prefer)

### Step 4: Configure Database

Open **PowerShell** (or pgAdmin) and run:

```powershell
psql -U postgres

# In the psql prompt:
CREATE DATABASE qbilhub;
CREATE USER qbilhub WITH PASSWORD 'qbilhub';
GRANT ALL PRIVILEGES ON DATABASE qbilhub TO qbilhub;
\q
```

### Step 5: Install Dependencies

```powershell
# Install PHP dependencies
composer install

# Create environment config
copy .env .env.local

# Create database schema
php bin/console doctrine:database:create --if-not-exists
php bin/console doctrine:migrations:migrate

# Install and build frontend
npm install
npm run build
```

### Step 6: Start Services

**Option A: Automatic**
```powershell
powershell -ExecutionPolicy Bypass -File start-qbilhub.ps1
```

**Option B: Manual (3 terminals)**

Terminal 1:
```powershell
php -S localhost:8080 -t public
```

Terminal 2:
```powershell
php bin/console messenger:consume async -vv
```

Terminal 3: Python service (already running ✅)

---

## Common Issues & Solutions

### "php: command not found"
**Problem**: Bash shell doesn't recognize PHP
**Solution**: Use PowerShell instead of bash, or restart your bash terminal

### "Access denied for user"
**Problem**: PostgreSQL password is incorrect
**Solution**: Use the password you set during PostgreSQL installation
```powershell
psql -U postgres -W  # -W will prompt for password
```

### "Port 8080 is already in use"
**Problem**: Another service is using port 8080
**Solution**: Use a different port
```powershell
php -S localhost:8081 -t public
```

### "Class ... not found"
**Problem**: Composer dependencies not installed
**Solution**: Run `composer install` again

### "SQLSTATE[08006] Connection refused"
**Problem**: PostgreSQL is not running
**Solution**: Start PostgreSQL service
```powershell
# Windows Services
net start postgresql-x64-16

# Or find in Services app (Win + R, type: services.msc)
```

### "npm ERR!"
**Problem**: Node/npm issue
**Solution**: Clear cache and reinstall
```powershell
rm -r node_modules
npm cache clean --force
npm install
```

---

## Verify Everything Works

### 1. Check Python Service (Already Running)
Open browser: http://localhost:8000/health

Should show:
```json
{"status":"healthy","components":{"api":"operational",...}}
```

### 2. After Starting Symfony
Open browser: http://localhost:8080

You should see the Symfony welcome page or QbilHub interface.

### 3. Test Full Integration
- Visit: http://localhost:8080/hub/inbox
- Check: http://localhost:8000/docs (API documentation)

---

## Quick Reference

```powershell
# Complete setup (run once)
powershell -ExecutionPolicy Bypass -File setup-database.ps1

# Start all services
powershell -ExecutionPolicy Bypass -File start-qbilhub.ps1

# Check installation
powershell -ExecutionPolicy Bypass -File check-installation.ps1

# Test Python API only
bash test-api.sh
```

---

## What's Working Right Now

✅ Python Intelligence Service (port 8000)
✅ All Python API endpoints
✅ Schema extraction
✅ Entity resolution
✅ Active learning feedback

## What You're Setting Up

⏳ Symfony Web Application (port 8080)
⏳ Database persistence
⏳ Hub Inbox UI
⏳ Split-View Mapping Interface
⏳ Message queue workers

---

## Need More Help?

1. **Run the setup script first**:
   ```powershell
   powershell -ExecutionPolicy Bypass -File setup-database.ps1
   ```

2. **If it fails**, note the error message and:
   - Check the "Common Issues" section above
   - Look at the specific error in the output
   - The script will tell you exactly what failed

3. **Files to check**:
   - [SETUP_GUIDE.md](SETUP_GUIDE.md) - Detailed setup instructions
   - [NEXT_STEPS.md](NEXT_STEPS.md) - What you're currently following
   - [README.md](README.md) - Project overview

---

## TL;DR - Just Run This

In PowerShell:

```powershell
# 1. Complete setup
powershell -ExecutionPolicy Bypass -File setup-database.ps1

# 2. Start everything
powershell -ExecutionPolicy Bypass -File start-qbilhub.ps1

# 3. Visit http://localhost:8080
```

Done! 🎉
