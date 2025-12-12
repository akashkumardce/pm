<?php
/**
 * Update Property API
 */

require_once __DIR__ . '/../../config/config.php';
require_once __DIR__ . '/../../includes/database.php';
require_once __DIR__ . '/../../includes/auth.php';

header('Content-Type: application/json');

// Require login
requireLogin();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Method not allowed']);
    exit;
}

try {
    require_once __DIR__ . '/../../includes/mongodb.php';
    
    $data = json_decode(file_get_contents('php://input'), true);
    
    if (empty($data['id'])) {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'Property ID required']);
        exit;
    }
    
    $userId = MongoDBHelper::toObjectId($_SESSION['user_id']);
    $propertyId = MongoDBHelper::toObjectId($data['id']);
    
    if (!$userId || !$propertyId) {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'Invalid IDs']);
        exit;
    }
    
    // Verify ownership
    $property = MongoDBHelper::findOne('properties', [
        '_id' => $propertyId,
        'user_id' => $userId
    ]);
    
    if (!$property) {
        http_response_code(404);
        echo json_encode(['success' => false, 'message' => 'Property not found']);
        exit;
    }
    
    // Build update document
    $update = ['$set' => []];
    
    if (isset($data['name'])) {
        $update['$set']['name'] = $data['name'];
    }
    if (isset($data['country'])) {
        $update['$set']['country'] = $data['country'];
    }
    if (isset($data['city'])) {
        $update['$set']['city'] = $data['city'];
    }
    if (isset($data['address'])) {
        $update['$set']['address'] = $data['address'];
    }
    if (isset($data['description'])) {
        $update['$set']['description'] = $data['description'];
    }
    if (isset($data['status'])) {
        $update['$set']['status'] = $data['status'];
    }
    
    if (!empty($update['$set'])) {
        MongoDBHelper::updateOne('properties', ['_id' => $propertyId], $update);
    }
    
    // Update property details
    if (isset($data['details']) && is_array($data['details'])) {
        foreach ($data['details'] as $key => $value) {
            MongoDBHelper::updateOne(
                'property_details',
                ['property_id' => $propertyId, 'key' => $key],
                ['$set' => ['value' => $value]],
                ['upsert' => true]
            );
        }
    }
    
    echo json_encode([
        'success' => true,
        'message' => 'Property updated successfully'
    ]);
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'Failed to update property: ' . $e->getMessage()
    ]);
}

