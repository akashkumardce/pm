# MongoDB PHP Extension Simple Installer
Write-Host "MongoDB PHP Extension Installer" -ForegroundColor Cyan
Write-Host ""

$phpExe = "C:\xampp\php\php.exe"
$phpExtDir = "C:\xampp\php\ext"
$phpIni = "C:\xampp\php\php.ini"

# Check if already installed
Write-Host "Checking installation status..." -ForegroundColor Green
$result = & $phpExe -m 2>&1 | Select-String "mongodb"
if ($result) {
    Write-Host "[OK] MongoDB extension is already installed!" -ForegroundColor Green
    exit 0
}

Write-Host "[X] MongoDB extension is NOT installed" -ForegroundColor Red
Write-Host ""

# Check if DLL exists
if (Test-Path "$phpExtDir\php_mongodb.dll") {
    Write-Host "Found php_mongodb.dll in ext directory" -ForegroundColor Yellow
} else {
    Write-Host "Step 1: Downloading MongoDB extension..." -ForegroundColor Green
    Write-Host ""
    Write-Host "Please download manually from one of these sources:" -ForegroundColor Yellow
    Write-Host "1. https://pecl.php.net/package/mongodb" -ForegroundColor Cyan
    Write-Host "2. https://windows.php.net/downloads/pecl/releases/mongodb/" -ForegroundColor Cyan
    Write-Host ""
    Write-Host "For PHP 8.2 ZTS x64, download:" -ForegroundColor Yellow
    Write-Host "php_mongodb-1.19.0-8.2-ts-vs16-x64.zip" -ForegroundColor Cyan
    Write-Host ""
    
    $download = Read-Host "Would you like to open the download page? (y/n)"
    if ($download -eq "y") {
        Start-Process "https://windows.php.net/downloads/pecl/releases/mongodb/"
    }
    
    Write-Host ""
    Write-Host "After downloading:" -ForegroundColor Yellow
    Write-Host "1. Extract php_mongodb.dll from the zip file" -ForegroundColor White
    Write-Host "2. Copy it to: $phpExtDir" -ForegroundColor White
    Write-Host ""
    
    $continue = Read-Host "Press Enter after you've copied the DLL, or 'q' to quit"
    if ($continue -eq "q") { exit }
    
    if (-not (Test-Path "$phpExtDir\php_mongodb.dll")) {
        Write-Host "[X] php_mongodb.dll not found in $phpExtDir" -ForegroundColor Red
        Write-Host "Please copy the DLL and run this script again." -ForegroundColor Yellow
        exit 1
    }
}

Write-Host "[OK] php_mongodb.dll found" -ForegroundColor Green
Write-Host ""

# Configure php.ini
Write-Host "Step 2: Configuring php.ini..." -ForegroundColor Green

if (-not (Test-Path $phpIni)) {
    Write-Host "[X] php.ini not found at: $phpIni" -ForegroundColor Red
    exit 1
}

$iniLines = Get-Content $phpIni
$alreadyEnabled = $false

foreach ($line in $iniLines) {
    if ($line -match "^extension\s*=\s*mongodb" -and -not $line.StartsWith(";")) {
        $alreadyEnabled = $true
        Write-Host "[OK] Extension already enabled in php.ini" -ForegroundColor Green
        break
    }
}

if (-not $alreadyEnabled) {
    $newLines = @()
    $inExtensionSection = $false
    $added = $false
    
    foreach ($line in $iniLines) {
        $newLines += $line
        
        if ($line -match "^;?\s*extension\s*=") {
            $inExtensionSection = $true
        }
        
        if ($inExtensionSection -and $line -match "^;?\s*extension\s*=" -and -not $added) {
            $newLines += "extension=mongodb"
            $added = $true
        }
    }
    
    if (-not $added) {
        $newLines += ""
        $newLines += "; MongoDB Extension"
        $newLines += "extension=mongodb"
    }
    
    # Backup
    Copy-Item $phpIni "$phpIni.backup" -ErrorAction SilentlyContinue
    
    # Write
    try {
        $newLines | Set-Content $phpIni -Encoding UTF8
        Write-Host "[OK] Added extension=mongodb to php.ini" -ForegroundColor Green
    } catch {
        Write-Host "[X] Failed to write php.ini: $($_.Exception.Message)" -ForegroundColor Red
        Write-Host "Please add extension=mongodb manually to: $phpIni" -ForegroundColor Yellow
        exit 1
    }
}

Write-Host ""
Write-Host "========================================" -ForegroundColor Green
Write-Host "[OK] Installation complete!" -ForegroundColor Green
Write-Host "========================================" -ForegroundColor Green
Write-Host ""
Write-Host "IMPORTANT: Restart Apache in XAMPP Control Panel" -ForegroundColor Yellow
Write-Host "for the changes to take effect." -ForegroundColor Yellow
Write-Host ""
Write-Host "After restarting, verify with:" -ForegroundColor Cyan
Write-Host "C:\xampp\php\php.exe check_mongodb.php" -ForegroundColor White
Write-Host ""
