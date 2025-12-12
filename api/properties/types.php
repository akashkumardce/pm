<?php
/**
 * Get Property Types API
 */

require_once __DIR__ . '/../../config/config.php';
require_once __DIR__ . '/../../includes/database.php';
require_once __DIR__ . '/../../includes/auth.php';

header('Content-Type: application/json');

// Require login
requireLogin();

try {
    require_once __DIR__ . '/../../includes/mongodb.php';
    
    $types = MongoDBHelper::find('property_types', [], ['sort' => ['name' => 1]]);
    
    // Convert _id to id for backward compatibility
    foreach ($types as &$type) {
        $type['id'] = (string)$type['_id'];
        unset($type['_id']);
    }
    
    echo json_encode([
        'success' => true,
        'types' => $types
    ]);
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'Failed to fetch property types: ' . $e->getMessage()
    ]);
}

