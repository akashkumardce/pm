<?php
/**
 * Get Property Details API
 */

require_once __DIR__ . '/../../config/config.php';
require_once __DIR__ . '/../../includes/database.php';
require_once __DIR__ . '/../../includes/auth.php';

header('Content-Type: application/json');

// Require login
requireLogin();

try {
    require_once __DIR__ . '/../../includes/mongodb.php';
    
    $propertyId = $_GET['id'] ?? null;
    
    if (!$propertyId) {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'Property ID required']);
        exit;
    }
    
    $userId = MongoDBHelper::toObjectId($_SESSION['user_id']);
    $propertyIdObj = MongoDBHelper::toObjectId($propertyId);
    
    if (!$userId || !$propertyIdObj) {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'Invalid IDs']);
        exit;
    }
    
    // Get property
    $property = MongoDBHelper::findOne('properties', [
        '_id' => $propertyIdObj,
        'user_id' => $userId
    ]);
    
    if (!$property) {
        http_response_code(404);
        echo json_encode(['success' => false, 'message' => 'Property not found']);
        exit;
    }
    
    // Get property type info
    $propertyTypeId = MongoDBHelper::toObjectId($property['property_type_id']);
    if ($propertyTypeId) {
        $propertyType = MongoDBHelper::findOne('property_types', ['_id' => $propertyTypeId]);
        if ($propertyType) {
            $property['property_type_name'] = $propertyType['name'];
            $property['property_type_slug'] = $propertyType['slug'];
            $property['has_rooms'] = $propertyType['has_rooms'] ?? false;
            $property['has_floors'] = $propertyType['has_floors'] ?? false;
        }
    }
    
    // Get property details
    $details = MongoDBHelper::find('property_details', ['property_id' => $propertyIdObj]);
    $property['details'] = [];
    foreach ($details as $detail) {
        $property['details'][$detail['key']] = $detail['value'];
    }
    
    // Get floors if property has floors
    if ($property['has_floors'] ?? false) {
        $floors = MongoDBHelper::find('floors', ['property_id' => $propertyIdObj], ['sort' => ['floor_number' => 1]]);
        foreach ($floors as &$floor) {
            $floor['id'] = (string)$floor['_id'];
            unset($floor['_id']);
        }
        $property['floors'] = $floors;
    }
    
    // Get rooms if property has rooms
    if ($property['has_rooms'] ?? false) {
        $rooms = MongoDBHelper::find('rooms', ['property_id' => $propertyIdObj]);
        foreach ($rooms as &$room) {
            $room['id'] = (string)$room['_id'];
            // Use floor_number directly if available, otherwise get from floor_id
            if (empty($room['floor_number']) && !empty($room['floor_id'])) {
                $floor = MongoDBHelper::findOne('floors', ['_id' => MongoDBHelper::toObjectId($room['floor_id'])]);
                if ($floor) {
                    $room['floor_number'] = $floor['floor_number'];
                    $room['floor_name'] = $floor['name'] ?? null;
                }
            }
            unset($room['_id']);
        }
        // Sort by floor number then room number
        usort($rooms, function($a, $b) {
            $floorCmp = ($a['floor_number'] ?? 0) <=> ($b['floor_number'] ?? 0);
            if ($floorCmp !== 0) return $floorCmp;
            return strcmp($a['room_number'] ?? '', $b['room_number'] ?? '');
        });
        $property['rooms'] = $rooms;
    }
    
    // Check if property is listed for rental
    $listing = MongoDBHelper::findOne('property_listings', ['property_id' => $propertyIdObj, 'status' => 'active']);
    if ($listing) {
        $property['is_listed_for_rental'] = true;
        $property['rental_listing'] = [
            'rental_amount' => $listing['rental_amount'],
            'preferred_tenant_type' => $listing['preferred_tenant_type'] ?? 'any',
            'description' => $listing['description'] ?? null
        ];
    } else {
        $property['is_listed_for_rental'] = false;
    }
    
    // Convert _id to id
    $property['id'] = (string)$property['_id'];
    unset($property['_id']);
    
    echo json_encode([
        'success' => true,
        'property' => $property
    ]);
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'Failed to fetch property: ' . $e->getMessage()
    ]);
}

