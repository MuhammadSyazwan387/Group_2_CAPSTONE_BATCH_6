<?php
// filepath: c:\xampp\htdocs\Group_2_CAPSTONE_BATCH_6\config.php

// Database configuration settings
define('DB_HOST', 'localhost');
define('DB_USERNAME', 'root');
define('DB_PASSWORD', '');
define('DB_NAME', 'optima_bank');
define('DB_CHARSET', 'utf8mb4');

// Create database connection
function getDBConnection() {
    try {
        $dsn = "mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=" . DB_CHARSET;
        $pdo = new PDO($dsn, DB_USERNAME, DB_PASSWORD, [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES => false,
        ]);
        return $pdo;
    } catch (PDOException $e) {
        // Log the specific error
        error_log("Database connection failed: " . $e->getMessage());
        
        // Check if database doesn't exist
        if ($e->getCode() == 1049) {
            throw new Exception("Database '" . DB_NAME . "' does not exist. Please run init_database.php first.");
        }
        
        throw new Exception("Database connection failed: " . $e->getMessage());
    }
}

// Alternative mysqli connection (if preferred)
function getMySQLiConnection() {
    $connection = new mysqli(DB_HOST, DB_USERNAME, DB_PASSWORD, DB_NAME);
    
    if ($connection->connect_error) {
        error_log("MySQL connection failed: " . $connection->connect_error);
        die("Database connection failed. Please try again later.");
    }
    
    $connection->set_charset(DB_CHARSET);
    return $connection;
}

// Test database connection
function testConnection() {
    try {
        $pdo = getDBConnection();
        echo "Database connection successful!";
        return true;
    } catch (Exception $e) {
        echo "Database connection failed: " . $e->getMessage();
        return false;
    }
}

// Close database connection
function closeConnection($connection) {
    if ($connection instanceof PDO) {
        $connection = null;
    } elseif ($connection instanceof mysqli) {
        $connection->close();
    }
}

// Enable error reporting for development (remove in production)
if ($_SERVER['SERVER_NAME'] === 'localhost') {
    ini_set('display_errors', 1);
    ini_set('display_startup_errors', 1);
    error_reporting(E_ALL);
}
?>