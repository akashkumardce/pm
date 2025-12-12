<?php
/**
 * Manage Floors API (MongoDB)
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
        // Get floors for a property
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
        
        $floors = MongoDBHelper::find('floors', ['property_id' => $propertyIdObj], ['sort' => ['floor_number' => 1]]);
        foreach ($floors as &$floor) {
            $floor['id'] = (string)$floor['_id'];
            unset($floor['_id']);
        }
        echo json_encode(['success' => true, 'floors' => $floors]);
        
    } elseif ($method === 'POST') {
        // Create or update floor
        $data = json_decode(file_get_contents('php://input'), true);
        
        if (empty($data['property_id']) || !isset($data['floor_number'])) {
            http_response_code(400);
            echo json_encode(['success' => false, 'message' => 'Property ID and floor number required']);
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
        
        $floorDocument = [
            'property_id' => $propertyIdObj,
            'floor_number' => (int)$data['floor_number'],
            'name' => $data['name'] ?? null,
            'description' => $data['description'] ?? null
        ];
        
        if (isset($data['id'])) {
            // Update existing floor
            $floorId = MongoDBHelper::toObjectId($data['id']);
            MongoDBHelper::updateOne('floors', ['_id' => $floorId], ['$set' => $floorDocument]);
        } else {
            // Create new floor
            $floorId = MongoDBHelper::insertOne('floors', $floorDocument);
        }
        
        $floor = MongoDBHelper::findOne('floors', ['_id' => $floorId]);
        if ($floor) {
            $floor['id'] = (string)$floor['_id'];
            unset($floor['_id']);
        }
        echo json_encode(['success' => true, 'floor' => $floor]);
        
    } elseif ($method === 'DELETE') {
        // Delete floor
        $floorId = $_GET['id'] ?? null;
        if (!$floorId) {
            http_response_code(400);
            echo json_encode(['success' => false, 'message' => 'Floor ID required']);
            exit;
        }
        
        $floorIdObj = MongoDBHelper::toObjectId($floorId);
        
        // Verify ownership through property
        $floor = MongoDBHelper::findOne('floors', ['_id' => $floorIdObj]);
        if ($floor) {
            $property = MongoDBHelper::findOne('properties', [
                '_id' => MongoDBHelper::toObjectId($floor['property_id']),
                'user_id' => $userId
            ]);
            if (!$property) {
                http_response_code(404);
                echo json_encode(['success' => false, 'message' => 'Floor not found']);
                exit;
            }
        } else {
            http_response_code(404);
            echo json_encode(['success' => false, 'message' => 'Floor not found']);
            exit;
        }
        
        MongoDBHelper::deleteOne('floors', ['_id' => $floorIdObj]);
        echo json_encode(['success' => true, 'message' => 'Floor deleted']);
    }
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}

