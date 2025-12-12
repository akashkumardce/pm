# MongoDB PHP Extension Auto-Installer
# This script downloads and installs the MongoDB PHP extension for XAMPP

Write-Host "========================================" -ForegroundColor Cyan
Write-Host "MongoDB PHP Extension Auto-Installer" -ForegroundColor Cyan
Write-Host "========================================" -ForegroundColor Cyan
Write-Host ""

# Check if running as administrator
$isAdmin = ([Security.Principal.WindowsPrincipal] [Security.Principal.WindowsIdentity]::GetCurrent()).IsInRole([Security.Principal.WindowsBuiltInRole]::Administrator)
if (-not $isAdmin) {
    Write-Host "WARNING: Not running as administrator. Some operations may fail." -ForegroundColor Yellow
    Write-Host ""
}

# PHP Configuration
$phpVersion = "8.2"
$phpExtDir = "C:\xampp\php\ext"
$phpIni = "C:\xampp\php\php.ini"
$tempDir = $env:TEMP

# Check if extension is already installed
Write-Host "Step 1: Checking if MongoDB extension is already installed..." -ForegroundColor Green
$phpExe = "C:\xampp\php\php.exe"
if (Test-Path $phpExe) {
    $result = & $phpExe -m 2>&1 | Select-String "mongodb"
    if ($result) {
        Write-Host "✓ MongoDB extension is already installed!" -ForegroundColor Green
        Write-Host "Version: $(& $phpExe -r 'echo phpversion(""mongodb"");')" -ForegroundColor Green
        exit 0
    }
}

Write-Host "✗ MongoDB extension is NOT installed" -ForegroundColor Red
Write-Host ""

# Check if DLL exists
if (Test-Path "$phpExtDir\php_mongodb.dll") {
    Write-Host "Found php_mongodb.dll but extension is not loaded." -ForegroundColor Yellow
    Write-Host "Checking php.ini configuration..." -ForegroundColor Yellow
} else {
    Write-Host "Step 2: Downloading MongoDB extension..." -ForegroundColor Green
    
    # Try multiple download sources
    $downloadUrls = @(
        "https://windows.php.net/downloads/pecl/releases/mongodb/1.19.0/php_mongodb-1.19.0-8.2-ts-vs16-x64.zip",
        "https://windows.php.net/downloads/pecl/releases/mongodb/1.18.0/php_mongodb-1.18.0-8.2-ts-vs16-x64.zip",
        "https://pecl.php.net/get/mongodb-1.19.0.tgz"
    )
    
    $downloaded = $false
    $zipFile = "$tempDir\php_mongodb.zip"
    
    foreach ($url in $downloadUrls) {
        Write-Host "Trying: $url" -ForegroundColor Gray
        try {
            Invoke-WebRequest -Uri $url -OutFile $zipFile -ErrorAction Stop
            Write-Host "✓ Downloaded successfully!" -ForegroundColor Green
            $downloaded = $true
            break
        } catch {
            Write-Host "✗ Failed: $($_.Exception.Message)" -ForegroundColor Red
        }
    }
    
    if (-not $downloaded) {
        Write-Host ""
        Write-Host "ERROR: Could not download automatically." -ForegroundColor Red
        Write-Host ""
        Write-Host "Please download manually:" -ForegroundColor Yellow
        Write-Host "1. Visit: https://windows.php.net/downloads/pecl/releases/mongodb/" -ForegroundColor Cyan
        Write-Host "2. Download: php_mongodb-1.19.0-8.2-ts-vs16-x64.zip" -ForegroundColor Cyan
        Write-Host "3. Extract php_mongodb.dll to: $phpExtDir" -ForegroundColor Cyan
        Write-Host ""
        exit 1
    }
    
    Write-Host ""
    Write-Host "Step 3: Extracting DLL..." -ForegroundColor Green
    
    # Extract the DLL
    try {
        Add-Type -AssemblyName System.IO.Compression.FileSystem
        $zip = [System.IO.Compression.ZipFile]::OpenRead($zipFile)
        
        $dllFound = $false
        foreach ($entry in $zip.Entries) {
            if ($entry.Name -eq "php_mongodb.dll") {
                $extractPath = Join-Path $phpExtDir $entry.Name
                [System.IO.Compression.ZipFileExtensions]::ExtractToFile($entry, $extractPath, $true)
                Write-Host "✓ Extracted php_mongodb.dll to: $extractPath" -ForegroundColor Green
                $dllFound = $true
                break
            }
        }
        
        $zip.Dispose()
        
        if (-not $dllFound) {
            Write-Host "✗ Could not find php_mongodb.dll in the zip file" -ForegroundColor Red
            Write-Host "Please extract manually and copy to: $phpExtDir" -ForegroundColor Yellow
            exit 1
        }
        
        # Clean up
        Remove-Item $zipFile -ErrorAction SilentlyContinue
        
    } catch {
        Write-Host "✗ Extraction failed: $($_.Exception.Message)" -ForegroundColor Red
        Write-Host "Please extract manually from: $zipFile" -ForegroundColor Yellow
        exit 1
    }
}

Write-Host ""
Write-Host "Step 4: Configuring php.ini..." -ForegroundColor Green

# Check if extension is already enabled in php.ini
if (Test-Path $phpIni) {
    $iniContent = Get-Content $phpIni -Raw
    $iniLines = Get-Content $phpIni
    
    # Check if already enabled
    $alreadyEnabled = $iniLines | Select-String -Pattern "^extension\s*=\s*mongodb" -CaseSensitive
    
    if ($alreadyEnabled) {
        Write-Host "✓ Extension already enabled in php.ini" -ForegroundColor Green
    } else {
        # Find the extension section
        $extensionSection = $false
        $newLines = @()
        $added = $false
        
        foreach ($line in $iniLines) {
            $newLines += $line
            
            # Look for extension section
            if ($line -match "^;?\s*extension\s*=" -and -not $added) {
                $extensionSection = $true
            }
            
            # Add our extension after first extension line
            if ($extensionSection -and $line -match "^;?\s*extension\s*=" -and -not $added) {
                $newLines += "extension=mongodb"
                $added = $true
                Write-Host "✓ Added 'extension=mongodb' to php.ini" -ForegroundColor Green
            }
        }
        
        if (-not $added) {
            # Add at the end of the file
            $newLines += ""
            $newLines += "; MongoDB Extension"
            $newLines += "extension=mongodb"
            Write-Host "✓ Added 'extension=mongodb' to end of php.ini" -ForegroundColor Green
        }
        
        # Backup original
        Copy-Item $phpIni "$phpIni.backup" -ErrorAction SilentlyContinue
        
        # Write new content
        try {
            $newLines | Set-Content $phpIni -Encoding UTF8
            Write-Host "✓ php.ini updated successfully" -ForegroundColor Green
        } catch {
            Write-Host "✗ Failed to write php.ini: $($_.Exception.Message)" -ForegroundColor Red
            Write-Host "Please add 'extension=mongodb' manually to: $phpIni" -ForegroundColor Yellow
        }
    }
} else {
    Write-Host "✗ php.ini not found at: $phpIni" -ForegroundColor Red
    exit 1
}

Write-Host ""
Write-Host "Step 5: Verifying installation..." -ForegroundColor Green

# Verify
Start-Sleep -Seconds 1
$result = & $phpExe -m 2>&1 | Select-String "mongodb"

if ($result) {
    Write-Host ""
    Write-Host "========================================" -ForegroundColor Green
    Write-Host "✓ SUCCESS! MongoDB extension installed!" -ForegroundColor Green
    Write-Host "========================================" -ForegroundColor Green
    Write-Host ""
    Write-Host "Version: $(& $phpExe -r 'echo phpversion(""mongodb"");')" -ForegroundColor Cyan
    Write-Host ""
    Write-Host "IMPORTANT: Restart Apache in XAMPP Control Panel" -ForegroundColor Yellow
    Write-Host "for the changes to take effect." -ForegroundColor Yellow
    Write-Host ""
} else {
    Write-Host ""
    Write-Host "⚠ Extension installed but not yet loaded." -ForegroundColor Yellow
    Write-Host "Please restart Apache in XAMPP Control Panel." -ForegroundColor Yellow
    Write-Host ""
    Write-Host "After restarting, run: C:\xampp\php\php.exe check_mongodb.php" -ForegroundColor Cyan
    Write-Host ""
}

