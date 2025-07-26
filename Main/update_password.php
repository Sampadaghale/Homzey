<?php 
session_start();
include 'db.php';

$email = $_SESSION['reset_email'] ?? '';
if (!$email) {
    $_SESSION['reset_msg'] = "Session expired. Please start the reset process again.";
    header("Location: login.php");
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $new = $_POST['new_password'] ?? '';
    $confirm = $_POST['confirm_password'] ?? '';

    if ($new !== $confirm) {
        $_SESSION['reset_msg'] = "Passwords do not match.";
        header("Location: login.php");
        exit;
    }

    $hash = password_hash($new, PASSWORD_DEFAULT);
    $stmt = $conn->prepare("UPDATE users SET password = ? WHERE email = ?");
    $stmt->bind_param("ss", $hash, $email);

    if ($stmt->execute()) {
        // Clean up reset tokens and session
        $delete_stmt = $conn->prepare("DELETE FROM password_resets WHERE email = ?");
        $delete_stmt->bind_param("s", $email);
        $delete_stmt->execute();

        session_unset();
        session_destroy();

        // Redirect with success message
        session_start();
        $_SESSION['login_error'] = "Password updated successfully. You can login now.";
        header("Location: login.php");
        exit;
    } else {
        $_SESSION['reset_msg'] = "Failed to update password. Please try again.";
        header("Location: login.php");
        exit;
    }
} else {
    // If accessed without POST
    header("Location: login.php");
    exit;
}
