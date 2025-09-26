<?php
header('Content-Type: application/json'); // Fixed typo from '1ontent-Type'
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST');
header('Access-Control-Allow-Headers: Content-Type');

require_once 'config.php';
require_once 'google_config.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Method not allowed']);
    exit;
}

$input = json_decode(file_get_contents('php://input'), true);

if (!$input) {
    $input = $_POST;
}

$googleToken = $input['google_token'] ?? '';

if (empty($googleToken)) {
    echo json_encode(['success' => false, 'message' => 'Google token is required']);
    exit;
}

try {
    // Verify Google token
    $googleUser = verifyGoogleToken($googleToken);
    
    if (!$googleUser) {
        echo json_encode(['success' => false, 'message' => 'Invalid Google token']);
        exit;
    }
    
    $pdo = getDBConnection();
    
    // Check if user exists
    $stmt = $pdo->prepare("SELECT id, email, fullname, is_active, points FROM users WHERE email = ? OR google_id = ?");
    $stmt->execute([$googleUser['email'], $googleUser['id']]);
    $existingUser = $stmt->fetch();
    
    if ($existingUser) {
        // User exists, update Google ID if not set
        if (empty($existingUser['google_id'])) {
            $updateStmt = $pdo->prepare("UPDATE users SET google_id = ? WHERE id = ?");
            $updateStmt->execute([$googleUser['id'], $existingUser['id']]);
        }
        
        // Check if account is active
        if (!$existingUser['is_active']) {
            echo json_encode(['success' => false, 'message' => 'Account is deactivated. Please contact support.']);
            exit;
        }
        
        // Return existing user
        echo json_encode([
            'success' => true,
            'message' => 'Login successful',
            'user' => [
                'id' => $existingUser['id'],
                'email' => $existingUser['email'],
                'fullname' => $existingUser['fullname'],
                'points' => $existingUser['points'] ?? 0
            ]
        ]);
    } else {
        // Create new user
        $sql = "INSERT INTO users (email, fullname, google_id, is_active, points) VALUES (?, ?, ?, 1, 0)";
        $stmt = $pdo->prepare($sql);
        $result = $stmt->execute([$googleUser['email'], $googleUser['name'], $googleUser['id']]);
        
        if ($result) {
            $userId = $pdo->lastInsertId();
            
            echo json_encode([
                'success' => true,
                'message' => 'Account created successfully',
                'user' => [
                    'id' => $userId,
                    'email' => $googleUser['email'],
                    'fullname' => $googleUser['name'],
                    'points' => 0
                ]
            ]);
        } else {
            echo json_encode(['success' => false, 'message' => 'Failed to create account']);
        }
    }
    
} catch (Exception $e) {
    error_log("Google auth error: " . $e->getMessage());
    echo json_encode(['success' => false, 'message' => 'Authentication error occurred']);
}

function verifyGoogleToken($token) {
    // Verify token with Google
    $url = 'https://oauth2.googleapis.com/tokeninfo?id_token=' . $token;
    
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
    curl_setopt($ch, CURLOPT_TIMEOUT, 30);
    
    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $curlError = curl_error($ch);
    curl_close($ch);
    
    // Log detailed error information
    error_log("Google token verification - HTTP Code: $httpCode");
    error_log("Google token verification - Response: $response");
    if ($curlError) {
        error_log("Google token verification - cURL Error: $curlError");
    }
    
    if ($httpCode !== 200) {
        error_log("Google token verification failed with HTTP code: $httpCode");
        return false;
    }
    
    $data = json_decode($response, true);
    
    if (!$data) {
        error_log("Google token verification - Invalid JSON response");
        return false;
    }
    
    // Verify the token is for our app
    if (!isset($data['aud']) || $data['aud'] !== GOOGLE_CLIENT_ID) {
        error_log("Google token verification - Client ID mismatch. Expected: " . GOOGLE_CLIENT_ID . ", Got: " . ($data['aud'] ?? 'null'));
        return false;
    }
    
    // Return user data
    return [
        'id' => $data['sub'],
        'email' => $data['email'],
        'name' => $data['name'],
        'picture' => $data['picture'] ?? ''
    ];
}

?>
