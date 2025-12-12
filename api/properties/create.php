<?php
/**
 * Create Property API
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
    
    // Validate required fields
    if (empty($data['name']) || empty($data['country']) || empty($data['city']) || empty($data['property_type_id'])) {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'Missing required fields']);
        exit;
    }
    
    $userId = MongoDBHelper::toObjectId($_SESSION['user_id']);
    $propertyTypeId = MongoDBHelper::toObjectId($data['property_type_id']);
    
    if (!$userId || !$propertyTypeId) {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'Invalid user or property type ID']);
        exit;
    }
    
    // Create property document
    $propertyDocument = [
        'user_id' => $userId,
        'property_type_id' => $propertyTypeId,
        'name' => $data['name'],
        'country' => $data['country'],
        'city' => $data['city'],
        'address' => $data['address'] ?? null,
        'description' => $data['description'] ?? null,
        'status' => 'active' // active, inactive, sold, rented, listed_for_rent
    ];
    
    // Insert property
    $propertyId = MongoDBHelper::insertOne('properties', $propertyDocument);
    
    if ($propertyId) {
        // Fetch the created property
        $property = MongoDBHelper::findOne('properties', ['_id' => $propertyId]);
        
        // Get property type info
        $propertyType = MongoDBHelper::findOne('property_types', ['_id' => $propertyTypeId]);
        if ($propertyType) {
            $property['property_type_name'] = $propertyType['name'];
            $property['property_type_slug'] = $propertyType['slug'];
        }
        
        // Convert _id to id
        $property['id'] = (string)$property['_id'];
        unset($property['_id']);
        
        echo json_encode([
            'success' => true,
            'message' => 'Property created successfully',
            'property' => $property
        ]);
    } else {
        throw new Exception('Failed to create property');
    }
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'Failed to create property: ' . $e->getMessage()
    ]);
}

