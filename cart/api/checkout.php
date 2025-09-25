<?php
// api/checkout.php
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

    $user_id = (int) $input->user_id;

    // Start transaction
    $db->beginTransaction();

    try {
        // Get cart items with voucher details and lock rows for update
        $cart_query = "SELECT 
                        ci.id,
                        ci.voucher_id,
                        ci.user_id,
                        ci.quantity,
                        v.title as voucher_name,
                        v.points as points_required,
                        v.description,
                        v.image,
                        v.terms_and_condition,
                        v.total_redeem,
                        (ci.quantity * v.points) as total_points
                       FROM cart_items ci
                       LEFT JOIN voucher v ON ci.voucher_id = v.id
                       WHERE ci.user_id = :user_id
                       ORDER BY ci.id
                       FOR UPDATE"; // Lock rows for update

        $cart_stmt = $db->prepare($cart_query);
        $cart_stmt->bindParam(':user_id', $user_id, PDO::PARAM_INT);
        $cart_stmt->execute();

        $cart_items = $cart_stmt->fetchAll(PDO::FETCH_ASSOC);

        if (empty($cart_items)) {
            $db->rollBack();
            sendResponse(false, 'Your cart is empty');
        }

        // Calculate total points needed
        $total_points = 0;
        $order_items = [];

        foreach ($cart_items as $item) {
            $total_points += $item['total_points'];

            $order_items[] = [
                'cart_item_id' => $item['id'],
                'voucher_id' => $item['voucher_id'],
                'voucher_name' => $item['voucher_name'],
                'quantity' => $item['quantity'],
                'points_per_item' => $item['points_required'],
                'total_points' => $item['total_points'],
                'description' => $item['description'],
                'image' => $item['image']
            ];
        }

        // Step 1: Get user's available points
        $user_query = "SELECT points FROM users WHERE id = :user_id FOR UPDATE";
        // FOR UPDATE to lock row so points don’t get modified in parallel
        $user_stmt = $db->prepare($user_query);
        $user_stmt->bindParam(':user_id', $user_id, PDO::PARAM_INT);
        $user_stmt->execute();
        $user = $user_stmt->fetch(PDO::FETCH_ASSOC);

        if (!$user) {
            $db->rollBack();
            sendResponse(false, 'User not found');
        }

        $available_points = (int) $user['points'];

        // Step 2: Check if enough points
        if ($available_points < $total_points) {
            $db->rollBack();
            sendResponse(false, 'Insufficient points. You need ' . $total_points . ' but only have ' . $available_points);
        }

        // Step 3: Deduct points
        $deduct_query = "UPDATE users SET points = points - :deduct WHERE id = :user_id";
        $deduct_stmt = $db->prepare($deduct_query);
        $deduct_stmt->bindParam(':deduct', $total_points, PDO::PARAM_INT);
        $deduct_stmt->bindParam(':user_id', $user_id, PDO::PARAM_INT);
        $deduct_stmt->execute();

        // Move cart items to cart_item_history BEFORE clearing cart
        foreach ($cart_items as $item) {
            $history_query = "INSERT INTO cart_item_history (
                                voucher_id,
                                user_id,
                                quantity,
                                completed_date
                            ) VALUES (
                                :voucher_id,
                                :user_id,
                                :quantity,
                                NOW()
                            )";

            $history_stmt = $db->prepare($history_query);
            $history_stmt->bindParam(':voucher_id', $item['voucher_id'], PDO::PARAM_INT);
            $history_stmt->bindParam(':user_id', $item['user_id'], PDO::PARAM_INT);
            $history_stmt->bindParam(':quantity', $item['quantity'], PDO::PARAM_INT);
            $history_stmt->execute();
        }

        // Update voucher total_redeem count
        foreach ($cart_items as $item) {
            $update_redeem_query = "UPDATE voucher SET total_redeem = total_redeem + :quantity WHERE id = :voucher_id";
            $update_redeem_stmt = $db->prepare($update_redeem_query);
            $update_redeem_stmt->bindParam(':quantity', $item['quantity'], PDO::PARAM_INT);
            $update_redeem_stmt->bindParam(':voucher_id', $item['voucher_id'], PDO::PARAM_INT);
            $update_redeem_stmt->execute();
        }

        // Clear cart items AFTER adding to history
        $clear_cart_query = "DELETE FROM cart_items WHERE user_id = :user_id";
        $clear_cart_stmt = $db->prepare($clear_cart_query);
        $clear_cart_stmt->bindParam(':user_id', $user_id, PDO::PARAM_INT);
        $clear_cart_stmt->execute();

        $db->commit();

        // Send success response with checkout details
        closeConnection($db);
        sendResponse(true, 'Checkout completed successfully', [
            'total_items' => count($order_items),
            'total_points' => $total_points,
            'items' => $order_items,
            'checkout_date' => date('Y-m-d H:i:s'),
            'message' => 'Items have been moved to your history and vouchers are ready for use',
        ]);

    } catch (Exception $e) {
        $db->rollBack();
        throw $e;
    }

} catch (Exception $e) {
    logApiError("Checkout error", ['user_id' => $user_id ?? 'unknown', 'error' => $e->getMessage()]);
    sendResponse(false, 'Error processing checkout: ' . $e->getMessage());
}
?>