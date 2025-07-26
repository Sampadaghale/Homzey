<?php 
session_start();
include '../Main/db.php';

if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] !== 'tenant') {
    header('Location: login.php');
    exit();
}

$tenant_id = $_SESSION['user_id'];
$message = '';
$error = '';

// Handle new manual payment addition
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_payment'])) {
    $booking_id = (int)($_POST['booking_id'] ?? 0);
    $amount = floatval($_POST['amount'] ?? 0);
    $due_date = $_POST['due_date'] ?? '';

    if ($booking_id <= 0 || $amount <= 0 || !$due_date) {
        $error = "Please enter valid booking, amount, and due date.";
    } else {
        $stmt = $conn->prepare("SELECT id FROM bookings WHERE id = ? AND tenant_id = ?");
        $stmt->bind_param("ii", $booking_id, $tenant_id);
        $stmt->execute();
        $res = $stmt->get_result();

        if ($res->num_rows === 0) {
            $error = "Invalid booking selected.";
        } else {
            $stmt->close();

            // Insert payment as PAID immediately
            $stmt2 = $conn->prepare("INSERT INTO rent_payments (booking_id, tenant_id, amount, due_date, status) VALUES (?, ?, ?, ?, 'paid')");
            $stmt2->bind_param("iids", $booking_id, $tenant_id, $amount, $due_date);
            if ($stmt2->execute()) {
                $message = "New rent payment added successfully.";
            } else {
                $error = "Failed to add payment. Please try again.";
            }
            $stmt2->close();
        }
    }
}

// Get tenant name
$stmt = $conn->prepare("SELECT name FROM users WHERE id = ?");
$stmt->bind_param("i", $tenant_id);
$stmt->execute();
$user_result = $stmt->get_result();
$user = $user_result->fetch_assoc();
$tenant_name = $user['name'] ?? 'Tenant';
$stmt->close();

// Fetch all confirmed bookings of tenant with house info
$stmt = $conn->prepare("
    SELECT b.id AS booking_id, b.start_date, b.end_date, h.title AS house_title, h.price AS monthly_rent
    FROM bookings b
    JOIN houses h ON b.house_id = h.id
    WHERE b.tenant_id = ? AND b.status = 'confirmed'
");
$stmt->bind_param("i", $tenant_id);
$stmt->execute();
$bookings_result = $stmt->get_result();
$bookings = $bookings_result->fetch_all(MYSQLI_ASSOC);
$stmt->close();

// Prepare summary data
$summary_data = [];

foreach ($bookings as $booking) {
    $booking_id = $booking['booking_id'];
    $house_title = $booking['house_title'];
    $monthly_rent = (float)$booking['monthly_rent'];

    $start = new DateTime($booking['start_date']);
    $end = new DateTime($booking['end_date']);
    $interval = $start->diff($end);

    $months = $interval->y * 12 + $interval->m;
    if ($interval->d > 0) {
        $months += 1;
    }
    if ($months == 0) $months = 1;

    $total_rent = $monthly_rent * $months;

    // Sum all payments regardless of status (no status condition)
    $stmt = $conn->prepare("
        SELECT IFNULL(SUM(amount),0) AS total_paid
        FROM rent_payments
        WHERE booking_id = ? AND tenant_id = ?
    ");
    $stmt->bind_param("ii", $booking_id, $tenant_id);
    $stmt->execute();
    $payment_result = $stmt->get_result();
    $payment_row = $payment_result->fetch_assoc();
    $total_paid = (float)$payment_row['total_paid'];
    $stmt->close();

    $rent_due = max($total_rent - $total_paid, 0);

    $summary_data[] = [
        'house_title' => $house_title,
        'total_rent' => $total_rent,
        'rent_paid' => $total_paid,
        'rent_due' => $rent_due,
        'booking_id' => $booking_id,
        'start_date' => $booking['start_date'],
        'end_date' => $booking['end_date'],
    ];
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8" />
<meta name="viewport" content="width=device-width, initial-scale=1" />
<title>Rent Payment Summary & Add Payment</title>
<link rel="stylesheet" href="rent_payment.css" />
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" />
</head>
<body>

<div class="dashboard">
   <div class="sidebar">
        <div class="sidebar-header">
            <h2><?= htmlspecialchars($tenant_name) ?></h2>
        </div>
        <ul class="nav-menu">
            <li><a href="tenant_dashboard.php"><i class="fas fa-home"></i> Dashboard</a></li>
            <li><a href="#" class="active"><i class="fas fa-file-invoice-dollar"></i> Rent Payments</a></li>
            <li><a href="maintenance.php"><i class="fas fa-tools"></i> Maintenance</a></li>
            <li><a href="../Main/index.php"><i class="fas fa-home"></i> Home</a></li>
        </ul>
    </div>

    <main>
        <h1>Rent Payment Tracking</h1>

        <?php if ($message): ?>
            <div class="message-success"><?= htmlspecialchars($message) ?></div>
        <?php endif; ?>
        <?php if ($error): ?>
            <div class="message-error"><?= htmlspecialchars($error) ?></div>
        <?php endif; ?>

        <!-- Rent Summary Section -->
        <section>
            <h2 class="section-title">Rent Payment Summary</h2>

            <?php if (empty($summary_data)): ?>
                <p>No active bookings or rent records found.</p>
            <?php else: ?>
                <table>
                    <thead>
                        <tr>
                            <th>Property</th>
                            <th>Booking Period</th>
                            <th>Total Rent</th>
                            <th>Rent Paid</th>
                            <th>Rent Due</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($summary_data as $row): ?>
                        <tr>
                            <td><?= htmlspecialchars($row['house_title']) ?></td>
                            <td><?= htmlspecialchars($row['start_date']) ?> to <?= htmlspecialchars($row['end_date']) ?></td>
                            <td>$<?= number_format($row['total_rent'], 2) ?></td>
                            <td>$<?= number_format($row['rent_paid'], 2) ?></td>
                            <td class="<?= $row['rent_due'] > 0 ? 'due' : 'paid' ?>">Rs<?= number_format($row['rent_due'], 2) ?></td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            <?php endif; ?>
        </section>

        <!-- Add Payment Section -->
        <section>
            <h2 class="section-title">Add New Rent Payment</h2>
            <?php if (empty($bookings)): ?>
                <p>No bookings available to add payments.</p>
            <?php else: ?>
                <form method="POST" class="add-payment" novalidate>
                    <label for="booking_id">Select Property / Booking:</label>
                    <select name="booking_id" id="booking_id" required>
                        <option value="" disabled selected>-- Select Booking --</option>
                        <?php foreach ($bookings as $booking): ?>
                            <option value="<?= $booking['booking_id'] ?>">
                                <?= htmlspecialchars($booking['house_title']) ?> (<?= htmlspecialchars($booking['start_date']) ?> to <?= htmlspecialchars($booking['end_date']) ?>)
                            </option>
                        <?php endforeach; ?>
                    </select>

                    <label for="amount">Payment Amount ($):</label>
                    <input type="number" step="0.01" min="0.01" name="amount" id="amount" required>

                    <label for="due_date">Due Date:</label>
                    <input type="date" name="due_date" id="due_date" required>

                    <button type="submit" name="add_payment">Add Payment</button>
                </form>
            <?php endif; ?>
        </section>

    </main>
</div>

<!-- FontAwesome for icons -->
<script src="https://kit.fontawesome.com/a076d05399.js" crossorigin="anonymous"></script>

</body>
</html>
