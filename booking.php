<?php 
session_start();
include 'db.php';  // assumes $conn is mysqli connection

// Check tenant login
if (!isset($_SESSION["user_id"]) || $_SESSION["user_role"] !== "tenant") {
    header("Location: ../login.php");
    exit();
}

$tenant_id = $_SESSION["user_id"];
$tenant_email = $_SESSION["user_email"] ?? '';
$house_id = isset($_GET["id"]) ? intval($_GET["id"]) : 0;

if ($house_id <= 0) {
    echo "<p>Invalid request.</p>";
    exit();
}

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $start_date = $_POST['start_date'] ?? '';
    $end_date = $_POST['end_date'] ?? '';

    // Basic date validation
    if (!$start_date || !$end_date || strtotime($start_date) === false || strtotime($end_date) === false) {
        echo "<p>Please provide valid start and end dates.</p>";
        exit();
    }

    if (strtotime($end_date) <= strtotime($start_date)) {
        echo "<p>End date must be after start date.</p>";
        exit();
    }

    // Get house info
    $sql = "SELECT * FROM houses WHERE id = ?";
    $stmt = mysqli_prepare($conn, $sql);
    mysqli_stmt_bind_param($stmt, "i", $house_id);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    $house = mysqli_fetch_assoc($result);
    mysqli_stmt_close($stmt);

    if (!$house) {
        echo "<p>House not found.</p>";
        exit();
    }

    // Calculate days, price, commission
    $days = (strtotime($end_date) - strtotime($start_date)) / (60 * 60 * 24);
    $price_per_day = floatval($house['price']);
    $total_price = $days * $price_per_day;
    $commission = $total_price * 0.1; // 10%

    // Insert booking
    $booking_sql = "INSERT INTO bookings (tenant_id, house_id, booking_date, start_date, end_date, total_price, commission, status)
                    VALUES (?, ?, CURDATE(), ?, ?, ?, ?, 'confirmed')";
    $stmt = mysqli_prepare($conn, $booking_sql);
    mysqli_stmt_bind_param($stmt, "iissdd", $tenant_id, $house_id, $start_date, $end_date, $total_price, $commission);
    $exec_success = mysqli_stmt_execute($stmt);
    mysqli_stmt_close($stmt);

    if ($exec_success) {
        // Update house status to 'booked'
        $update_sql = "UPDATE houses SET status = 'booked' WHERE id = ?";
        $stmt2 = mysqli_prepare($conn, $update_sql);
        mysqli_stmt_bind_param($stmt2, "i", $house_id);
        mysqli_stmt_execute($stmt2);
        mysqli_stmt_close($stmt2);

        // Get landlord info
        $landlord_id = $house['landlord_id'];
        $landlord_sql = "SELECT name, email FROM users WHERE id = ?";
        $stmt3 = mysqli_prepare($conn, $landlord_sql);
        mysqli_stmt_bind_param($stmt3, "i", $landlord_id);
        mysqli_stmt_execute($stmt3);
        $landlord_result = mysqli_stmt_get_result($stmt3);
        $landlord = mysqli_fetch_assoc($landlord_result);
        mysqli_stmt_close($stmt3);

        // Send emails (simple mail function)
        if ($tenant_email && $landlord) {
            $tenant_subject = "Booking Confirmed - " . $house['title'];
            $tenant_message = "Your booking is confirmed.\nLandlord Contact:\nName: " . $landlord['name'] . "\nEmail: " . $landlord['email'];
            mail($tenant_email, $tenant_subject, $tenant_message);

            $landlord_subject = "New Booking for Your House";
            $landlord_message = "Your house '" . $house['title'] . "' was booked.\nTenant Email: " . $tenant_email;
            mail($landlord['email'], $landlord_subject, $landlord_message);
        }

        echo "<p>Booking successful! Confirmation has been emailed.</p>";
    } else {
        echo "<p>Error while booking. Please try again.</p>";
    }
} else {
    // Show booking form
    $sql = "SELECT * FROM houses WHERE id = ?";
    $stmt = mysqli_prepare($conn, $sql);
    mysqli_stmt_bind_param($stmt, "i", $house_id);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    $house = mysqli_fetch_assoc($result);
    mysqli_stmt_close($stmt);

    if ($house) {
        ?>
        <h2>Book: <?= htmlspecialchars($house["title"]) ?></h2>
        <form method="post">
            <label>Start Date: <input type="date" name="start_date" required></label><br><br>
            <label>End Date: <input type="date" name="end_date" required></label><br><br>
            <button type="submit">Confirm Booking</button>
        </form>
        <?php
    } else {
        echo "<p>House not found.</p>";
    }
}
?>
