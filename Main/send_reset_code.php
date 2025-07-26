<?php 
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

require 'db.php';
require '../PHPMailer/PHPMailer.php';
require '../PHPMailer/SMTP.php';
require '../PHPMailer/Exception.php';

session_start();

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $email = $_POST['email'];

    $stmt = $conn->prepare("SELECT id FROM users WHERE email = ?");
    $stmt->bind_param("s", $email);
    $stmt->execute();
    $stmt->store_result();

    if ($stmt->num_rows === 1) {
        $reset_code = random_int(100000, 999999);
        $expires_at = date("Y-m-d H:i:s", strtotime("+10 minutes"));

        // Delete previous codes for this email
        $delete_stmt = $conn->prepare("DELETE FROM password_resets WHERE email = ?");
        $delete_stmt->bind_param("s", $email);
        $delete_stmt->execute();

        // Insert new reset code
        $insert_stmt = $conn->prepare("INSERT INTO password_resets (email, token, expires_at) VALUES (?, ?, ?)");
        $insert_stmt->bind_param("sss", $email, $reset_code, $expires_at);
        $insert_stmt->execute();

        // Send code via email
        $mail = new PHPMailer(true);
        try {
            $mail->isSMTP();
            $mail->Host = 'smtp.gmail.com';
            $mail->SMTPAuth = true;
            $mail->Username = 'homzeyrent@gmail.com';
            $mail->Password = 'yrvk pqxh hsxa stsk'; // Use app password
            $mail->SMTPSecure = 'tls';
            $mail->Port = 587;

            $mail->setFrom('homzeyrent@gmail.com', 'Homzey');
            $mail->addAddress($email);
            $mail->Subject = 'Your Password Reset Code';
            $mail->Body = "Your reset code is: $reset_code (valid for 10 minutes)";

            $mail->send();

            // Set session for verify form
            $_SESSION['reset_email'] = $email;
            $_SESSION['verify_msg'] = "Code sent to your email. Please check and enter the code below.";

            header("Location: login.php");
            exit;
        } catch (Exception $e) {
            $_SESSION['forgot_msg'] = "Email error: " . $mail->ErrorInfo;
            header("Location: login.php");
            exit;
        }
    } else {
        $_SESSION['forgot_msg'] = "Email not found in our system.";
        header("Location: login.php");
        exit;
    }
} else {
    $_SESSION['forgot_msg'] = "Invalid request.";
    header("Location: login.php");
    exit;
}
?>
