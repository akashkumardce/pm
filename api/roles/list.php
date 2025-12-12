<?php
/**
 * List Available Roles API
 */

header('Content-Type: application/json');
require_once __DIR__ . '/../../config/config.php';
require_once __DIR__ . '/../../includes/install-check.php';

// Check if installed
if (!isInstalled()) {
    http_response_code(503);
    echo json_encode(['success' => false, 'message' => 'Application not installed. Please run the installer.']);
    exit;
}

require_once __DIR__ . '/../../includes/database.php';

try {
    $roles = dbFetchAll("SELECT id, name, slug, description FROM roles ORDER BY name");
    
    echo json_encode([
        'success' => true,
        'roles' => $roles
    ]);
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Failed to fetch roles']);
}

