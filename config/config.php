<?php
/**
 * Application Configuration
 */

// Auto-detect base URL
$scriptName = $_SERVER['SCRIPT_NAME'] ?? '';
$requestUri = $_SERVER['REQUEST_URI'] ?? '';

// Detect if we're in a subdirectory (like /pm/)
if (strpos($scriptName, '/pm/') !== false || strpos($requestUri, '/pm/') === 0) {
    $baseUrl = '/pm/';
} else {
    $baseUrl = '/';
}

// Application settings
define('APP_NAME', 'Property Management System');
define('APP_VERSION', '1.0.0');
define('BASE_URL', $baseUrl);

// Session settings
define('SESSION_LIFETIME', 3600); // 1 hour

// Security settings
define('PASSWORD_MIN_LENGTH', 8);
define('PASSWORD_REQUIRE_UPPERCASE', true);
define('PASSWORD_REQUIRE_LOWERCASE', true);
define('PASSWORD_REQUIRE_NUMBER', true);
define('PASSWORD_REQUIRE_SPECIAL', false);

// Timezone
date_default_timezone_set('UTC');

// Error reporting (set to 0 in production)
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Start session
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

