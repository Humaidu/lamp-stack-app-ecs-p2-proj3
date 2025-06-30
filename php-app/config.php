<?php
function getDBConnection() {

    $serverParts = explode(':',  getenv('DB_ENDPOINT'));
    $servername = $serverParts[0];
    $port = isset($serverParts[1]) ? $serverParts[1] : 3306;

    $username =  getenv('DB_USERNAME');
    $password =  getenv('DB_PASSWORD');
    $dbname =  getenv('DB_NAME');

    // Connect to MySQL
    $conn = new mysqli($servername, $username, $password, "", $port);
    if ($conn->connect_error) {
        die("Connection failed: " . $conn->connect_error);
    }

    // Create database if not exists
    if (!$conn->query("CREATE DATABASE IF NOT EXISTS `$dbname`")) {
        die("Error creating database: " . $conn->error);
    }

    $conn->select_db($dbname);

    // Create table
    $sql = "CREATE TABLE IF NOT EXISTS visitors (
        id INT AUTO_INCREMENT PRIMARY KEY,
        ip_address VARCHAR(50),
        visit_time TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    )";

    if (!$conn->query($sql)) {
        die("Error creating table: " . $conn->error);
    }

    return $conn;
}
?>
