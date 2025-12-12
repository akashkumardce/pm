<?php
/**
 * Get Complaint Details with Replies API
 */

require_once __DIR__ . '/../../config/config.php';
require_once __DIR__ . '/../../includes/mongodb.php';
require_once __DIR__ . '/../../includes/auth.php';

header('Content-Type: application/json');
requireLogin();

try {
    $complaintId = $_GET['id'] ?? null;
    
    if (!$complaintId) {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'Complaint ID required']);
        exit;
    }
    
    $userId = MongoDBHelper::toObjectId($_SESSION['user_id']);
    $complaintIdObj = MongoDBHelper::toObjectId($complaintId);
    
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
        echo json_encode(['success' => false, 'message' => 'Access denied']);
        exit;
    }
    
    // Get replies
    $replies = MongoDBHelper::find('complaint_replies', ['complaint_id' => $complaintIdObj], ['sort' => ['created_at' => 1]]);
    
    // Enrich replies with user info
    foreach ($replies as &$reply) {
        if (!empty($reply['user_id'])) {
            $user = MongoDBHelper::findOne('users', ['_id' => MongoDBHelper::toObjectId($reply['user_id'])]);
            if ($user) {
                $reply['user_name'] = ($user['first_name'] ?? '') . ' ' . ($user['last_name'] ?? '');
                $reply['user_email'] = $user['email'];
            }
        }
        $reply['id'] = (string)$reply['_id'];
        unset($reply['_id']);
    }
    
    // Enrich complaint with related data
    if (!empty($complaint['renter_id'])) {
        $renter = MongoDBHelper::findOne('renters', ['_id' => MongoDBHelper::toObjectId($complaint['renter_id'])]);
        if ($renter) {
            $complaint['renter_name'] = $renter['name'];
        }
    }
    
    if (!empty($complaint['property_id'])) {
        $property = MongoDBHelper::findOne('properties', ['_id' => MongoDBHelper::toObjectId($complaint['property_id'])]);
        if ($property) {
            $complaint['property_name'] = $property['name'];
        }
    }
    
    $complaint['id'] = (string)$complaint['_id'];
    $complaint['replies'] = $replies;
    unset($complaint['_id']);
    
    echo json_encode([
        'success' => true,
        'complaint' => $complaint
    ]);
    
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'Failed to fetch complaint: ' . $e->getMessage()
    ]);
}

