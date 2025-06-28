<?php 
session_start();
require 'db.php'; // expects $conn = mysqli_connect(...)

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = trim($_POST['email']);
    $password = $_POST['password'];
    $role = $_POST['role'] ?? '';

    if (empty($email) || empty($password) || empty($role)) {
        $_SESSION['error'] = 'Please enter all fields including role.';
        header('Location: login.php');
        exit();
    }

    $sql = "SELECT id, name, email, password, role FROM users WHERE email = ? AND role = ?";
    $stmt = mysqli_prepare($conn, $sql);
    mysqli_stmt_bind_param($stmt, "ss", $email, $role);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);

    if ($result && $user = mysqli_fetch_assoc($result)) {
        if (password_verify($password, $user['password'])) {
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['user_name'] = $user['name'];
            $_SESSION['user_email'] = $user['email'];
            $_SESSION['user_role'] = $user['role'];

            switch ($user['role']) {
                case 'tenant':
                    header('Location: index.php');
                    break;
                case 'landlord':
                    header('Location: landlord_dashboard.php');
                    break;
                case 'admin':
                    header('Location: admin_dashboard.php');
                    break;
                default:
                    header('Location: index.php');
                    break;
            }
            exit();
        } else {
            $_SESSION['error'] = 'Invalid email, password, or role.';
            header('Location: login.php');
            exit();
        }
    } else {
        $_SESSION['error'] = 'Invalid email, password, or role.';
        header('Location: login.php');
        exit();
    }
} else {
    header('Location: login.php');
    exit();
}
