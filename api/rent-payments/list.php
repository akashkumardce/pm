<?php
/**
 * List Rent Payments API
 */

require_once __DIR__ . '/../../config/config.php';
require_once __DIR__ . '/../../includes/mongodb.php';
require_once __DIR__ . '/../../includes/auth.php';

header('Content-Type: application/json');
requireLogin();

try {
    $userId = MongoDBHelper::toObjectId($_SESSION['user_id']);
    $propertyId = $_GET['property_id'] ?? null;
    $renterId = $_GET['renter_id'] ?? null;
    $status = $_GET['status'] ?? null;
    
    // Check user role
    $userRoles = getUserRoles((string)$userId);
    $roleSlugs = array_column($userRoles, 'slug');
    $isOwner = in_array('property_owner', $roleSlugs) || in_array('property_manager', $roleSlugs) || in_array('admin', $roleSlugs);
    
    $filter = [];
    
    if ($isOwner) {
        // Owner can see all payments for their properties
        if ($propertyId) {
            $propertyIdObj = MongoDBHelper::toObjectId($propertyId);
            // Verify ownership
            $property = MongoDBHelper::findOne('properties', [
                '_id' => $propertyIdObj,
                'user_id' => $userId
            ]);
            if ($property) {
                $filter['property_id'] = $propertyIdObj;
            } else {
                http_response_code(403);
                echo json_encode(['success' => false, 'message' => 'Property not found or access denied']);
                exit;
            }
        } else {
            // Get all properties owned by user
            $properties = MongoDBHelper::find('properties', ['user_id' => $userId]);
            $propertyIds = array_map(function($p) {
                return MongoDBHelper::toObjectId($p['_id']);
            }, $properties);
            if (!empty($propertyIds)) {
                $filter['property_id'] = ['$in' => $propertyIds];
            } else {
                echo json_encode(['success' => true, 'payments' => []]);
                exit;
            }
        }
        
        if ($renterId) {
            $filter['renter_id'] = MongoDBHelper::toObjectId($renterId);
        }
    } else {
        // Tenant can only see their own payments
        $renter = MongoDBHelper::findOne('renters', [
            'user_id' => $userId,
            'status' => 'active'
        ]);
        
        if (!$renter) {
            echo json_encode(['success' => true, 'payments' => []]);
            exit;
        }
        
        $filter['renter_id'] = MongoDBHelper::toObjectId($renter['_id']);
        
        if ($propertyId) {
            $filter['property_id'] = MongoDBHelper::toObjectId($propertyId);
        }
    }
    
    if ($status) {
        $filter['status'] = $status;
    }
    
    $payments = MongoDBHelper::find('rent_payments', $filter, ['sort' => ['payment_date' => -1]]);
    
    // Enrich with related data
    foreach ($payments as &$payment) {
        // Get renter info
        if (!empty($payment['renter_id'])) {
            $renter = MongoDBHelper::findOne('renters', ['_id' => MongoDBHelper::toObjectId($payment['renter_id'])]);
            if ($renter) {
                $payment['renter_name'] = $renter['name'];
                $payment['renter_mobile'] = $renter['mobile'];
            }
        }
        
        // Get property info
        if (!empty($payment['property_id'])) {
            $property = MongoDBHelper::findOne('properties', ['_id' => MongoDBHelper::toObjectId($payment['property_id'])]);
            if ($property) {
                $payment['property_name'] = $property['name'];
            }
        }
        
        $payment['id'] = (string)$payment['_id'];
        unset($payment['_id']);
    }
    
    echo json_encode([
        'success' => true,
        'payments' => $payments
    ]);
    
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'Failed to fetch payments: ' . $e->getMessage()
    ]);
}

