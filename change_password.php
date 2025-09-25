<?php
// filepath: c:\xampp\htdocs\Group_2_CAPSTONE_BATCH_6\change_password.php
header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(204);
    exit;
}

require_once 'config.php';

function respond(array $payload, int $status = 200): void {
    http_response_code($status);
    echo json_encode($payload, JSON_UNESCAPED_UNICODE);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    respond(['success' => false, 'message' => 'Method not allowed'], 405);
}

$input = json_decode(file_get_contents('php://input'), true);
if (!$input) {
    $input = $_POST;
}

$userId = isset($input['user_id']) ? (int)$input['user_id'] : 0;
$currentPassword = $input['current_password'] ?? '';
$newPassword = $input['new_password'] ?? '';
$confirmPassword = $input['confirm_password'] ?? '';

if ($userId <= 0) {
    respond(['success' => false, 'message' => 'Invalid user identifier'], 400);
}

if ($currentPassword === '' || $newPassword === '' || $confirmPassword === '') {
    respond(['success' => false, 'message' => 'All password fields are required'], 400);
}

if (strlen($newPassword) < 6) {
    respond(['success' => false, 'message' => 'New password must be at least 6 characters long'], 400);
}

if ($newPassword !== $confirmPassword) {
    respond(['success' => false, 'message' => 'New password and confirmation do not match'], 400);
}

try {
    $pdo = getDBConnection();
    $pdo->beginTransaction();

    $stmt = $pdo->prepare('SELECT password FROM users WHERE id = :id LIMIT 1');
    $stmt->execute([':id' => $userId]);
    $row = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$row) {
        $pdo->rollBack();
        respond(['success' => false, 'message' => 'User not found'], 404);
    }

    $storedHash = $row['password'];
    if (!$storedHash || !password_verify($currentPassword, $storedHash)) {
        $pdo->rollBack();
        respond(['success' => false, 'message' => 'Current password is incorrect'], 400);
    }

    $newHash = password_hash($newPassword, PASSWORD_DEFAULT);

    $update = $pdo->prepare('UPDATE users SET password = :password WHERE id = :id');
    $update->execute([
        ':password' => $newHash,
        ':id' => $userId,
    ]);

    $pdo->commit();

    respond([
        'success' => true,
        'message' => 'Password updated successfully',
    ]);
} catch (PDOException $e) {
    if (isset($pdo) && $pdo->inTransaction()) {
        $pdo->rollBack();
    }
    error_log('Change password error: ' . $e->getMessage());
    respond(['success' => false, 'message' => 'Database error occurred'], 500);
} catch (Exception $e) {
    if (isset($pdo) && $pdo->inTransaction()) {
        $pdo->rollBack();
    }
    error_log('Change password fatal error: ' . $e->getMessage());
    respond(['success' => false, 'message' => 'Server error occurred'], 500);
}
