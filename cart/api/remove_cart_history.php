<?php
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
        // First check if item exists and belongs to user
        $check_query = "SELECT id FROM cart_item_history WHERE user_id = :user_id";
        $check_stmt = $db->prepare($check_query);
        $check_stmt->bindParam(':user_id', $user_id, PDO::PARAM_INT);
        $check_stmt->execute();

        if ($check_stmt->rowCount() === 0) {
            $db->rollBack();
            sendResponse(false, 'No history found');
        }

        // Delete the item
        $delete_query = "DELETE FROM cart_item_history WHERE user_id = :user_id";
        $delete_stmt = $db->prepare($delete_query);
        $delete_stmt->bindParam(':user_id', $user_id, PDO::PARAM_INT);
        $delete_stmt->execute(); // 🚀 this was missing

        $db->commit();
        closeConnection($db);
        sendResponse(true, 'Cart history cleared successfully');
    } catch (Exception $e) {
        $db->rollBack();
        throw $e;
    }

} catch (Exception $e) {
    logApiError("Cart history removal error", ['user_id' => $user_id ?? 'unknown', 'error' => $e->getMessage()]);
    sendResponse(false, $e->getMessage());
}
?>