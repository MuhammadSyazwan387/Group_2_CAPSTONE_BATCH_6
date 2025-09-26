<?php
require_once 'common.php';

try {
    $db = getDBConnection();
    
    // Get JSON input
    $input = json_decode(file_get_contents('php://input'));
    
    // Validate input
    $validation_error = validateInput($input, ['id', 'user_id', 'quantity']);
    if ($validation_error) {
        sendResponse(false, $validation_error);
    }
    
    $item_id = (int)$input->id;
    $user_id = (int)$input->user_id;
    $quantity = (int)$input->quantity;
    
    // Validate quantity
    if ($quantity < 1 || $quantity > 99) {
        sendResponse(false, 'Quantity must be between 1 and 99');
    }
    
    // Start transaction
    $db->beginTransaction();
    
    try {
        // Check if item exists and belongs to user
        $check_query = "SELECT id, voucher_id FROM cart_items WHERE id = :id AND user_id = :user_id";
        $check_stmt = $db->prepare($check_query);
        $check_stmt->bindParam(':id', $item_id, PDO::PARAM_INT);
        $check_stmt->bindParam(':user_id', $user_id, PDO::PARAM_INT);
        $check_stmt->execute();
        
        $item = $check_stmt->fetch(PDO::FETCH_ASSOC);
        if (!$item) {
            $db->rollBack();
            sendResponse(false, 'Item not found in your cart');
        }
        
        // Update quantity
        $update_query = "UPDATE cart_items SET quantity = :quantity WHERE id = :id AND user_id = :user_id";
        $update_stmt = $db->prepare($update_query);
        $update_stmt->bindParam(':quantity', $quantity, PDO::PARAM_INT);
        $update_stmt->bindParam(':id', $item_id, PDO::PARAM_INT);
        $update_stmt->bindParam(':user_id', $user_id, PDO::PARAM_INT);
        
        if ($update_stmt->execute()) {
            $db->commit();
            
            // Get updated item details
            $item_query = "SELECT 
                            ci.*,
                            COALESCE(v.name, 'Voucher Name') as voucher_name,
                            COALESCE(v.points_required, 1000) as points_required,
                            COALESCE(v.price, 10000) as price,
                            (ci.quantity * COALESCE(v.points_required, 1000)) as total_points,
                            (ci.quantity * COALESCE(v.price, 10000)) as total_price
                           FROM cart_items ci
                           LEFT JOIN vouchers v ON ci.voucher_id = v.id
                           WHERE ci.id = :id";
            $item_stmt = $db->prepare($item_query);
            $item_stmt->bindParam(':id', $item_id, PDO::PARAM_INT);
            $item_stmt->execute();
            $updated_item = $item_stmt->fetch(PDO::FETCH_ASSOC);
            
            closeConnection($db);
            sendResponse(true, 'Quantity updated successfully', $updated_item);
        } else {
            $db->rollBack();
            sendResponse(false, 'Failed to update quantity');
        }
        
    } catch (Exception $e) {
        $db->rollBack();
        throw $e;
    }
    
} catch (Exception $e) {
    logApiError("Update cart quantity error", ['item_id' => $item_id ?? 'unknown', 'user_id' => $user_id ?? 'unknown', 'error' => $e->getMessage()]);
    sendResponse(false, 'Error updating quantity');
}
?>