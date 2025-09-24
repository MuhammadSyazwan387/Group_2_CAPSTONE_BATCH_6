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
                ci.id,
                ci.user_id,
                ci.voucher_id,
                ci.quantity,
                COALESCE(v.title, 'Voucher Name') as voucher_name,
                COALESCE(v.points, 1000) as points_required,
                v.image as image_url,
                (ci.quantity * COALESCE(v.points, 1000)) as total_points
              FROM cart_items ci
              LEFT JOIN voucher v ON ci.voucher_id = v.id
              WHERE ci.user_id = :user_id
              ORDER BY ci.id DESC";
    
    $stmt = $db->prepare($query);
    $stmt->execute(['user_id' => (int)$user_id]);
    
    $items = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Calculate totals
    $total_items = count($items);
    $total_points = 0;
    $total_price = 0;
    
    foreach ($items as &$item) {
        $total_points += $item['total_points'] ?? 0;
        $total_price += $item['total_price'] ?? 0;
        
        // Ensure numeric values
        $item['quantity'] = (int)$item['quantity'];
        $item['points_required'] = (int)($item['points_required'] ?? 1000);
        $item['total_points'] = (int)($item['total_points'] ?? 0);
    }
    
    $response_data = [
        'items' => $items,
        'summary' => [
            'total_items' => $total_items,
            'total_points' => $total_points,
        ]
    ];
    
    closeConnection($db);
    sendResponse(true, 'Cart items retrieved successfully', $response_data);
    
} catch (Exception $e) {
    logApiError("Get cart error", ['user_id' => $user_id ?? 'unknown', 'error' => $e->getMessage()]);
    sendResponse(false, $e->getMessage());
}
?>