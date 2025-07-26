<?php 
// sidebar.php
if (session_status() === PHP_SESSION_NONE) session_start();
include '../Main/db.php';

if (!isset($_SESSION["user_id"]) || $_SESSION["user_role"] !== "landlord") {
    header("Location: ../login.php");
    exit();
}

$user_id = $_SESSION["user_id"];

// Fetch landlord name and profile picture
$query = "SELECT name, profile_picture FROM users WHERE id = ?";
$stmt = $conn->prepare($query);
$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result();
$user = $result->fetch_assoc();
$stmt->close();

$name = $user['name'] ?? 'Landlord';
$profile_pic = !empty($user['profile_picture']) ? '../image/' . $user['profile_picture'] : '../image/default_profile.png';

// Determine current page for active link highlight
$current_page = basename($_SERVER['PHP_SELF']);
?>

<aside class="sidebar fixed top-0 left-0 w-64 h-full bg-white shadow-md overflow-y-auto z-20" aria-label="Sidebar navigation">
  <!-- Logo -->
  <div class="p-4 border-b border-gray-200">
    <div class="flex items-center space-x-3">
      <img src="../image/house.png" alt="Homzey Logo" class="rounded-lg w-10 h-auto max-w-[120px]" />
      <h1 class="text-xl font-bold text-gray-800">Homzey</h1>
    </div>
  </div>

  <!-- Navigation -->
  <nav class="p-4">
    <ul class="space-y-2">
      <li>
        <a href="landlord_dashboard.php"
          class="flex items-center space-x-3 p-2 rounded-lg <?= $current_page === 'landlord_dashboard.php' ? 'bg-indigo-50 text-indigo-700' : 'hover:bg-gray-100 text-gray-700' ?>">
          <i class="fas fa-tachometer-alt"></i><span>Dashboard</span>
        </a>
      </li>
      <li>
        <a href="properties.php"
          class="flex items-center space-x-3 p-2 rounded-lg <?= $current_page === 'properties.php' ? 'bg-indigo-50 text-indigo-700' : 'hover:bg-gray-100 text-gray-700' ?>">
          <i class="fas fa-home"></i><span>Properties</span>
        </a>
      </li>
      <li>
        <a href="tenants.php"
          class="flex items-center space-x-3 p-2 rounded-lg <?= $current_page === 'tenants.php' ? 'bg-indigo-50 text-indigo-700' : 'hover:bg-gray-100 text-gray-700' ?>">
          <i class="fas fa-users"></i><span>Tenants</span>
        </a>
      </li>
      <li>
        <a href="landlord_payment.php"
          class="flex items-center space-x-3 p-2 rounded-lg <?= $current_page === 'landlord_payment.php' ? 'bg-indigo-50 text-indigo-700' : 'hover:bg-gray-100 text-gray-700' ?>">
          <i class="fas fa-file-invoice-dollar"></i><span>Payments</span>
        </a>
      </li>
      <li>
        <a href="maintenance_request.php"
          class="flex items-center space-x-3 p-2 rounded-lg <?= $current_page === 'maintenance.php' ? 'bg-indigo-50 text-indigo-700' : 'hover:bg-gray-100 text-gray-700' ?>">
          <i class="fas fa-tools"></i><span>Maintenance</span>
        </a>
      </li>
    
    
  <a href="profile.php"
    class="flex items-center space-x-3 p-2 rounded-lg <?= $current_page === 'profile.php' ? 'bg-indigo-50 text-indigo-700' : 'hover:bg-gray-100 text-gray-700' ?>">
    <i class="fas fa-user"></i><span>Profile</span>
  </a>
</li>

      <li>
        <a href="../Main/logout.php"
          class="flex items-center space-x-3 p-2 rounded-lg hover:bg-gray-100 text-gray-700">
          <i class="fas fa-sign-out-alt"></i><span>Logout</span>
        </a>
      </li>
    </ul>
  </nav>

  <!-- Profile at bottom -->
<div class="bottom-profile absolute bottom-0 w-full p-4 bg-gray-100 flex items-center gap-3">
  <img src="<?= htmlspecialchars($profile_pic) ?>" alt="Profile Picture" class="w-10 h-10 rounded-full object-cover" />
  <div class="flex-grow">
    <h4 class="font-semibold text-gray-900 m-0"><?= htmlspecialchars($name) ?></h4>
    <p class="text-sm text-gray-600 m-0">Landlord</p>
  </div>
</div>
</aside>
