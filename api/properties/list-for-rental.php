<?php
/**
 * List Property for Rental API
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
    
    if (empty($data['property_id']) || empty($data['rental_amount'])) {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'Property ID and rental amount are required']);
        exit;
    }
    
    $userId = MongoDBHelper::toObjectId($_SESSION['user_id']);
    $propertyIdObj = MongoDBHelper::toObjectId($data['property_id']);
    
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
    
    // Check if property is empty (no active renters)
    $activeRenters = MongoDBHelper::count('renters', [
        'property_id' => $propertyIdObj,
        'status' => 'active'
    ]);
    
    if ($activeRenters > 0) {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'Property has active renters. Cannot list for rental.']);
        exit;
    }
    
    // Check if already listed
    $existingListing = MongoDBHelper::findOne('property_listings', ['property_id' => $propertyIdObj]);
    
    $listingDocument = [
        'property_id' => $propertyIdObj,
        'rental_amount' => (float)$data['rental_amount'],
        'preferred_tenant_type' => $data['preferred_tenant_type'] ?? 'any', // family, bachelor, any
        'description' => $data['description'] ?? null,
        'status' => 'active', // active, rented, inactive
        'listed_by' => $userId
    ];
    
    if ($existingListing) {
        // Update existing listing
        MongoDBHelper::updateOne('property_listings', ['property_id' => $propertyIdObj], [
            '$set' => $listingDocument
        ]);
        $listing = MongoDBHelper::findOne('property_listings', ['property_id' => $propertyIdObj]);
    } else {
        // Create new listing
        $listingId = MongoDBHelper::insertOne('property_listings', $listingDocument);
        $listing = MongoDBHelper::findOne('property_listings', ['_id' => $listingId]);
    }
    
    // Update property status
    MongoDBHelper::updateOne('properties', ['_id' => $propertyIdObj], [
        '$set' => ['status' => 'listed_for_rent']
    ]);
    
    if ($listing) {
        $listing['id'] = (string)$listing['_id'];
        unset($listing['_id']);
    }
    
    echo json_encode([
        'success' => true,
        'message' => 'Property listed for rental successfully',
        'listing' => $listing
    ]);
    
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'Failed to list property: ' . $e->getMessage()
    ]);
}

