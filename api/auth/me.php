<?php
/**
 * Get Current User API
 */

header('Content-Type: application/json');
require_once __DIR__ . '/../../config/config.php';
require_once __DIR__ . '/../../includes/auth.php';

requireLogin();

$user = getCurrentUser();
$roles = getUserRoles($_SESSION['user_id']);

echo json_encode([
    'success' => true,
    'user' => $user,
    'roles' => $roles
]);

