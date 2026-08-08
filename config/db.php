<?php
session_start();

// Database configuration
$host = 'localhost';
$dbname = 'student_assistant';
$username = 'root';
$password = '';

try {
    $pdo = new PDO("mysql:host=$host;dbname=$dbname", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch(PDOException $e) {
    die("Database connection failed: " . $e->getMessage());
}

// Authentication check function
function checkAuth() {
    if (!isset($_SESSION['user_id'])) {
        header("Location: ../index.php");
        exit();
    }
}
?>