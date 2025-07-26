<?php  
$host = 'localhost'; 
$port = '3306';

$db = 'houserent';
$user = 'root';
$pass = '';

$conn = new mysqli($host, $user, $pass, $db, (int)$port);

if ($conn->connect_error) {
    die("Database connection failed: " . $conn->connect_error);
}

$conn->set_charset("utf8mb4");
?>
