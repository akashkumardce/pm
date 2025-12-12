<?php
/**
 * User Login API
 */

header('Content-Type: application/json');
require_once __DIR__ . '/../../config/config.php';
require_once __DIR__ . '/../../includes/install-check.php';

// Check if installed
if (!isInstalled()) {
    http_response_code(503);
    echo json_encode(['success' => false, 'message' => 'Application not installed. Please run the installer.']);
    exit;
}

require_once __DIR__ . '/../../includes/auth.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Method not allowed']);
    exit;
}

// Get JSON input
$input = json_decode(file_get_contents('php://input'), true);

$email = $input['email'] ?? '';
$password = $input['password'] ?? '';

if (empty($email) || empty($password)) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Email and password are required']);
    exit;
}

try {
    require_once __DIR__ . '/../../includes/mongodb.php';
    
    // Get user by email
    $user = MongoDBHelper::findOne('users', ['email' => $email]);
    
    if (!$user) {
        http_response_code(401);
        echo json_encode(['success' => false, 'message' => 'Invalid email or password']);
        exit;
    }
    
    // Check if user is active
    if (($user['status'] ?? 'active') !== 'active') {
        http_response_code(403);
        echo json_encode(['success' => false, 'message' => 'Account is not active']);
        exit;
    }
    
    // Verify password
    if (!verifyPassword($password, $user['password'])) {
        http_response_code(401);
        echo json_encode(['success' => false, 'message' => 'Invalid email or password']);
        exit;
    }
    
    // Convert _id to string for session
    $userId = (string)$user['_id'];
    
    // Login user
    loginUser($userId);
    
    // Get user roles
    $roles = getUserRoles($userId);
    
    // Prepare user data for response
    $userData = [
        'id' => $userId,
        'email' => $user['email'],
        'first_name' => $user['first_name'],
        'last_name' => $user['last_name'],
        'phone' => $user['phone'] ?? null,
        'status' => $user['status'] ?? 'active'
    ];
    
    echo json_encode([
        'success' => true,
        'message' => 'Login successful',
        'user' => $userData,
        'roles' => $roles
    ]);
    
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Login failed: ' . $e->getMessage()]);
}

