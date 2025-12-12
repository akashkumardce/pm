<?php
/**
 * Installation Check Helper
 */

function isInstalled() {
    $configFile = __DIR__ . '/../config/database.php';
    
    // Check if config file exists
    if (!file_exists($configFile)) {
        return false;
    }
    
    // Try to connect to database to verify installation
    try {
        // Include config (suppress errors if not ready)
        @require_once $configFile;
        
        // Check if MongoDB constants are defined
        if (!defined('MONGODB_URI') || !defined('MONGODB_DB')) {
            return false;
        }
        
        // Try to connect
        $db = getDBConnection();
        if (!$db) {
            return false;
        }
        
        // Check if collections exist (users collection is created during installation)
        require_once __DIR__ . '/mongodb.php';
        $usersCount = MongoDBHelper::count('users');
        $rolesCount = MongoDBHelper::count('roles');
        
        // Consider installed if both collections exist and have data
        return $usersCount >= 0 && $rolesCount > 0;
    } catch (Exception $e) {
        return false;
    }
}

if (!function_exists('getBaseUrl')) {
    function getBaseUrl() {
        if (defined('BASE_URL')) {
            return BASE_URL;
        }
        // Auto-detect base URL
        $scriptName = $_SERVER['SCRIPT_NAME'] ?? '';
        $requestUri = $_SERVER['REQUEST_URI'] ?? '';
        if (strpos($scriptName, '/pm/') !== false || strpos($requestUri, '/pm/') === 0) {
            return '/pm/';
        }
        return '/';
    }
}

function requireInstallation() {
    if (!isInstalled()) {
        $baseUrl = getBaseUrl();
        header('Location: ' . $baseUrl . 'install/index.php');
        exit;
    }
}

