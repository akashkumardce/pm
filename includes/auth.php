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
    
    $user = dbFetchOne(
        "SELECT id, email, first_name, last_name, phone, created_at FROM users WHERE id = ?",
        [$_SESSION['user_id']]
    );
    
    return $user;
}

/**
 * Get user roles
 */
function getUserRoles($userId) {
    return dbFetchAll(
        "SELECT r.id, r.name, r.slug FROM roles r 
         INNER JOIN user_roles ur ON r.id = ur.role_id 
         WHERE ur.user_id = ?",
        [$userId]
    );
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

