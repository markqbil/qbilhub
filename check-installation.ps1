# QbilHub Installation Checker

Write-Host "`n========================================" -ForegroundColor Cyan
Write-Host "   QbilHub Installation Checker" -ForegroundColor Cyan
Write-Host "========================================`n" -ForegroundColor Cyan

function Test-Command {
    param($CommandName, $DisplayName)

    $cmd = Get-Command $CommandName -ErrorAction SilentlyContinue
    if ($cmd) {
        Write-Host "✓ $DisplayName found" -ForegroundColor Green

        # Try to get version
        try {
            if ($CommandName -eq "php") {
                $version = & $CommandName --version 2>&1 | Select-Object -First 1
                Write-Host "  Version: $version" -ForegroundColor Gray
            } elseif ($CommandName -eq "composer") {
                $version = & $CommandName --version 2>&1 | Select-Object -First 1
                Write-Host "  Version: $version" -ForegroundColor Gray
            } elseif ($CommandName -eq "psql") {
                $version = & $CommandName --version 2>&1 | Select-Object -First 1
                Write-Host "  Version: $version" -ForegroundColor Gray
            } elseif ($CommandName -eq "python") {
                $version = & $CommandName --version 2>&1
                Write-Host "  Version: $version" -ForegroundColor Gray
            }
        } catch {
            Write-Host "  (version check failed)" -ForegroundColor Yellow
        }

        Write-Host "  Path: $($cmd.Source)" -ForegroundColor Gray
        return $true
    } else {
        Write-Host "✗ $DisplayName NOT found" -ForegroundColor Red
        Write-Host "  Please install $DisplayName and add to PATH" -ForegroundColor Yellow
        return $false
    }
}

Write-Host "Checking required tools...`n" -ForegroundColor Yellow

$phpOk = Test-Command "php" "PHP"
Write-Host ""
$composerOk = Test-Command "composer" "Composer"
Write-Host ""
$psqlOk = Test-Command "psql" "PostgreSQL (psql)"
Write-Host ""
$pythonOk = Test-Command "python" "Python"
Write-Host ""
$npmOk = Test-Command "npm" "npm"
Write-Host ""

# Check for common installation paths
Write-Host "`n========================================" -ForegroundColor Cyan
Write-Host "   Common Installation Locations" -ForegroundColor Cyan
Write-Host "========================================`n" -ForegroundColor Cyan

$commonPaths = @{
    "PHP" = @("C:\php", "C:\tools\php82", "C:\Program Files\php")
    "Composer" = @("C:\ProgramData\ComposerSetup\bin", "$env:APPDATA\Composer\vendor\bin")
    "PostgreSQL" = @("C:\Program Files\PostgreSQL\16\bin", "C:\Program Files\PostgreSQL\15\bin", "C:\PostgreSQL\bin")
}

foreach ($tool in $commonPaths.Keys) {
    Write-Host "$tool common paths:" -ForegroundColor Yellow
    foreach ($path in $commonPaths[$tool]) {
        if (Test-Path $path) {
            Write-Host "  ✓ Found: $path" -ForegroundColor Green
        } else {
            Write-Host "  ✗ Not found: $path" -ForegroundColor Gray
        }
    }
    Write-Host ""
}

# Summary
Write-Host "`n========================================" -ForegroundColor Cyan
Write-Host "   Summary" -ForegroundColor Cyan
Write-Host "========================================`n" -ForegroundColor Cyan

$allGood = $phpOk -and $composerOk -and $psqlOk -and $pythonOk -and $npmOk

if ($allGood) {
    Write-Host "✓ All required tools are installed and in PATH!" -ForegroundColor Green
    Write-Host "`nYou can now run:" -ForegroundColor Yellow
    Write-Host "  powershell -ExecutionPolicy Bypass -File start-qbilhub.ps1" -ForegroundColor White
} else {
    Write-Host "✗ Some required tools are missing or not in PATH" -ForegroundColor Red
    Write-Host "`nTo fix this:" -ForegroundColor Yellow
    Write-Host "1. Install missing tools" -ForegroundColor White
    Write-Host "2. Add them to your system PATH" -ForegroundColor White
    Write-Host "3. Restart your terminal" -ForegroundColor White
    Write-Host "4. Run this script again to verify" -ForegroundColor White
    Write-Host "`nSee SETUP_GUIDE.md for detailed instructions" -ForegroundColor Cyan
}

# Check project files
Write-Host "`n========================================" -ForegroundColor Cyan
Write-Host "   Project Files" -ForegroundColor Cyan
Write-Host "========================================`n" -ForegroundColor Cyan

$projectFiles = @{
    "composer.json" = "Composer configuration"
    "package.json" = "npm configuration"
    "src/Kernel.php" = "Symfony Kernel"
    "python-service/app/main.py" = "Python service"
    "python-service/venv" = "Python virtual environment"
}

foreach ($file in $projectFiles.Keys) {
    if (Test-Path $file) {
        Write-Host "✓ $($projectFiles[$file]): $file" -ForegroundColor Green
    } else {
        Write-Host "✗ $($projectFiles[$file]): $file" -ForegroundColor Red
    }
}

Write-Host ""
