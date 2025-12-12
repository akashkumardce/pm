@echo off
echo ========================================
echo MongoDB PHP Extension Installer Helper
echo ========================================
echo.

REM Check if running as administrator
net session >nul 2>&1
if %errorLevel% neq 0 (
    echo ERROR: This script requires administrator privileges.
    echo Please right-click and select "Run as administrator"
    pause
    exit /b 1
)

echo Step 1: Checking PHP version...
C:\xampp\php\php.exe -v
echo.

echo Step 2: Checking if MongoDB extension is already installed...
C:\xampp\php\php.exe -m | findstr mongodb
if %errorLevel% equ 0 (
    echo MongoDB extension is already installed!
    pause
    exit /b 0
)

echo MongoDB extension is NOT installed.
echo.

echo Step 3: Download instructions:
echo.
echo For PHP 8.2.12 ZTS x64, download:
echo https://windows.php.net/downloads/pecl/releases/mongodb/1.19.0/php_mongodb-1.19.0-8.2-ts-vs16-x64.zip
echo.
echo After downloading:
echo 1. Extract php_mongodb.dll
echo 2. Copy to C:\xampp\php\ext\
echo 3. Edit C:\xampp\php\php.ini and add: extension=mongodb
echo 4. Restart Apache
echo.

set /p download="Would you like to open the download page? (y/n): "
if /i "%download%"=="y" (
    start https://windows.php.net/downloads/pecl/releases/mongodb/1.19.0/
)

echo.
echo Opening php.ini for editing...
notepad C:\xampp\php\php.ini

echo.
echo ========================================
echo Next steps:
echo 1. Add 'extension=mongodb' to php.ini (if not already added)
echo 2. Restart Apache from XAMPP Control Panel
echo 3. Run check_mongodb.php to verify installation
echo ========================================
pause

