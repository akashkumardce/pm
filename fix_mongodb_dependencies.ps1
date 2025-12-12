# Fix MongoDB Extension Dependencies
Write-Host "=== MongoDB Extension Dependency Fixer ===" -ForegroundColor Cyan
Write-Host ""

$phpExtDir = "C:\xampp\php\ext"
$dllPath = "$phpExtDir\php_mongodb.dll"

if (-not (Test-Path $dllPath)) {
    Write-Host "[X] php_mongodb.dll not found!" -ForegroundColor Red
    Write-Host "Please download and copy php_mongodb.dll first." -ForegroundColor Yellow
    exit 1
}

Write-Host "The MongoDB extension requires these dependencies:" -ForegroundColor Yellow
Write-Host "1. libmongoc.dll" -ForegroundColor White
Write-Host "2. libbson.dll" -ForegroundColor White
Write-Host ""

Write-Host "These DLLs should be in the same zip file as php_mongodb.dll" -ForegroundColor Cyan
Write-Host ""

# Check if they exist
$missing = @()
if (-not (Test-Path "$phpExtDir\libmongoc.dll")) {
    $missing += "libmongoc.dll"
}
if (-not (Test-Path "$phpExtDir\libbson.dll")) {
    $missing += "libbson.dll"
}

if ($missing.Count -eq 0) {
    Write-Host "[OK] All dependencies found!" -ForegroundColor Green
    exit 0
}

Write-Host "[X] Missing dependencies:" -ForegroundColor Red
foreach ($dep in $missing) {
    Write-Host "   - $dep" -ForegroundColor Red
}
Write-Host ""

Write-Host "Solution:" -ForegroundColor Yellow
Write-Host "1. Go back to the zip file you downloaded" -ForegroundColor White
Write-Host "2. Extract ALL DLLs from the zip (not just php_mongodb.dll)" -ForegroundColor White
Write-Host "3. Copy ALL DLLs to: $phpExtDir" -ForegroundColor White
Write-Host ""

Write-Host "The zip file should contain:" -ForegroundColor Cyan
Write-Host "   - php_mongodb.dll" -ForegroundColor White
Write-Host "   - libmongoc.dll" -ForegroundColor White
Write-Host "   - libbson.dll" -ForegroundColor White
Write-Host "   - (possibly other DLLs)" -ForegroundColor Gray
Write-Host ""

Write-Host "After copying all DLLs, restart Apache and test again." -ForegroundColor Yellow
Write-Host ""

