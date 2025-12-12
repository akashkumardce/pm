<?php
/**
 * Manage Rooms API (MongoDB)
 */

require_once __DIR__ . '/../../config/config.php';
require_once __DIR__ . '/../../includes/mongodb.php';
require_once __DIR__ . '/../../includes/auth.php';

header('Content-Type: application/json');
requireLogin();

$method = $_SERVER['REQUEST_METHOD'];
$userId = MongoDBHelper::toObjectId($_SESSION['user_id']);

try {
    if ($method === 'GET') {
        // Get rooms for a property
        $propertyId = $_GET['property_id'] ?? null;
        if (!$propertyId) {
            http_response_code(400);
            echo json_encode(['success' => false, 'message' => 'Property ID required']);
            exit;
        }
        
        $propertyIdObj = MongoDBHelper::toObjectId($propertyId);
        
        // Verify ownership
        $property = MongoDBHelper::findOne('properties', ['_id' => $propertyIdObj, 'user_id' => $userId]);
        if (!$property) {
            http_response_code(404);
            echo json_encode(['success' => false, 'message' => 'Property not found']);
            exit;
        }
        
        $rooms = MongoDBHelper::find('rooms', ['property_id' => $propertyIdObj]);
        
        // Enrich with floor info and occupancy
        foreach ($rooms as &$room) {
            if (!empty($room['floor_id'])) {
                $floor = MongoDBHelper::findOne('floors', ['_id' => MongoDBHelper::toObjectId($room['floor_id'])]);
                if ($floor) {
                    $room['floor_number'] = $floor['floor_number'];
                    $room['floor_name'] = $floor['name'] ?? null;
                }
            }
            
            // Count current occupants
            $roomId = MongoDBHelper::toObjectId($room['_id']);
            $room['current_occupants'] = MongoDBHelper::count('renters', [
                'room_id' => $roomId,
                'status' => 'active'
            ]);
            
            $room['id'] = (string)$room['_id'];
            unset($room['_id']);
        }
        
        // Sort by floor number then room number
        usort($rooms, function($a, $b) {
            $floorCmp = ($a['floor_number'] ?? 0) <=> ($b['floor_number'] ?? 0);
            if ($floorCmp !== 0) return $floorCmp;
            return strcmp($a['room_number'] ?? '', $b['room_number'] ?? '');
        });
        
        echo json_encode(['success' => true, 'rooms' => $rooms]);
        
    } elseif ($method === 'POST') {
        // Create or update room
        $data = json_decode(file_get_contents('php://input'), true);
        
        if (empty($data['property_id']) || empty($data['room_number'])) {
            http_response_code(400);
            echo json_encode(['success' => false, 'message' => 'Property ID and room number required']);
            exit;
        }
        
        $propertyIdObj = MongoDBHelper::toObjectId($data['property_id']);
        
        // Verify ownership
        $property = MongoDBHelper::findOne('properties', ['_id' => $propertyIdObj, 'user_id' => $userId]);
        if (!$property) {
            http_response_code(404);
            echo json_encode(['success' => false, 'message' => 'Property not found']);
            exit;
        }
        
        // Get property type to check if floors are needed
        $propertyTypeId = MongoDBHelper::toObjectId($property['property_type_id']);
        $propertyType = MongoDBHelper::findOne('property_types', ['_id' => $propertyTypeId]);
        $needsFloors = $propertyType['has_floors'] ?? false;
        
        // Handle floor - if property needs floors, use floor_number directly
        // For PG properties, create/use floor based on floor_number
        $floorId = null;
        if ($needsFloors && isset($data['floor_number'])) {
            $floorNumber = (int)$data['floor_number'];
            // Find or create floor for this property and floor number
            $floor = MongoDBHelper::findOne('floors', [
                'property_id' => $propertyIdObj,
                'floor_number' => $floorNumber
            ]);
            
            if (!$floor) {
                // Create floor automatically
                $floorId = MongoDBHelper::insertOne('floors', [
                    'property_id' => $propertyIdObj,
                    'floor_number' => $floorNumber,
                    'name' => "Floor $floorNumber"
                ]);
            } else {
                $floorId = MongoDBHelper::toObjectId($floor['_id']);
            }
        } elseif (!empty($data['floor_id'])) {
            // Legacy support - if floor_id is provided, use it
            $floorId = MongoDBHelper::toObjectId($data['floor_id']);
        }
        
        $roomDocument = [
            'property_id' => $propertyIdObj,
            'room_number' => $data['room_number'],
            'floor_id' => $floorId,
            'floor_number' => $needsFloors && isset($data['floor_number']) ? (int)$data['floor_number'] : null,
            'name' => $data['name'] ?? null,
            'capacity' => (int)($data['capacity'] ?? 1),
            'rental_amount' => (float)($data['rental_amount'] ?? 0),
            'status' => $data['status'] ?? 'available',
            'description' => $data['description'] ?? null
        ];
        
        if (isset($data['id'])) {
            // Update existing room
            $roomId = MongoDBHelper::toObjectId($data['id']);
            MongoDBHelper::updateOne('rooms', ['_id' => $roomId], ['$set' => $roomDocument]);
        } else {
            // Create new room
            $roomId = MongoDBHelper::insertOne('rooms', $roomDocument);
        }
        
        // Update current occupancy
        $occupancy = MongoDBHelper::count('renters', [
            'room_id' => $roomId,
            'status' => 'active'
        ]);
        MongoDBHelper::updateOne('rooms', ['_id' => $roomId], ['$set' => ['current_occupancy' => $occupancy]]);
        
        $room = MongoDBHelper::findOne('rooms', ['_id' => $roomId]);
        if ($room && !empty($room['floor_id'])) {
            $floor = MongoDBHelper::findOne('floors', ['_id' => MongoDBHelper::toObjectId($room['floor_id'])]);
            if ($floor) {
                $room['floor_number'] = $floor['floor_number'];
                $room['floor_name'] = $floor['name'] ?? null;
            }
        }
        if ($room) {
            $room['id'] = (string)$room['_id'];
            unset($room['_id']);
        }
        echo json_encode(['success' => true, 'room' => $room]);
        
    } elseif ($method === 'DELETE') {
        // Delete room
        $roomId = $_GET['id'] ?? null;
        if (!$roomId) {
            http_response_code(400);
            echo json_encode(['success' => false, 'message' => 'Room ID required']);
            exit;
        }
        
        $roomIdObj = MongoDBHelper::toObjectId($roomId);
        
        // Verify ownership through property
        $room = MongoDBHelper::findOne('rooms', ['_id' => $roomIdObj]);
        if ($room) {
            $property = MongoDBHelper::findOne('properties', [
                '_id' => MongoDBHelper::toObjectId($room['property_id']),
                'user_id' => $userId
            ]);
            if (!$property) {
                http_response_code(404);
                echo json_encode(['success' => false, 'message' => 'Room not found']);
                exit;
            }
        } else {
            http_response_code(404);
            echo json_encode(['success' => false, 'message' => 'Room not found']);
            exit;
        }
        
        MongoDBHelper::deleteOne('rooms', ['_id' => $roomIdObj]);
        echo json_encode(['success' => true, 'message' => 'Room deleted']);
    }
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
