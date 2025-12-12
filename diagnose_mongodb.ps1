# MongoDB Extension Diagnostic Script
Write-Host "=== MongoDB Extension Diagnostic ===" -ForegroundColor Cyan
Write-Host ""

$phpExe = "C:\xampp\php\php.exe"
$phpExtDir = "C:\xampp\php\ext"
$phpIni = "C:\xampp\php\php.ini"

# Check PHP version
Write-Host "1. PHP Configuration:" -ForegroundColor Green
& $phpExe -v | Select-Object -First 1
$phpInfo = & $phpExe -i
$zts = ($phpInfo | Select-String "Thread Safety").Line
$arch = ($phpInfo | Select-String "Architecture").Line
Write-Host "   $zts" -ForegroundColor Gray
Write-Host "   $arch" -ForegroundColor Gray
Write-Host ""

# Check if DLL exists
Write-Host "2. Checking for php_mongodb.dll:" -ForegroundColor Green
$dllPath = "$phpExtDir\php_mongodb.dll"
if (Test-Path $dllPath) {
    $dll = Get-Item $dllPath
    Write-Host "   [OK] Found: $($dll.Name)" -ForegroundColor Green
    Write-Host "   Size: $([math]::Round($dll.Length/1KB, 2)) KB" -ForegroundColor Gray
    Write-Host "   Modified: $($dll.LastWriteTime)" -ForegroundColor Gray
    
    # Check if it's a valid DLL
    try {
        $fileInfo = [System.IO.File]::ReadAllBytes($dllPath)
        if ($fileInfo[0] -eq 0x4D -and $fileInfo[1] -eq 0x5A) {
            Write-Host "   [OK] Valid PE (Portable Executable) file" -ForegroundColor Green
        } else {
            Write-Host "   [X] Invalid DLL format!" -ForegroundColor Red
        }
    } catch {
        Write-Host "   [X] Error reading DLL: $_" -ForegroundColor Red
    }
} else {
    Write-Host "   [X] NOT FOUND: $dllPath" -ForegroundColor Red
}
Write-Host ""

# Check for dependencies
Write-Host "3. Checking for required dependencies:" -ForegroundColor Green
$deps = @("libmongoc.dll", "libbson.dll")
$allDepsFound = $true
foreach ($dep in $deps) {
    $depPath = "$phpExtDir\$dep"
    if (Test-Path $depPath) {
        Write-Host "   [OK] Found: $dep" -ForegroundColor Green
    } else {
        Write-Host "   [X] Missing: $dep" -ForegroundColor Red
        $allDepsFound = $false
    }
}
if (-not $allDepsFound) {
    Write-Host "   [WARNING] Missing dependencies may cause loading errors!" -ForegroundColor Yellow
}
Write-Host ""

# Check php.ini
Write-Host "4. Checking php.ini configuration:" -ForegroundColor Green
if (Test-Path $phpIni) {
    $iniLines = Get-Content $phpIni
    $found = $false
    foreach ($line in $iniLines) {
        if ($line -match "extension.*mongodb") {
            $found = $true
            if ($line.StartsWith(";")) {
                Write-Host "   [X] Extension is COMMENTED OUT (disabled)" -ForegroundColor Red
                Write-Host "   Line: $line" -ForegroundColor Gray
            } else {
                Write-Host "   [OK] Extension is enabled" -ForegroundColor Green
                Write-Host "   Line: $line" -ForegroundColor Gray
            }
        }
    }
    if (-not $found) {
        Write-Host "   [X] Extension line NOT FOUND in php.ini" -ForegroundColor Red
    }
} else {
    Write-Host "   [X] php.ini not found!" -ForegroundColor Red
}
Write-Host ""

# Try to load extension
Write-Host "5. Testing extension loading:" -ForegroundColor Green
$testResult = & $phpExe -r "echo extension_loaded('mongodb') ? 'LOADED' : 'NOT LOADED';" 2>&1
if ($testResult -match "LOADED") {
    Write-Host "   [OK] Extension is loaded!" -ForegroundColor Green
} else {
    Write-Host "   [X] Extension is NOT loaded" -ForegroundColor Red
    Write-Host "   Error output:" -ForegroundColor Yellow
    $testResult | ForEach-Object { Write-Host "   $_" -ForegroundColor Red }
}

Write-Host ""
Write-Host "=== Diagnostic Complete ===" -ForegroundColor Cyan
Write-Host ""

# Common issues and solutions
Write-Host "Common Issues:" -ForegroundColor Yellow
Write-Host "1. '%1 is not a valid Win32 application' = Wrong DLL version (NTS vs ZTS, x86 vs x64)" -ForegroundColor White
Write-Host "2. 'The specified module could not be found' = Missing dependencies (libmongoc.dll, libbson.dll)" -ForegroundColor White
Write-Host "3. Extension not loading = Wrong PHP version or build type" -ForegroundColor White
Write-Host ""

