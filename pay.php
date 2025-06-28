<?php
session_start();
require 'db.php';

if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] !== 'tenant') {
    header('Location: login.php');
    exit();
}

$tenant_id = $_SESSION['user_id'];

if (!isset($_GET['payment_id']) || !is_numeric($_GET['payment_id'])) {
    die('Invalid payment ID.');
}

$payment_id = (int)$_GET['payment_id'];

// Fetch payment info to verify ownership and status
$stmt = $conn->prepare("SELECT id, amount, due_date, status FROM rent_payments WHERE id = ? AND tenant_id = ?");
$stmt->bind_param("ii", $payment_id, $tenant_id);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 0) {
    die('Payment not found or access denied.');
}

$payment = $result->fetch_assoc();
$stmt->close();

if ($payment['status'] === 'paid') {
    die('This payment is already marked as paid.');
}

// Process form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Here you can add payment gateway integration or validation

    // For now, just update status to paid
    $update = $conn->prepare("UPDATE rent_payments SET status = 'paid' WHERE id = ? AND tenant_id = ?");
    $update->bind_param("ii", $payment_id, $tenant_id);
    if ($update->execute()) {
        $update->close();
        header("Location: rent_payments.php?message=Payment+successful");
        exit();
    } else {
        $update->close();
        $error = "Failed to update payment status. Please try again.";
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1" />
    <title>Pay Rent</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            padding: 2rem;
            background-color: #f5f7fa;
            color: #333;
        }
        .container {
            background: white;
            padding: 2rem;
            max-width: 400px;
            margin: auto;
            border-radius: 8px;
            box-shadow: 0 4px 6px rgb(0 0 0 / 0.1);
        }
        h2 {
            margin-bottom: 1rem;
            color: #4a6fa5;
        }
        .info {
            margin-bottom: 1.5rem;
        }
        button {
            background-color: #4a6fa5;
            color: white;
            border: none;
            padding: 0.6rem 1.2rem;
            border-radius: 4px;
            cursor: pointer;
            font-size: 1rem;
            transition: background-color 0.3s ease;
        }
        button:hover {
            background-color: #166088;
        }
        .error {
            color: #e53935;
            margin-bottom: 1rem;
        }
        a.back-link {
            display: inline-block;
            margin-top: 1rem;
            text-decoration: none;
            color: #4a6fa5;
        }
        a.back-link:hover {
            text-decoration: underline;
        }
    </style>
</head>
<body>
    <div class="container">
        <h2>Pay Rent</h2>
        <?php if (!empty($error)): ?>
            <div class="error"><?= htmlspecialchars($error) ?></div>
        <?php endif; ?>
        <div class="info">
            <p><strong>Amount:</strong> $<?= number_format($payment['amount'], 2) ?></p>
            <p><strong>Due Date:</strong> <?= htmlspecialchars($payment['due_date']) ?></p>
        </div>
        <form method="post">
            <button type="submit">Confirm Payment</button>
        </form>
        <a href="rent_payments.php" class="back-link">&larr; Back to Rent Payments</a>
    </div>
</body>
</html>
