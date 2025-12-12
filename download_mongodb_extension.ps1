# MongoDB PHP Extension Downloader and Installer
# Based on official MongoDB documentation: https://www.mongodb.com/docs/languages/php/#installation

Write-Host "========================================" -ForegroundColor Cyan
Write-Host "MongoDB PHP Extension Installer" -ForegroundColor Cyan
Write-Host "Based on official MongoDB docs" -ForegroundColor Cyan
Write-Host "========================================" -ForegroundColor Cyan
Write-Host ""

$phpExe = "C:\xampp\php\php.exe"
$phpExtDir = "C:\xampp\php\ext"
$phpIni = "C:\xampp\php\php.ini"

# Check if already installed
Write-Host "Step 1: Checking installation..." -ForegroundColor Green
$result = & $phpExe -m 2>&1 | Select-String "mongodb"
if ($result) {
    Write-Host "[OK] MongoDB extension is already installed!" -ForegroundColor Green
    Write-Host "Version: $(& $phpExe -r 'echo phpversion(""mongodb"");')" -ForegroundColor Green
    exit 0
}

Write-Host "[X] MongoDB extension is NOT installed" -ForegroundColor Red
Write-Host ""

# Check if DLL exists
if (Test-Path "$phpExtDir\php_mongodb.dll") {
    Write-Host "[OK] Found php_mongodb.dll" -ForegroundColor Green
    Write-Host "But extension is not loaded. Checking php.ini..." -ForegroundColor Yellow
} else {
    Write-Host "Step 2: Downloading MongoDB extension..." -ForegroundColor Green
    Write-Host ""
    Write-Host "According to MongoDB documentation:" -ForegroundColor Cyan
    Write-Host "https://www.mongodb.com/docs/languages/php/#installation" -ForegroundColor Cyan
    Write-Host ""
    Write-Host "You need to download the PHP extension DLL manually." -ForegroundColor Yellow
    Write-Host ""
    Write-Host "Download from:" -ForegroundColor White
    Write-Host "1. https://windows.php.net/downloads/pecl/releases/mongodb/" -ForegroundColor Cyan
    Write-Host "2. https://pecl.php.net/package/mongodb" -ForegroundColor Cyan
    Write-Host ""
    Write-Host "For PHP 8.2.12 ZTS x64, download:" -ForegroundColor Yellow
    Write-Host "   php_mongodb-1.19.0-8.2-ts-vs16-x64.zip" -ForegroundColor White
    Write-Host "   (or latest version available)" -ForegroundColor Gray
    Write-Host ""
    
    # Try to open download page
    $open = Read-Host "Open download page in browser? (y/n)"
    if ($open -eq "y") {
        Start-Process "https://windows.php.net/downloads/pecl/releases/mongodb/"
        Start-Sleep -Seconds 2
    }
    
    Write-Host ""
    Write-Host "After downloading:" -ForegroundColor Yellow
    Write-Host "1. Extract php_mongodb.dll from the zip file" -ForegroundColor White
    Write-Host "2. Copy it to: $phpExtDir" -ForegroundColor White
    Write-Host ""
    
    $continue = Read-Host "Press Enter after copying the DLL, or 'q' to quit"
    if ($continue -eq "q") { exit }
    
    if (-not (Test-Path "$phpExtDir\php_mongodb.dll")) {
        Write-Host "[X] php_mongodb.dll not found in $phpExtDir" -ForegroundColor Red
        Write-Host "Please copy the DLL and run this script again." -ForegroundColor Yellow
        exit 1
    }
    
    Write-Host "[OK] php_mongodb.dll found!" -ForegroundColor Green
}

Write-Host ""
Write-Host "Step 3: Configuring php.ini..." -ForegroundColor Green

if (-not (Test-Path $phpIni)) {
    Write-Host "[X] php.ini not found at: $phpIni" -ForegroundColor Red
    exit 1
}

# Check if already enabled
$iniContent = Get-Content $phpIni
$alreadyEnabled = $false

foreach ($line in $iniContent) {
    if ($line -match "^extension\s*=\s*mongodb" -and -not $line.StartsWith(";")) {
        $alreadyEnabled = $true
        Write-Host "[OK] Extension already enabled in php.ini" -ForegroundColor Green
        break
    }
}

if (-not $alreadyEnabled) {
    Write-Host "Adding extension=mongodb to php.ini..." -ForegroundColor Yellow
    
    $newLines = @()
    $added = $false
    $inExtSection = $false
    
    foreach ($line in $iniContent) {
        $newLines += $line
        
        # Look for extension section
        if ($line -match "^;?\s*extension\s*=") {
            $inExtSection = $true
        }
        
        # Add after first extension line
        if ($inExtSection -and $line -match "^;?\s*extension\s*=" -and -not $added) {
            $newLines += "extension=mongodb"
            $added = $true
        }
    }
    
    # If not added, add at end
    if (-not $added) {
        $newLines += ""
        $newLines += "; MongoDB Extension"
        $newLines += "extension=mongodb"
    }
    
    # Backup
    $backupFile = "$phpIni.backup.$(Get-Date -Format 'yyyyMMdd_HHmmss')"
    Copy-Item $phpIni $backupFile -ErrorAction SilentlyContinue
    Write-Host "Backup created: $backupFile" -ForegroundColor Gray
    
    # Write
    try {
        $newLines | Set-Content $phpIni -Encoding UTF8
        Write-Host "[OK] Added extension=mongodb to php.ini" -ForegroundColor Green
    } catch {
        Write-Host "[X] Failed to write php.ini: $($_.Exception.Message)" -ForegroundColor Red
        Write-Host "Please add 'extension=mongodb' manually to: $phpIni" -ForegroundColor Yellow
        exit 1
    }
}

Write-Host ""
Write-Host "Step 4: Installing Composer dependencies..." -ForegroundColor Green

# Check if composer.json exists
if (Test-Path "composer.json") {
    Write-Host "Found composer.json" -ForegroundColor Gray
    
    # Try to run composer install
    $composerCmd = "composer"
    if (Get-Command $composerCmd -ErrorAction SilentlyContinue) {
        Write-Host "Running: composer install" -ForegroundColor Yellow
        & $composerCmd install
    } else {
        Write-Host "Composer not found in PATH." -ForegroundColor Yellow
        Write-Host "Please run manually: composer install" -ForegroundColor Cyan
    }
} else {
    Write-Host "composer.json not found in current directory" -ForegroundColor Yellow
}

Write-Host ""
Write-Host "========================================" -ForegroundColor Green
Write-Host "[OK] Configuration complete!" -ForegroundColor Green
Write-Host "========================================" -ForegroundColor Green
Write-Host ""
Write-Host "CRITICAL: Restart Apache in XAMPP Control Panel" -ForegroundColor Yellow
Write-Host "The extension will not load until Apache is restarted." -ForegroundColor Yellow
Write-Host ""
Write-Host "After restarting, verify with:" -ForegroundColor Cyan
Write-Host "  C:\xampp\php\php.exe check_mongodb.php" -ForegroundColor White
Write-Host ""
Write-Host "Then you can connect to MongoDB Atlas:" -ForegroundColor Cyan
Write-Host "  mongodb+srv://username:password@cluster.mongodb.net/" -ForegroundColor White
Write-Host ""

