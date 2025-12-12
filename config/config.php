<?php
/**
 * Application Configuration
 */

// Application settings
define('APP_NAME', 'Property Management System');
define('APP_VERSION', '1.0.0');
define('BASE_URL', '/');

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

