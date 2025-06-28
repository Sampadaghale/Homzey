<?php
session_start();
include 'db.php';

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

require 'PHPMailer/PHPMailer.php';
require 'PHPMailer/SMTP.php';
require 'PHPMailer/Exception.php';

// Check if tenant is logged in
if (!isset($_SESSION["user_id"]) || $_SESSION["user_role"] != "tenant") {
    header("Location: login.php");
    exit();
}

$tenant_id = $_SESSION["user_id"];
$tenant_name = $_SESSION["user_name"] ?? 'Tenant';
$tenant_email = $_SESSION["user_email"] ?? '';
$house_id = isset($_GET["id"]) ? intval($_GET["id"]) : 0;
$search = $_GET['search'] ?? '';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>Booking - Homzey</title>
    <link rel="stylesheet" href="styles.css" />
</head>
<body>
<header>
    <nav class="container nav-flex">
        <div class="logo"><img src="image/house.png" alt="Homzey logo" /></div>
        <form action="browse.php" method="get" class="search-form">
            <input type="text" name="search" placeholder="Search by location, title or price" value="<?= htmlspecialchars($search); ?>" />
            <button type="submit">Search</button>
        </form>
        <div class="nav-links">
            <a href="index.php">Home</a>
            <a href="browse.php">Browse</a>
            <?php if (isset($_SESSION['user_name'])): ?>
                <div class="user-dropdown-container">
                    <button class="user-toggle" onclick="toggleDropdown()">
                        <span><?= htmlspecialchars($_SESSION['user_name']); ?></span>
                        <i class="fa fa-caret-down" aria-hidden="true"></i>
                    </button>
                    <div id="userDropdown" class="user-dropdown hidden">
                        <strong class="user-name"><?= htmlspecialchars($_SESSION['user_name']); ?></strong>
                        <a href="tenant_dashboard.php" class="account-button booking-btn">My Bookings</a>
                        <a href="logout.php" class="account-button logout-btn">Logout</a>
                    </div>
                </div>
            <?php else: ?>
                <a href="login.php">Login</a>
            <?php endif; ?>
        </div>
    </nav>
</header>

<div class="booking-container">
<?php
if ($house_id <= 0) {
    echo "<p>Invalid request.</p>";
    exit();
}

$stmt = mysqli_prepare($conn, "SELECT * FROM houses WHERE id = ?");
mysqli_stmt_bind_param($stmt, "i", $house_id);
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);
$house = mysqli_fetch_assoc($result);
mysqli_stmt_close($stmt);

if (!$house) {
    echo "<p>House not found.</p>";
    exit();
}

$imagePreview = explode(',', $house['image'])[0];
echo '<div class="house-summary">';
echo "<img src='images/" . htmlspecialchars(trim($imagePreview)) . "' alt='House Image' width='300'><br>";
echo "<strong>Location:</strong> " . htmlspecialchars($house['location']) . "<br>";
echo "<strong>Monthly Price:</strong> Rs. " . htmlspecialchars($house['price']) . "<br><br>";
echo "</div>";

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $start_date = $_POST['start_date'] ?? '';
    $end_date = $_POST['end_date'] ?? '';
    $full_name = $_POST['full_name'] ?? '';
    $address = $_POST['address'] ?? '';
    $phone = $_POST['phone'] ?? '';
    $notes = $_POST['notes'] ?? '';

    if (!$start_date || !$end_date || strtotime($end_date) <= strtotime($start_date)) {
        echo "<p>Invalid dates.</p>";
        exit();
    }

    $checkBooking = $conn->prepare("SELECT id FROM bookings WHERE tenant_id = ? AND house_id = ?");
    $checkBooking->bind_param("ii", $tenant_id, $house_id);
    $checkBooking->execute();
    $checkBookingResult = $checkBooking->get_result();
    if ($checkBookingResult->num_rows > 0) {
        echo "<p>You have already booked this house.</p>";
        exit();
    }

    $ref_code = strtoupper(uniqid("BOOK"));
    $status = 'pending';

    $stmt = mysqli_prepare($conn, "INSERT INTO bookings (tenant_id, house_id, booking_date, start_date, end_date, name, address, phone, status, notes, ref_code) VALUES (?, ?, CURDATE(), ?, ?, ?, ?, ?, ?, ?, ?)");
    mysqli_stmt_bind_param($stmt, "iissssssss", $tenant_id, $house_id, $start_date, $end_date, $full_name, $address, $phone, $status, $notes, $ref_code);
    $exec_success = mysqli_stmt_execute($stmt);
    mysqli_stmt_close($stmt);

    if ($exec_success) {
        $stmt3 = mysqli_prepare($conn, "SELECT name, email, phone FROM users WHERE id = ?");
        mysqli_stmt_bind_param($stmt3, "i", $house['landlord_id']);
        mysqli_stmt_execute($stmt3);
        $landlord_result = mysqli_stmt_get_result($stmt3);
        $landlord = mysqli_fetch_assoc($landlord_result);
        mysqli_stmt_close($stmt3);

        if ($tenant_email && $landlord['email']) {
            $mail = new PHPMailer(true);
            try {
                $mail->isSMTP();
                $mail->Host = 'smtp.gmail.com';
                $mail->SMTPAuth = true;
                $mail->Username = 'homzeyrent@gmail.com';
                $mail->Password = 'yrvk pqxh hsxa stsk';
                $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
                $mail->Port = 587;
                $mail->setFrom('homzeyrent@gmail.com', 'Homzey');

                $mail->addAddress($landlord['email'], $landlord['name']);
                $mail->Subject = "Booking Request Received - Ref: $ref_code";
                $mail->Body =
                    "Hello {$landlord['name']},\n\nYou have received a booking request for your house '{$house['title']}'.\n\nReference Code: $ref_code\n\nTenant Info:\nName: $full_name\nAddress: $address\nPhone: $phone\n\nBooking Duration:\nStart Date: $start_date\nEnd Date: $end_date\nNotes: $notes\n\nPlease log in to your landlord dashboard to accept or reject this request.";

                $mail->send();

                echo "<p>Booking request sent! Please wait for landlord confirmation. Ref: <strong>$ref_code</strong></p>";
            } catch (Exception $e) {
                echo "<p>Booking request saved, but email failed: {$mail->ErrorInfo}</p>";
            }
        } else {
            echo "<p>Booking request saved. Email skipped due to missing addresses.</p>";
        }
    } else {
        echo "<p>Error while booking.</p>";
    }
} else {
    echo "<h2>Book: " . htmlspecialchars($house["title"]) . "</h2>";
    echo '<form method="post">
            <label>Full Name: <input type="text" name="full_name" required></label>
            <label>Address: <input type="text" name="address" required></label>
            <label>Phone Number: <input type="text" name="phone" pattern="[0-9]{10}" title="Enter 10-digit number" required></label>
            <label>Start Date: <input type="date" name="start_date" required></label>
            <label>End Date: <input type="date" name="end_date" required></label>
            <label>Additional Notes (Optional):<br><textarea name="notes" rows="3"></textarea></label>
            <button type="submit">Request Booking</button>
          </form>';
}
?>
</div>
<script>
function toggleDropdown() {
  const dropdown = document.getElementById("userDropdown");
  dropdown.classList.toggle("hidden");
}
</script>
</body>
</html>