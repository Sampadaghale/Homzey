<!DOCTYPE html>  
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1" />
  <link rel="stylesheet" href="styles.css"/>
  <title>LogIn</title>
</head>
<body>
  <section class="auth-section" aria-label="Login and Signup forms on homepage">
    <h2>Access Your Account</h2>
    <div class="auth-forms">
      <section class="auth-card" aria-labelledby="login-title">
        <h3 id="login-title" style="color:#92400e; font-weight:800; font-size:1.75rem; margin-bottom:1rem;">
          Login
        </h3>
        <form action="login_process.php" method="post" novalidate aria-label="Login form">
          <label for="homepage-login-email">Email</label>
          <input
            type="email"
            id="homepage-login-email"
            name="email"
            placeholder="you@example.com"
            required
            aria-required="true"
          />

          <label for="homepage-login-password">Password</label>
          <input
            type="password"
            id="homepage-login-password"
            name="password"
            placeholder="Enter your password"
            required
            aria-required="true"
          />

          <label for="role">Login as</label>
          <select id="role" name="role" required>
            <option value="">-- Select Role --</option>
            <option value="tenant">Tenant</option>
            <option value="landlord">Landlord</option>
            <option value="admin">Admin</option>
          </select>

          <button type="submit" aria-label="Submit login form">Login</button>

          <p class="signup-link">
            Don't have an account? <a href="signup.php">Sign up here</a>
          </p>
        </form>
      </section>
    </div>
  </section>
</body>
</html>
