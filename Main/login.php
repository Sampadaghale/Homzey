<?php 
session_start();

// Optionally show login error or success messages
$login_error = $_SESSION['login_error'] ?? '';
$forgot_msg = $_SESSION['forgot_msg'] ?? '';
$verify_msg = $_SESSION['verify_msg'] ?? '';
$reset_msg = $_SESSION['reset_msg'] ?? '';
unset($_SESSION['login_error'], $_SESSION['forgot_msg'], $_SESSION['verify_msg'], $_SESSION['reset_msg']);
?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1" />
  <link rel="stylesheet" href="styles.css" />
  <title>LogIn</title>
  <style>
    .hidden { display: none; }
    .form-section { margin-top: 1em; }
    .back-link { cursor: pointer; color: blue; text-decoration: underline; margin-top: 10px; display: inline-block; }
    .message { color: red; text-align: center; margin-bottom: 10px; }
  </style>
</head>
<body>
  <section class="auth-section">
    <h2>Access Your Account</h2>
    <div class="auth-forms">
      <section class="auth-card">

        <!-- Logo -->
        <div class="logo-container">
          <a href="index.php"><img src="/homzey/image/house.png" alt="Homzey Logo" class="auth-logo"></a>
        </div>

        <!-- Login Form -->
        <div id="login-form">
          <?php if ($login_error): ?>
            <p class="message"><?= htmlspecialchars($login_error) ?></p>
          <?php endif; ?>

          <form action="login_process.php" method="post">
            <label>Email</label>
            <input type="email" name="email" required />

            <label>Password</label>
            <input type="password" name="password" required />

            <label>Login as</label>
            <select name="role" required>
              <option value="">-- Select Role --</option>
              <option value="tenant">Tenant</option>
              <option value="landlord">Landlord</option>
              <option value="admin">Admin</option>
            </select>

            <button type="submit">Login</button>
            <p class="forget"><a href="#" onclick="showForm('forgot-form')">Forgot Password?</a></p>
            <p class="signup-link">Don't have an account? <a href="signup.php">Sign up here</a></p>
          </form>
        </div>

        <!-- Forgot Password Form -->
        <div id="forgot-form" class="hidden">
          <?php if ($forgot_msg): ?>
            <p class="message"><?= htmlspecialchars($forgot_msg) ?></p>
          <?php endif; ?>

          <form action="send_reset_code.php" method="post">
            <label>Enter your email to reset password</label>
            <input type="email" name="email" required />
            <button type="submit">Send Reset Code</button>
            <span class="back-link" onclick="showForm('login-form')">← Back to Login</span>
          </form>
        </div>

        <!-- Verify Code Form -->
        <div id="verify-form" class="hidden">
          <?php if ($verify_msg): ?>
            <p class="message"><?= htmlspecialchars($verify_msg) ?></p>
          <?php endif; ?>

          <form action="verify_code.php" method="post">
            <label>Enter Verification Code</label>
            <input type="text" name="code" required />
            <button type="submit">Verify</button>
            <span class="back-link" onclick="showForm('login-form')">← Back to Login</span>
          </form>
        </div>

        <!-- Reset Password Form -->
        <div id="reset-form" class="hidden">
          <?php if ($reset_msg): ?>
            <p class="message"><?= htmlspecialchars($reset_msg) ?></p>
          <?php endif; ?>

          <form action="update_password.php" method="post">
            <label>New Password</label>
            <input type="password" name="new_password" required />
            <label>Confirm Password</label>
            <input type="password" name="confirm_password" required />
            <button type="submit">Reset Password</button>
            <span class="back-link" onclick="showForm('login-form')">← Back to Login</span>
          </form>
        </div>

      </section>
    </div>
  </section>

  <script>
    function showForm(id) {
      const forms = ['login-form', 'forgot-form', 'verify-form', 'reset-form'];
      forms.forEach(form => {
        document.getElementById(form).classList.add('hidden');
      });
      document.getElementById(id).classList.remove('hidden');
    }

    // Optional: Auto-open based on session messages
    <?php if ($forgot_msg): ?> showForm('forgot-form'); <?php endif; ?>
    <?php if ($verify_msg): ?> showForm('verify-form'); <?php endif; ?>
    <?php if ($reset_msg): ?> showForm('reset-form'); <?php endif; ?>
  </script>
</body>
</html>
