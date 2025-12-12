<?php
/**
 * Create Complaint/Task/Request API
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
    
    if (empty($data['property_id']) || empty($data['title']) || empty($data['description'])) {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'Property ID, title, and description are required']);
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
    
    // Handle file upload if photo is provided
    $photoUrl = null;
    if (!empty($data['photo']) || (isset($_FILES['photo']) && $_FILES['photo']['error'] === UPLOAD_ERR_OK)) {
        // Handle base64 image or file upload
        if (!empty($data['photo'])) {
            // Base64 image
            $uploadDir = __DIR__ . '/../../uploads/complaints/';
            if (!is_dir($uploadDir)) {
                mkdir($uploadDir, 0755, true);
            }
            
            $imageData = $data['photo'];
            if (preg_match('/^data:image\/(\w+);base64,/', $imageData, $matches)) {
                $imageData = substr($imageData, strpos($imageData, ',') + 1);
                $extension = $matches[1];
                $fileName = uniqid('complaint_') . '.' . $extension;
                $filePath = $uploadDir . $fileName;
                
                if (file_put_contents($filePath, base64_decode($imageData))) {
                    $photoUrl = BASE_URL . 'uploads/complaints/' . $fileName;
                }
            }
        } elseif (isset($_FILES['photo'])) {
            // File upload
            $uploadDir = __DIR__ . '/../../uploads/complaints/';
            if (!is_dir($uploadDir)) {
                mkdir($uploadDir, 0755, true);
            }
            
            $extension = pathinfo($_FILES['photo']['name'], PATHINFO_EXTENSION);
            $fileName = uniqid('complaint_') . '.' . $extension;
            $filePath = $uploadDir . $fileName;
            
            if (move_uploaded_file($_FILES['photo']['tmp_name'], $filePath)) {
                $photoUrl = BASE_URL . 'uploads/complaints/' . $fileName;
            }
        }
    }
    
    // Create complaint
    $complaintDocument = [
        'renter_id' => MongoDBHelper::toObjectId($renter['_id']),
        'property_id' => $propertyIdObj,
        'room_id' => !empty($data['room_id']) ? MongoDBHelper::toObjectId($data['room_id']) : MongoDBHelper::toObjectId($renter['room_id'] ?? null),
        'title' => $data['title'],
        'description' => $data['description'],
        'photo_url' => $photoUrl,
        'status' => 'open', // open, in_progress, resolved, closed
        'priority' => $data['priority'] ?? 'medium', // low, medium, high, urgent
        'category' => $data['category'] ?? 'general' // general, maintenance, complaint, request
    ];
    
    $complaintId = MongoDBHelper::insertOne('complaints', $complaintDocument);
    
    // Get created complaint with details
    $complaint = MongoDBHelper::findOne('complaints', ['_id' => $complaintId]);
    if ($complaint) {
        $complaint['id'] = (string)$complaint['_id'];
        unset($complaint['_id']);
    }
    
    echo json_encode([
        'success' => true,
        'message' => 'Complaint created successfully',
        'complaint' => $complaint
    ]);
    
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'Failed to create complaint: ' . $e->getMessage()
    ]);
}

