<?php
/**
 * User Registration API
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

// Validate input
$email = $input['email'] ?? '';
$password = $input['password'] ?? '';
$firstName = $input['first_name'] ?? '';
$lastName = $input['last_name'] ?? '';
$phone = $input['phone'] ?? '';
$roles = $input['roles'] ?? []; // Array of role slugs

$errors = [];

// Validate email
if (empty($email) || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
    $errors[] = 'Valid email is required';
}

// Validate password
if (empty($password) || strlen($password) < PASSWORD_MIN_LENGTH) {
    $errors[] = 'Password must be at least ' . PASSWORD_MIN_LENGTH . ' characters';
}

// Validate name
if (empty($firstName)) {
    $errors[] = 'First name is required';
}
if (empty($lastName)) {
    $errors[] = 'Last name is required';
}

if (!empty($errors)) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Validation failed', 'errors' => $errors]);
    exit;
}

try {
    require_once __DIR__ . '/../../includes/mongodb.php';
    
    // Check if email already exists
    $existing = MongoDBHelper::findOne('users', ['email' => $email]);
    if ($existing) {
        http_response_code(409);
        echo json_encode(['success' => false, 'message' => 'Email already registered']);
        exit;
    }
    
    // Hash password
    $hashedPassword = hashPassword($password);
    
    // Insert user
    $userDocument = [
        'email' => $email,
        'password' => $hashedPassword,
        'first_name' => $firstName,
        'last_name' => $lastName,
        'phone' => $phone ?: null,
        'status' => 'active'
    ];
    
    $userId = MongoDBHelper::insertOne('users', $userDocument);
    
    if (!$userId) {
        throw new Exception('Failed to create user');
    }
    
    // Assign roles
    if (!empty($roles) && is_array($roles)) {
        foreach ($roles as $roleSlug) {
            $role = MongoDBHelper::findOne('roles', ['slug' => $roleSlug]);
            if ($role) {
                MongoDBHelper::insertOne('user_roles', [
                    'user_id' => $userId,
                    'role_id' => MongoDBHelper::toObjectId($role['_id'])
                ]);
            }
        }
    }
    
    // Get user data
    $user = MongoDBHelper::findOne('users', ['_id' => $userId]);
    if ($user) {
        $user['id'] = (string)$user['_id'];
        unset($user['password']);
        unset($user['_id']);
    }
    
    $userRoles = getUserRoles((string)$userId);
    
    echo json_encode([
        'success' => true,
        'message' => 'Registration successful',
        'user' => $user,
        'roles' => $userRoles
    ]);
    
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Registration failed: ' . $e->getMessage()]);
}

