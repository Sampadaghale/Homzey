<?php
session_start();
include 'db.php';

$email = $_SESSION['reset_email'] ?? '';
$code = $_POST['code'] ?? '';

if (!$email || !$code) {
  die("Invalid session or code.");
}

$stmt = $conn->prepare("SELECT * FROM password_resets WHERE email = ? AND token = ?");
$stmt->bind_param("ss", $email, $code);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 1) {
    $row = $result->fetch_assoc();
    if (strtotime($row['expires_at']) > time()) {
        $_SESSION['code_verified'] = true;
        header("Location: new_password.php");
        exit;
    } else {
        echo "Code expired.";
    }
} else {
    echo "Invalid code.";
}
?>
