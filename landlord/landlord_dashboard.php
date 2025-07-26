<?php   
session_start();
include '../Main/db.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

$user_id = $_SESSION['user_id'];
$query = "SELECT name, profile_picture FROM users WHERE id = ?";
$stmt = $conn->prepare($query);
$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result();
$user = $result->fetch_assoc();

$name = $user['name'] ?? 'Landlord';
$profile_pic = !empty($user['profile_picture']) ? $user['profile_picture'] : 'image/default_profile.png';

// Total properties
$totalPropertiesQuery = "SELECT COUNT(*) as total FROM houses WHERE landlord_id = ?";
$stmt = $conn->prepare($totalPropertiesQuery);
$stmt->bind_param("i", $user_id);
$stmt->execute();
$totalProperties = $stmt->get_result()->fetch_assoc()['total'] ?? 0;

// Occupied properties
$occupiedQuery = "SELECT COUNT(*) as occupied FROM bookings b JOIN houses h ON b.house_id = h.id WHERE h.landlord_id = ?";
$stmt = $conn->prepare($occupiedQuery);
$stmt->bind_param("i", $user_id);
$stmt->execute();
$occupied = $stmt->get_result()->fetch_assoc()['occupied'] ?? 0;
$occupancyRate = $totalProperties > 0 ? round(($occupied / $totalProperties) * 100) : 0;

// Maintenance requests count
$maintenanceCountQuery = "SELECT COUNT(*) as count FROM maintenance_requests mr JOIN houses h ON mr.house_id = h.id WHERE h.landlord_id = ?";
$stmt = $conn->prepare($maintenanceCountQuery);
$stmt->bind_param("i", $user_id);
$stmt->execute();
$maintenanceCount = $stmt->get_result()->fetch_assoc()['count'] ?? 0;

// Monthly revenue
$currentMonth = date('Y-m-01');
$revenueQuery = "SELECT SUM(rp.amount) as revenue FROM rent_payments rp JOIN bookings b ON rp.booking_id = b.id JOIN houses h ON b.house_id = h.id WHERE h.landlord_id = ? AND rp.due_date >= ? AND rp.status = 'paid'";
$stmt = $conn->prepare($revenueQuery);
$stmt->bind_param("is", $user_id, $currentMonth);
$stmt->execute();
$monthlyRevenue = $stmt->get_result()->fetch_assoc()['revenue'] ?? 0;

// Revenue trend for chart (last 6 months)
$monthlyRevenueTrend = [];
for ($i = 5; $i >= 0; $i--) {
    $month = date('Y-m', strtotime("-$i months"));
    $start = $month . '-01';
    $end = date('Y-m-t', strtotime($start));
    $stmt = $conn->prepare(
        "SELECT SUM(rp.amount) as total FROM rent_payments rp JOIN bookings b ON rp.booking_id = b.id JOIN houses h ON b.house_id = h.id WHERE h.landlord_id = ? AND rp.due_date BETWEEN ? AND ? AND rp.status = 'paid'"
    );
    $stmt->bind_param("iss", $user_id, $start, $end);
    $stmt->execute();
    $result = $stmt->get_result()->fetch_assoc();
    $monthlyRevenueTrend[$month] = $result['total'] ?? 0;
}

// Recent maintenance requests
$maintenanceSql = "SELECT mr.*, h.title AS house_title FROM maintenance_requests mr JOIN houses h ON mr.house_id = h.id WHERE h.landlord_id = ? ORDER BY mr.created_at DESC LIMIT 5";
$maintenanceStmt = $conn->prepare($maintenanceSql);
$maintenanceStmt->bind_param("i", $user_id);
$maintenanceStmt->execute();
$maintenanceRequests = $maintenanceStmt->get_result();
?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1" />
  <title>Landlord Dashboard</title>
  <script src="https://cdn.tailwindcss.com"></script>
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" />
  <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
  <style>
    body { font-family: sans-serif; background: #f5f7fa; 
        margin: 0; }

    main {
  margin-left: 16rem; /* Sidebar width */
  padding: 1.5rem 2rem;
  min-height: 100vh;
  background: #f8fafc;
}
    /* Optional: prevent horizontal scroll on small devices */
    @media (max-width: 768px) {
      main {
        margin-left: 0;
        padding: 1rem;
      }
    }
  </style>
</head>
<body class="bg-gray-100 min-h-screen">
  <div class="flex min-h-screen">
    <?php include 'sidebar.php'; ?>

    <!-- Main Content -->
    <main class="flex-1 p-6">
      <h2 class="text-2xl font-bold mb-6">Welcome back, <?= htmlspecialchars($name) ?>!</h2>

      <!-- Stat Cards -->
      <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4 mb-6">
        <div class="dashboard-card bg-white p-4 rounded shadow">
          <p class="text-sm text-gray-500">Total Properties</p>
          <p class="text-3xl font-bold text-gray-800"><?= $totalProperties ?></p>
        </div>
        <div class="dashboard-card bg-white p-4 rounded shadow">
          <p class="text-sm text-gray-500">Occupancy Rate</p>
          <p class="text-3xl font-bold text-gray-800"><?= $occupancyRate ?>%</p>
        </div>
        <div class="dashboard-card bg-white p-4 rounded shadow">
          <p class="text-sm text-gray-500">Monthly Revenue</p>
          <p class="text-3xl font-bold text-gray-800">
            $<?= number_format($monthlyRevenue, 2) ?>
          </p>
        </div>
        <div class="dashboard-card bg-white p-4 rounded shadow">
          <p class="text-sm text-gray-500">Maintenance Tasks</p>
          <p class="text-3xl font-bold text-gray-800"><?= $maintenanceCount ?></p>
        </div>
      </div>

      <!-- Revenue Chart -->
      <div class="bg-white rounded shadow p-6 mb-6 max-w-full overflow-x-auto">
        <h3 class="text-lg font-semibold mb-4">Revenue Trends</h3>
        <canvas id="incomeChart" style="max-height: 250px; width: 100%; height: auto;"></canvas>
      </div>

      <!-- Maintenance Requests -->
      <div class="bg-white rounded-xl shadow p-6">
        <div class="flex justify-between items-center mb-6">
            <h2 class="font-bold text-gray-800">Recent Maintenance Requests</h2>
            <a href="maintenance_request.php" class="text-sm text-indigo-700 hover:text-indigo-900">View All</a>
        </div>
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
            <?php while ($row = $maintenanceRequests->fetch_assoc()): ?>
            <div class="border rounded-lg p-4 hover:border-indigo-300 transition">
                <div class="flex justify-between items-start mb-2">
                    <div>
                        <h3 class="font-medium"><?= htmlspecialchars($row['category']) ?></h3>
                        <p class="text-sm text-gray-500"><?= htmlspecialchars($row['house_title']) ?></p>
                    </div>
                    <span class="px-2 py-1 rounded-full text-xs <?= $row['status'] === 'Pending' ? 'bg-gray-200 text-gray-700' : ($row['status'] === 'In Progress' ? 'bg-blue-100 text-blue-800' : 'bg-green-100 text-green-800') ?>">
                        <?= htmlspecialchars($row['status']) ?>
                    </span>
                </div>
                <p class="text-sm text-gray-700 mb-2"><?= htmlspecialchars($row['description']) ?></p>
                <p class="text-xs text-gray-500"><?= date('M d, Y', strtotime($row['created_at'])) ?></p>
            </div>
            <?php endwhile; ?>
        </div>
      </div>
    </main>
  </div>

  <script>
    const ctx = document.getElementById("incomeChart").getContext("2d");
    new Chart(ctx, {
      type: 'line',
      data: {
        labels: <?= json_encode(array_keys($monthlyRevenueTrend)) ?>,
        datasets: [{
          label: 'Revenue',
          data: <?= json_encode(array_values($monthlyRevenueTrend)) ?>,
          backgroundColor: 'rgba(99, 102, 241, 0.1)',
          borderColor: 'rgba(99, 102, 241, 1)',
          borderWidth: 2,
          tension: 0.4,
          fill: true
        }]
      },
      options: {
        responsive: true,
        plugins: { legend: { display: false } },
        scales: {
          y: {
            beginAtZero: true,
            ticks: {
              callback: value => '$' + value.toLocaleString()
            }
          },
          x: { grid: { display: false } }
        }
      }
    });
  </script>
</body>
</html>
