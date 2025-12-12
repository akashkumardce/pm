<?php
/**
 * List Properties API
 */

require_once __DIR__ . '/../../config/config.php';
require_once __DIR__ . '/../../includes/database.php';
require_once __DIR__ . '/../../includes/auth.php';

header('Content-Type: application/json');

// Require login
requireLogin();

try {
    require_once __DIR__ . '/../../includes/mongodb.php';
    
    $userId = MongoDBHelper::toObjectId($_SESSION['user_id']);
    if (!$userId) {
        throw new Exception('Invalid user ID');
    }
    
    // Get properties for user
    $properties = MongoDBHelper::find('properties', ['user_id' => $userId], ['sort' => ['created_at' => -1]]);
    
    // Enrich with property type info and counts
    foreach ($properties as &$property) {
        // Get property type
        $propertyTypeId = MongoDBHelper::toObjectId($property['property_type_id']);
        if ($propertyTypeId) {
            $propertyType = MongoDBHelper::findOne('property_types', ['_id' => $propertyTypeId]);
            if ($propertyType) {
                $property['property_type_name'] = $propertyType['name'];
                $property['property_type_slug'] = $propertyType['slug'];
            }
        }
        
        // Count renters
        $propertyId = MongoDBHelper::toObjectId($property['_id']);
        $property['total_renters'] = MongoDBHelper::count('renters', [
            'property_id' => $propertyId,
            'status' => 'active'
        ]);
        
        // Count rooms
        $property['total_rooms'] = MongoDBHelper::count('rooms', ['property_id' => $propertyId]);
        
        // Convert _id to id
        $property['id'] = (string)$property['_id'];
        unset($property['_id']);
    }
    
    echo json_encode([
        'success' => true,
        'properties' => $properties
    ]);
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'Failed to fetch properties: ' . $e->getMessage()
    ]);
}

