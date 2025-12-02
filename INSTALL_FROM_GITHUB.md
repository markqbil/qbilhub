# Installing QbilHub from GitHub

## Prerequisites

- ✅ PHP 8.2+ (with Composer)
- ✅ PostgreSQL 15+
- ✅ Node.js 18+
- ✅ Python 3.11+
- ✅ Git

---

## Quick Install (3 Steps)

### 1. Clone Repository

```powershell
git clone https://github.com/markqbil/qbilhub.git
cd qbilhub
```

### 2. Fix SSL Certificate (Windows Only)

**Download certificate:**
```powershell
Invoke-WebRequest -Uri "https://curl.se/ca/cacert.pem" -OutFile "cacert.pem"
```

**Configure Composer to use it:**
```powershell
composer config cafile "cacert.pem"
```

Or see [WINDOWS_SSL_FIX.md](WINDOWS_SSL_FIX.md) for permanent solution.

### 3. Install & Run

```powershell
# Install PHP dependencies
composer install

# Create environment config
copy .env .env.local

# Create database
psql -U postgres -c "CREATE DATABASE qbilhub;"
psql -U postgres -c "CREATE USER qbilhub WITH PASSWORD 'qbilhub';"
psql -U postgres -c "GRANT ALL PRIVILEGES ON DATABASE qbilhub TO qbilhub;"

# Run migrations
php bin/console doctrine:database:create --if-not-exists
php bin/console doctrine:migrations:migrate

# Install frontend
npm install
npm run build

# Setup Python service
cd python-service
python -m venv venv
.\venv\Scripts\activate
pip install -r requirements-simple.txt
cd ..

# Start application
.\start-qbilhub.ps1
```

**Then visit:** http://localhost:8080

---

## Detailed Installation Steps

### Step 1: System Prerequisites

#### Windows

```powershell
# Check installations
php --version       # Should be 8.2+
composer --version  # Should be 2.0+
psql --version      # PostgreSQL
node --version      # Node.js 18+
python --version    # Python 3.11+
```

If any are missing, install:
- **PHP**: https://windows.php.net/download/
- **Composer**: https://getcomposer.org/download/
- **PostgreSQL**: https://www.postgresql.org/download/windows/
- **Node.js**: https://nodejs.org/
- **Python**: https://www.python.org/downloads/

#### Linux/Mac

```bash
# Ubuntu/Debian
sudo apt update
sudo apt install php8.2 php8.2-pgsql composer postgresql nodejs npm python3

# macOS (with Homebrew)
brew install php composer postgresql node python3
```

---

### Step 2: Clone Repository

```powershell
git clone https://github.com/markqbil/qbilhub.git
cd qbilhub
```

---

### Step 3: Fix SSL (Windows Only)

**Option A: Quick Fix (Project Level)**

```powershell
# Download certificate
Invoke-WebRequest -Uri "https://curl.se/ca/cacert.pem" -OutFile "cacert.pem"

# Configure Composer
composer config cafile "cacert.pem"
```

**Option B: Permanent Fix (System Level)**

See [WINDOWS_SSL_FIX.md](WINDOWS_SSL_FIX.md) for complete instructions.

**Option C: Automated Script**

```powershell
.\generate-composer-lock.ps1
```

---

### Step 4: Install Dependencies

#### PHP (Symfony)

```powershell
composer install
```

If SSL errors persist:
```powershell
composer config disable-tls true
composer config secure-http false
composer install
composer config disable-tls false
composer config secure-http true
```

#### Frontend

```powershell
npm install
npm run build
```

---

### Step 5: Configure Environment

```powershell
# Copy environment template
copy .env .env.local

# Edit .env.local if needed (database credentials, etc.)
```

Default database settings:
- Host: `127.0.0.1`
- Port: `5432`
- Database: `qbilhub`
- User: `qbilhub`
- Password: `qbilhub`

---

### Step 6: Setup Database

**Create database:**

```powershell
psql -U postgres
```

In psql:
```sql
CREATE DATABASE qbilhub;
CREATE USER qbilhub WITH PASSWORD 'qbilhub';
GRANT ALL PRIVILEGES ON DATABASE qbilhub TO qbilhub;
\q
```

**Run migrations:**

```powershell
php bin/console doctrine:database:create --if-not-exists
php bin/console doctrine:migrations:migrate --no-interaction
```

---

### Step 7: Setup Python Service

```powershell
cd python-service

# Create virtual environment
python -m venv venv

# Activate (Windows)
.\venv\Scripts\activate

# Activate (Linux/Mac)
source venv/bin/activate

# Install dependencies
pip install -r requirements-simple.txt

# Return to project root
cd ..
```

---

### Step 8: Start Services

**Option A: Automated (Recommended)**

```powershell
.\start-qbilhub.ps1
```

This opens 3 windows:
1. Python service (port 8000)
2. Symfony server (port 8080)
3. Message queue worker

**Option B: Manual**

**Terminal 1 - Python Service:**
```powershell
cd python-service
.\venv\Scripts\python.exe -m uvicorn app.main:app --host 0.0.0.0 --port 8000
```

**Terminal 2 - Symfony Server:**
```powershell
php -S localhost:8080 -t public
```

**Terminal 3 - Message Queue (Optional):**
```powershell
php bin/console messenger:consume async -vv
```

---

### Step 9: Access Application

- **Web Application**: http://localhost:8080
- **Hub Inbox**: http://localhost:8080/hub/inbox
- **Python API Docs**: http://localhost:8000/docs
- **Health Check**: http://localhost:8000/health

---

## Testing the Installation

### Test Python Service

```powershell
curl http://localhost:8000/health
```

Should return:
```json
{"status":"healthy","components":{"api":"operational",...}}
```

### Test Schema Extraction

```powershell
curl -X POST http://localhost:8000/api/extract-schema `
  -H "Content-Type: application/json" `
  -d '{"rawData":{"contract_no":"C-001","supplier_name":"Acme Corp"}}'
```

### Run Full API Tests

```bash
bash test-api.sh
```

---

## Troubleshooting

### "SSL certificate problem" during composer install

See [WINDOWS_SSL_FIX.md](WINDOWS_SSL_FIX.md)

Quick fix:
```powershell
composer config disable-tls true
composer install
composer config disable-tls false
```

### "Connection refused" to PostgreSQL

Start PostgreSQL service:
```powershell
# Windows
net start postgresql-x64-18

# Linux
sudo systemctl start postgresql

# Mac
brew services start postgresql
```

### "Port 8080 already in use"

Use different port:
```powershell
php -S localhost:8081 -t public
```

### Python service won't start

Check Python version:
```powershell
python --version  # Should be 3.11+
```

Reinstall dependencies:
```powershell
cd python-service
pip install --upgrade pip
pip install -r requirements-simple.txt
```

---

## What's Working Right Now

✅ **Python Intelligence Service** (fully functional)
- Schema extraction API
- Entity resolution with confidence scores
- Active learning feedback
- Product code mapping
- Interactive API docs at http://localhost:8000/docs

⏳ **Symfony Application** (needs Composer dependencies)
- Will work after `composer install` succeeds
- Full web interface
- Database persistence
- Real-time notifications

---

## Alternative: Docker Installation

Skip all SSL issues:

```powershell
# Install Docker Desktop for Windows
# Then run:
docker-compose up
```

Everything runs in Linux containers where SSL works out of the box.

---

## Need Help?

- **SSL Issues**: [WINDOWS_SSL_FIX.md](WINDOWS_SSL_FIX.md)
- **Setup Guide**: [SETUP_GUIDE.md](SETUP_GUIDE.md)
- **Project Overview**: [README.md](README.md)
- **Implementation Details**: [IMPLEMENTATION_NOTES.md](IMPLEMENTATION_NOTES.md)

---

## Contributing

1. Fork the repository
2. Create a feature branch
3. Make your changes
4. Submit a pull request

---

## License

Proprietary - All rights reserved
