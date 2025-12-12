<?php
/**
 * Create Renter API
 */

require_once __DIR__ . '/../../config/config.php';
require_once __DIR__ . '/../../includes/database.php';
require_once __DIR__ . '/../../includes/auth.php';

header('Content-Type: application/json');
requireLogin();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Method not allowed']);
    exit;
}

try {
    require_once __DIR__ . '/../../includes/mongodb.php';
    
    $data = json_decode(file_get_contents('php://input'), true);
    
    if (empty($data['property_id']) || empty($data['name']) || empty($data['mobile'])) {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'Property ID, name, and mobile are required']);
        exit;
    }
    
    $userId = MongoDBHelper::toObjectId($_SESSION['user_id']);
    $propertyIdObj = MongoDBHelper::toObjectId($data['property_id']);
    
    // Verify property ownership
    $property = MongoDBHelper::findOne('properties', [
        '_id' => $propertyIdObj,
        'user_id' => $userId
    ]);
    
    if (!$property) {
        http_response_code(404);
        echo json_encode(['success' => false, 'message' => 'Property not found']);
        exit;
    }
    
    // Check if user exists with this email
    $user = null;
    $userIdObj = null;
    if (!empty($data['email'])) {
        $user = MongoDBHelper::findOne('users', ['email' => $data['email']]);
        if ($user) {
            $userIdObj = MongoDBHelper::toObjectId($user['_id']);
        }
    }
    
    // Create renter document
    $renterDocument = [
        'property_id' => $propertyIdObj,
        'room_id' => !empty($data['room_id']) ? MongoDBHelper::toObjectId($data['room_id']) : null,
        'user_id' => $userIdObj,
        'name' => $data['name'],
        'mobile' => $data['mobile'],
        'email' => $data['email'] ?? null,
        'rental_amount' => (float)($data['rental_amount'] ?? 0),
        'rental_start_date' => $data['rental_start_date'] ?? null,
        'rental_end_date' => $data['rental_end_date'] ?? null,
        'status' => 'active',
        'has_app_account' => $user ? true : false,
        'notes' => $data['notes'] ?? null
    ];
    
    $renterId = MongoDBHelper::insertOne('renters', $renterDocument);
    
    if ($renterId) {
        // Update room occupancy if room is specified
        if (!empty($data['room_id'])) {
            $roomIdObj = MongoDBHelper::toObjectId($data['room_id']);
            $occupancy = MongoDBHelper::count('renters', [
                'room_id' => $roomIdObj,
                'status' => 'active'
            ]);
            MongoDBHelper::updateOne('rooms', ['_id' => $roomIdObj], ['$set' => ['current_occupancy' => $occupancy]]);
        }
        
        // Get created renter with related data
        $renter = MongoDBHelper::findOne('renters', ['_id' => $renterId]);
        if ($renter) {
            $property = MongoDBHelper::findOne('properties', ['_id' => $propertyIdObj]);
            if ($property) {
                $renter['property_name'] = $property['name'];
            }
            
            if (!empty($renter['room_id'])) {
                $room = MongoDBHelper::findOne('rooms', ['_id' => MongoDBHelper::toObjectId($renter['room_id'])]);
                if ($room) {
                    $renter['room_number'] = $room['room_number'];
                }
            }
            
            $renter['id'] = (string)$renter['_id'];
            unset($renter['_id']);
        }
        
        echo json_encode([
            'success' => true,
            'message' => 'Renter added successfully',
            'renter' => $renter
        ]);
    } else {
        throw new Exception('Failed to create renter');
    }
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'Failed to create renter: ' . $e->getMessage()
    ]);
}

