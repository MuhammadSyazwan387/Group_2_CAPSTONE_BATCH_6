<?php
// filepath: c:\xampp\htdocs\Group_2_CAPSTONE_BATCH_6\update_user.php
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
$fullname = trim($input['fullname'] ?? '');
$phone = trim($input['phone_number'] ?? '');
$address = trim($input['address'] ?? '');
$about = trim($input['about_me'] ?? '');

if ($userId <= 0) {
    respond(['success' => false, 'message' => 'Invalid user identifier'], 400);
}

if ($fullname === '') {
    respond(['success' => false, 'message' => 'Full name is required'], 400);
}

if (strlen($fullname) > 50) {
    respond(['success' => false, 'message' => 'Full name must be 50 characters or less'], 400);
}

if ($phone !== '' && strlen($phone) > 20) {
    respond(['success' => false, 'message' => 'Phone number is too long'], 400);
}

if (strlen($address) > 255) {
    respond(['success' => false, 'message' => 'Address must be 255 characters or less'], 400);
}

if (strlen($about) > 255) {
    respond(['success' => false, 'message' => 'About me must be 255 characters or less'], 400);
}

try {
    $pdo = getDBConnection();

    $stmt = $pdo->prepare('SELECT id FROM users WHERE id = ? LIMIT 1');
    $stmt->execute([$userId]);
    if (!$stmt->fetchColumn()) {
        respond(['success' => false, 'message' => 'User not found'], 404);
    }

    $update = $pdo->prepare('UPDATE users SET fullname = :fullname, phone_number = :phone, address = :address, about_me = :about WHERE id = :id');
    $update->execute([
        ':fullname' => $fullname,
        ':phone' => $phone,
        ':address' => $address,
        ':about' => $about,
        ':id' => $userId,
    ]);

    $fetch = $pdo->prepare('SELECT phone_number, fullname, email, profile_image, address, points, about_me FROM users WHERE id = :id LIMIT 1');
    $fetch->execute([':id' => $userId]);
    $updatedUser = $fetch->fetch(PDO::FETCH_ASSOC);

    respond([
        'success' => true,
        'message' => 'Profile updated successfully',
        'data' => $updatedUser,
    ]);
} catch (PDOException $e) {
    error_log('Update user error: ' . $e->getMessage());
    respond(['success' => false, 'message' => 'Database error occurred'], 500);
} catch (Exception $e) {
    error_log('Update user fatal error: ' . $e->getMessage());
    respond(['success' => false, 'message' => 'Server error occurred'], 500);
}
