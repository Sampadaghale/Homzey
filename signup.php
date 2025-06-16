<!DOCTYPE html> 
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <link rel="stylesheet" href="styles.css" />
  <title>Sign Up</title>
</head>
<body>

  <div class="signup-container">
    <section class="auth-card" aria-labelledby="signup-title">
      <img src="image/house.png" alt="Homzey Logo" class="logo" />
      <form action="signup_process.php" method="post" novalidate aria-label="Signup form">
        <label for="homepage-signup-name">Name</label>
        <input type="text" id="homepage-signup-name" name="name" placeholder="Your full name" required aria-required="true" />

        <label for="homepage-signup-email">Email</label>
        <input type="email" id="homepage-signup-email" name="email" placeholder="you@example.com" required aria-required="true" />

        <label for="role">Role</label>
        <select name="role" id="role" required>
          <option value="">-- Select Role --</option>
          <option value="1">Tenant</option>
          <option value="2">Landlord</option>
          <option value="3">Admin</option>
        </select>

        <label for="homepage-signup-password">Password</label>
        <input type="password" id="homepage-signup-password" name="password" placeholder="Create a password" required aria-required="true" />

        <label for="homepage-signup-confirm-password">Confirm Password</label>
        <input type="password" id="homepage-signup-confirm-password" name="confirm_password" placeholder="Confirm your password" required aria-required="true" />

        <button type="submit">Sign Up</button>
      </form>
    </section>
  </div>

</body>
</html>
