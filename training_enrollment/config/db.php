<?php
// config/db.php
// Creates the single shared PDO connection used by every page.
require_once __DIR__ . '/../classes/Database.php';

$dsn  = "mysql:host=localhost;dbname=training_db;charset=utf8mb4";
$user = "root";
$pass = "";

try
{
    // Singleton: every call returns the same Database object
    $db = Database::getInstance($dsn, $user, $pass);
}
catch (PDOException $e)
{
    // Do not show the real error to the user (it can leak details)
    die("Database connection failed. Please check config/db.php.");
}

// Small helper to escape output and prevent XSS
function Esc($value)
{
    return htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');
}
