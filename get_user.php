<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');

// Database connection
$servername = "localhost";
$username   = "root";
$password   = "";
$dbname     = "optima_bank";

try {
    $pdo = new PDO("mysql:host=$servername;dbname=$dbname", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    // Get JSON input
    $input = json_decode(file_get_contents('php://input'));

    // Validate input
    if (!isset($input->user_id) || !is_numeric($input->user_id)) {
        echo json_encode([
            "success" => false,
            "message" => "Invalid or missing user_id"
        ]);
        exit;
    }

    $user_id = (int) $input->user_id;

    // Fetch user information
    $query = "SELECT phone_number, fullname, email, profile_image, address, points, about_me
              FROM users 
              WHERE id = :user_id LIMIT 1";

    $stmt = $pdo->prepare($query);
    $stmt->bindParam(':user_id', $user_id, PDO::PARAM_INT);
    $stmt->execute();

    if ($stmt->rowCount() === 0) {
        echo json_encode([
            "success" => false,
            "message" => "User not found"
        ]);
        exit;
    }

    $user = $stmt->fetch(PDO::FETCH_ASSOC);

    // Return response
    echo json_encode([
        "success" => true,
        "message" => "User information fetched successfully",
        "data"    => $user
    ]);

} catch (Exception $e) {
    echo json_encode([
        "success" => false,
        "message" => "Error: " . $e->getMessage()
    ]);
} finally {
    $pdo = null; // Close connection
}
?>