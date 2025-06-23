<?php

session_start();

if(!isset($_SESSION['user_id']) || $_SESSION['user_role'] !== 'admin') {
    header('Location: login.php');
    exit();
}

echo '"Welcome to the Admin Dashboard, ' . htmlspecialchars($_SESSION['user_name']) . '!<br>';

?>