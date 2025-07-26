<?php   
require 'db.php'; // expects $conn = mysqli_connect(...)

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name     = trim($_POST['name']);
    $email    = trim($_POST['email']);
    $password = $_POST['password'];
    $confirm  = $_POST['confirm_password'];
    $role     = $_POST['role'] ?? '';

    // Basic validation
    if (empty($name) || empty($email) || empty($password) || empty($confirm) || empty($role)) {
        die('Please fill all fields including role.');
    }

    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        die('Invalid email address.');
    }

    if ($password !== $confirm) {
        die('Passwords do not match.');
    }

    // Check if user exists
    $stmt = mysqli_prepare($conn, "SELECT id FROM users WHERE email = ?");
    mysqli_stmt_bind_param($stmt, "s", $email);
    mysqli_stmt_execute($stmt);
    mysqli_stmt_store_result($stmt);
    if (mysqli_stmt_num_rows($stmt) > 0) {
        mysqli_stmt_close($stmt);
        die('Email already registered.');
    }
    mysqli_stmt_close($stmt);

    // Hash password and insert
    $hashed = password_hash($password, PASSWORD_DEFAULT);
    $stmt = mysqli_prepare($conn, "INSERT INTO users (name, email, password, role) VALUES (?, ?, ?, ?)");
    mysqli_stmt_bind_param($stmt, "ssss", $name, $email, $hashed, $role);

    if (mysqli_stmt_execute($stmt)) {
        echo 'Signup successful. <a href="login.php">Login now</a>';
    } else {
        echo 'Something went wrong.';
    }
    mysqli_stmt_close($stmt);

} else {
    header('Location: signup.php');
    exit();
}
?>
