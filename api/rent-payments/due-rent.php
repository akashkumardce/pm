<?php
/**
 * Calculate Due Rent API
 */

require_once __DIR__ . '/../../config/config.php';
require_once __DIR__ . '/../../includes/mongodb.php';
require_once __DIR__ . '/../../includes/auth.php';

header('Content-Type: application/json');
requireLogin();

try {
    $renterId = $_GET['renter_id'] ?? null;
    $propertyId = $_GET['property_id'] ?? null;
    
    $userId = MongoDBHelper::toObjectId($_SESSION['user_id']);
    
    // Check user role
    $userRoles = getUserRoles((string)$userId);
    $roleSlugs = array_column($userRoles, 'slug');
    $isOwner = in_array('property_owner', $roleSlugs) || in_array('property_manager', $roleSlugs) || in_array('admin', $roleSlugs);
    
    $filter = [];
    
    if ($isOwner) {
        // Owner can check due rent for any renter in their properties
        if ($renterId) {
            $renterIdObj = MongoDBHelper::toObjectId($renterId);
            $renter = MongoDBHelper::findOne('renters', ['_id' => $renterIdObj]);
            if ($renter) {
                $propertyIdObj = MongoDBHelper::toObjectId($renter['property_id']);
                // Verify ownership
                $property = MongoDBHelper::findOne('properties', [
                    '_id' => $propertyIdObj,
                    'user_id' => $userId
                ]);
                if ($property) {
                    $filter['renter_id'] = $renterIdObj;
                } else {
                    http_response_code(403);
                    echo json_encode(['success' => false, 'message' => 'Access denied']);
                    exit;
                }
            }
        } elseif ($propertyId) {
            $propertyIdObj = MongoDBHelper::toObjectId($propertyId);
            $property = MongoDBHelper::findOne('properties', [
                '_id' => $propertyIdObj,
                'user_id' => $userId
            ]);
            if ($property) {
                // Get all renters for this property
                $renters = MongoDBHelper::find('renters', [
                    'property_id' => $propertyIdObj,
                    'status' => 'active'
                ]);
                $renterIds = array_map(function($r) {
                    return MongoDBHelper::toObjectId($r['_id']);
                }, $renters);
                if (!empty($renterIds)) {
                    $filter['renter_id'] = ['$in' => $renterIds];
                } else {
                    echo json_encode(['success' => true, 'due_rents' => []]);
                    exit;
                }
            } else {
                http_response_code(403);
                echo json_encode(['success' => false, 'message' => 'Access denied']);
                exit;
            }
        }
    } else {
        // Tenant can only see their own due rent
        $renter = MongoDBHelper::findOne('renters', [
            'user_id' => $userId,
            'status' => 'active'
        ]);
        
        if (!$renter) {
            echo json_encode(['success' => true, 'due_rents' => []]);
            exit;
        }
        
        $filter['renter_id'] = MongoDBHelper::toObjectId($renter['_id']);
    }
    
    // Get all renters matching filter
    $renters = MongoDBHelper::find('renters', $filter);
    
    $dueRents = [];
    
    foreach ($renters as $renter) {
        $renterIdObj = MongoDBHelper::toObjectId($renter['_id']);
        $propertyIdObj = MongoDBHelper::toObjectId($renter['property_id']);
        
        // Get renter's monthly rent amount
        $monthlyRent = (float)($renter['rental_amount'] ?? 0);
        
        if ($monthlyRent <= 0) {
            continue;
        }
        
        // Get rental start date
        $rentalStartDate = !empty($renter['rental_start_date']) ? new DateTime($renter['rental_start_date']) : new DateTime();
        $currentDate = new DateTime();
        
        // Calculate months since rental started
        $monthsDiff = ($currentDate->format('Y') - $rentalStartDate->format('Y')) * 12 + 
                      ($currentDate->format('m') - $rentalStartDate->format('m'));
        
        // Get all received payments for this renter
        $receivedPayments = MongoDBHelper::find('rent_payments', [
            'renter_id' => $renterIdObj,
            'status' => 'received'
        ]);
        
        $totalPaid = 0;
        foreach ($receivedPayments as $payment) {
            $totalPaid += (float)($payment['amount'] ?? 0);
        }
        
        // Calculate expected total rent
        $expectedTotal = $monthlyRent * ($monthsDiff + 1); // +1 for current month
        
        // Calculate due amount
        $dueAmount = max(0, $expectedTotal - $totalPaid);
        
        // Get property info
        $property = MongoDBHelper::findOne('properties', ['_id' => $propertyIdObj]);
        
        $dueRents[] = [
            'renter_id' => (string)$renter['_id'],
            'renter_name' => $renter['name'],
            'property_id' => (string)$propertyIdObj,
            'property_name' => $property['name'] ?? null,
            'monthly_rent' => $monthlyRent,
            'total_paid' => $totalPaid,
            'expected_total' => $expectedTotal,
            'due_amount' => $dueAmount,
            'rental_start_date' => $renter['rental_start_date'] ?? null
        ];
    }
    
    echo json_encode([
        'success' => true,
        'due_rents' => $dueRents
    ]);
    
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'Failed to calculate due rent: ' . $e->getMessage()
    ]);
}

