<?php
session_start();
require 'db.php'; // Your MySQLi connection

// Load PHPMailer
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

require 'PHPMailer/PHPMailer.php';
require 'PHPMailer/SMTP.php';
require 'PHPMailer/Exception.php';

if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] !== 'tenant') {
    header('Location: login.php');
    exit();
}

$tenant_id = $_SESSION['user_id'];

// Fetch tenant name for sidebar
$stmt = $conn->prepare("SELECT name FROM users WHERE id = ?");
$stmt->bind_param("i", $tenant_id);
$stmt->execute();
$user_result = $stmt->get_result();
$user = $user_result->fetch_assoc();
$tenant_name = $user['name'] ?? 'Tenant';
$stmt->close();

$error = '';
$success = '';

// Handle new maintenance request submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $category = trim($_POST['category'] ?? '');
    $description = trim($_POST['description'] ?? '');

    if (empty($category) || empty($description)) {
        $error = 'Please fill in all fields.';
    } else {
        // Fetch tenant's booked house (latest confirmed)
        $bookingStmt = $conn->prepare("SELECT house_id FROM bookings WHERE tenant_id = ? AND status = 'confirmed' ORDER BY start_date DESC LIMIT 1");
        $bookingStmt->bind_param("i", $tenant_id);
        $bookingStmt->execute();
        $bookingResult = $bookingStmt->get_result();
        $booking = $bookingResult->fetch_assoc();
        $house_id = $booking['house_id'] ?? null;
        $bookingStmt->close();

        if (!$house_id) {
            $error = "No booked house found. You cannot submit maintenance request without a booked property.";
        } else {
            // Insert new maintenance request
            $insert = $conn->prepare("INSERT INTO maintenance_requests (tenant_id, house_id, category, description, status) VALUES (?, ?, ?, ?, 'Pending')");
            $insert->bind_param("iiss", $tenant_id, $house_id, $category, $description);

            if ($insert->execute()) {
                // Fetch landlord info to send email
                $landlordStmt = $conn->prepare("
                    SELECT u.email, u.name 
                    FROM users u
                    JOIN houses h ON u.id = h.landlord_id
                    WHERE h.id = ?
                    LIMIT 1
                ");
                $landlordStmt->bind_param("i", $house_id);
                $landlordStmt->execute();
                $landlordResult = $landlordStmt->get_result();
                $landlord = $landlordResult->fetch_assoc();
                $landlordStmt->close();

                if ($landlord) {
                    $mail = new PHPMailer(true);
                    try {
                        // SMTP server configuration
                        // $mail->SMTPDebug = 2; // Enable verbose debug output for troubleshooting
                        $mail->isSMTP();
                        $mail->Host       = 'smtp.example.com';           // Your SMTP server
                        $mail->SMTPAuth   = true;
                        $mail->Username   = 'homzeyrent@gmail.com';     // SMTP username
                        $mail->Password   = 'yrvk pqxh hsxa stsk';              // SMTP password
                        $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
                        $mail->Port       = 587;

                        // Recipients
                        $mail->setFrom('no-reply@yourdomain.com', 'Homzey Rental System');
                        $mail->addAddress($landlord['email'], $landlord['name']);

                        // Content
                        $mail->isHTML(false);
                        $mail->Subject = 'New Maintenance Request Submitted';
                        $mail->Body    = "Hello {$landlord['name']},\n\n"
                            . "You have a new maintenance request from tenant {$tenant_name}.\n\n"
                            . "Category: $category\n"
                            . "Description: $description\n\n"
                            . "Please check your landlord dashboard for details.\n\n"
                            . "Regards,\nHomzey Rental System";

                        $mail->send();
                    } catch (Exception $e) {
                        // Optional: log error or notify admin
                        // error_log('Mailer Error: ' . $mail->ErrorInfo);
                    }
                }

                $success = 'Maintenance request submitted successfully.';
            } else {
                $error = 'Failed to submit maintenance request. Please try again.';
            }
            $insert->close();
        }
    }
}

// Fetch tenant's maintenance requests
$stmt = $conn->prepare("SELECT category, description, status, created_at FROM maintenance_requests WHERE tenant_id = ? ORDER BY created_at DESC");
$stmt->bind_param("i", $tenant_id);
$stmt->execute();
$requests = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
$stmt->close();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1" />
    <title>Maintenance Requests</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" />
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
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        }
        body {
            background-color: var(--background-color);
            color: var(--text-dark);
            line-height: 1.6;
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
            position: relative;
        }
        .sidebar-header {
            display: flex;
            align-items: center;
            margin-bottom: 2rem;
            padding-left: 0.5rem;
        }
        .sidebar-header h2 {
            font-size: 1.2rem;
        }
        .nav-menu {
            list-style: none;
        }
        .nav-menu li {
            margin-bottom: 1rem;
        }
        .nav-menu li a {
            color: white;
            text-decoration: none;
            display: block;
            padding: 0.5rem;
            border-radius: 4px;
            transition: background-color 0.3s;
        }
        .nav-menu li a:hover,
        .nav-menu li a.active {
            background-color: rgba(255, 255, 255, 0.2);
        }
        .nav-menu li a i {
            margin-right: 0.5rem;
            width: 20px;
            text-align: center;
        }
        main {
            padding: 2rem;
        }
        h1 {
            margin-bottom: 1rem;
            color: var(--primary-color);
        }
        table {
            width: 100%;
            border-collapse: collapse;
            background: white;
            border-radius: 8px;
            overflow: hidden;
            box-shadow: 0 4px 6px rgb(0 0 0 / 0.1);
            margin-bottom: 2rem;
        }
        th, td {
            padding: 12px 15px;
            text-align: left;
            border-bottom: 1px solid #ddd;
        }
        th {
            background: var(--secondary-color);
            color: white;
        }
        tr:hover {
            background-color: #f1f1f1;
        }
        .status-Pending {
            color: var(--warning-color);
            font-weight: bold;
        }
        .status-In\ Progress {
            color: var(--secondary-color);
            font-weight: bold;
        }
        .status-Completed {
            color: var(--success-color);
            font-weight: bold;
        }
        form {
            background: white;
            padding: 1.5rem;
            border-radius: 8px;
            box-shadow: 0 4px 6px rgb(0 0 0 / 0.1);
            max-width: 600px;
        }
        label {
            display: block;
            margin-bottom: 0.5rem;
            font-weight: 600;
        }
        input[type="text"],
        textarea,
        select {
            width: 100%;
            padding: 0.5rem;
            margin-bottom: 1rem;
            border-radius: 4px;
            border: 1px solid #ccc;
            font-size: 1rem;
        }
        button {
            background-color: var(--primary-color);
            color: white;
            border: none;
            padding: 0.6rem 1.2rem;
            border-radius: 4px;
            cursor: pointer;
            font-size: 1rem;
            transition: background-color 0.3s ease;
        }
        button:hover {
            background-color: var(--secondary-color);
        }
        .message {
            max-width: 600px;
            margin-bottom: 1rem;
            padding: 1rem;
            border-radius: 6px;
        }
        .error {
            background-color: #ffebee;
            color: var(--danger-color);
            border: 1px solid var(--danger-color);
        }
        .success {
            background-color: #e8f5e9;
            color: var(--success-color);
            border: 1px solid var(--success-color);
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
                <li><a href="rent_payments.php"><i class="fas fa-file-invoice-dollar"></i> Rent Payments</a></li>
                <li><a href="maintenance.php" class="active"><i class="fas fa-tools"></i> Maintenance</a></li>
                <li><a href="messages.php"><i class="fas fa-envelope"></i> Messages</a></li>
                <li><a href="lease_documents.php"><i class="fas fa-file-signature"></i> Lease Documents</a></li>
                <li><a href="settings.php"><i class="fas fa-cog"></i> Settings</a></li>
                <li><a href="logout.php"><i class="fas fa-sign-out-alt"></i> Logout</a></li>
            </ul>
        </aside>

        <main>
            <h1>Maintenance Requests</h1>

            <?php if ($error): ?>
                <div class="message error"><?= htmlspecialchars($error) ?></div>
            <?php elseif ($success): ?>
                <div class="message success"><?= htmlspecialchars($success) ?></div>
            <?php endif; ?>

            <?php if ($requests): ?>
                <table>
                    <thead>
                        <tr>
                            <th>Date</th>
                            <th>Category</th>
                            <th>Description</th>
                            <th>Status</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($requests as $req): ?>
                            <tr>
                                <td><?= htmlspecialchars(date('M d, Y', strtotime($req['created_at']))) ?></td>
                                <td><?= htmlspecialchars($req['category']) ?></td>
                                <td><?= htmlspecialchars($req['description']) ?></td>
                                <td class="status-<?= str_replace(' ', '\\ ', htmlspecialchars($req['status'])) ?>">
                                    <?= htmlspecialchars($req['status']) ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            <?php else: ?>
                <p>No maintenance requests found.</p>
            <?php endif; ?>

            <h2>Submit New Request</h2>
            <form method="post">
                <label for="category">Category</label>
                <select name="category" id="category" required>
                    <option value="">-- Select Category --</option>
                    <option value="Plumbing">Plumbing</option>
                    <option value="Electrical">Electrical</option>
                    <option value="HVAC">HVAC</option>
                    <option value="Appliances">Appliances</option>
                    <option value="Other">Other</option>
                </select>

                <label for="description">Description</label>
                <textarea name="description" id="description" rows="4" required></textarea>

                <button type="submit">Submit Request</button>
            </form>
        </main>
    </div>
</body>
</html>
