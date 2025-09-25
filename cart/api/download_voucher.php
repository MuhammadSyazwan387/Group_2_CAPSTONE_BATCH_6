<?php
require_once __DIR__ . '/../../libs/fpdf.php';
require_once __DIR__ . '/../../config.php';

// Single entry point to finalize checkout and download vouchers for the current cart
if (!isset($_GET['user_id'])) {
    die("Invalid request");
}

$userId = intval($_GET['user_id']);

try {
    $db = getDBConnection();
    $db->beginTransaction();

    // Fetch current cart items with voucher info
    $cartQuery = $db->prepare(
        "SELECT ci.id, ci.voucher_id, ci.quantity, v.title, v.description, v.points
         FROM cart_items ci
         JOIN voucher v ON v.id = ci.voucher_id
         WHERE ci.user_id = :user_id
         ORDER BY ci.id FOR UPDATE"
    );
    $cartQuery->bindParam(':user_id', $userId, PDO::PARAM_INT);
    $cartQuery->execute();
    $cartItems = $cartQuery->fetchAll(PDO::FETCH_ASSOC);

    if (!$cartItems || count($cartItems) === 0) {
        $db->rollBack();
        die("Your cart is empty");
    }

    // Calculate total points required
    $totalPoints = 0;
    foreach ($cartItems as $ci) {
        $totalPoints += ((int)$ci['points']) * ((int)$ci['quantity']);
    }

    // Check user's available points (lock row)
    $userStmt = $db->prepare("SELECT points FROM users WHERE id = :user_id FOR UPDATE");
    $userStmt->bindParam(':user_id', $userId, PDO::PARAM_INT);
    $userStmt->execute();
    $user = $userStmt->fetch(PDO::FETCH_ASSOC);
    if (!$user) {
        $db->rollBack();
        die("User not found");
    }
    $available = (int)$user['points'];
    if ($available < $totalPoints) {
        $db->rollBack();
        die("Insufficient points");
    }

    // Prepare PDF first (content in memory), then commit DB and stream
    $expiryDate = date("Y-m-d", strtotime("+30 days"));
    $pdf = new FPDF('P', 'mm', 'A4');
    $logoPath = __DIR__ . '/../../images/optima_bank_logo.png';

    foreach ($cartItems as $row) {
        $voucherTitle = $row['title'];
        $voucherDesc = $row['description'];
        $quantity = max(1, (int)$row['quantity']);

        for ($i = 0; $i < $quantity; $i++) {
            $pdf->AddPage();
            if (file_exists($logoPath)) {
                $pdf->Image($logoPath, 10, 10, 30);
            }
            $pdf->SetFont('Arial', 'B', 20);
            $pdf->Cell(190, 20, 'Optima Bank Voucher', 0, 1, 'C');
            $pdf->Ln(6);
            $pdf->SetFont('Arial', '', 14);
            $pdf->MultiCell(0, 8, "Congratulations! You have redeemed the voucher:\n\n$voucherTitle", 0, 'C');
            $pdf->Ln(4);
            $pdf->SetFont('Arial', '', 12);
            $pdf->MultiCell(0, 6, $voucherDesc, 0, 'C');
            $voucherCode = strtoupper(substr(md5(uniqid("v-$userId", true)), 0, 10));
            $pdf->Ln(8);
            $pdf->SetFont('Arial', 'B', 16);
            $pdf->SetFillColor(255, 230, 240);
            $pdf->Cell(190, 14, "Voucher Code: $voucherCode", 0, 1, 'C', true);
            $pdf->Ln(4);
            $pdf->SetFont('Arial', '', 12);
            $pdf->Cell(190, 8, "Expiry Date: $expiryDate", 0, 1, 'C');
            $pdf->Ln(12);
            $pdf->SetFont('Arial', 'I', 10);
            $pdf->MultiCell(0, 6, "Terms & Conditions:\nThis voucher is valid for one-time use only. Non-transferable. Cannot be exchanged for cash. Valid until $expiryDate.", 0, 'C');
        }
    }

    // Deduct points
    $deduct = $db->prepare("UPDATE users SET points = points - :deduct WHERE id = :user_id");
    $deduct->bindParam(':deduct', $totalPoints, PDO::PARAM_INT);
    $deduct->bindParam(':user_id', $userId, PDO::PARAM_INT);
    $deduct->execute();

    // Insert into history and update voucher redeem counts
    $hist = $db->prepare("INSERT INTO cart_item_history (voucher_id, user_id, quantity, completed_date) VALUES (:voucher_id, :user_id, :quantity, NOW())");
    $updRedeem = $db->prepare("UPDATE voucher SET total_redeem = total_redeem + :q WHERE id = :vid");
    foreach ($cartItems as $row) {
        $vid = (int)$row['voucher_id'];
        $qty = (int)$row['quantity'];
        $hist->execute([':voucher_id' => $vid, ':user_id' => $userId, ':quantity' => $qty]);
        $updRedeem->execute([':q' => $qty, ':vid' => $vid]);
    }

    // Clear cart
    $clear = $db->prepare("DELETE FROM cart_items WHERE user_id = :user_id");
    $clear->bindParam(':user_id', $userId, PDO::PARAM_INT);
    $clear->execute();

    $db->commit();

    $filename = "vouchers_" . date('Ymd_His') . ".pdf";
    $pdf->Output('D', $filename);
    exit;
} catch (Exception $e) {
    if (isset($db) && $db->inTransaction()) {
        $db->rollBack();
    }
    die("Error: " . $e->getMessage());
}
?>
