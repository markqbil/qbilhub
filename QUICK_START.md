# QbilHub Quick Start Guide

## Prerequisites Installation

### 1. Install PHP (Required for Symfony)

**Option A: Using Chocolatey (Recommended for Windows)**
```powershell
# Install Chocolatey if not installed
# Then run:
choco install php composer
```

**Option B: Manual Installation**
- Download PHP 8.2+ from: https://windows.php.net/download/
- Download Composer from: https://getcomposer.org/download/

### 2. Install PostgreSQL
Download from: https://www.postgresql.org/download/windows/

### 3. Already Installed ✓
- Python 3.12 ✓
- Node.js ✓
- npm ✓

---

## Step-by-Step Startup (After PHP Installation)

### Step 1: Install Symfony Dependencies
```bash
composer install
```

### Step 2: Configure Environment
```bash
# Copy environment file
cp .env .env.local

# Edit .env.local with your settings:
# - DATABASE_URL (PostgreSQL connection)
# - MESSENGER_TRANSPORT_DSN
# - MERCURE settings
```

### Step 3: Setup Database
```bash
php bin/console doctrine:database:create
php bin/console doctrine:migrations:migrate
```

### Step 4: Install Frontend Dependencies
```bash
npm install
npm run dev
```

### Step 5: Setup Python Service
```bash
cd python-service
python -m venv venv
venv\Scripts\activate
pip install -r requirements.txt
cp .env.example .env
# Edit .env with OPENAI_API_KEY
```

### Step 6: Start All Services

**Terminal 1: Symfony Web Server**
```bash
php -S localhost:8080 -t public
```

**Terminal 2: Python Intelligence Service**
```bash
cd python-service
python -m uvicorn app.main:app --reload --port 8000
```

**Terminal 3: Message Queue Consumer**
```bash
php bin/console messenger:consume async -vv
```

### Step 7: Access the Application
Open browser: http://localhost:8080

---

## Quick Test (Python Service Only)

Since PHP is not installed, we can test the Python intelligence service:

```bash
cd python-service
python -m venv venv
venv\Scripts\activate
pip install -r requirements.txt
python -m uvicorn app.main:app --reload --port 8000
```

Then visit: http://localhost:8000/docs
