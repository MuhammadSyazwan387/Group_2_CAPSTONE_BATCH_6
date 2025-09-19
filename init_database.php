<?php
require_once 'config.php';

try {
    $pdo = getDBConnection();
    
    // Check if google_id column exists
    $stmt = $pdo->query("SHOW COLUMNS FROM users LIKE 'google_id'");
    $columnExists = $stmt->fetch();
    
    if (!$columnExists) {
        // Add google_id column
        $pdo->exec("ALTER TABLE users ADD COLUMN google_id VARCHAR(255) NULL UNIQUE AFTER email");
        echo "✅ Added google_id column to users table<br>";
    } else {
        echo "✅ google_id column already exists<br>";
    }
    
    // Make password nullable for Google users
    $pdo->exec("ALTER TABLE users MODIFY password VARCHAR(255) NULL");
    echo "✅ Made password column nullable<br>";
    
    echo "<br>🎉 Database updated successfully for Google Sign-In!<br>";
    echo "<br>📝 Next steps:<br>";
    echo "1. Go to <a href='https://console.cloud.google.com/' target='_blank'>Google Cloud Console</a><br>";
    echo "2. Create a project and enable Google Sign-In API<br>";
    echo "3. Create OAuth2 credentials<br>";
    echo "4. Update google_config.php with your Client ID and Secret<br>";
    echo "5. Update authentication_page.html with your Client ID<br>";
    
} catch (Exception $e) {
    echo "❌ Error: " . $e->getMessage();
}
?>
