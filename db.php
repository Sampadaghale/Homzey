<?php 
//$host = 'localhost';
$host='localhost'; 
$port='3306';


$db = 'houserent';
$user = 'root';  // change if needed
$pass = '';      // update with your DB password

$conn = new mysqli($host, $user, $pass, $db);

if ($conn->connect_error) {
    die("Database connection failed: " . $conn->connect_error);
}

$conn->set_charset("utf8mb4");
?>
