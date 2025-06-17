<?php   
session_start();
include 'db.php';

// Enable error reporting for debugging
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// Load PHPMailer
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

require 'PHPMailer/PHPMailer.php';
require 'PHPMailer/SMTP.php';
require 'PHPMailer/Exception.php';

// Check if logged in as tenant
if (!isset($_SESSION["user_id"]) || $_SESSION["user_role"] !== "tenant") {
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

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $start_date = $_POST['start_date'] ?? '';
    $end_date = $_POST['end_date'] ?? '';
    $full_name = $_POST['full_name'] ?? '';
    $address = $_POST['address'] ?? '';
    $phone = $_POST['phone'] ?? '';

    if (!$start_date || !$end_date || strtotime($end_date) <= strtotime($start_date)) {
        echo "<p>Invalid dates.</p>";
        exit();
    }

    // Fetch house
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

    $days = (strtotime($end_date) - strtotime($start_date)) / (60 * 60 * 24);
    $total_price = $days * floatval($house['price']);
    $commission = $total_price * 0.1;

    // Insert booking with additional fields
    $stmt = mysqli_prepare($conn, "INSERT INTO bookings (tenant_id, house_id, booking_date, start_date, end_date, total_price, commission, name, address, phone, status) VALUES (?, ?, CURDATE(), ?, ?, ?, ?, ?, ?, ?, 'confirmed')");
    mysqli_stmt_bind_param($stmt, "iissddsss", $tenant_id, $house_id, $start_date, $end_date, $total_price, $commission, $full_name, $address, $phone);
    $exec_success = mysqli_stmt_execute($stmt);
    mysqli_stmt_close($stmt);

    if ($exec_success) {
        // Mark house as booked
        $stmt2 = mysqli_prepare($conn, "UPDATE houses SET status = 'booked' WHERE id = ?");
        mysqli_stmt_bind_param($stmt2, "i", $house_id);
        mysqli_stmt_execute($stmt2);
        mysqli_stmt_close($stmt2);

        // Fetch landlord info
        $stmt3 = mysqli_prepare($conn, "SELECT name, email, phone FROM users WHERE id = ?");
        mysqli_stmt_bind_param($stmt3, "i", $house['landlord_id']);
        mysqli_stmt_execute($stmt3);
        $landlord_result = mysqli_stmt_get_result($stmt3);
        $landlord = mysqli_fetch_assoc($landlord_result);
        mysqli_stmt_close($stmt3);

        // Send Emails
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

                // Email to tenant
                $mail->addAddress($tenant_email, $full_name);
                $mail->Subject = "Booking Confirmed - {$house['title']}";
                $mail->Body =
                    "Hello $full_name,\n\n" .
                    "Your booking for '{$house['title']}' is confirmed.\n\n" .
                    "Landlord Contact Info:\n" .
                    "Name: {$landlord['name']}\n" .
                    "Email: {$landlord['email']}\n" .
                    "Phone: {$landlord['phone']}\n\n" .
                    "Booking Info:\n" .
                    "Start Date: $start_date\n" .
                    "End Date: $end_date\n" .
                    "Total Price: Rs. $total_price\n\n" .
                    "Your Details:\n" .
                    "Name: $full_name\n" .
                    "Address: $address\n" .
                    "Phone: $phone";

                $mail->send();

                // Email to landlord
                $mail->clearAddresses();
                $mail->addAddress($landlord['email'], $landlord['name']);
                $mail->Subject = "New Booking Received";
                $mail->Body =
                    "Hello {$landlord['name']},\n\n" .
                    "Your house '{$house['title']}' has been booked.\n\n" .
                    "Tenant Info:\n" .
                    "Name: $full_name\n" .
                    "Address: $address\n" .
                    "Phone: $phone\n\n" .
                    "Booking Details:\n" .
                    "Start Date: $start_date\n" .
                    "End Date: $end_date\n" .
                    "Total Price: Rs. $total_price";

                $mail->send();

                echo "<p>Booking successful! Emails sent.</p>";
            } catch (Exception $e) {
                echo "<p>Booking saved, but email failed: {$mail->ErrorInfo}</p>";
            }
        } else {
            echo "<p>Booking saved. Email skipped due to missing addresses.</p>";
        }
    } else {
        echo "<p>Error while booking.</p>";
    }
} else {
    $stmt = mysqli_prepare($conn, "SELECT * FROM houses WHERE id = ?");
    mysqli_stmt_bind_param($stmt, "i", $house_id);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    $house = mysqli_fetch_assoc($result);
    mysqli_stmt_close($stmt);

    if ($house) {
        echo "<h2>Book: " . htmlspecialchars($house["title"]) . "</h2>";
        echo '<form method="post">
                <label>Full Name: <input type="text" name="full_name" required></label>
                <label>Address: <input type="text" name="address" required></label>
                <label>Phone Number: <input type="text" name="phone" required></label>
                <label>Start Date: <input type="date" name="start_date" required></label>
                <label>End Date: <input type="date" name="end_date" required></label>
                <button type="submit">Confirm Booking</button>
              </form>';
    } else {
        echo "<p>House not found.</p>";
    }
}
?>
</div>
</body>
</html>
