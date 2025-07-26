<?php  
session_start();
include 'db.php';

$email = $_SESSION['reset_email'] ?? '';
if (!$email) {
    $_SESSION['verify_msg'] = "Session expired. Please start the reset process again.";
    header("Location: login.php");
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $code = $_POST['code'] ?? '';

    $stmt = $conn->prepare("SELECT token, expires_at FROM password_resets WHERE email = ? ORDER BY expires_at DESC LIMIT 1");
    $stmt->bind_param("s", $email);
    $stmt->execute();
    $stmt->bind_result($token, $expires_at);
    $stmt->fetch();
    $stmt->close();

    if (!$token) {
        $_SESSION['verify_msg'] = "No reset request found. Please start again.";
        header("Location: login.php");
        exit;
    }

    if (new DateTime() > new DateTime($expires_at)) {
        $_SESSION['verify_msg'] = "Reset code expired. Please request a new one.";
        header("Location: login.php");
        exit;
    }

    if ($code === $token) {
        $_SESSION['code_verified'] = true;  // Flag to allow password reset
        $_SESSION['reset_msg'] = "Code verified! You can now reset your password.";
        header("Location: login.php");
        exit;
    } else {
        $_SESSION['verify_msg'] = "Invalid verification code.";
        header("Location: login.php");
        exit;
    }
} else {
    header("Location: login.php");
    exit;
}
