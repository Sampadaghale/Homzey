<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>Admin Management System</title>
  <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
  <style>
    :root {
      --primary: #6c63ff;
      --primary-dark: #564fd8;
      --danger: #ff6b6b;
      --danger-dark: #e53935;
      --success: #4caf50;
      --success-dark: #43a047;
      --warning: #ff9800;
      --info: #2196f3;
      --light: #f8f9fa;
      --dark: #343a40;
      --gray-100: #f8f9fa;
      --gray-200: #e9ecef;
      --gray-300: #dee2e6;
      --gray-400: #ced4da;
      --gray-500: #adb5bd;
      --gray-600: #6c757d;
      --gray-700: #495057;
      --gray-800: #343a40;
      --gray-900: #212529;
      --border-radius: 8px;
      --box-shadow: 0 4px 20px rgba(0, 0, 0, 0.08);
      --transition: all 0.3s cubic-bezier(0.25, 0.8, 0.25, 1);
    }
    
    * {
      margin: 0;
      padding: 0;
      box-sizing: border-box;
    }
    
    body {
      font-family: 'Inter', sans-serif;
      background-color: #f5f7ff;
      color: var(--gray-800);
      line-height: 1.6;
    }
    
    .container {
      max-width: 1200px;
      margin: 0 auto;
      padding: 2rem;
    }
    
    .card {
      background: white;
      border-radius: var(--border-radius);
      box-shadow: var(--box-shadow);
      padding: 2rem;
      margin-bottom: 2rem;
      transition: var(--transition);
      border: 1px solid rgba(0, 0, 0, 0.05);
    }
    
    .card:hover {
      box-shadow: 0 6px 25px rgba(0, 0, 0, 0.1);
    }
    
    .header {
      display: flex;
      justify-content: space-between;
      align-items: center;
      margin-bottom: 2rem;
      padding-bottom: 1rem;
      border-bottom: 1px solid var(--gray-200);
    }
    
    h1, h2, h3, h4 {
      color: var(--gray-800);
      font-weight: 600;
    }
    
    h1 {
      font-size: 1.75rem;
      display: flex;
      align-items: center;
      gap: 0.75rem;
    }
    
    h2 {
      font-size: 1.5rem;
      margin-bottom: 1.5rem;
      display: flex;
      align-items: center;
      gap: 0.75rem;
    }
    
    h3 {
      font-size: 1.25rem;
      margin-bottom: 1rem;
    }
    
    .btn {
      display: inline-flex;
      align-items: center;
      justify-content: center;
      padding: 0.75rem 1.5rem;
      border-radius: var(--border-radius);
      font-weight: 500;
      cursor: pointer;
      transition: var(--transition);
      border: none;
      text-decoration: none;
      font-size: 0.95rem;
      gap: 0.5rem;
    }
    
    .btn-primary {
      background-color: var(--primary);
      color: white;
    }
    
    .btn-primary:hover {
      background-color: var(--primary-dark);
      transform: translateY(-2px);
    }
    
    .btn-danger {
      background-color: var(--danger);
      color: white;
    }
    
    .btn-danger:hover {
      background-color: var(--danger-dark);
      transform: translateY(-2px);
    }
    
    .btn-success {
      background-color: var(--success);
      color: white;
    }
    
    .btn-success:hover {
      background-color: var(--success-dark);
      transform: translateY(-2px);
    }
    
    .btn-outline {
      background: transparent;
      border: 1px solid var(--gray-300);
      color: var(--gray-700);
    }
    
    .btn-outline:hover {
      background: var(--gray-100);
      transform: translateY(-2px);
    }
    
    .btn-sm {
      padding: 0.5rem 1rem;
      font-size: 0.85rem;
    }
    
    .form-group {
      margin-bottom: 1.5rem;
    }
    
    label {
      display: block;
      margin-bottom: 0.5rem;
      font-weight: 500;
      color: var(--gray-700);
      font-size: 0.95rem;
    }
    
    input[type="text"],
    input[type="email"],
    input[type="password"],
    select {
      width: 100%;
      padding: 0.875rem;
      border: 1px solid var(--gray-300);
      border-radius: var(--border-radius);
      font-size: 1rem;
      transition: var(--transition);
      background-color: white;
    }
    
    input[type="text"]:focus,
    input[type="email"]:focus,
    input[type="password"]:focus,
    select:focus {
      outline: none;
      border-color: var(--primary);
      box-shadow: 0 0 0 3px rgba(108, 99, 255, 0.15);
    }
    
    .alert {
      padding: 1rem 1.25rem;
      border-radius: var(--border-radius);
      margin-bottom: 1.5rem;
      display: flex;
      align-items: center;
      gap: 0.75rem;
      font-size: 0.95rem;
    }
    
    .alert-danger {
      background-color: rgba(255, 107, 107, 0.1);
      color: var(--danger);
      border-left: 4px solid var(--danger);
    }
    
    .alert-success {
      background-color: rgba(76, 175, 80, 0.1);
      color: var(--success);
      border-left: 4px solid var(--success);
    }
    
    table {
      width: 100%;
      border-collapse: collapse;
      margin-top: 1.5rem;
      background: white;
      border-radius: var(--border-radius);
      overflow: hidden;
      box-shadow: 0 0 0 1px var(--gray-200);
    }
    
    th, td {
      padding: 1.25rem;
      text-align: left;
      border-bottom: 1px solid var(--gray-200);
    }
    
    th {
      background-color: var(--gray-100);
      font-weight: 600;
      color: var(--gray-700);
      text-transform: uppercase;
      font-size: 0.75rem;
      letter-spacing: 0.05em;
    }
    
    tr:last-child td {
      border-bottom: none;
    }
    
    tr:hover {
      background-color: rgba(108, 99, 255, 0.03);
    }
    
    .actions {
      display: flex;
      gap: 0.75rem;
    }
    
    .login-container {
      max-width: 450px;
      margin: 5rem auto;
    }
    
    .login-logo {
      text-align: center;
      margin-bottom: 2.5rem;
    }
    
    .login-logo i {
      font-size: 3.5rem;
      color: var(--primary);
      background: rgba(108, 99, 255, 0.1);
      padding: 1.5rem;
      border-radius: 50%;
      margin-bottom: 1.5rem;
    }
    
    .login-title {
      text-align: center;
      margin-bottom: 2rem;
    }
    
    .footer {
      text-align: center;
      margin-top: 3rem;
      color: var(--gray-600);
      font-size: 0.875rem;
      padding-top: 1.5rem;
      border-top: 1px solid var(--gray-200);
    }
    
    .welcome-message {
      color: var(--gray-600);
      font-size: 0.95rem;
    }
    
    .input-with-icon {
      position: relative;
    }
    
    .input-with-icon i {
      position: absolute;
      left: 15px;
      top: 50%;
      transform: translateY(-50%);
      color: var(--gray-500);
    }
    
    .input-with-icon input {
      padding-left: 45px;
    }
    
    @media (max-width: 768px) {
      .container {
        padding: 1.5rem;
      }
      
      .header {
        flex-direction: column;
        align-items: flex-start;
        gap: 1.25rem;
      }
      
      .actions {
        flex-direction: column;
        gap: 0.75rem;
      }
      
      th, td {
        padding: 1rem;
      }
    }
  </style>
</head>
<body>

<!-- Login Page -->
<div class="container login-container">
  <div class="login-logo">
    <i class="fas fa-shield-alt"></i>
    <h2>Superadmin Portal</h2>
  </div>
  <div class="card">
    <div class="alert alert-danger">
      <i class="fas fa-exclamation-circle"></i> Incorrect password.
    </div>
    <form>
      <div class="form-group">
        <label for="email">Email Address</label>
        <div class="input-with-icon">
          <i class="fas fa-envelope"></i>
          <input type="email" id="email" name="email" placeholder="superadmin@example.com" required>
        </div>
      </div>
      <div class="form-group">
        <label for="password">Password</label>
        <div class="input-with-icon">
          <i class="fas fa-lock"></i>
          <input type="password" id="password" name="password" placeholder="••••••••" required>
        </div>
      </div>
      <button type="submit" class="btn btn-primary" style="width: 100%;">
        <i class="fas fa-sign-in-alt"></i> Login
      </button>
    </form>
  </div>
  <div class="footer">
    <p>Admin Management System &copy; 2023</p>
  </div>
</div>
</body>
</html>