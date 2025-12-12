<?php
/**
 * Send Notification to Renter API
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
    
    if (empty($data['renter_id']) || empty($data['title']) || empty($data['message'])) {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'Renter ID, title, and message are required']);
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
    
    // Create notification
    $notificationDocument = [
        'renter_id' => $renterIdObj,
        'property_id' => MongoDBHelper::toObjectId($renter['property_id']),
        'sender_id' => $userId,
        'title' => $data['title'],
        'message' => $data['message'],
        'type' => $data['type'] ?? 'info',
        'status' => 'sent'
    ];
    
    $notificationId = MongoDBHelper::insertOne('notifications', $notificationDocument);
    
    // TODO: Send actual notification (email/SMS/push)
    // For now, just store in database
    
    echo json_encode([
        'success' => true,
        'message' => 'Notification sent successfully',
        'notification_id' => (string)$notificationId
    ]);
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'Failed to send notification: ' . $e->getMessage()
    ]);
}

