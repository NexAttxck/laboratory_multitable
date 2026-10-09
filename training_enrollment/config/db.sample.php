<?php
// config/db.sample.php
// Copy this file to config/db.php and fill in your own credentials.
// config/db.php is listed in .gitignore so the password is never committed.
require_once __DIR__ . '/../classes/Database.php';

$dsn  = "mysql:host=localhost;dbname=training_db;charset=utf8mb4";
$user = "your_db_user";
$pass = "your_db_password";

try
{
    $db = Database::getInstance($dsn, $user, $pass);
}
catch (PDOException $e)
{
    die("Database connection failed. Please check config/db.php.");
}

function Esc($value)
{
    return htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');
}
