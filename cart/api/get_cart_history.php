<?php
// api/get_cart_history.php
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
    $limit = isset($input->limit) ? (int)$input->limit : 50;
    $offset = isset($input->offset) ? (int)$input->offset : 0;
    
    // Get cart history
    $history_query = "SELECT 
                        ch.*,
                        v.title as voucher_title,
                        v.image as voucher_image
                      FROM cart_item_history ch
                      LEFT JOIN voucher v ON ch.voucher_id = v.id
                      WHERE ch.user_id = :user_id
                      ORDER BY ch.completed_date DESC
                      LIMIT :limit OFFSET :offset";
    
    $history_stmt = $db->prepare($history_query);
    $history_stmt->bindParam(':user_id', $user_id, PDO::PARAM_INT);
    $history_stmt->bindParam(':limit', $limit, PDO::PARAM_INT);
    $history_stmt->bindParam(':offset', $offset, PDO::PARAM_INT);
    $history_stmt->execute();
    
    $history_items = $history_stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Get total count
    $count_query = "SELECT COUNT(*) as total FROM cart_item_history WHERE user_id = :user_id";
    $count_stmt = $db->prepare($count_query);
    $count_stmt->bindParam(':user_id', $user_id, PDO::PARAM_INT);
    $count_stmt->execute();
    $total_count = $count_stmt->fetch(PDO::FETCH_ASSOC)['total'];
    
    closeConnection($db);
    sendResponse(true, 'Cart history retrieved successfully', [
        'items' => $history_items,
        'pagination' => [
            'total' => (int)$total_count,
            'limit' => $limit,
            'offset' => $offset,
            'has_more' => ($offset + $limit) < $total_count
        ]
    ]);
    
} catch (Exception $e) {
    logApiError("Get cart history error", ['user_id' => $user_id ?? 'unknown', 'error' => $e->getMessage()]);
    sendResponse(false, $e->getMessage());
}
?>