<?php
/**
 * Create Rent Payment API
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
    
    if (empty($data['property_id']) || empty($data['amount']) || empty($data['payment_date'])) {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'Property ID, amount, and payment date are required']);
        exit;
    }
    
    $userId = MongoDBHelper::toObjectId($_SESSION['user_id']);
    $propertyIdObj = MongoDBHelper::toObjectId($data['property_id']);
    
    // Verify renter belongs to this property
    $renter = MongoDBHelper::findOne('renters', [
        'property_id' => $propertyIdObj,
        'user_id' => $userId,
        'status' => 'active'
    ]);
    
    if (!$renter) {
        http_response_code(403);
        echo json_encode(['success' => false, 'message' => 'You are not a renter of this property']);
        exit;
    }
    
    // Create payment record
    $paymentDocument = [
        'renter_id' => MongoDBHelper::toObjectId($renter['_id']),
        'property_id' => $propertyIdObj,
        'room_id' => !empty($data['room_id']) ? MongoDBHelper::toObjectId($data['room_id']) : MongoDBHelper::toObjectId($renter['room_id'] ?? null),
        'amount' => (float)$data['amount'],
        'payment_date' => $data['payment_date'], // Format: YYYY-MM-DD
        'payment_mode' => $data['payment_mode'] ?? 'cash', // cash, bank_transfer, cheque, online, other
        'comment' => $data['comment'] ?? null,
        'status' => 'pending', // pending, approved, rejected, received
        'created_by' => $userId
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
        'message' => 'Rent payment registered successfully. Waiting for owner approval.',
        'payment' => $payment
    ]);
    
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'Failed to register payment: ' . $e->getMessage()
    ]);
}

