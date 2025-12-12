<?php
/**
 * Authentication Helper Functions
 */

require_once __DIR__ . '/database.php';

/**
 * Hash password
 */
function hashPassword($password) {
    return password_hash($password, PASSWORD_BCRYPT);
}

/**
 * Verify password
 */
function verifyPassword($password, $hash) {
    return password_verify($password, $hash);
}

/**
 * Check if user is logged in
 */
function isLoggedIn() {
    return isset($_SESSION['user_id']) && !empty($_SESSION['user_id']);
}

/**
 * Get current user
 */
function getCurrentUser() {
    if (!isLoggedIn()) {
        return null;
    }
    
    require_once __DIR__ . '/mongodb.php';
    $userId = MongoDBHelper::toObjectId($_SESSION['user_id']);
    if (!$userId) {
        return null;
    }
    
    $user = MongoDBHelper::findOne('users', ['_id' => $userId]);
    
    if ($user) {
        // Convert _id to id for backward compatibility
        $user['id'] = $user['_id'];
        unset($user['password']); // Don't return password
    }
    
    return $user;
}

/**
 * Get user roles
 */
function getUserRoles($userId) {
    require_once __DIR__ . '/mongodb.php';
    $userIdObj = MongoDBHelper::toObjectId($userId);
    if (!$userIdObj) {
        return [];
    }
    
    // Get user role IDs
    $userRoles = MongoDBHelper::find('user_roles', ['user_id' => $userIdObj]);
    
    if (empty($userRoles)) {
        return [];
    }
    
    // Get role IDs
    $roleIds = array_map(function($ur) {
        return MongoDBHelper::toObjectId($ur['role_id']);
    }, $userRoles);
    
    // Get roles
    $roles = MongoDBHelper::find('roles', ['_id' => ['$in' => $roleIds]]);
    
    // Convert _id to id for backward compatibility
    foreach ($roles as &$role) {
        $role['id'] = $role['_id'];
    }
    
    return $roles;
}

/**
 * Check if user has role
 */
function hasRole($userId, $roleSlug) {
    $roles = getUserRoles($userId);
    foreach ($roles as $role) {
        if ($role['slug'] === $roleSlug) {
            return true;
        }
    }
    return false;
}

/**
 * Require login
 */
function requireLogin() {
    if (!isLoggedIn()) {
        http_response_code(401);
        echo json_encode(['success' => false, 'message' => 'Authentication required']);
        exit;
    }
}

/**
 * Require role
 */
function requireRole($roleSlug) {
    requireLogin();
    
    if (!hasRole($_SESSION['user_id'], $roleSlug)) {
        http_response_code(403);
        echo json_encode(['success' => false, 'message' => 'Insufficient permissions']);
        exit;
    }
}

/**
 * Login user
 */
function loginUser($userId) {
    $_SESSION['user_id'] = $userId;
    $_SESSION['login_time'] = time();
}

/**
 * Logout user
 */
function logoutUser() {
    session_unset();
    session_destroy();
}

