<?php
require_once 'common.php';

try {
    $db = getDBConnection();
    
    // Get JSON input
    $input = json_decode(file_get_contents('php://input'));
    
    // Validate input
    $validation_error = validateInput($input, ['id', 'user_id']);
    if ($validation_error) {
        sendResponse(false, $validation_error);
    }
    
    $item_id = (int)$input->id;
    $user_id = (int)$input->user_id;
    
    // Start transaction
    $db->beginTransaction();
    
    try {
        // First check if item exists and belongs to user
        $check_query = "SELECT id FROM cart_items WHERE id = :id AND user_id = :user_id";
        $check_stmt = $db->prepare($check_query);
        $check_stmt->bindParam(':id', $item_id, PDO::PARAM_INT);
        $check_stmt->bindParam(':user_id', $user_id, PDO::PARAM_INT);
        $check_stmt->execute();
        
        if ($check_stmt->rowCount() === 0) {
            $db->rollBack();
            sendResponse(false, 'Item not found in your cart');
        }
        
        // Delete the item
        $delete_query = "DELETE FROM cart_items WHERE id = :id AND user_id = :user_id";
        $delete_stmt = $db->prepare($delete_query);
        $delete_stmt->bindParam(':id', $item_id, PDO::PARAM_INT);
        $delete_stmt->bindParam(':user_id', $user_id, PDO::PARAM_INT);
        
        if ($delete_stmt->execute()) {
            $db->commit();
            
            // Get updated cart count
            $count_query = "SELECT COUNT(*) as count FROM cart_items WHERE user_id = :user_id";
            $count_stmt = $db->prepare($count_query);
            $count_stmt->bindParam(':user_id', $user_id, PDO::PARAM_INT);
            $count_stmt->execute();
            $cart_count = $count_stmt->fetch(PDO::FETCH_ASSOC)['count'];
            
            closeConnection($db);
            sendResponse(true, 'Item removed from cart successfully', ['cart_count' => $cart_count]);
        } else {
            $db->rollBack();
            sendResponse(false, 'Failed to remove item from cart');
        }
        
    } catch (Exception $e) {
        $db->rollBack();
        throw $e;
    }
    
} catch (Exception $e) {
    logApiError("Remove cart item error", ['item_id' => $item_id ?? 'unknown', 'user_id' => $user_id ?? 'unknown', 'error' => $e->getMessage()]);
    sendResponse(false, 'Error removing item from cart');
}
?>