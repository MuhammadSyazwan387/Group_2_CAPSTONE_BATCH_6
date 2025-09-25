<?php
// filepath: c:\xampp\htdocs\capstone\Group_2_CAPSTONE_BATCH_6\profile_change_password.php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Method not allowed']);
    exit;
}

require_once 'config.php';

$input = json_decode(file_get_contents('php://input'), true);
if (!$input) {
    $input = $_POST;
}

$userId = isset($input['user_id']) ? (int)$input['user_id'] : 0;
$currentPassword = $input['current_password'] ?? '';
$newPassword = $input['new_password'] ?? '';
$confirmPassword = $input['confirm_password'] ?? '';

if ($userId <= 0) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Invalid user ID provided.']);
    exit;
}

if ($newPassword === '' || strlen($newPassword) < 6) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'New password must be at least 6 characters long.']);
    exit;
}

if ($newPassword !== $confirmPassword) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'New password and confirmation do not match.']);
    exit;
}

try {
    $pdo = getDBConnection();

    $stmt = $pdo->prepare('SELECT password FROM users WHERE id = ? LIMIT 1');
    $stmt->execute([$userId]);
    $user = $stmt->fetch();

    if (!$user) {
        http_response_code(404);
        echo json_encode(['success' => false, 'message' => 'User not found.']);
        exit;
    }

    $storedPassword = $user['password'];

    if (!empty($storedPassword)) {
        if ($currentPassword === '') {
            http_response_code(400);
            echo json_encode(['success' => false, 'message' => 'Current password is required.']);
            exit;
        }

        if (!password_verify($currentPassword, $storedPassword)) {
            http_response_code(401);
            echo json_encode(['success' => false, 'message' => 'Current password is incorrect.']);
            exit;
        }
    } else {
        // For accounts without a password (e.g., Google sign-in), allow setting new password without current password
        if ($currentPassword !== '') {
            // Optional: warn that current password was ignored
            error_log('Profile change password: current password provided but ignored for password-less account.');
        }
    }

    $hashedPassword = password_hash($newPassword, PASSWORD_DEFAULT);

    $updateStmt = $pdo->prepare('UPDATE users SET password = ? WHERE id = ?');
    $updateStmt->execute([$hashedPassword, $userId]);

    echo json_encode(['success' => true, 'message' => 'Password updated successfully.']);
} catch (PDOException $e) {
    error_log('Password change error: ' . $e->getMessage());
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'Database error occurred while updating the password.'
    ]);
} catch (Exception $e) {
    error_log('Password change general error: ' . $e->getMessage());
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'An unexpected error occurred while updating the password.'
    ]);
}
