<?php  
session_start();
include '../Main/db.php'; // expects $conn = mysqli_connect(...)

if (!isset($_SESSION["user_id"]) || $_SESSION["user_role"] !== "landlord") {
    header("Location: ../login.php");
    exit();
}

$user_id = $_SESSION["user_id"];

// Optional: show success/error message after booking action
$msg = '';
if (isset($_GET['msg'])) {
    $msg = htmlspecialchars($_GET['msg']);
}

// Fetch landlord's name and profile picture for sidebar
$query = "SELECT name, profile_picture FROM users WHERE id = ?";
$stmt = $conn->prepare($query);
$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result();
$user = $result->fetch_assoc();

$name = $user['name'] ?? 'Landlord';
$profile_pic = !empty($user['profile_picture']) ? $user['profile_picture'] : 'image/default_profile.png';

// Fetch landlord's houses (including is_approved field)
$sql = "SELECT * FROM houses WHERE landlord_id = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result();
$houses = $result->fetch_all(MYSQLI_ASSOC);
$stmt->close();

// Fetch bookings for landlord's houses
$sql = "
SELECT b.*, h.title AS house_title, h.id AS house_id, u.name AS tenant_name, u.email AS tenant_email 
FROM bookings b
JOIN houses h ON b.house_id = h.id
JOIN users u ON b.tenant_id = u.id
WHERE h.landlord_id = ?
ORDER BY b.booking_date DESC
";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result();
$bookings = $result->fetch_all(MYSQLI_ASSOC);
$stmt->close();
?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1" />
  <title>Properties - Landlord Dashboard</title>
  <script src="https://cdn.tailwindcss.com"></script>
  <link
    rel="stylesheet"
    href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css"
  />
  <style>
    :root {
      --primary: #4f46e5;
      --secondary: #f0f9ff;
      --dark: #1e293b;
    }
    body { font-family: sans-serif; background: #f5f7fa; 
        margin: 0; }

    main {
      margin-left: 16rem; /* Sidebar width */
      padding: 1.5rem 2rem;
      min-height: 100vh;
      background: #f8fafc;
    }
    h1 {
      font-size: 1.875rem;
      font-weight: 700;
      margin-bottom: 1rem;
      color: #111827; /* gray-900 */
    }
    section {
      background: white;
      padding: 1.5rem 2rem;
      border-radius: 0.5rem;
      margin-bottom: 2rem;
      box-shadow: 0 0 10px rgb(0 0 0 / 0.05);
    }
    table {
      width: 100%;
      border-collapse: collapse;
      margin-top: 1rem;
    }
    th, td {
      border: 1px solid #e5e7eb; /* gray-200 */
      padding: 0.75rem 1rem;
      text-align: left;
      vertical-align: middle;
      font-size: 0.875rem;
    }
    th {
      background-color: #102542;
      color: white;
    }
    .slider {
      position: relative;
      width: 120px;
      height: 90px;
      overflow: hidden;
      border-radius: 0.375rem;
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
      cursor: pointer;
      background: #102542;
      color: white;
      border: none;
      padding: 0.375rem 0.75rem;
      border-radius: 0.375rem;
      font-size: 0.875rem;
      margin-right: 0.3rem;
      display: inline-block;
      text-decoration: none;
    }
    .btn.delete {
      background: #dc3545;
    }
    .btn:hover {
      opacity: 0.9;
    }
    .status-confirmed {
      color: green;
      font-weight: bold;
    }
    .status-cancelled {
      color: red;
      font-weight: bold;
    }
    .approved {
      color: green;
      font-weight: bold;
    }
    .pending-approval {
      color: orange;
      font-weight: bold;
    }
    /* Smaller buttons for booking actions */
    form.booking-action-form button {
      font-size: 0.75rem;
      padding: 0.25rem 0.5rem;
      margin-left: 0.3rem;
    }
    /* Success message */
    .message {
      background: #d4edda;
      color: #155724;
      padding: 0.75rem 1rem;
      margin-bottom: 1.5rem;
      border-radius: 0.375rem;
      text-align: center;
    }
    /* Responsive */
    @media (max-width: 768px) {
      main {
        margin-left: 0;
        padding: 1rem;
      }
    }
  </style>
</head>
<body>

<!-- Sidebar -->
<?php include 'sidebar.php'; ?>

<!-- Main Content -->
<main>
  <?php if ($msg): ?>
    <div class="message"><?= $msg ?></div>
  <?php endif; ?>

  <section>
    <h2>Your Listings</h2>
    <div class="mb-4">
      <a href="add_listing.php" 
         class="inline-block bg-indigo-600 text-white px-4 py-2 rounded hover:bg-indigo-700 transition">
        <i class="fas fa-plus mr-2"></i> Add New Listing
      </a>
    </div>
    <table>
      <thead>
        <tr>
          <th>Images</th>
          <th>Title</th>
          <th>Location</th>
          <th>Price</th>
          <th>Approved</th>
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
                    <img src="../images/<?= htmlspecialchars($img) ?>" class="<?= $j === 0 ? 'active' : '' ?>">
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
                <?php if (!empty($house['is_approved']) && $house['is_approved'] == 1): ?>
                  <span class="approved">Approved</span>
                <?php else: ?>
                  <span class="pending-approval">Pending</span>
                <?php endif; ?>
              </td>
              <td>
                <a href="edit_listing.php?id=<?= $house['id']; ?>" class="btn">Edit</a>
                <a href="delete_listing.php?id=<?= $house['id']; ?>" class="btn delete" onclick="return confirm('Are you sure to delete this listing?');">Delete</a>
              </td>
            </tr>
          <?php endforeach; ?>
        <?php else: ?>
          <tr><td colspan="6">No listings found.</td></tr>
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
          <th>Status / Actions</th>
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
                <?php if ($booking['status'] === 'pending'): ?>
                  <form action="../landlord/handle_booking_action.php" method="post" class="booking-action-form" style="display:inline-block; margin-left:10px;">
                    <input type="hidden" name="booking_id" value="<?= $booking['id'] ?>">
                    <button type="submit" name="action" value="accept" class="btn">Accept</button>
                    <button type="submit" name="action" value="reject" class="btn delete">Reject</button>
                  </form>
                <?php endif; ?>
              </td>
            </tr>
          <?php endforeach; ?>
        <?php else: ?>
          <tr><td colspan="8">No bookings found.</td></tr>
        <?php endif; ?>
      </tbody>
    </table>
  </section>
</main>

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
