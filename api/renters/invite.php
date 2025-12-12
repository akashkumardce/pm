<?php
/**
 * Invite Renter to App API
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
    
    if (empty($data['renter_id'])) {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'Renter ID required']);
        exit;
    }
    
    $userId = MongoDBHelper::toObjectId($_SESSION['user_id']);
    $renterIdObj = MongoDBHelper::toObjectId($data['renter_id']);
    
    // Verify renter belongs to user's property
    $renter = MongoDBHelper::findOne('renters', ['_id' => $renterIdObj]);
    if ($renter) {
        $property = MongoDBHelper::findOne('properties', [
            '_id' => MongoDBHelper::toObjectId($renter['property_id']),
            'user_id' => $userId
        ]);
        if (!$property) {
            http_response_code(404);
            echo json_encode(['success' => false, 'message' => 'Renter not found']);
            exit;
        }
    } else {
        http_response_code(404);
        echo json_encode(['success' => false, 'message' => 'Renter not found']);
        exit;
    }
    
    // Update renter invite status
    MongoDBHelper::updateOne('renters', ['_id' => $renterIdObj], [
        '$set' => [
            'invite_sent' => true,
            'invite_sent_at' => new MongoDB\BSON\UTCDateTime()
        ]
    ]);
    
    // TODO: Send actual invitation (email/SMS)
    // For now, just mark as sent
    
    echo json_encode([
        'success' => true,
        'message' => 'Invitation sent successfully'
    ]);
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'Failed to send invitation: ' . $e->getMessage()
    ]);
}

