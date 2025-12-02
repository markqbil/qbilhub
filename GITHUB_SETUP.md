# Publishing QbilHub to GitHub

## Step 1: Initialize Git (if not already done)

```powershell
cd C:\Users\MarkEllis\Documents\QbilHub

# Initialize git repository
git init

# Check status
git status
```

## Step 2: Create GitHub Repository

1. Go to **https://github.com/new**
2. Repository name: `qbilhub` (or your preferred name)
3. Description: "B2B Document Exchange and Data Integration Platform"
4. Choose **Private** or **Public**
5. **DO NOT** initialize with README, .gitignore, or license
6. Click **"Create repository"**

## Step 3: Add All Files to Git

```powershell
# Add all files (respects .gitignore)
git add .

# Create initial commit
git commit -m "Initial QbilHub implementation with Symfony and Python microservice"
```

## Step 4: Push to GitHub

Replace `YOUR_USERNAME` with your GitHub username:

```powershell
# Connect to your GitHub repository
git remote add origin https://github.com/YOUR_USERNAME/qbilhub.git

# Push to GitHub
git branch -M main
git push -u origin main
```

If prompted for credentials:
- Username: Your GitHub username
- Password: Use a **Personal Access Token** (not your password)
  - Get token: https://github.com/settings/tokens
  - Select: `repo` permissions
  - Copy the token and use it as password

## Step 5: Fresh Installation from GitHub

After pushing to GitHub, you or anyone can install it:

```powershell
# Clone the repository
git clone https://github.com/YOUR_USERNAME/qbilhub.git
cd qbilhub

# Install dependencies
composer install

# Setup environment
copy .env .env.local

# Create database
psql -U postgres -c "CREATE DATABASE qbilhub;"
psql -U postgres -c "CREATE USER qbilhub WITH PASSWORD 'qbilhub';"
psql -U postgres -c "GRANT ALL PRIVILEGES ON DATABASE qbilhub TO qbilhub;"

# Run migrations
php bin/console doctrine:migrations:migrate

# Install frontend
npm install
npm run build

# Setup Python service
cd python-service
python -m venv venv
.\venv\Scripts\activate
pip install -r requirements-simple.txt

# Start services
cd ..
.\start-qbilhub.ps1
```

## Benefits of GitHub Approach

1. ✅ **Version Control** - Track all changes
2. ✅ **Backup** - Code is safe in the cloud
3. ✅ **Collaboration** - Easy to share and work together
4. ✅ **Clean Installation** - Fresh clone avoids local issues
5. ✅ **composer.lock** - I can add a working lock file to the repo

## Files Excluded from Git

The `.gitignore` file excludes:
- `/vendor/` - Composer dependencies (installed via `composer install`)
- `/node_modules/` - npm dependencies (installed via `npm install`)
- `/python-service/venv/` - Python virtual environment
- `cacert.pem` - Local SSL certificate
- `.env.local` - Local environment config
- `/var/` - Symfony cache and logs

These are regenerated during installation.

## What Gets Committed

✅ Source code (`/src/`, `/python-service/app/`)
✅ Configuration (`composer.json`, `package.json`, `.env`)
✅ Templates (`/templates/`)
✅ Assets (`/assets/`)
✅ Documentation (`.md` files)
✅ Database migrations

## After Publishing

Share your repository URL and I can:
1. Create a proper `composer.lock` file
2. Add GitHub Actions for CI/CD
3. Create Docker configuration
4. Add automated tests

## Quick Commands Reference

```powershell
# First time setup
git init
git add .
git commit -m "Initial commit"
git remote add origin https://github.com/YOUR_USERNAME/qbilhub.git
git push -u origin main

# Making changes later
git add .
git commit -m "Description of changes"
git push

# Pulling updates
git pull origin main
```

---

## Ready to Push?

Once you run the commands above, your code will be on GitHub and we can:
- Fix the Composer SSL issue more easily
- Add a working `composer.lock`
- Make installation smoother for anyone cloning the repo
