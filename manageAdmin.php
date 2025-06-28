<?php
session_start();
require 'db.php';

$timeout_duration = 2*60;

if (isset($_SESSION['LAST_ACTIVITY']) && (time() - $_SESSION['LAST_ACTIVITY']) > $timeout_duration) {
    session_unset();     // clear session
    session_destroy();   // destroy session
    header("Location: index.php");
    exit();
}

$_SESSION['LAST_ACTIVITY'] = time();

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['login_superadmin'])) {
    $email = trim($_POST['email']);
    $password = $_POST['password'];

    $stmt = $conn->prepare("SELECT * FROM users WHERE email = ? AND role = 'superadmin'");
    $stmt->bind_param("s", $email);
    $stmt->execute();
    $res = $stmt->get_result();

    if ($res && $user = $res->fetch_assoc()) {
        if (password_verify($password, $user['password'])) {
            $_SESSION['superadmin'] = $user;
            header("Location: manageAdmin.php");
            exit();
        } else {
            $error = "Incorrect password.";

        }
    } else {
        $error = "Superadmin not found.";
    }
}

// Logout
if (isset($_GET['logout'])) {
    session_destroy();
    header("Location: manageAdmin.php");
    exit();
}

// CRUD actions (only for logged-in superadmin)
if (isset($_SESSION['superadmin'])) {
    // Create admin
    if (isset($_POST['action']) && $_POST['action'] === 'create') {
        $name = trim($_POST['name']);
        $email = trim($_POST['email']);
        $password = password_hash($_POST['password'], PASSWORD_BCRYPT);
        $stmt = $conn->prepare("INSERT INTO users (name, email, password, role) VALUES (?, ?, ?, 'admin')");
        $stmt->bind_param("sss", $name, $email, $password);
        $stmt->execute();
        $success = "Admin created successfully!";
    }

    // Update admin
    if (isset($_POST['action']) && $_POST['action'] === 'update') {
        $id = $_POST['id'];
        $name = trim($_POST['name']);
        $email = trim($_POST['email']);
        $stmt = $conn->prepare("UPDATE users SET name=?, email=? WHERE id=? AND role='admin'");
        $stmt->bind_param("ssi", $name, $email, $id);
        $stmt->execute();
        $success = "Admin updated successfully!";
    }

    // Delete admin
    if (isset($_GET['delete']) && is_numeric($_GET['delete'])) {
        $id = $_GET['delete'];
        $stmt = $conn->prepare("DELETE FROM users WHERE id=? AND role='admin'");
        $stmt->bind_param("i", $id);
        $stmt->execute();
        $success = "Admin deleted successfully!";
    }

    // Fetch admins
    $admins = $conn->query("SELECT * FROM users WHERE role = 'admin'");
}
?>

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
      --primary: #4361ee;
      --primary-dark: #3a56d4;
      --danger: #f44336;
      --danger-dark: #e53935;
      --success: #4caf50;
      --success-dark: #43a047;
      --gray-100: #f8f9fa;
      --gray-200: #e9ecef;
      --gray-300: #dee2e6;
      --gray-400: #ced4da;
      --gray-500: #adb5bd;
      --gray-600: #6c757d;
      --gray-700: #495057;
      --gray-800: #343a40;
      --gray-900: #212529;
    }
    
    * {
      margin: 0;
      padding: 0;
      box-sizing: border-box;
    }
    
    body {
      font-family: 'Inter', sans-serif;
      background-color: var(--gray-100);
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
      border-radius: 0.5rem;
      box-shadow: 0 4px 6px rgba(0, 0, 0, 0.05);
      padding: 2rem;
      margin-bottom: 2rem;
    }
    
    .header {
      display: flex;
      justify-content: space-between;
      align-items: center;
      margin-bottom: 2rem;
    }
    
    h1, h2, h3 {
      color: var(--gray-800);
      font-weight: 600;
    }
    
    h1 {
      font-size: 1.75rem;
    }
    
    h2 {
      font-size: 1.5rem;
      margin-bottom: 1.5rem;
    }
    
    h3 {
      font-size: 1.25rem;
      margin-bottom: 1rem;
    }
    
    .btn {
      display: inline-flex;
      align-items: center;
      justify-content: center;
      padding: 0.625rem 1.25rem;
      border-radius: 0.375rem;
      font-weight: 500;
      cursor: pointer;
      transition: all 0.2s ease;
      border: none;
      text-decoration: none;
    }
    
    .btn-primary {
      background-color: var(--primary);
      color: white;
    }
    
    .btn-primary:hover {
      background-color: var(--primary-dark);
    }
    
    .btn-danger {
      background-color: var(--danger);
      color: white;
    }
    
    .btn-danger:hover {
      background-color: var(--danger-dark);
    }
    
    .btn-success {
      background-color: var(--success);
      color: white;
    }
    
    .btn-success:hover {
      background-color: var(--success-dark);
    }
    
    .btn-outline {
      background: transparent;
      border: 1px solid var(--gray-300);
      color: var(--gray-700);
    }
    
    .btn-outline:hover {
      background: var(--gray-100);
    }
    
    .btn-sm {
      padding: 0.375rem 0.75rem;
      font-size: 0.875rem;
    }
    
    .btn i {
      margin-right: 0.5rem;
    }
    
    .form-group {
      margin-bottom: 1.25rem;
    }
    
    label {
      display: block;
      margin-bottom: 0.5rem;
      font-weight: 500;
      color: var(--gray-700);
    }
    
    input[type="text"],
    input[type="email"],
    input[type="password"] {
      width: 100%;
      padding: 0.75rem;
      border: 1px solid var(--gray-300);
      border-radius: 0.375rem;
      font-size: 1rem;
      transition: border-color 0.2s;
    }
    
    input[type="text"]:focus,
    input[type="email"]:focus,
    input[type="password"]:focus {
      outline: none;
      border-color: var(--primary);
      box-shadow: 0 0 0 3px rgba(67, 97, 238, 0.1);
    }
    
    .alert {
      padding: 1rem;
      border-radius: 0.375rem;
      margin-bottom: 1.5rem;
    }
    
    .alert-danger {
      background-color: #fdecea;
      color: var(--danger);
      border-left: 4px solid var(--danger);
    }
    
    .alert-success {
      background-color: #e8f5e9;
      color: var(--success);
      border-left: 4px solid var(--success);
    }
    
    table {
      width: 100%;
      border-collapse: collapse;
      margin-top: 1.5rem;
    }
    
    th, td {
      padding: 1rem;
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
    
    tr:hover {
      background-color: var(--gray-50);
    }
    
    .actions {
      display: flex;
      gap: 0.5rem;
    }
    
    .login-container {
      max-width: 400px;
      margin: 5rem auto;
    }
    
    .login-logo {
      text-align: center;
      margin-bottom: 2rem;
    }
    
    .login-logo i {
      font-size: 3rem;
      color: var(--primary);
    }
    
    .login-title {
      text-align: center;
      margin-bottom: 2rem;
    }
    
    .footer {
      text-align: center;
      margin-top: 2rem;
      color: var(--gray-600);
      font-size: 0.875rem;
    }
    
    @media (max-width: 768px) {
      .container {
        padding: 1rem;
      }
      
      .header {
        flex-direction: column;
        align-items: flex-start;
        gap: 1rem;
      }
      
      .actions {
        flex-direction: column;
        gap: 0.5rem;
      }
    }
  </style>
</head>
<body>

<?php if (!isset($_SESSION['superadmin'])): ?>
  <div class="container login-container">
    <div class="login-logo">
      <i class="fas fa-shield-alt"></i>
    </div>
    <div class="card">
      <h2 class="login-title">Superadmin Portal</h2>
      <?php if (isset($error)): ?>
        <div class="alert alert-danger">
          <i class="fas fa-exclamation-circle"></i> <?= $error ?>
        </div>
      <?php endif; ?>
      <form method="POST">
        <div class="form-group">
          <label for="email">Email</label>
          <input type="email" id="email" name="email" placeholder="Enter your email" required>
        </div>
        <div class="form-group">
          <label for="password">Password</label>
          <input type="password" id="password" name="password" placeholder="Enter your password" required>
        </div>
        <button type="submit" name="login_superadmin" class="btn btn-primary" style="width: 100%;">
          <i class="fas fa-sign-in-alt"></i> Login
        </button>
      </form>
    </div>
    <div class="footer">
      <p>Admin Management System &copy; <?= date('Y') ?></p>
    </div>
  </div>
<?php else: ?>
  <div class="container">
    <div class="header">
      <div>
        <h1><i class="fas fa-shield-alt"></i> Admin Management</h1>
        <p>Welcome back, <?= htmlspecialchars($_SESSION['superadmin']['name']) ?></p>
      </div>
      <a href="?logout" class="btn btn-danger">
        <i class="fas fa-sign-out-alt"></i> Logout
      </a>
    </div>
    
    <?php if (isset($success)): ?>
      <div class="alert alert-success">
        <i class="fas fa-check-circle"></i> <?= $success ?>
      </div>
    <?php endif; ?>
    
    <div class="card">
      <h2><i class="fas fa-user-plus"></i> Create New Admin</h2>
      <form method="POST">
        <input type="hidden" name="action" value="create">
        <div class="form-group">
          <label for="name">Full Name</label>
          <input type="text" id="name" name="name" placeholder="Enter admin's full name" required>
        </div>
        <div class="form-group">
          <label for="email">Email Address</label>
          <input type="email" id="email" name="email" placeholder="Enter admin's email" required>
        </div>
        <div class="form-group">
          <label for="password">Password</label>
          <input type="password" id="password" name="password" placeholder="Set a password" required>
        </div>
        <button type="submit" class="btn btn-success">
          <i class="fas fa-save"></i> Create Admin
        </button>
      </form>
    </div>
    
    <div class="card">
      <h2><i class="fas fa-users-cog"></i> Admin List</h2>
      <table>
        <thead>
          <tr>
            <th>Name</th>
            <th>Email</th>
            <th>Actions</th>
          </tr>
        </thead>
        <tbody>
          <?php while ($admin = $admins->fetch_assoc()): ?>
          <tr>
            <form method="POST">
              <input type="hidden" name="action" value="update">
              <input type="hidden" name="id" value="<?= $admin['id'] ?>">
              <td>
                <input type="text" name="name" value="<?= htmlspecialchars($admin['name']) ?>" required>
              </td>
              <td>
                <input type="email" name="email" value="<?= htmlspecialchars($admin['email']) ?>" required>
              </td>
              <td class="actions">
                <button type="submit" class="btn btn-primary btn-sm">
                  <i class="fas fa-edit"></i> Update
                </button>
                <a href="?delete=<?= $admin['id'] ?>" onclick="return confirm('Are you sure you want to delete this admin?')" class="btn btn-danger btn-sm">
                  <i class="fas fa-trash-alt"></i> Delete
                </a>
              </td>
            </form>
          </tr>
          <?php endwhile; ?>
        </tbody>
      </table>
    </div>
    
    <div class="footer">
      <p>Admin Management System &copy; <?= date('Y') ?></p>
    </div>
  </div>
<?php endif; ?>

</body>
</html>
<script>
  setTimeout(() => {
    window.location.reload();
  }, 40000); //ms
</script>
