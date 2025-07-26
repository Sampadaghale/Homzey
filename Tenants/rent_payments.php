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
<style>
    :root {
        --primary-color: #4a6fa5;
        --secondary-color: #166088;
        --background-color: #f5f7fa;
        --text-dark: #333333;
        --text-light: #666666;
        --success-color: #43a047;
        --warning-color: #ff9800;
        --danger-color: #e53935;
    }
    body {
        margin: 0;
        font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        background-color: var(--background-color);
        color: var(--text-dark);
    }
    .dashboard {
        display: grid;
        grid-template-columns: 250px 1fr;
        min-height: 100vh;
    }
    .sidebar {
        background-color: var(--primary-color);
        color: white;
        padding: 2rem 1rem;
    }
    .sidebar-header h2 {
        margin-bottom: 1rem;
    }
    .nav-menu {
        list-style: none;
        padding: 0;
    }
    .nav-menu li {
        margin: 1rem 0;
    }
    .nav-menu a {
        color: white;
        text-decoration: none;
        display: block;
        padding: 0.5rem;
        border-radius: 4px;
        transition: background-color 0.3s ease;
    }
    .nav-menu a.active, .nav-menu a:hover {
        background-color: rgba(255, 255, 255, 0.2);
    }
    main {
        padding: 2rem;
        max-width: 900px;
        margin: 0;
    }
    h2.section-title {
        border-bottom: 2px solid var(--primary-color);
        padding-bottom: 0.5rem;
        margin-top: 2rem;
    }
    table {
        border-collapse: collapse;
        width: 100%;
        background: white;
        border-radius: 8px;
        box-shadow: 0 4px 6px rgba(0,0,0,0.1);
        margin-bottom: 2rem;
    }
    th, td {
        padding: 12px;
        border-bottom: 1px solid #ddd;
        text-align: left;
    }
    th {
        background-color: var(--secondary-color);
        color: white;
    }
    tr:hover {
        background-color: #f1f1f1;
    }
    .paid {
        color: var(--success-color);
        font-weight: bold;
    }
    .due {
        color: var(--warning-color);
        font-weight: bold;
    }
    .message-success {
        color: var(--success-color);
        font-weight: bold;
        margin-bottom: 1rem;
    }
    .message-error {
        color: var(--danger-color);
        font-weight: bold;
        margin-bottom: 1rem;
    }
    form.add-payment {
        background: white;
        padding: 1rem;
        border-radius: 8px;
        margin-top: 1rem;
        box-shadow: 0 4px 6px rgba(0,0,0,0.1);
        max-width: 400px;
    }
    form.add-payment label {
        display: block;
        margin-bottom: 0.5rem;
        font-weight: bold;
    }
    form.add-payment input[type="number"],
    form.add-payment input[type="date"],
    form.add-payment select {
        width: 100%;
        padding: 6px 8px;
        margin-bottom: 1rem;
        border: 1px solid #ccc;
        border-radius: 4px;
    }
    form.add-payment button {
        background: var(--primary-color);
        color: white;
        border: none;
        padding: 8px 16px;
        border-radius: 4px;
        cursor: pointer;
    }
</style>
</head>
<body>

<div class="dashboard">
    <aside class="sidebar">
        <div class="sidebar-header">
            <h2><?= htmlspecialchars($tenant_name) ?></h2>
        </div>
        <ul class="nav-menu">
            <li><a href="tenant_dashboard.php"><i class="fas fa-home"></i> Dashboard</a></li>
            <li><a href="rent_payments.php" class="active"><i class="fas fa-file-invoice-dollar"></i> Payment Tracking</a></li>
            <li><a href="maintenance.php"><i class="fas fa-tools"></i> Maintenance</a></li>
            <li><a href="logout.php"><i class="fas fa-sign-out-alt"></i> Logout</a></li>
        </ul>
    </aside>

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
                            <td class="<?= $row['rent_due'] > 0 ? 'due' : 'paid' ?>">$<?= number_format($row['rent_due'], 2) ?></td>
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
