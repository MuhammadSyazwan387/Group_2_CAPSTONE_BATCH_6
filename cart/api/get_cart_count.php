<?php
// api/get_cart.php
require_once 'common.php';

try {
    $db = getDBConnection();
    
    // Get JSON input
    $input = json_decode(file_get_contents('php://input'));
    
    // Validate input
    $validation_error = validateInput($input, ['user_id']);
    if ($validation_error) {
        sendResponse(false, $validation_error);
    }
    
    $user_id = (int)$input->user_id;
    
    // Query to get cart items with additional details if needed
    $query = "SELECT 
                COUNT(id) as item_count
              FROM cart_items 
              WHERE user_id = :user_id";
    
    $stmt = $db->prepare($query);
    $stmt->execute(['user_id' => (int)$user_id]);
    
    $result = $stmt->fetchAll(PDO::FETCH_ASSOC);

    
    $response_data = [
        'item_count' => $result[0]['item_count'] ?? 0,
    ];
    
    closeConnection($db);
    sendResponse(true, 'Cart items retrieved successfully', $response_data);
    
} catch (Exception $e) {
    logApiError("Get cart error", ['user_id' => $user_id ?? 'unknown', 'error' => $e->getMessage()]);
    sendResponse(false, $e->getMessage());
}
?>