<?php
session_start();

$db_host = 'localhost';
$db_port = '8889';
$db_name = 'finance_tracker';
$db_user = 'root';
$db_pass = 'root';

error_reporting(E_ALL);
ini_set('display_errors', 1);

try {
    $dsn = "mysql:host=$db_host;port=$db_port;dbname=$db_name;charset=utf8mb4";
    $conn = new PDO($dsn, $db_user, $db_pass);
    $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $conn->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
} catch(PDOException $e) {
    die("Database connection failed: " . $e->getMessage());
}

function hash_password($password) { 
    return password_hash($password, PASSWORD_DEFAULT); 
}

function verify_password($input_password, $stored_hash) { 
    return password_verify($input_password, $stored_hash); 
}
?>