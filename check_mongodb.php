<?php
/**
 * MongoDB Extension Checker and Installer Helper
 * Run this script to check if MongoDB extension is installed
 */

echo "=== MongoDB PHP Extension Checker ===\n\n";

// Check PHP version
$phpVersion = PHP_VERSION;
echo "PHP Version: $phpVersion\n";

// Check if extension is loaded
if (extension_loaded('mongodb')) {
    echo "✓ MongoDB extension is LOADED\n";
    echo "  Version: " . phpversion('mongodb') . "\n";
    echo "\n✓ You're all set! The MongoDB extension is installed.\n";
    exit(0);
} else {
    echo "✗ MongoDB extension is NOT loaded\n\n";
    
    // Get PHP info
    $phpInfo = [
        'version' => PHP_VERSION,
        'zts' => ZEND_THREAD_SAFE ? 'ZTS' : 'NTS',
        'arch' => PHP_INT_SIZE * 8 . '-bit',
        'compiler' => 'VS' . (defined('PHP_VERSION_ID') && PHP_VERSION_ID >= 80200 ? '16' : '15')
    ];
    
    echo "Your PHP Configuration:\n";
    echo "  - Version: {$phpInfo['version']}\n";
    echo "  - Build: {$phpInfo['zts']}\n";
    echo "  - Architecture: {$phpInfo['arch']}\n";
    echo "  - Compiler: {$phpInfo['compiler']}\n\n";
    
    echo "=== Installation Instructions ===\n\n";
    echo "1. Download MongoDB extension DLL:\n";
    echo "   For PHP {$phpInfo['version']} {$phpInfo['zts']} {$phpInfo['arch']}:\n";
    echo "   https://windows.php.net/downloads/pecl/releases/mongodb/\n\n";
    
    $majorVersion = explode('.', $phpInfo['version'])[0] . '.' . explode('.', $phpInfo['version'])[1];
    $zts = strtolower($phpInfo['zts']);
    $arch = $phpInfo['arch'] === '64-bit' ? 'x64' : 'x86';
    
    echo "2. Look for file matching:\n";
    echo "   php_mongodb-*-{$majorVersion}-{$zts}-{$phpInfo['compiler']}-{$arch}.zip\n\n";
    
    echo "3. Extract php_mongodb.dll to:\n";
    $extDir = ini_get('extension_dir');
    if ($extDir) {
        echo "   $extDir\n";
    } else {
        echo "   C:\\xampp\\php\\ext\\\n";
    }
    
    echo "\n4. Edit php.ini and add:\n";
    echo "   extension=mongodb\n\n";
    
    $iniFile = php_ini_loaded_file();
    if ($iniFile) {
        echo "   php.ini location: $iniFile\n\n";
    }
    
    echo "5. Restart Apache in XAMPP Control Panel\n\n";
    
    echo "6. Run this script again to verify installation\n\n";
    
    echo "=== Alternative: Quick Download Links ===\n\n";
    echo "For PHP 8.2 ZTS x64 (your version):\n";
    echo "https://windows.php.net/downloads/pecl/releases/mongodb/1.19.0/\n";
    echo "Download: php_mongodb-1.19.0-8.2-ts-vs16-x64.zip\n\n";
    
    exit(1);
}

