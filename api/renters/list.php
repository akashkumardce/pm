<?php
/**
 * List Renters API (MongoDB)
 */

require_once __DIR__ . '/../../config/config.php';
require_once __DIR__ . '/../../includes/mongodb.php';
require_once __DIR__ . '/../../includes/auth.php';

header('Content-Type: application/json');
requireLogin();

try {
    require_once __DIR__ . '/../../includes/mongodb.php';
    
    $userId = MongoDBHelper::toObjectId($_SESSION['user_id']);
    $propertyId = $_GET['property_id'] ?? null;
    
    // Get properties owned by user
    $properties = MongoDBHelper::find('properties', ['user_id' => $userId]);
    $propertyIds = array_map(function($p) {
        return MongoDBHelper::toObjectId($p['_id']);
    }, $properties);
    
    if (empty($propertyIds)) {
        echo json_encode(['success' => true, 'renters' => []]);
        exit;
    }
    
    // Build filter
    $filter = ['property_id' => ['$in' => $propertyIds]];
    if ($propertyId) {
        $filter['property_id'] = MongoDBHelper::toObjectId($propertyId);
    }
    
    $renters = MongoDBHelper::find('renters', $filter, ['sort' => ['created_at' => -1]]);
    
    // Enrich with related data
    foreach ($renters as &$renter) {
        // Get property info
        $propertyIdObj = MongoDBHelper::toObjectId($renter['property_id']);
        $property = MongoDBHelper::findOne('properties', ['_id' => $propertyIdObj]);
        if ($property) {
            $renter['property_name'] = $property['name'];
            $renter['city'] = $property['city'];
            $renter['country'] = $property['country'];
        }
        
        // Get room info
        if (!empty($renter['room_id'])) {
            $room = MongoDBHelper::findOne('rooms', ['_id' => MongoDBHelper::toObjectId($renter['room_id'])]);
            if ($room) {
                $renter['room_number'] = $room['room_number'];
                $renter['room_name'] = $room['name'] ?? null;
                
                // Get floor info
                if (!empty($room['floor_id'])) {
                    $floor = MongoDBHelper::findOne('floors', ['_id' => MongoDBHelper::toObjectId($room['floor_id'])]);
                    if ($floor) {
                        $renter['floor_number'] = $floor['floor_number'];
                    }
                }
            }
        }
        
        // Get user email if linked
        if (!empty($renter['user_id'])) {
            $user = MongoDBHelper::findOne('users', ['_id' => MongoDBHelper::toObjectId($renter['user_id'])]);
            if ($user) {
                $renter['user_email'] = $user['email'];
            }
        }
        
        $renter['id'] = (string)$renter['_id'];
        unset($renter['_id']);
    }
    
    echo json_encode([
        'success' => true,
        'renters' => $renters
    ]);
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'Failed to fetch renters: ' . $e->getMessage()
    ]);
}

