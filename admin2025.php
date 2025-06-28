<?php
session_start();
require 'db.php'; // Ensure $conn is defined here

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';

    if (empty($email) || empty($password)) {
        $error = 'Please fill in all fields.';
    } else {
        $sql = "SELECT id, name, email, password, role FROM users WHERE email = ? AND role = 'admin'";
        $stmt = mysqli_prepare($conn, $sql);
        mysqli_stmt_bind_param($stmt, "s", $email);
        mysqli_stmt_execute($stmt);
        $result = mysqli_stmt_get_result($stmt);

        if ($result && $user = mysqli_fetch_assoc($result)) {
            if (password_verify($password, $user['password'])) {
                $_SESSION['user_id'] = $user['id'];
                $_SESSION['user_name'] = $user['name'];
                $_SESSION['user_email'] = $user['email'];
                $_SESSION['user_role'] = $user['role'];
                header("Location: admin_dashboard.php");
                exit();
            } else {
                $error = "Incorrect password.";
            }
        } else {
            $error = "Admin not found or role mismatch.";
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>Admin Login - Homzey</title>
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
  <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;600&display=swap" rel="stylesheet">
  <style>
    body {
      font-family: 'Poppins', sans-serif;
    }

    .auth-section {
      display: flex;
      justify-content: center;
      align-items: center;
      flex-direction: column;
      height: 100vh;
      background-color: #fef3c7;
    }

    .auth-card {
      background-color: white;
      border-radius: 8px;
      box-shadow: 0 2px 8px rgba(0, 0, 0, 0.1);
      padding: 2rem;
      width: 95%;
      max-width: 400px;
    }

    .auth-card h3 {
      text-align: center;
      margin-bottom: 1.5rem;
      font-weight: 600;
      color: #111827;
    }

    .auth-logo {
      width: 70px;
      height: auto;
      display: block;
      margin: -2rem auto 1rem auto;
    }

    .logo-container {
      text-align: center;
    }

    label {
      margin-top: 1rem;
      font-weight: bold;
      display: block;
    }

    input[type="email"],
    input[type="password"] {
      width: 100%;
      padding: 0.5rem;
      margin-top: 0.5rem;
      border: 1px solid #ccc;
      border-radius: 4px;
    }

    button {
      width: 100%;
      background-color: #92400e;
      color: white;
      border: none;
      border-radius: 4px;
      padding: 0.75rem;
      font-size: 1rem;
      margin-top: 1.5rem;
      cursor: pointer;
    }

    button:hover {
      background-color: #7a3e0e;
    }

    .signup-link {
      text-align: center;
      margin-top: 1rem;
    }

    .signup-link a {
      color: #3b82f6;
      text-decoration: none;
      font-weight: bold;
    }

    .signup-link a:hover {
      text-decoration: underline;
    }

    .error {
      background-color: #fef2f2;
      color: #b91c1c;
      padding: 0.75rem;
      margin-bottom: 1rem;
      border-radius: 4px;
      font-size: 0.875rem;
      text-align: center;
    }
  </style>
</head>
<body>
  <section class="auth-section">
    <div class="auth-card">
      <div class="logo-container">
        <a href="index.php"><img src="image/house.png" alt="Homzey Logo" class="auth-logo"></a>
      </div>
      <h3>Admin Login</h3>

      <?php if (!empty($error)): ?>
        <div class="error">
          <i class="fas fa-exclamation-circle"></i> <?= htmlspecialchars($error) ?>
        </div>
      <?php endif; ?>

      <form method="POST" action="">
        <label for="email">Email</label>
        <input type="email" id="email" name="email" placeholder="admin@example.com" required>

        <label for="password">Password</label>
        <input type="password" id="password" name="password" placeholder="••••••••" required>

        <button type="submit">Login</button>
      </form>

      <div class="signup-link">
        <a href="#">Forgot password?</a>
      </div>
    </div>
  </section>
</body>
</html>
