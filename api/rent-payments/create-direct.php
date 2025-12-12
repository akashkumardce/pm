<?php
/**
 * Create Rent Payment Directly (Owner marks payment as received)
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
    
    if (empty($data['renter_id']) || empty($data['property_id']) || empty($data['amount']) || empty($data['payment_date'])) {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'Renter ID, property ID, amount, and payment date are required']);
        exit;
    }
    
    $userId = MongoDBHelper::toObjectId($_SESSION['user_id']);
    $propertyIdObj = MongoDBHelper::toObjectId($data['property_id']);
    
    // Check user role - only owners/managers can create direct payments
    $userRoles = getUserRoles((string)$userId);
    $roleSlugs = array_column($userRoles, 'slug');
    $isOwner = in_array('property_owner', $roleSlugs) || in_array('property_manager', $roleSlugs) || in_array('admin', $roleSlugs);
    
    if (!$isOwner) {
        http_response_code(403);
        echo json_encode(['success' => false, 'message' => 'Only property owners/managers can create direct payments']);
        exit;
    }
    
    // Verify ownership
    $property = MongoDBHelper::findOne('properties', [
        '_id' => $propertyIdObj,
        'user_id' => $userId
    ]);
    
    if (!$property) {
        http_response_code(404);
        echo json_encode(['success' => false, 'message' => 'Property not found']);
        exit;
    }
    
    // Verify renter belongs to this property
    $renterIdObj = MongoDBHelper::toObjectId($data['renter_id']);
    $renter = MongoDBHelper::findOne('renters', [
        '_id' => $renterIdObj,
        'property_id' => $propertyIdObj,
        'status' => 'active'
    ]);
    
    if (!$renter) {
        http_response_code(404);
        echo json_encode(['success' => false, 'message' => 'Renter not found or does not belong to this property']);
        exit;
    }
    
    // Create payment record with received status
    $paymentDocument = [
        'renter_id' => $renterIdObj,
        'property_id' => $propertyIdObj,
        'room_id' => !empty($data['room_id']) ? MongoDBHelper::toObjectId($data['room_id']) : MongoDBHelper::toObjectId($renter['room_id'] ?? null),
        'amount' => (float)$data['amount'],
        'payment_date' => $data['payment_date'],
        'payment_mode' => $data['payment_mode'] ?? 'cash',
        'comment' => $data['comment'] ?? null,
        'status' => 'received', // Directly marked as received
        'created_by' => $userId,
        'updated_by' => $userId
    ];
    
    $paymentId = MongoDBHelper::insertOne('rent_payments', $paymentDocument);
    
    // Get created payment
    $payment = MongoDBHelper::findOne('rent_payments', ['_id' => $paymentId]);
    if ($payment) {
        $payment['id'] = (string)$payment['_id'];
        unset($payment['_id']);
    }
    
    echo json_encode([
        'success' => true,
        'message' => 'Payment marked as received successfully',
        'payment' => $payment
    ]);
    
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'Failed to create payment: ' . $e->getMessage()
    ]);
}

