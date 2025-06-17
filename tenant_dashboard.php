<?php   
session_start();
require 'db.php';

if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] !== 'tenant') {
    header('Location: login.php');
    exit();
}

$tenant_id = $_SESSION['user_id'];

// Fetch bookings with LEFT JOIN in case house deleted
$sql = "SELECT b.*, h.title, h.location 
        FROM bookings b
        LEFT JOIN houses h ON b.house_id = h.id
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
<meta name="viewport" content="width=device-width, initial-scale=1" />
<title>Tenant Dashboard - Your Bookings</title>
<style>
  body { font-family: Arial, sans-serif; max-width: 900px; margin: 2rem auto; background: #f9fafb; color: #111; }
  header {
    background: #3b82f6;
    padding: 1rem 2rem;
    color: white;
    display: flex;
    justify-content: space-between;
    align-items: center;
    border-radius: 6px;
    margin-bottom: 2rem;
  }
  header h1 {
    margin: 0;
    font-size: 1.5rem;
  }
  header nav a {
    color: white;
    text-decoration: none;
    margin-left: 1rem;
    font-weight: bold;
  }
  header nav a:hover {
    text-decoration: underline;
  }
  table { 
    border-collapse: collapse; 
    width: 100%; 
    background: white;
    border-radius: 6px;
    overflow: hidden;
    box-shadow: 0 0 8px rgb(0 0 0 / 0.1);
  }
  th, td { 
    border: 1px solid #ddd; 
    padding: 0.75rem; 
    text-align: left; 
  }
  th { 
    background-color: #3b82f6; 
    color: white; 
  }
  tr:nth-child(even) { background-color: #f3f4f6; }
  .no-bookings { color: #555; font-style: italic; margin-top: 1rem; }
  .cancel-btn {
    background-color: #ef4444;
    color: white;
    border: none;
    padding: 5px 10px;
    border-radius: 5px;
    cursor: pointer;
    font-weight: bold;
  }
  .cancel-btn:hover {
    background-color: #dc2626;
  }
  .status {
    font-weight: bold;
    padding: 4px 8px;
    border-radius: 12px;
    display: inline-block;
    text-transform: capitalize;
  }
  .status.confirmed {
    background-color: #d1fae5;
    color: #065f46;
  }
  .status.cancelled {
    background-color: #fee2e2;
    color: #991b1b;
  }
  .status.pending {
    background-color: #e0e7ff;
    color: #3730a3;
  }
  .status.unknown {
    background-color: #f3f4f6;
    color: #6b7280;
  }
  .message {
    background-color: #d1fae5;
    color: #065f46;
    padding: 10px;
    border: 1px solid #10b981;
    border-radius: 5px;
    margin-bottom: 1rem;
  }
</style>
</head>
<body>

<header>
  <h1>Tenant Dashboard</h1>
  <nav>
    <a href="index.php">Home</a>
    <a href="logout.php">Logout</a>
  </nav>
</header>

<?php if (isset($_SESSION['message'])): ?>
  <div class="message"><?= htmlspecialchars($_SESSION['message']); unset($_SESSION['message']); ?></div>
<?php endif; ?>

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
        <th>Action</th>
      </tr>
    </thead>
    <tbody>
      <?php foreach ($bookings as $b): ?>
        <tr>
          <td><?= $b['title'] ? htmlspecialchars($b['title']) : '<em>Property Deleted</em>' ?></td>
          <td><?= $b['location'] ? htmlspecialchars($b['location']) : '<em>N/A</em>' ?></td>
          <td><?= htmlspecialchars($b['start_date']) ?></td>
          <td><?= htmlspecialchars($b['end_date']) ?></td>
          <td>
            <?php
              $status = strtolower($b['status']);
              $class = in_array($status, ['confirmed', 'cancelled', 'pending']) ? $status : 'unknown';
            ?>
            <span class="status <?= $class ?>"><?= htmlspecialchars(ucfirst($status)) ?></span>
          </td>
          <td>Rs. <?= number_format($b['total_price'], 2) ?></td>
          <td>
            <?php if ($status === 'confirmed'): ?>
              <form action="cancel_booking.php" method="POST" onsubmit="return confirm('Are you sure you want to cancel this booking?');">
                <input type="hidden" name="booking_id" value="<?= $b['id'] ?>">
                <button type="submit" class="cancel-btn">Cancel</button>
              </form>
            <?php else: ?>
              &mdash;
            <?php endif; ?>
          </td>
        </tr>
      <?php endforeach; ?>
    </tbody>
  </table>
<?php else: ?>
  <p class="no-bookings">You have no bookings yet.</p>
<?php endif; ?>

</body>
</html>
