<?php 
session_start();
include '../includes/db.php'; // expects $conn = mysqli_connect(...)

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
SELECT b.*, h.title AS house_title, u.name AS tenant_name, u.email AS tenant_email 
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
    <title>Landlord Dashboard</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            margin: 0;
            padding: 20px;
            background: #f4f4f4;
        }
        h1 {
            text-align: center;
        }
        section {
            margin-bottom: 40px;
            background: white;
            padding: 15px 30px;
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
            vertical-align: middle;
        }
        th {
            background-color: #4CAF50;
            color: white;
        }
        img {
            max-width: 120px;
            max-height: 90px;
            object-fit: cover;
            border-radius: 4px;
        }
        .btn {
            background: #28a745;
            color: white;
            text-decoration: none;
            padding: 6px 12px;
            border-radius: 4px;
            font-size: 14px;
        }
        .btn.delete {
            background: #dc3545;
        }
    </style>
</head>
<body>

    <h1>Welcome, <?= htmlspecialchars($name); ?>!</h1>

    <section>
        <h2>Your Listings</h2>
        <a href="add_listing.php" class="btn">Add New Listing</a>
        <table>
            <thead>
                <tr>
                    <th>Image</th>
                    <th>Title</th>
                    <th>Location</th>
                    <th>Price</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php if (count($houses) > 0): ?>
                    <?php foreach ($houses as $house): ?>
                        <tr>
                            <td><img src="../<?= htmlspecialchars($house['image']); ?>" alt="<?= htmlspecialchars($house['title']); ?>"></td>
                            <td><?= htmlspecialchars($house['title']); ?></td>
                            <td><?= htmlspecialchars($house['location']); ?></td>
                            <td>$<?= htmlspecialchars($house['price']); ?></td>
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
                            <td><?= htmlspecialchars($booking['house_title']); ?></td>
                            <td><?= htmlspecialchars($booking['tenant_name']); ?></td>
                            <td><?= htmlspecialchars($booking['tenant_email']); ?></td>
                            <td><?= htmlspecialchars($booking['booking_date']); ?></td>
                            <td><?= htmlspecialchars($booking['start_date']); ?></td>
                            <td><?= htmlspecialchars($booking['end_date']); ?></td>
                            <td>$<?= htmlspecialchars($booking['total_price']); ?></td>
                            <td><?= htmlspecialchars(ucfirst($booking['status'])); ?></td>
                        </tr>
                    <?php endforeach; ?>
                <?php else: ?>
                    <tr><td colspan="8">No bookings found.</td></tr>
                <?php endif; ?>
            </tbody>
        </table>
    </section>

</body>
</html>
