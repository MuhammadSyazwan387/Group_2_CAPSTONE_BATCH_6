<?php
require_once 'common.php';
require_once 'pdf_generator.php';

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

    // Start transaction
    $db->beginTransaction();

    try {
        // ✅ Get voucher details
        $voucher_query = "SELECT 
                            id,
                            title as voucher_name,
                            points as points_required,
                            description,
                            image,
                            terms_and_condition,
                            total_redeem
                          FROM voucher 
                          WHERE id = :voucher_id
                          FOR UPDATE";

        $voucher_stmt = $db->prepare($voucher_query);
        $voucher_stmt->bindParam(':voucher_id', $voucher_id, PDO::PARAM_INT);
        $voucher_stmt->execute();
        $voucher = $voucher_stmt->fetch(PDO::FETCH_ASSOC);

        if (!$voucher) {
            $db->rollBack();
            sendResponse(false, 'Voucher not found');
        }

        $points_required = (int)$voucher['points_required'];

        // ✅ Get user details
        $user_query = "SELECT id, fullname, email, points FROM users WHERE id = :user_id FOR UPDATE";
        $user_stmt = $db->prepare($user_query);
        $user_stmt->bindParam(':user_id', $user_id, PDO::PARAM_INT);
        $user_stmt->execute();
        $user = $user_stmt->fetch(PDO::FETCH_ASSOC);

        if (!$user) {
            $db->rollBack();
            sendResponse(false, 'User not found');
        }

        $available_points = (int)$user['points'];

        // ✅ Check balance
        if ($available_points < $points_required) {
            $db->rollBack();
            sendResponse(false, 'Insufficient points. You need ' . $points_required . ' but only have ' . $available_points);
        }

        // ✅ Deduct points
        $deduct_query = "UPDATE users SET points = points - :deduct WHERE id = :user_id";
        $deduct_stmt = $db->prepare($deduct_query);
        $deduct_stmt->bindParam(':deduct', $points_required, PDO::PARAM_INT);
        $deduct_stmt->bindParam(':user_id', $user_id, PDO::PARAM_INT);
        $deduct_stmt->execute();

        // ✅ Insert into history
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
        $history_stmt->bindParam(':voucher_id', $voucher_id, PDO::PARAM_INT);
        $history_stmt->bindParam(':user_id', $user_id, PDO::PARAM_INT);
        $quantity = 1; // redeem one voucher
        $history_stmt->bindParam(':quantity', $quantity, PDO::PARAM_INT);
        $history_stmt->execute();

        // ✅ Update voucher redeem count
        $update_redeem_query = "UPDATE voucher SET total_redeem = total_redeem + 1 WHERE id = :voucher_id";
        $update_redeem_stmt = $db->prepare($update_redeem_query);
        $update_redeem_stmt->bindParam(':voucher_id', $voucher_id, PDO::PARAM_INT);
        $update_redeem_stmt->execute();

        // Commit transaction
        $db->commit();

        // ✅ Generate PDF Receipt
        $redemption_data = [
            'voucher' => $voucher['voucher_name'],
            'points_spent' => $points_required,
            'remaining_points' => $available_points - $points_required,
            'checkout_date' => date('Y-m-d H:i:s'),
            'voucher_id' => $voucher_id
        ];

        try {
            // Try to use TCPDF if available
            if (class_exists('TCPDF')) {
                $pdfGenerator = new VoucherPDFGenerator();
                $pdfContent = $pdfGenerator->generateVoucherPDF($voucher, $user, $redemption_data);
                $pdfBase64 = base64_encode($pdfContent);
                $pdfType = 'binary';
            } else {
                // Fallback to simple HTML receipt
                $pdfContent = SimplePDFGenerator::generateSimpleReceipt($voucher, $user, $redemption_data);
                $pdfBase64 = base64_encode($pdfContent);
                $pdfType = 'html';
            }
        } catch (Exception $pdfError) {
            // If PDF generation fails, continue without PDF
            error_log("PDF generation failed: " . $pdfError->getMessage());
            $pdfBase64 = null;
            $pdfType = null;
        }

        // ✅ Response with PDF data
        sendResponse(true, 'Checkout completed successfully', [
            'voucher' => $voucher['voucher_name'],
            'points_spent' => $points_required,
            'remaining_points' => $available_points - $points_required,
            'checkout_date' => date('Y-m-d H:i:s'),
            'message' => 'Voucher redeemed successfully and added to history',
            'pdf_receipt' => [
                'available' => !is_null($pdfBase64),
                'data' => $pdfBase64,
                'type' => $pdfType,
                'filename' => 'voucher_receipt_' . date('Ymd_His') . '.pdf'
            ]
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
