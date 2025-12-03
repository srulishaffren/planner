<?php
// config.php
// Copy this file to config.php and fill in your values

// DB connection
$dbHost = 'localhost';
$dbName = 'your_database_name';
$dbUser = 'your_db_user';
$dbPass = 'your_db_password';

// File uploads
$uploadDir = __DIR__ . '/uploads';
$maxFileSize = 10 * 1024 * 1024; // 10MB
$allowedMimeTypes = [
    'image/jpeg', 'image/png', 'image/gif', 'image/webp', 'image/svg+xml',
    'application/pdf',
    'text/plain', 'text/csv', 'text/markdown',
    'application/msword', 'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
    'application/vnd.ms-excel', 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
];

// Location for zmanim (update these for your location)
$locationName = 'New York';
$latitude = 40.7128;
$longitude = -74.0060;
$timezone = 'America/New_York';
$elevation = 10; // meters

// Single app login user
$appUsername = 'your_username';

// Generate this once in a PHP shell and paste it:
// password_hash('your-strong-password', PASSWORD_DEFAULT);
$appPasswordHash = '';

// Error reporting - log to file, never display to browser
error_reporting(E_ALL);
ini_set('display_errors', 0);
ini_set('log_errors', 1);
ini_set('error_log', __DIR__ . '/logs/php_errors.log');

function get_pdo() {
    global $dbHost, $dbName, $dbUser, $dbPass;
    static $pdo = null;
    if ($pdo === null) {
        $dsn = "mysql:host=$dbHost;dbname=$dbName;charset=utf8mb4";
        $pdo = new PDO($dsn, $dbUser, $dbPass, [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        ]);
    }
    return $pdo;
}
