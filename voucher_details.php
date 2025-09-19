<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');

// Database connection
$servername = "localhost";
$username = "root";
$password = "";
$dbname = "optima_bank";

try {
    $pdo = new PDO("mysql:host=$servername;dbname=$dbname", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    // Get voucher ID from URL parameter
    $voucher_id = isset($_GET['id']) ? intval($_GET['id']) : 0;
    
    if ($voucher_id <= 0) {
        echo json_encode(["error" => "Invalid voucher ID"]);
        exit;
    }
    
    // Fetch specific voucher details
    $stmt = $pdo->prepare("SELECT id, title, image, description, terms_and_condition, total_redeem FROM voucher WHERE id = ?");
    $stmt->execute([$voucher_id]);
    $voucher = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$voucher) {
        echo json_encode(["error" => "Voucher not found"]);
        exit;
    }
    
    // Process the image URL
    if (!empty($voucher['image'])) {
        if (strpos($voucher['image'], 'http') === 0) {
            // Already a full URL, keep as is
        } 
        else if (strpos($voucher['image'], 'images/') === 0) {
            // Already has images/ prefix, keep as is
        }
        else {
            $voucher['image'] = 'images/' . $voucher['image'];
        }
    } else {
        $voucher['image'] = 'images/placeholder.jpg';
    }
    
    echo json_encode($voucher);
} catch(PDOException $e) {
    echo json_encode(["error" => "Connection failed: " . $e->getMessage()]);
}
?>