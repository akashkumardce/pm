<?php
/**
 * Reply to Complaint API
 */

require_once __DIR__ . '/../../config/config.php';
require_once __DIR__ . '/../../includes/mongodb.php';
require_once __DIR__ . '/../../includes/auth.php';

header('Content-Type: application/json');
requireLogin();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Method not allowed']);
    exit;
}

try {
    $data = json_decode(file_get_contents('php://input'), true);
    
    if (empty($data['complaint_id']) || empty($data['message'])) {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'Complaint ID and message are required']);
        exit;
    }
    
    $userId = MongoDBHelper::toObjectId($_SESSION['user_id']);
    $complaintIdObj = MongoDBHelper::toObjectId($data['complaint_id']);
    
    // Get complaint
    $complaint = MongoDBHelper::findOne('complaints', ['_id' => $complaintIdObj]);
    if (!$complaint) {
        http_response_code(404);
        echo json_encode(['success' => false, 'message' => 'Complaint not found']);
        exit;
    }
    
    // Check permissions
    $userRoles = getUserRoles((string)$userId);
    $roleSlugs = array_column($userRoles, 'slug');
    $isOwner = in_array('property_owner', $roleSlugs) || in_array('property_manager', $roleSlugs) || in_array('admin', $roleSlugs);
    
    $renterId = MongoDBHelper::toObjectId($complaint['renter_id']);
    $isRenter = $renterId && (string)$renterId === (string)$userId;
    
    if (!$isOwner && !$isRenter) {
        http_response_code(403);
        echo json_encode(['success' => false, 'message' => 'You do not have permission to reply to this complaint']);
        exit;
    }
    
    // If owner is replying, update complaint status if provided
    if ($isOwner && isset($data['status'])) {
        MongoDBHelper::updateOne('complaints', ['_id' => $complaintIdObj], [
            '$set' => [
                'status' => $data['status'],
                'updated_at' => new MongoDB\BSON\UTCDateTime()
            ]
        ]);
    }
    
    // Create reply
    $replyDocument = [
        'complaint_id' => $complaintIdObj,
        'user_id' => $userId,
        'message' => $data['message'],
        'is_owner_reply' => $isOwner
    ];
    
    $replyId = MongoDBHelper::insertOne('complaint_replies', $replyDocument);
    
    // Get created reply
    $reply = MongoDBHelper::findOne('complaint_replies', ['_id' => $replyId]);
    if ($reply) {
        // Get user info
        $user = MongoDBHelper::findOne('users', ['_id' => $userId]);
        if ($user) {
            $reply['user_name'] = ($user['first_name'] ?? '') . ' ' . ($user['last_name'] ?? '');
        }
        $reply['id'] = (string)$reply['_id'];
        unset($reply['_id']);
    }
    
    echo json_encode([
        'success' => true,
        'message' => 'Reply added successfully',
        'reply' => $reply
    ]);
    
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'Failed to add reply: ' . $e->getMessage()
    ]);
}

