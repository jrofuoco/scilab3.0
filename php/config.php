
<?php
/**
 * config.php
 * Database configuration and connection setup using PDO.
 */

// Database credentials
define('DB_HOST', 'localhost:3306');
define('DB_NAME', 'scilab_reservation'); // CHANGE THIS
define('DB_USER', 'root');   // CHANGE THIS
define('DB_PASS', '');   // CHANGE THIS
define('DB_CHARSET', 'utf8mb4');


//TANGINANYONG LAHAT

$dsn = "mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=" . DB_CHARSET;
$options = [
    PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    PDO::ATTR_EMULATE_PREPARES   => false,
];

// Global PDO connection variable
$pdo = null;

try {
     $pdo = new PDO($dsn, DB_USER, DB_PASS, $options);
} catch (\PDOException $e) {
     // Log the error and stop execution
     error_log("Database Connection Error: " . $e->getMessage());
     // Display a user-friendly error
     die("A critical system error occurred. Please contact the administrator.");
}
?>