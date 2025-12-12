<?php
/**
 * Update Rent Payment Status API (Owner can approve/reject/receive)
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
    
    if (empty($data['payment_id']) || empty($data['status'])) {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'Payment ID and status are required']);
        exit;
    }
    
    $userId = MongoDBHelper::toObjectId($_SESSION['user_id']);
    $paymentIdObj = MongoDBHelper::toObjectId($data['payment_id']);
    
    // Check user role - only owners/managers can update status
    $userRoles = getUserRoles((string)$userId);
    $roleSlugs = array_column($userRoles, 'slug');
    $isOwner = in_array('property_owner', $roleSlugs) || in_array('property_manager', $roleSlugs) || in_array('admin', $roleSlugs);
    
    if (!$isOwner) {
        http_response_code(403);
        echo json_encode(['success' => false, 'message' => 'Only property owners/managers can update payment status']);
        exit;
    }
    
    // Get payment
    $payment = MongoDBHelper::findOne('rent_payments', ['_id' => $paymentIdObj]);
    if (!$payment) {
        http_response_code(404);
        echo json_encode(['success' => false, 'message' => 'Payment not found']);
        exit;
    }
    
    // Verify ownership of property
    $propertyIdObj = MongoDBHelper::toObjectId($payment['property_id']);
    $property = MongoDBHelper::findOne('properties', [
        '_id' => $propertyIdObj,
        'user_id' => $userId
    ]);
    
    if (!$property) {
        http_response_code(403);
        echo json_encode(['success' => false, 'message' => 'You do not own this property']);
        exit;
    }
    
    // Validate status
    $validStatuses = ['pending', 'approved', 'rejected', 'received'];
    $newStatus = $data['status'];
    if (!in_array($newStatus, $validStatuses)) {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'Invalid status. Must be: ' . implode(', ', $validStatuses)]);
        exit;
    }
    
    // Update payment status
    $updateData = [
        'status' => $newStatus,
        'updated_by' => $userId,
        'updated_at' => new MongoDB\BSON\UTCDateTime()
    ];
    
    if (!empty($data['comment'])) {
        $updateData['owner_comment'] = $data['comment'];
    }
    
    MongoDBHelper::updateOne('rent_payments', ['_id' => $paymentIdObj], ['$set' => $updateData]);
    
    // Get updated payment
    $updatedPayment = MongoDBHelper::findOne('rent_payments', ['_id' => $paymentIdObj]);
    if ($updatedPayment) {
        $updatedPayment['id'] = (string)$updatedPayment['_id'];
        unset($updatedPayment['_id']);
    }
    
    echo json_encode([
        'success' => true,
        'message' => 'Payment status updated successfully',
        'payment' => $updatedPayment
    ]);
    
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'Failed to update payment status: ' . $e->getMessage()
    ]);
}

