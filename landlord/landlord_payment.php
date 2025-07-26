<?php
session_start();
require '../Main/db.php';

if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] !== 'landlord') {
    header('Location: login.php');
    exit();
}

$landlord_id = $_SESSION['user_id'];
$message = '';
$error = '';

// Get landlord info
$stmt = $conn->prepare("SELECT name, profile_picture FROM users WHERE id = ?");
$stmt->bind_param("i", $landlord_id);
$stmt->execute();
$user = $stmt->get_result()->fetch_assoc();
$landlord_name = $user['name'] ?? 'Landlord';
$profile_pic = $user['profile_picture'] ?? 'default.png';
$stmt->close();

// Fetch payment summaries for each tenant and property
$stmt = $conn->prepare("
    SELECT 
        b.id AS booking_id,
        b.start_date, b.end_date,
        h.title AS house_title,
        h.price AS monthly_rent,
        t.name AS tenant_name,
        t.id AS tenant_id
    FROM bookings b
    JOIN houses h ON b.house_id = h.id
    JOIN users t ON b.tenant_id = t.id
    WHERE h.landlord_id = ? AND b.status = 'confirmed'
");
$stmt->bind_param("i", $landlord_id);
$stmt->execute();
$bookings_result = $stmt->get_result();
$payment_summary = [];

while ($row = $bookings_result->fetch_assoc()) {
    $booking_id = $row['booking_id'];
    $monthly_rent = (float)$row['monthly_rent'];

    $start = new DateTime($row['start_date']);
    $end = new DateTime($row['end_date']);
    $months = $start->diff($end)->m + ($start->diff($end)->y * 12);
    if ($months == 0) $months = 1;
    $total_rent = $monthly_rent * $months;

    // Get total paid for this booking
    $stmt2 = $conn->prepare("SELECT IFNULL(SUM(amount),0) AS total_paid FROM rent_payments WHERE booking_id = ?");
    $stmt2->bind_param("i", $booking_id);
    $stmt2->execute();
    $payment_row = $stmt2->get_result()->fetch_assoc();
    $total_paid = (float)$payment_row['total_paid'];
    $stmt2->close();

    $rent_due = max($total_rent - $total_paid, 0);

    $payment_summary[] = [
        'tenant_name' => $row['tenant_name'],
        'house_title' => $row['house_title'],
        'start_date' => $row['start_date'],
        'end_date' => $row['end_date'],
        'total_rent' => $total_rent,
        'rent_paid' => $total_paid,
        'rent_due' => $rent_due,
    ];
}
$stmt->close();
?>

<!DOCTYPE html>
<html>
<head>
    <title>Landlord Payment Summary</title>
     <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
      
       body { font-family: sans-serif; background: #f5f7fa; 
        margin: 0; }

    main {
  margin-left: 16rem; /* Sidebar width */
  padding: 1.5rem 2rem;
  min-height: 100vh;
  background: #f8fafc;
}
        h1 {
            color: #1a3e72;
        }
        table {
            width: 100%;
            border-collapse: collapse;
            background: white;
            margin-top: 2rem;
            border-radius: 8px;
            overflow: hidden;
            box-shadow: 0 2px 6px rgba(0,0,0,0.1);
        }
        th, td {
            padding: 12px 15px;
            border-bottom: 1px solid #ddd;
        }
        th {
            background-color: #3b82f6;
            color: white;
        }
        .paid {
            color: green;
        }
        .due {
            color: orange;
        }
    </style>
</head>
<body>

<div class="dashboard">
   <?php include 'sidebar.php'; ?>
    <main>
        <h1>Tenant Payments Overview</h1>
        <table>
            <thead>
                <tr>
                    <th>Tenant</th>
                    <th>Property</th>
                    <th>Booking Period</th>
                    <th>Total Rent</th>
                    <th>Paid</th>
                    <th>Due</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($payment_summary as $row): ?>
                <tr>
                    <td><?= htmlspecialchars($row['tenant_name']) ?></td>
                    <td><?= htmlspecialchars($row['house_title']) ?></td>
                    <td><?= htmlspecialchars($row['start_date']) ?> to <?= htmlspecialchars($row['end_date']) ?></td>
                    <td>$<?= number_format($row['total_rent'], 2) ?></td>
                    <td class="paid">$<?= number_format($row['rent_paid'], 2) ?></td>
                    <td class="<?= $row['rent_due'] > 0 ? 'due' : 'paid' ?>">$<?= number_format($row['rent_due'], 2) ?></td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </main>
</div>

</body>
</html>
