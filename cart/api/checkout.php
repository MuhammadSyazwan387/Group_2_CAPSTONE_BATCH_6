<?php
// api/checkout.php
require_once 'common.php';
require_once 'pdf_generator.php';

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

        // Calculate total points needed and validate vouchers
        $total_points = 0;
        $order_items = [];

        foreach ($cart_items as $item) {
            // Check if voucher still exists and is valid
            if (!$item['voucher_name'] || !$item['points_required']) {
                $db->rollBack();
                sendResponse(false, 'One or more vouchers in your cart are no longer available');
            }

            $item_total = (int)$item['quantity'] * (int)$item['points_required'];
            $total_points += $item_total;

            $order_items[] = [
                'cart_item_id' => (int)$item['id'],
                'voucher_id' => (int)$item['voucher_id'],
                'voucher_name' => $item['voucher_name'],
                'quantity' => (int)$item['quantity'],
                'points_per_item' => (int)$item['points_required'],
                'total_points' => $item_total,
                'description' => $item['description'] ?? '',
                'image' => $item['image'] ?? ''
            ];
        }

        // Get user's available points and user details
        $user_query = "SELECT id, fullname, email, points FROM users WHERE id = :user_id FOR UPDATE";
        $user_stmt = $db->prepare($user_query);
        $user_stmt->bindParam(':user_id', $user_id, PDO::PARAM_INT);
        $user_stmt->execute();
        $user = $user_stmt->fetch(PDO::FETCH_ASSOC);

        if (!$user) {
            $db->rollBack();
            sendResponse(false, 'User not found');
        }

        $available_points = (int) $user['points'];

        // Check if user has enough points
        if ($available_points < $total_points) {
            $db->rollBack();
            sendResponse(false, 'Insufficient points. You need ' . number_format($total_points) . ' but only have ' . number_format($available_points));
        }

        // Deduct points from user
        $deduct_query = "UPDATE users SET points = points - :deduct WHERE id = :user_id";
        $deduct_stmt = $db->prepare($deduct_query);
        $deduct_stmt->bindParam(':deduct', $total_points, PDO::PARAM_INT);
        $deduct_stmt->bindParam(':user_id', $user_id, PDO::PARAM_INT);
        
        if (!$deduct_stmt->execute()) {
            $db->rollBack();
            sendResponse(false, 'Failed to deduct points');
        }

        // Move cart items to cart_item_history
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

        foreach ($cart_items as $item) {
            $history_stmt->bindParam(':voucher_id', $item['voucher_id'], PDO::PARAM_INT);
            $history_stmt->bindParam(':user_id', $item['user_id'], PDO::PARAM_INT);
            $history_stmt->bindParam(':quantity', $item['quantity'], PDO::PARAM_INT);
            
            if (!$history_stmt->execute()) {
                $db->rollBack();
                sendResponse(false, 'Failed to save order history');
            }
        }

        // Update voucher total_redeem count
        $update_redeem_query = "UPDATE voucher SET total_redeem = total_redeem + :quantity WHERE id = :voucher_id";
        $update_redeem_stmt = $db->prepare($update_redeem_query);

        foreach ($cart_items as $item) {
            $update_redeem_stmt->bindParam(':quantity', $item['quantity'], PDO::PARAM_INT);
            $update_redeem_stmt->bindParam(':voucher_id', $item['voucher_id'], PDO::PARAM_INT);
            
            if (!$update_redeem_stmt->execute()) {
                $db->rollBack();
                sendResponse(false, 'Failed to update voucher statistics');
            }
        }

        // Clear cart items
        $clear_cart_query = "DELETE FROM cart_items WHERE user_id = :user_id";
        $clear_cart_stmt = $db->prepare($clear_cart_query);
        $clear_cart_stmt->bindParam(':user_id', $user_id, PDO::PARAM_INT);
        
        if (!$clear_cart_stmt->execute()) {
            $db->rollBack();
            sendResponse(false, 'Failed to clear cart');
        }

        // Commit the transaction
        $db->commit();

        // Generate PDF Receipt for checkout
        $redemption_data = [
            'total_items' => count($order_items),
            'total_points' => $total_points,
            'checkout_date' => date('Y-m-d H:i:s'),
            'points_spent' => $total_points,
            'remaining_points' => $available_points - $total_points
        ];

        $pdfBase64 = null;
        $pdfType = null;

        try {
            // Check if TCPDF is available for advanced PDF
            if (class_exists('TCPDF')) {
                // Use TCPDF for better PDF generation if available
                $pdfGenerator = new VoucherPDFGenerator();
                // Create a combined voucher data for checkout
                $combined_voucher = [
                    'voucher_name' => 'Checkout Receipt - ' . count($order_items) . ' items',
                    'points_required' => $total_points,
                    'description' => 'Multiple vouchers checkout',
                    'terms_and_condition' => 'All vouchers are now available in your history for use.'
                ];
                $pdfContent = $pdfGenerator->generateVoucherPDF($combined_voucher, $user, $redemption_data);
                $pdfBase64 = base64_encode($pdfContent);
                $pdfType = 'binary';
            } else {
                // Use simple HTML PDF generation
                $checkout_html = SimplePDFGenerator::generateCheckoutReceipt($order_items, $user, $redemption_data);
                $pdfBase64 = base64_encode($checkout_html);
                $pdfType = 'html';
            }
        } catch (Exception $pdfError) {
            error_log("PDF generation failed: " . $pdfError->getMessage());
            // Continue without PDF - don't fail the checkout
        }

        // Send success response with checkout details
        closeConnection($db);
        sendResponse(true, 'Checkout completed successfully', [
            'total_items' => count($order_items),
            'total_points' => $total_points,
            'remaining_points' => $available_points - $total_points,
            'items' => $order_items,
            'checkout_date' => date('Y-m-d H:i:s'),
            'message' => 'Items have been moved to your history and vouchers are ready for use',
            'pdf_receipt' => [
                'available' => !is_null($pdfBase64),
                'data' => $pdfBase64,
                'type' => $pdfType,
                'filename' => 'checkout_receipt_' . date('Ymd_His') . '.pdf'
            ]
        ]);

    } catch (Exception $e) {
        if ($db->inTransaction()) {
            $db->rollBack();
        }
        throw $e;
    }

} catch (Exception $e) {
    logApiError("Checkout error", ['user_id' => $user_id ?? 'unknown', 'error' => $e->getMessage()]);
    sendResponse(false, 'Error processing checkout: ' . $e->getMessage());
}
?>