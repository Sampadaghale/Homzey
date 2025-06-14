<?php 
session_start();
require 'db.php'; // expects $conn = mysqli_connect(...)

// Redirect if not tenant
if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] !== 'tenant') {
    header('Location: login.php');
    exit();
}

$tenant_id = $_SESSION['user_id'];

// Fetch bookings for tenant
$sql = "SELECT b.*, h.title, h.location 
        FROM bookings b
        JOIN houses h ON b.house_id = h.id
        WHERE b.tenant_id = ?
        ORDER BY b.booking_date DESC";

$stmt = mysqli_prepare($conn, $sql);
mysqli_stmt_bind_param($stmt, "i", $tenant_id);
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);

$bookings = [];
if ($result) {
    while ($row = mysqli_fetch_assoc($result)) {
        $bookings[] = $row;
    }
}
mysqli_stmt_close($stmt);
?>

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8" />
<title>Tenant Dashboard - Your Bookings</title>
<style>
  body { font-family: Arial, sans-serif; max-width: 900px; margin: 2rem auto; }
  table { border-collapse: collapse; width: 100%; }
  th, td { border: 1px solid #ddd; padding: 0.75rem; text-align: left; }
  th { background-color: #3b82f6; color: white; }
  tr:nth-child(even) { background-color: #f9f9f9; }
  .no-bookings { color: #555; font-style: italic; margin-top: 1rem; }
</style>
</head>
<body>

<h1>Your Booking History</h1>

<?php if (!empty($bookings)): ?>
  <table>
    <thead>
      <tr>
        <th>House</th>
        <th>Location</th>
        <th>Start Date</th>
        <th>End Date</th>
        <th>Status</th>
        <th>Total Price</th>
      </tr>
    </thead>
    <tbody>
      <?php foreach ($bookings as $b): ?>
        <tr>
          <td><?= htmlspecialchars($b['title']) ?></td>
          <td><?= htmlspecialchars($b['location']) ?></td>
          <td><?= htmlspecialchars($b['start_date']) ?></td>
          <td><?= htmlspecialchars($b['end_date']) ?></td>
          <td><?= htmlspecialchars(ucfirst($b['status'])) ?></td>
          <td>$<?= number_format($b['total_price'], 2) ?></td>
        </tr>
      <?php endforeach; ?>
    </tbody>
  </table>
<?php else: ?>
  <p class="no-bookings">You have no bookings yet.</p>
<?php endif; ?>

</body>
</html>
