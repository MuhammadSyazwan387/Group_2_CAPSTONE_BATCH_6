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
    
    // Fetch vouchers with all details
    $stmt = $pdo->prepare("SELECT id, title, image, description, terms_and_condition, total_redeem FROM voucher ORDER BY id LIMIT 3");
    $stmt->execute();
    $vouchers = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Process the image URLs
    foreach ($vouchers as &$voucher) {
        if (!empty($voucher['image'])) {
            // If it's already a full URL, keep it as is
            if (strpos($voucher['image'], 'http') === 0) {
                // Already a full URL, keep as is
            } 
            // If it already contains 'images/', keep as is
            else if (strpos($voucher['image'], 'images/') === 0) {
                // Already has images/ prefix, keep as is
            }
            // If it's just a filename, add images/ prefix
            else {
                $voucher['image'] = 'images/' . $voucher['image'];
            }
        } else {
            // Default placeholder image if no image
            $voucher['image'] = 'images/placeholder.jpg';
        }
    }
    
    echo json_encode($vouchers);
} catch(PDOException $e) {
    echo json_encode(["error" => "Connection failed: " . $e->getMessage()]);
}
?>