<?php
// filepath: c:\xampp\htdocs\Group_2_CAPSTONE_BATCH_6\upload_profile_picture.php
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

$userId = isset($_POST['user_id']) ? (int)$_POST['user_id'] : 0;

if ($userId <= 0) {
    respond(['success' => false, 'message' => 'Invalid user identifier'], 400);
}

if (!isset($_FILES['profile_image']) || $_FILES['profile_image']['error'] !== UPLOAD_ERR_OK) {
    respond(['success' => false, 'message' => 'Profile image upload failed'], 400);
}

$file = $_FILES['profile_image'];
$allowedMime = ['image/jpeg', 'image/png', 'image/gif', 'image/webp'];
$detectedType = mime_content_type($file['tmp_name']);

if (!in_array($detectedType, $allowedMime, true)) {
    respond(['success' => false, 'message' => 'Unsupported image format'], 400);
}

$extensionMap = [
    'image/jpeg' => 'jpg',
    'image/png' => 'png',
    'image/gif' => 'gif',
    'image/webp' => 'webp',
];
$extension = $extensionMap[$detectedType] ?? 'jpg';

$uploadDir = __DIR__ . DIRECTORY_SEPARATOR . 'images' . DIRECTORY_SEPARATOR . 'uploads';
if (!is_dir($uploadDir)) {
    if (!mkdir($uploadDir, 0775, true) && !is_dir($uploadDir)) {
        respond(['success' => false, 'message' => 'Failed to prepare upload directory'], 500);
    }
}

$filename = sprintf('user_%d_%s.%s', $userId, uniqid('', true), $extension);
$destination = $uploadDir . DIRECTORY_SEPARATOR . $filename;

if (!move_uploaded_file($file['tmp_name'], $destination)) {
    respond(['success' => false, 'message' => 'Failed to save uploaded image'], 500);
}

$relativePath = 'images/uploads/' . $filename;

try {
    $pdo = getDBConnection();

    $pdo->beginTransaction();

    $select = $pdo->prepare('SELECT profile_image FROM users WHERE id = :id LIMIT 1');
    $select->execute([':id' => $userId]);
    $existing = $select->fetch(PDO::FETCH_ASSOC);

    if (!$existing) {
        $pdo->rollBack();
        @unlink($destination);
        respond(['success' => false, 'message' => 'User not found'], 404);
    }

    $previousImage = $existing['profile_image'] ?? null;

    $update = $pdo->prepare('UPDATE users SET profile_image = :path WHERE id = :id');
    $update->execute([
        ':path' => $relativePath,
        ':id' => $userId,
    ]);

    $pdo->commit();

    if (is_string($previousImage) && strpos($previousImage, 'images/uploads/') === 0) {
        $oldFile = __DIR__ . DIRECTORY_SEPARATOR . str_replace(['/', '\\'], DIRECTORY_SEPARATOR, $previousImage);
        if (is_file($oldFile) && $oldFile !== $destination) {
            @unlink($oldFile);
        }
    }

    respond([
        'success' => true,
        'message' => 'Profile picture updated successfully',
        'data' => [
            'profile_image' => $relativePath,
        ],
    ]);
} catch (PDOException $e) {
    if (isset($pdo) && $pdo->inTransaction()) {
        $pdo->rollBack();
    }
    error_log('Upload profile picture error: ' . $e->getMessage());
    respond(['success' => false, 'message' => 'Database error occurred'], 500);
} catch (Exception $e) {
    if (isset($pdo) && $pdo->inTransaction()) {
        $pdo->rollBack();
    }
    error_log('Upload profile picture fatal error: ' . $e->getMessage());
    respond(['success' => false, 'message' => 'Server error occurred'], 500);
}
