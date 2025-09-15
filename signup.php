<?php
// filepath: c:\xampp\htdocs\Group_2_CAPSTONE_BATCH_6\signup.php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST');
header('Access-Control-Allow-Headers: Content-Type');

require_once 'config.php';

// Enable error logging for debugging
error_reporting(E_ALL);
ini_set('log_errors', 1);
ini_set('error_log', 'signup_errors.log');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Method not allowed']);
    exit;
}

$input = json_decode(file_get_contents('php://input'), true);

if (!$input) {
    $input = $_POST;
}

// Log received data for debugging
error_log("Signup attempt - Input data: " . json_encode($input));

$fullname = trim($input['fullname'] ?? '');
$email = trim($input['email'] ?? '');
$password = $input['password'] ?? '';
$confirmPassword = $input['confirmPassword'] ?? '';

// Validation
if (empty($fullname) || empty($email) || empty($password) || empty($confirmPassword)) {
    echo json_encode(['success' => false, 'message' => 'All fields are required']);
    exit;
}

if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    echo json_encode(['success' => false, 'message' => 'Invalid email format']);
    exit;
}

if ($password !== $confirmPassword) {
    echo json_encode(['success' => false, 'message' => 'Passwords do not match']);
    exit;
}

if (strlen($password) < 6) {
    echo json_encode(['success' => false, 'message' => 'Password must be at least 6 characters long']);
    exit;
}

try {
    $pdo = getDBConnection();
    error_log("Database connection successful");
    
    // Check if email already exists
    $stmt = $pdo->prepare("SELECT id FROM users WHERE email = ?");
    $stmt->execute([$email]);
    
    if ($stmt->fetch()) {
        echo json_encode(['success' => false, 'message' => 'Email already registered']);
        exit;
    }
    
    // Hash password
    $hashedPassword = password_hash($password, PASSWORD_DEFAULT);
    error_log("Password hashed successfully");
    
    // Insert new user with explicit column names
    $sql = "INSERT INTO users (email, password, fullname, is_active, points, created_at) VALUES (?, ?, ?, 1, 0, NOW())";
    $stmt = $pdo->prepare($sql);
    
    error_log("Attempting to insert user with email: " . $email);
    $result = $stmt->execute([$email, $hashedPassword, $fullname]);
    
    if ($result) {
        $userId = $pdo->lastInsertId();
        error_log("User inserted successfully with ID: " . $userId);
        
        echo json_encode([
            'success' => true, 
            'message' => 'Account created successfully',
            'user' => [
                'id' => $userId,
                'email' => $email,
                'fullname' => $fullname
            ]
        ]);
    } else {
        $errorInfo = $stmt->errorInfo();
        error_log("Insert failed - Error info: " . json_encode($errorInfo));
        echo json_encode(['success' => false, 'message' => 'Failed to create account', 'debug' => $errorInfo]);
    }
    
} catch (PDOException $e) {
    error_log("Signup PDO error: " . $e->getMessage());
    echo json_encode(['success' => false, 'message' => 'Database error: ' . $e->getMessage()]);
} catch (Exception $e) {
    error_log("Signup general error: " . $e->getMessage());
    echo json_encode(['success' => false, 'message' => 'Error occurred: ' . $e->getMessage()]);
}
?>