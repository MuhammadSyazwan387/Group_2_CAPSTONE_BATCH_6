<?php
// filepath: c:\xampp\htdocs\capstone\Group_2_CAPSTONE_BATCH_6\profile_get.php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    exit;
}

require_once 'config.php';

$input = json_decode(file_get_contents('php://input'), true);
if (!$input) {
    $input = $_POST;
}

$userId = isset($input['user_id']) ? (int)$input['user_id'] : 0;

if ($userId <= 0) {
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'message' => 'Invalid user ID provided.'
    ]);
    exit;
}

try {
    $pdo = getDBConnection();

    $stmt = $pdo->prepare('SELECT id, email, fullname, phone_number, profile_image, points, address, about_me, created_at FROM users WHERE id = ? LIMIT 1');
    $stmt->execute([$userId]);
    $user = $stmt->fetch();

    if (!$user) {
        http_response_code(404);
        echo json_encode([
            'success' => false,
            'message' => 'User not found.'
        ]);
        exit;
    }

    // If profile image exists (stored as blob), convert to base64 data URI
    $profileImage = null;
    if (!empty($user['profile_image'])) {
        $profileImage = 'data:image/png;base64,' . base64_encode($user['profile_image']);
    }

    // Get recent redeemed vouchers (limit 10 for table, 3 for summary)
    $historyStmt = $pdo->prepare('
        SELECT v.title, h.completed_date, h.quantity
        FROM cart_item_history h
        JOIN voucher v ON v.id = h.voucher_id
        WHERE h.user_id = ?
        ORDER BY h.completed_date DESC, h.id DESC
        LIMIT 10
    ');
    $historyStmt->execute([$userId]);
    $historyRows = $historyStmt->fetchAll();

    $redeemedTotal = 0;
    foreach ($historyRows as $row) {
        $redeemedTotal += (int)($row['quantity'] ?? 0);
    }

    $recentRedeemed = array_slice($historyRows, 0, 3);

    echo json_encode([
        'success' => true,
        'data' => [
            'id' => (int)$user['id'],
            'email' => $user['email'],
            'fullname' => $user['fullname'],
            'phone_number' => $user['phone_number'],
            'address' => $user['address'],
            'about_me' => $user['about_me'],
            'points' => (int)$user['points'],
            'profile_image' => $profileImage,
            'created_at' => $user['created_at'],
            'redeemed_total' => $redeemedTotal,
            'redeemed_recent' => array_map(function ($row) {
                return [
                    'title' => $row['title'],
                    'completed_date' => $row['completed_date'],
                    'quantity' => (int)$row['quantity']
                ];
            }, $recentRedeemed),
            'redeemed_history' => array_map(function ($row) {
                return [
                    'title' => $row['title'],
                    'completed_date' => $row['completed_date'],
                    'quantity' => (int)$row['quantity']
                ];
            }, $historyRows)
        ]
    ]);
} catch (PDOException $e) {
    error_log('Profile fetch error: ' . $e->getMessage());
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'Database error occurred while fetching profile information.'
    ]);
} catch (Exception $e) {
    error_log('Profile fetch general error: ' . $e->getMessage());
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'An unexpected error occurred while fetching profile information.'
    ]);
}