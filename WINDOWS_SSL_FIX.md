# Windows SSL Certificate Fix for Composer

## The Problem

Windows PHP installations don't include SSL certificates by default, causing Composer to fail with "SSL certificate problem: unable to get local issuer certificate".

## Permanent Solution

### Option 1: Download and Configure Certificate (Recommended)

**Step 1: Download the certificate bundle**

Download from: https://curl.se/ca/cacert.pem

Or use PowerShell:
```powershell
Invoke-WebRequest -Uri "https://curl.se/ca/cacert.pem" -OutFile "C:\cacert.pem"
```

**Step 2: Find your php.ini file**

```powershell
php --ini
```

Look for "Loaded Configuration File:" (usually `C:\Program Files\php\php.ini`)

**Step 3: Edit php.ini**

Add these lines (replace path if you saved certificate elsewhere):

```ini
curl.cainfo = "C:\cacert.pem"
openssl.cafile = "C:\cacert.pem"
```

**Step 4: Restart and test**

Close all PowerShell/CMD windows and open a new one:

```powershell
composer diagnose
```

Should show "OK" for SSL/TLS checks.

---

### Option 2: Use Composer's Built-in Certificate

Tell Composer to use a specific certificate file:

```powershell
# Set for this project only
composer config cafile "C:\cacert.pem"

# Or set globally
composer config -g cafile "C:\cacert.pem"
```

---

### Option 3: Temporary Workaround (Less Secure)

**Only use this temporarily for development:**

```powershell
# Disable SSL for Composer only
composer config -g disable-tls true
composer config -g secure-http false

# Install dependencies
composer install

# Re-enable SSL after install
composer config -g disable-tls false
composer config -g secure-http true
```

---

## For QbilHub Installation

After fixing SSL with any method above:

```powershell
cd qbilhub

# Install PHP dependencies
composer install

# Setup database
php bin/console doctrine:database:create
php bin/console doctrine:migrations:migrate

# Install frontend
npm install
npm run build

# Start application
.\start-qbilhub.ps1
```

---

## Troubleshooting

### "Still getting SSL error after php.ini edit"

1. Make sure you edited the correct php.ini (check with `php --ini`)
2. Close ALL terminals and open a new one
3. Verify the certificate file exists at the path you specified
4. Try restarting your computer

### "Permission denied when editing php.ini"

Run Notepad as Administrator:
1. Search for "Notepad" in Start menu
2. Right-click → "Run as administrator"
3. Open php.ini and edit

### "Can't find php.ini"

If php.ini doesn't exist, create it:

```powershell
# Find PHP directory
where php

# Copy php.ini-production to php.ini
copy "C:\Program Files\php\php.ini-production" "C:\Program Files\php\php.ini"
```

Then add the certificate configuration lines.

---

## Verify It Works

```powershell
# Test Composer
composer diagnose

# Test SSL
php -r "file_get_contents('https://packagist.org');"
```

Both should work without errors.

---

## Alternative: Use Docker

Skip all Windows SSL issues by using Docker:

```yaml
# docker-compose.yml already included in the project
docker-compose up
```

This runs everything in Linux containers where SSL works out of the box.
