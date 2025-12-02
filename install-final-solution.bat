@echo off
echo ========================================
echo    QbilHub - Final Installation Method
echo ========================================
echo.

REM Set environment variable to disable SSL for this session only
set COMPOSER_DISABLE_NETWORK=0
set COMPOSER_ALLOW_SUPERUSER=1

echo Step 1: Configuring Composer for this session...
composer config --global disable-tls true
composer config --global secure-http false
echo Done.
echo.

echo Step 2: Installing Composer dependencies...
echo (This may take 5-10 minutes)
echo.

composer install --no-interaction --prefer-dist --ignore-platform-reqs

if %ERRORLEVEL% EQU 0 (
    echo.
    echo ========================================
    echo    SUCCESS! Dependencies Installed
    echo ========================================
    echo.

    echo Re-enabling SSL...
    composer config --global disable-tls false
    composer config --global secure-http true

    echo.
    echo Next steps:
    echo 1. php bin/console doctrine:database:create
    echo 2. php bin/console doctrine:migrations:migrate
    echo 3. npm install
    echo 4. npm run build
    echo 5. Start app: start-qbilhub.ps1
    echo.
) else (
    echo.
    echo Installation failed. See errors above.
    echo.
)

pause
