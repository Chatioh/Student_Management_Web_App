<?php
/* Database credentials. Assuming you are running MySQL
server with default settings (user 'root' with no password) */
define('DB_SERVER', 'localhost');
define('DB_USERNAME', 'root');
define('DB_PASSWORD', '');
define('DB_NAME', 'sma');

// Attempt to connect to MySQL database using MySQLi with object-oriented approach
$link = new mysqli(DB_SERVER, DB_USERNAME, DB_PASSWORD, DB_NAME);

// Check the connection
if ($link->connect_error) {
    // Handle connection error securely
    die("ERROR: Could not connect. " . $link->connect_error);
}

// Set the character set to UTF-8 for safe handling of characters
if (!$link->set_charset("utf8mb4")) {
    die("ERROR: Could not set character set. " . $link->error);
}
?>