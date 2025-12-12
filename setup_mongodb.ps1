# MongoDB Extension Setup Script
# This script configures php.ini once you've manually copied php_mongodb.dll

param(
    [switch]$SkipDownload
)

$phpExe = "C:\xampp\php\php.exe"
$phpExtDir = "C:\xampp\php\ext"
$phpIni = "C:\xampp\php\php.ini"

Write-Host "=== MongoDB PHP Extension Setup ===" -ForegroundColor Cyan
Write-Host ""

# Check if already installed
$result = & $phpExe -m 2>&1 | Select-String "mongodb"
if ($result) {
    Write-Host "[OK] MongoDB extension is already installed and loaded!" -ForegroundColor Green
    Write-Host "Version: $(& $phpExe -r 'echo phpversion(""mongodb"");')" -ForegroundColor Green
    exit 0
}

# Check if DLL exists
if (Test-Path "$phpExtDir\php_mongodb.dll") {
    Write-Host "[OK] Found php_mongodb.dll" -ForegroundColor Green
} else {
    Write-Host "[X] php_mongodb.dll NOT found in: $phpExtDir" -ForegroundColor Red
    Write-Host ""
    Write-Host "Please download and install the DLL first:" -ForegroundColor Yellow
    Write-Host "1. Visit: https://pecl.php.net/package/mongodb" -ForegroundColor Cyan
    Write-Host "2. Or: https://windows.php.net/downloads/pecl/releases/mongodb/" -ForegroundColor Cyan
    Write-Host "3. Download: php_mongodb-1.19.0-8.2-ts-vs16-x64.zip" -ForegroundColor Cyan
    Write-Host "4. Extract php_mongodb.dll to: $phpExtDir" -ForegroundColor Cyan
    Write-Host ""
    
    if (-not $SkipDownload) {
        $open = Read-Host "Open download page? (y/n)"
        if ($open -eq "y") {
            Start-Process "https://windows.php.net/downloads/pecl/releases/mongodb/"
        }
    }
    
    Write-Host ""
    Write-Host "After copying the DLL, run this script again." -ForegroundColor Yellow
    exit 1
}

# Configure php.ini
Write-Host ""
Write-Host "Configuring php.ini..." -ForegroundColor Green

if (-not (Test-Path $phpIni)) {
    Write-Host "[X] php.ini not found!" -ForegroundColor Red
    exit 1
}

$iniContent = Get-Content $phpIni
$needsUpdate = $true

foreach ($line in $iniContent) {
    if ($line -match "^extension\s*=\s*mongodb" -and -not $line.StartsWith(";")) {
        Write-Host "[OK] Extension already enabled in php.ini" -ForegroundColor Green
        $needsUpdate = $false
        break
    }
}

if ($needsUpdate) {
    # Find extension section
    $newLines = @()
    $added = $false
    $inExtSection = $false
    
    foreach ($line in $iniContent) {
        $newLines += $line
        
        if ($line -match "^;?\s*extension\s*=") {
            $inExtSection = $true
        }
        
        if ($inExtSection -and $line -match "^;?\s*extension\s*=" -and -not $added) {
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
    $backupFile = "$phpIni.backup.$(Get-Date -Format 'yyyyMMdd_HHmmss')"
    Copy-Item $phpIni $backupFile -ErrorAction SilentlyContinue
    Write-Host "Backup created: $backupFile" -ForegroundColor Gray
    
    # Write
    try {
        $newLines | Set-Content $phpIni -Encoding UTF8
        Write-Host "[OK] Added extension=mongodb to php.ini" -ForegroundColor Green
    } catch {
        Write-Host "[X] Failed to write php.ini: $($_.Exception.Message)" -ForegroundColor Red
        exit 1
    }
}

Write-Host ""
Write-Host "========================================" -ForegroundColor Green
Write-Host "[OK] Configuration complete!" -ForegroundColor Green
Write-Host "========================================" -ForegroundColor Green
Write-Host ""
Write-Host "NEXT STEP: Restart Apache in XAMPP Control Panel" -ForegroundColor Yellow
Write-Host ""
Write-Host "After restarting, verify with:" -ForegroundColor Cyan
Write-Host "  C:\xampp\php\php.exe check_mongodb.php" -ForegroundColor White
Write-Host ""

