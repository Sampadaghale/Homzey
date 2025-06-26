<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <title>Admin Login - Homzey</title>
  <meta name="viewport" content="width=device-width, initial-scale=1" />
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
  <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
  <style>
    :root {
      --primary: #6c63ff;
      --primary-dark: #564fd8;
      --secondary: #4dabf7;
      --danger: #ff6b6b;
      --light: #f8f9fa;
      --dark: #343a40;
      --gray: #6c757d;
      --border-radius: 12px;
      --box-shadow: 0 10px 30px rgba(0, 0, 0, 0.1);
      --transition: all 0.3s ease;
    }
    
    * {
      margin: 0;
      padding: 0;
      box-sizing: border-box;
    }
    
    body {
      font-family: 'Poppins', sans-serif;
      background: linear-gradient(135deg, #f5f7fa 0%, #c3cfe2 100%);
      display: flex;
      justify-content: center;
      align-items: center;
      min-height: 100vh;
      padding: 20px;
    }
    
    .login-container {
      background: white;
      padding: 40px;
      border-radius: var(--border-radius);
      box-shadow: var(--box-shadow);
      width: 100%;
      max-width: 450px;
      position: relative;
      overflow: hidden;
      z-index: 1;
    }
    
    .login-container::before {
      content: '';
      position: absolute;
      top: 0;
      left: 0;
      width: 100%;
      height: 8px;
      background: linear-gradient(to right, var(--primary), var(--secondary));
    }
    
    .logo {
      text-align: center;
      margin-bottom: 30px;
    }
    
    .logo i {
      font-size: 2.5rem;
      color: var(--primary);
      margin-bottom: 10px;
    }
    
    .logo h1 {
      font-size: 1.8rem;
      color: var(--dark);
      font-weight: 600;
    }
    
    .logo p {
      color: var(--gray);
      font-size: 0.9rem;
    }
    
    h2 {
      text-align: center;
      margin-bottom: 25px;
      color: var(--dark);
      font-weight: 600;
      font-size: 1.5rem;
    }
    
    .form-group {
      margin-bottom: 20px;
      position: relative;
    }
    
    .form-group label {
      display: block;
      margin-bottom: 8px;
      color: var(--dark);
      font-weight: 500;
    }
    
    .input-with-icon {
      position: relative;
    }
    
    .input-with-icon i {
      position: absolute;
      left: 15px;
      top: 50%;
      transform: translateY(-50%);
      color: var(--gray);
    }
    
    input[type="email"], 
    input[type="password"] {
      width: 100%;
      padding: 14px 14px 14px 45px;
      border: 1px solid #e0e0e0;
      border-radius: var(--border-radius);
      font-size: 15px;
      transition: var(--transition);
    }
    
    input[type="email"]:focus, 
    input[type="password"]:focus {
      outline: none;
      border-color: var(--primary);
      box-shadow: 0 0 0 3px rgba(108, 99, 255, 0.2);
    }
    
    .btn {
      width: 100%;
      padding: 14px;
      background: linear-gradient(to right, var(--primary), var(--secondary));
      color: white;
      border: none;
      border-radius: var(--border-radius);
      font-size: 16px;
      font-weight: 500;
      cursor: pointer;
      transition: var(--transition);
      margin-top: 10px;
    }
    
    .btn:hover {
      background: linear-gradient(to right, var(--primary-dark), var(--secondary));
      transform: translateY(-2px);
    }
    
    .error {
      background-color: rgba(255, 107, 107, 0.1);
      color: var(--danger);
      padding: 12px;
      border-radius: var(--border-radius);
      margin-bottom: 20px;
      text-align: center;
      border-left: 4px solid var(--danger);
      font-size: 14px;
    }
    
    .footer-links {
      margin-top: 25px;
      text-align: center;
      font-size: 14px;
    }
    
    .footer-links a {
      color: var(--gray);
      text-decoration: none;
      transition: var(--transition);
    }
    
    .footer-links a:hover {
      color: var(--primary);
    }
    
    .footer-links span {
      margin: 0 10px;
      color: #e0e0e0;
    }
    
    @media (max-width: 480px) {
      .login-container {
        padding: 30px 20px;
      }
      
      .logo h1 {
        font-size: 1.5rem;
      }
      
      h2 {
        font-size: 1.3rem;
      }
    }
  </style>
</head>
<body>
  <div class="login-container">
    <div class="logo">
      <i class="fas fa-home"></i>
      <h1>Homzey</h1>
      <p>Admin Dashboard</p>
    </div>
    
    <h2>Sign In</h2>    
    <form method="POST" action="">
      <div class="form-group">
        <label for="email">Email Address</label>
        <div class="input-with-icon">
          <i class="fas fa-envelope"></i>
          <input type="email" id="email" name="email" placeholder="admin@example.com" required>
        </div>
      </div>
      
      <div class="form-group">
        <label for="password">Password</label>
        <div class="input-with-icon">
          <i class="fas fa-lock"></i>
          <input type="password" id="password" name="password" placeholder="••••••••" required>
        </div>
      </div>
      
      <button type="submit" class="btn">
        <i class="fas fa-sign-in-alt"></i> Login
      </button>
    </form>
    
    <div class="footer-links">
      <a href="#"><i class="fas fa-question-circle"></i> Need help?</a>
      <span>|</span>
      <a href="#"><i class="fas fa-key"></i> Forgot password?</a>
    </div>
  </div>
</body>
</html>
