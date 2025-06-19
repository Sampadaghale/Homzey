<?php    
session_start();
include 'db.php'; // expects $conn = mysqli_connect(...)

if (!isset($_SESSION["user_id"]) || $_SESSION["user_role"] !== "landlord") {
    header("Location: ../login.php");
    exit();
}

$user_id = $_SESSION["user_id"];

// Fetch landlord's name
$name = "";
$sql = "SELECT name FROM users WHERE id = ?";
$stmt = mysqli_prepare($conn, $sql);
mysqli_stmt_bind_param($stmt, "i", $user_id);
mysqli_stmt_execute($stmt);
mysqli_stmt_bind_result($stmt, $name);
mysqli_stmt_fetch($stmt);
mysqli_stmt_close($stmt);

// Fetch landlord's houses
$sql = "SELECT * FROM houses WHERE landlord_id = ?";
$stmt = mysqli_prepare($conn, $sql);
mysqli_stmt_bind_param($stmt, "i", $user_id);
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);
$houses = mysqli_fetch_all($result, MYSQLI_ASSOC);
mysqli_stmt_close($stmt);

// Fetch bookings for landlord's houses
$sql = "
SELECT b.*, h.title AS house_title, h.id AS house_id, u.name AS tenant_name, u.email AS tenant_email 
FROM bookings b
JOIN houses h ON b.house_id = h.id
JOIN users u ON b.tenant_id = u.id
WHERE h.landlord_id = ?
ORDER BY b.booking_date DESC
";
$stmt = mysqli_prepare($conn, $sql);
mysqli_stmt_bind_param($stmt, "i", $user_id);
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);
$bookings = mysqli_fetch_all($result, MYSQLI_ASSOC);
mysqli_stmt_close($stmt);
?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1" />
  <link rel="stylesheet" href="styles.css" />
  <title>Landlord Dashboard</title>
  <style>
    body {
      font-family: Arial, sans-serif;
      margin: 0;
      background: #f4f4f4;
    }
    header {
      background: white;
      display: flex;
      justify-content: space-between;
      align-items: center;
      padding: 10px 20px;
    }
    .logo img {
      width: 60px;
    }
    nav a {
      color: black;
      text-decoration: none;
      margin-left: 20px;
      font-weight: bold;
    }
    nav a:hover {
      text-decoration: underline;
    }
    h1 {
      text-align: center;
      margin: 20px 0;
    }
    section {
      margin: 20px;
      background: white;
      padding: 20px 30px;
      border-radius: 6px;
      box-shadow: 0 0 10px rgb(0 0 0 / 0.1);
    }
    table {
      width: 100%;
      border-collapse: collapse;
      margin-top: 15px;
    }
    th, td {
      border: 1px solid #ddd;
      padding: 10px;
      text-align: left;
    }
    th {
      background-color: #4CAF50;
      color: white;
    }
    .slider {
      position: relative;
      width: 120px;
      height: 90px;
      overflow: hidden;
      border-radius: 4px;
    }
    .slider img {
      width: 100%;
      height: 90px;
      object-fit: cover;
      display: none;
    }
    .slider img.active {
      display: block;
    }
    .dots {
      text-align: center;
      position: absolute;
      bottom: 4px;
      width: 100%;
    }
    .dot {
      height: 8px;
      width: 8px;
      margin: 0 2px;
      background-color: #bbb;
      border-radius: 50%;
      display: inline-block;
      cursor: pointer;
    }
    .dot.active {
      background-color: #717171;
    }
    .btn {
      background: #4CAF50;
      color: white;
      text-decoration: none;
      padding: 6px 12px;
      border-radius: 4px;
      font-size: 14px;
    }
    .btn.delete {
      background: #dc3545;
    }
    .status-confirmed {
      color: green;
      font-weight: bold;
    }
    .status-cancelled {
      color: red;
      font-weight: bold;
    }
  </style>
</head>
<body>

<header>
  <div class="logo">
    <img src="image/house.png" alt="Logo"> 
  </div>
  <nav>
    <a href="add_listing.php">Add Listing</a>
    <a href="logout.php">Logout</a>
  </nav>
</header>

<h1>Welcome, <?= htmlspecialchars($name); ?>!</h1>

<section>
  <h2>Your Listings</h2>
  <table>
    <thead>
      <tr>
        <th>Images</th>
        <th>Title</th>
        <th>Location</th>
        <th>Price</th>
        <th>Actions</th>
      </tr>
    </thead>
    <tbody>
      <?php if (count($houses) > 0): ?>
        <?php foreach ($houses as $i => $house): ?>
          <?php $images = explode(',', $house['image']); ?>
          <tr>
            <td>
              <div class="slider" id="slider-<?= $i ?>">
                <?php foreach ($images as $j => $img): ?>
                  <img src="images/<?= htmlspecialchars($img) ?>" class="<?= $j === 0 ? 'active' : '' ?>">
                <?php endforeach; ?>
                <div class="dots">
                  <?php foreach ($images as $j => $_): ?>
                    <span class="dot <?= $j === 0 ? 'active' : '' ?>" onclick="setSlide(<?= $i ?>, <?= $j ?>)"></span>
                  <?php endforeach; ?>
                </div>
              </div>
            </td>
            <td><?= htmlspecialchars($house['title']); ?></td>
            <td><?= htmlspecialchars($house['location']); ?></td>
            <td>Rs<?= htmlspecialchars($house['price']); ?></td>
            <td>
              <a href="edit_listing.php?id=<?= $house['id']; ?>" class="btn">Edit</a>
              <a href="delete_listing.php?id=<?= $house['id']; ?>" class="btn delete" onclick="return confirm('Are you sure to delete this listing?');">Delete</a>
            </td>
          </tr>
        <?php endforeach; ?>
      <?php else: ?>
        <tr><td colspan="5">No listings found.</td></tr>
      <?php endif; ?>
    </tbody>
  </table>
</section>

<section>
  <h2>Your Bookings</h2>
  <table>
    <thead>
      <tr>
        <th>House</th>
        <th>Tenant</th>
        <th>Tenant Email</th>
        <th>Booking Date</th>
        <th>Start Date</th>
        <th>End Date</th>
        <th>Total Price</th>
        <th>Status</th>
      </tr>
    </thead>
    <tbody>
      <?php if (count($bookings) > 0): ?>
        <?php foreach ($bookings as $booking): ?>
          <tr>
            <td>
              <a href="house_detail.php?id=<?= $booking['house_id']; ?>" target="_blank">
                <?= htmlspecialchars($booking['house_title']); ?>
              </a>
            </td>
            <td><?= htmlspecialchars($booking['tenant_name']); ?></td>
            <td><?= htmlspecialchars($booking['tenant_email']); ?></td>
            <td><?= htmlspecialchars($booking['booking_date']); ?></td>
            <td><?= htmlspecialchars($booking['start_date']); ?></td>
            <td><?= htmlspecialchars($booking['end_date']); ?></td>
            <td>Rs<?= htmlspecialchars($booking['total_price']); ?></td>
            <td class="status-<?= strtolower(htmlspecialchars($booking['status'])); ?>">
              <?= htmlspecialchars(ucfirst($booking['status'])); ?>
            </td>
          </tr>
        <?php endforeach; ?>
      <?php else: ?>
        <tr><td colspan="8">No bookings found.</td></tr>
      <?php endif; ?>
    </tbody>
  </table>
</section>

<script>
function setSlide(sliderIndex, imgIndex) {
  const slider = document.getElementById(`slider-${sliderIndex}`);
  const imgs = slider.querySelectorAll('img');
  const dots = slider.querySelectorAll('.dot');

  imgs.forEach((img, i) => img.classList.toggle('active', i === imgIndex));
  dots.forEach((dot, i) => dot.classList.toggle('active', i === imgIndex));
}
</script>

</body>
</html>