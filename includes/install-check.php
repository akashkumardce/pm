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
        
        // Check if constants are defined
        if (!defined('DB_HOST') || !defined('DB_NAME') || !defined('DB_USER')) {
            return false;
        }
        
        // Try to connect
        $db = getDBConnection();
        if (!$db) {
            return false;
        }
        
        // Check if tables exist (users table is created during installation)
        $stmt = $db->query("SHOW TABLES LIKE 'users'");
        if ($stmt->rowCount() > 0) {
            // Also check if roles table exists (part of schema)
            $stmt = $db->query("SHOW TABLES LIKE 'roles'");
            return $stmt->rowCount() > 0;
        }
        
        return false;
    } catch (Exception $e) {
        return false;
    }
}

function requireInstallation() {
    if (!isInstalled()) {
        header('Location: /install/index.php');
        exit;
    }
}

