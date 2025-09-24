<?php
// api/add_to_cart.php (Bonus - for adding items to cart)
require_once 'common.php';

try {
    $db = getDBConnection();
    
    // Get JSON input
    $input = json_decode(file_get_contents('php://input'));
    
    // Validate input
    $validation_error = validateInput($input, ['user_id', 'voucher_id']);
    if ($validation_error) {
        sendResponse(false, $validation_error);
    }
    
    $user_id = (int)$input->user_id;
    $voucher_id = (int)$input->voucher_id;
    $quantity = isset($input->quantity) ? (int)$input->quantity : 1;
    
    // Validate quantity
    if ($quantity < 1 || $quantity > 99) {
        sendResponse(false, 'Quantity must be between 1 and 99');
    }
    
    // Start transaction
    $db->beginTransaction();
    
    try {
        // Check if voucher exists and get its details
        $voucher_query = "SELECT id, title, points FROM voucher WHERE id = :voucher_id";
        $voucher_stmt = $db->prepare($voucher_query);
        $voucher_stmt->bindParam(':voucher_id', $voucher_id, PDO::PARAM_INT);
        $voucher_stmt->execute();
        
        $voucher = $voucher_stmt->fetch(PDO::FETCH_ASSOC);
        if (!$voucher) {
            if ($db->inTransaction()) {
                $db->rollBack();
            }
            sendResponse(false, 'Voucher not found');
        }
        
        // Check if item already exists in cart
        $existing_query = "SELECT id, quantity FROM cart_items WHERE user_id = :user_id AND voucher_id = :voucher_id";
        $existing_stmt = $db->prepare($existing_query);
        $existing_stmt->bindParam(':user_id', $user_id, PDO::PARAM_INT);
        $existing_stmt->bindParam(':voucher_id', $voucher_id, PDO::PARAM_INT);
        $existing_stmt->execute();
        
        $existing_item = $existing_stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($existing_item) {
            // Update existing item
            $new_quantity = $existing_item['quantity'] + $quantity;
            if ($new_quantity > 99) {
                $new_quantity = 99;
            }
            
            $update_query = "UPDATE cart_items SET quantity = :quantity WHERE id = :id";
            $update_stmt = $db->prepare($update_query);
            $update_stmt->bindParam(':quantity', $new_quantity, PDO::PARAM_INT);
            $update_stmt->bindParam(':id', $existing_item['id'], PDO::PARAM_INT);
            $update_stmt->execute();
            
            $item_id = $existing_item['id'];
            $message = 'Item quantity updated in cart';
        } else {
            // Add new item
            $insert_query = "INSERT INTO cart_items (user_id, voucher_id, quantity) VALUES (:user_id, :voucher_id, :quantity)";
            $insert_stmt = $db->prepare($insert_query);
            $insert_stmt->bindParam(':user_id', $user_id, PDO::PARAM_INT);
            $insert_stmt->bindParam(':voucher_id', $voucher_id, PDO::PARAM_INT);
            $insert_stmt->bindParam(':quantity', $quantity, PDO::PARAM_INT);
            $insert_stmt->execute();
            
            $item_id = $db->lastInsertId();
            $message = 'Item added to cart successfully';
        }
        
        $db->commit();
        
        // Get updated cart count and total
        $summary_query = "SELECT 
                            COUNT(*) as cart_count,
                            SUM(ci.quantity * COALESCE(v.points, 1000)) as total_points
                          FROM cart_items ci
                          LEFT JOIN voucher v ON ci.voucher_id = v.id
                          WHERE ci.user_id = :user_id";
        $summary_stmt = $db->prepare($summary_query);
        $summary_stmt->bindParam(':user_id', $user_id, PDO::PARAM_INT);
        $summary_stmt->execute();
        $summary = $summary_stmt->fetch(PDO::FETCH_ASSOC);
        
        closeConnection($db);
        sendResponse(true, $message, [
            'item_id' => $item_id,
            'quantity' => $new_quantity ?? $quantity,
            'cart_summary' => [
                'cart_count' => (int)$summary['cart_count'],
                'total_points' => (int)($summary['total_points'] ?? 0),
                'total_price' => (float)($summary['total_price'] ?? 0)
            ]
        ]);
        
    } catch (Exception $e) {
        if ($db->inTransaction()) {
            $db->rollBack();
        }
        throw $e;
    }
    
} catch (Exception $e) {
    logApiError("Add to cart error", ['user_id' => $user_id ?? 'unknown', 'voucher_id' => $voucher_id ?? 'unknown', 'error' => $e->getMessage()]);
    sendResponse(false, $e->getMessage());
}
?>