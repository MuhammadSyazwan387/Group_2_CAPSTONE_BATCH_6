<?php
// api/update_cart.php
require_once 'common.php';

try {
    $db = getDBConnection();

    // Get JSON input
    $input = json_decode(file_get_contents('php://input'));

    // Validate input
    $validation_error = validateInput($input, ['user_id', 'updates']);
    if ($validation_error) {
        sendResponse(false, $validation_error);
    }

    $user_id = (int) $input->user_id;
    $updates = $input->updates;

    if (!is_array($updates) || empty($updates)) {
        sendResponse(false, 'No updates provided');
    }

    // Start transaction
    $db->beginTransaction();

    try {
        $updated_items = [];
        $errors = [];

        foreach ($updates as $update) {
            if (!isset($update->id) || !isset($update->quantity)) {
                $errors[] = "Missing id or quantity in update";
                continue;
            }

            $item_id = (int) $update->id;
            $quantity = (int) $update->quantity;

            // Validate quantity
            if ($quantity < 1 || $quantity > 99) {
                $errors[] = "Invalid quantity for item $item_id (must be 1-99)";
                continue;
            }

            // Check if item exists and belongs to user
            $check_query = "SELECT id, voucher_id FROM cart_items WHERE id = :id AND user_id = :user_id";
            $check_stmt = $db->prepare($check_query);
            $check_stmt->bindParam(':id', $item_id, PDO::PARAM_INT);
            $check_stmt->bindParam(':user_id', $user_id, PDO::PARAM_INT);
            $check_stmt->execute();

            if ($check_stmt->rowCount() === 0) {
                $errors[] = "Item $item_id not found in your cart";
                continue;
            }

            // Update quantity
            $update_query = "UPDATE cart_items SET quantity = :quantity WHERE id = :id AND user_id = :user_id";
            $update_stmt = $db->prepare($update_query);
            $update_stmt->bindParam(':quantity', $quantity, PDO::PARAM_INT);
            $update_stmt->bindParam(':id', $item_id, PDO::PARAM_INT);
            $update_stmt->bindParam(':user_id', $user_id, PDO::PARAM_INT);

            if ($update_stmt->execute()) {
                $updated_items[] = $item_id;
            } else {
                $errors[] = "Failed to update item $item_id";
            }
        }

        if (!empty($errors)) {
            if ($db->inTransaction()) {
                $db->rollBack();
            }
            sendResponse(false, 'Some updates failed: ' . implode(', ', $errors));
        }

        $db->commit();

        // // Get updated cart summary
        // $summary_query = "SELECT 
        //                     COUNT(*) as total_items,
        //                     SUM(ci.quantity * COALESCE(v.points, 1000)) as total_points,
        //                   FROM cart_items ci
        //                   LEFT JOIN voucher v ON ci.voucher_id = v.id
        //                   WHERE ci.user_id = :user_id";
        // $summary_stmt = $db->prepare($summary_query);
        // $summary_stmt->bindParam(':user_id', $user_id, PDO::PARAM_INT);
        // $summary_stmt->execute();
        // $summary = $summary_stmt->fetch(PDO::FETCH_ASSOC);

        // // Ensure numeric values
        // $summary['total_items'] = (int) $summary['total_items'];
        // $summary['total_points'] = (int) ($summary['total_points'] ?? 0);
        // $summary['total_price'] = (float) ($summary['total_price'] ?? 0);

        closeConnection($db);
        sendResponse(true, count($updated_items) . ' items updated successfully', [
            'updated_items' => $updated_items,
            //'summary' => $summary
        ]);
    } catch (Exception $e) {
        if ($db->inTransaction()) {
            $db->rollBack();
        }
        throw $e;
    }

} catch (Exception $e) {
    logApiError("Update cart error", ['user_id' => $user_id ?? 'unknown', 'error' => $e->getMessage()]);
    sendResponse(false, $e->getMessage());
}
?>