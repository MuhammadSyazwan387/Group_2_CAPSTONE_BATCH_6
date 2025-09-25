<?php
// filepath: c:\xampp\htdocs\capstone\Group_2_CAPSTONE_BATCH_6\profile_update.php
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
$fullname = isset($input['fullname']) ? trim($input['fullname']) : '';
$phoneNumber = isset($input['phone_number']) ? trim($input['phone_number']) : '';
$address = isset($input['address']) ? trim($input['address']) : '';
$aboutMe = isset($input['about_me']) ? trim($input['about_me']) : '';

if ($userId <= 0) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Invalid user ID provided.']);
    exit;
}

if ($fullname === '') {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Full name is required.']);
    exit;
}

if ($phoneNumber !== '' && !preg_match('/^\+?[0-9]{6,15}$/', $phoneNumber)) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Phone number must contain 6-15 digits and may start with +.']);
    exit;
}

if (strlen($address) > 255) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Address is too long.']);
    exit;
}

if (strlen($aboutMe) > 500) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'About me must be 500 characters or fewer.']);
    exit;
}

try {
    $pdo = getDBConnection();

    // Ensure user exists
    $stmt = $pdo->prepare('SELECT id FROM users WHERE id = ? LIMIT 1');
    $stmt->execute([$userId]);

    if (!$stmt->fetch()) {
        http_response_code(404);
        echo json_encode(['success' => false, 'message' => 'User not found.']);
        exit;
    }

    $updateStmt = $pdo->prepare('UPDATE users SET fullname = ?, phone_number = ?, address = ?, about_me = ? WHERE id = ?');
    $updateStmt->execute([
        $fullname,
        $phoneNumber,
        $address,
        $aboutMe,
        $userId
    ]);

    $refreshStmt = $pdo->prepare('SELECT id, email, fullname, phone_number, address, about_me, points FROM users WHERE id = ? LIMIT 1');
    $refreshStmt->execute([$userId]);
    $updatedUser = $refreshStmt->fetch();

    echo json_encode([
        'success' => true,
        'message' => 'Profile updated successfully.',
        'data' => $updatedUser
    ]);
} catch (PDOException $e) {
    error_log('Profile update error: ' . $e->getMessage());

    if ($e->errorInfo[1] === 1062) { // Duplicate entry
        $duplicateField = strpos($e->getMessage(), 'phone_number') !== false ? 'phone number' : 'provided value';
        http_response_code(409);
        echo json_encode([
            'success' => false,
            'message' => 'The ' . $duplicateField . ' is already in use.'
        ]);
    } else {
        http_response_code(500);
        echo json_encode([
            'success' => false,
            'message' => 'Database error occurred while updating the profile.'
        ]);
    }
} catch (Exception $e) {
    error_log('Profile update general error: ' . $e->getMessage());
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'An unexpected error occurred while updating the profile.'
    ]);
}
