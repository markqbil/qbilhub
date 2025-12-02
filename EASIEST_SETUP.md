# QbilHub - Easiest Setup Method

## You Have Two Options:

---

## Option 1: Quick Fix (Recommended) ⚡

The SSL issue is just PHP missing a certificate file. Here's the 2-minute fix:

### Step 1: Download SSL Certificate Bundle

Run this in PowerShell:
```powershell
# Download the certificate bundle
Invoke-WebRequest -Uri "https://curl.se/ca/cacert.pem" -OutFile "C:\cacert.pem"
```

### Step 2: Configure PHP

Find your `php.ini` file (usually in `C:\Program Files\php\php.ini`) and add this line:
```ini
curl.cainfo = "C:\cacert.pem"
openssl.cafile = "C:\cacert.pem"
```

Or run this PowerShell command to do it automatically:
```powershell
$phpIni = "C:\Program Files\php\php.ini"
Add-Content $phpIni "`ncurl.cainfo = `"C:\cacert.pem`""
Add-Content $phpIni "openssl.cafile = `"C:\cacert.pem`""
```

### Step 3: Run Setup

```powershell
composer install
php bin/console doctrine:database:create
php bin/console doctrine:migrations:migrate
npm install
npm run build
```

### Step 4: Start App

```powershell
.\start-qbilhub.ps1
```

---

## Option 2: GitHub Approach (Also Good) 🌐

If you prefer a clean start:

### Step 1: Create GitHub Repo

1. Go to https://github.com/new
2. Create a new repository (e.g., "qbilhub")
3. Don't initialize with README (we have files already)

### Step 2: Push Current Code

```powershell
cd C:\Users\MarkEllis\Documents\QbilHub

# Initialize git (if not already)
git init

# Add files
git add .
git commit -m "Initial QbilHub implementation"

# Connect to GitHub (replace with your repo URL)
git remote add origin https://github.com/YOUR_USERNAME/qbilhub.git
git branch -M main
git push -u origin main
```

### Step 3: Clone Fresh Copy

```powershell
cd C:\Users\MarkEllis\Documents\
mv QbilHub QbilHub_backup
git clone https://github.com/YOUR_USERNAME/qbilhub.git QbilHub
cd QbilHub
```

### Step 4: Fix SSL (still needed)

Same as Option 1 - download cacert.pem and configure PHP

### Step 5: Install & Run

```powershell
composer install
php bin/console doctrine:database:create
php bin/console doctrine:migrations:migrate
npm install
npm run build
.\start-qbilhub.ps1
```

---

## Option 3: Skip Composer Issue Entirely 🎯

Use PHP's built-in server without the full Symfony setup to test the Python service:

```powershell
# Python service is already running on port 8000
# Just test the APIs:
curl http://localhost:8000/docs
```

The Python Intelligence Service is fully working and testable right now!

---

## My Recommendation:

**Do Option 1** (SSL fix) because:
- ✅ Fastest (2 minutes)
- ✅ Fixes root cause
- ✅ You'll need it for any PHP project anyway
- ✅ Then you can push to GitHub after it works

The SSL certificate issue is a common Windows PHP problem, not specific to QbilHub. Once you fix it, everything will work smoothly.

---

## Want me to create an automated script?

I can create a PowerShell script that:
1. Downloads the certificate
2. Updates php.ini automatically
3. Runs composer install
4. Sets up everything

Just let me know!
