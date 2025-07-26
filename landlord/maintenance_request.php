<?php
session_start();
require '../Main/db.php';
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

require '../PHPMailer/PHPMailer.php';
require '../PHPMailer/SMTP.php';
require '../PHPMailer/Exception.php';

if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] !== 'landlord') {
    header('Location: login.php');
    exit();
}

$landlord_id = $_SESSION['user_id'];
$message = '';
$error = '';

// Handle form submission to update status
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['request_id'], $_POST['status'])) {
    $request_id = (int)$_POST['request_id'];
    $new_status = $_POST['status'];

    $allowed_statuses = ['Pending', 'In Progress', 'Completed'];

    if (!in_array($new_status, $allowed_statuses)) {
        $error = 'Invalid status selected.';
    } else {
        $stmt = $conn->prepare("UPDATE maintenance_requests SET status = ? WHERE id = ?");
        $stmt->bind_param("si", $new_status, $request_id);

        if ($stmt->execute()) {
            // Send email notification
            $stmt->close();

            $stmt = $conn->prepare("
                SELECT u.email, u.name, h.title
                FROM maintenance_requests m
                JOIN users u ON m.tenant_id = u.id
                JOIN houses h ON m.house_id = h.id
                WHERE m.id = ?
            ");
            $stmt->bind_param("i", $request_id);
            $stmt->execute();
            $row = $stmt->get_result()->fetch_assoc();
            $stmt->close();

            $tenant_email = $row['email'];
            $tenant_name = $row['name'];
            $house_title = $row['title'];

            $mail = new PHPMailer(true);
            try {
                $mail->isSMTP();
                $mail->Host = 'smtp.gmail.com';
                $mail->SMTPAuth = true;
                $mail->Username = 'homzeyrent@gmail.com';    // change this
                $mail->Password = 'yrvk pqxh hsxa stsk';           // change this
                $mail->SMTPSecure = 'tls';
                $mail->Port = 587;

                $mail->setFrom('your_email@gmail.com', 'Homzey Support');
                $mail->addAddress($tenant_email, $tenant_name);

                $mail->isHTML(true);
                $mail->Subject = 'Maintenance Request Status Updated';
                $mail->Body = "
                    <p>Dear {$tenant_name},</p>
                    <p>Your maintenance request for <strong>{$house_title}</strong> has been updated to: <strong>{$new_status}</strong>.</p>
                    <p>Thank you.<br>Homzey Team</p>
                ";

                $mail->send();
                $message = "Status updated and email sent!";
            } catch (Exception $e) {
                $error = "Status updated but email failed: " . $mail->ErrorInfo;
            }
        } else {
            $error = "Failed to update status: " . $stmt->error;
        }
    }
}

// Fetch landlord name
$stmt = $conn->prepare("SELECT name FROM users WHERE id = ?");
$stmt->bind_param("i", $landlord_id);
$stmt->execute();
$user = $stmt->get_result()->fetch_assoc();
$landlord_name = $user['name'] ?? 'Landlord';
$stmt->close();

// Fetch maintenance requests
$stmt = $conn->prepare("
    SELECT 
        m.id AS request_id,
        m.description,
        m.status,
        m.created_at,
        h.title AS house_title,
        u.name AS tenant_name
    FROM maintenance_requests m
    JOIN houses h ON m.house_id = h.id
    JOIN users u ON m.tenant_id = u.id
    WHERE h.landlord_id = ?
    ORDER BY m.created_at DESC
");
$stmt->bind_param("i", $landlord_id);
$stmt->execute();
$result = $stmt->get_result();
$requests = $result->fetch_all(MYSQLI_ASSOC);
$stmt->close();

?>

<!DOCTYPE html>
<html>
<head>
    <title>Maintenance Requests</title>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1" />
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" />
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
  font-size: 2.5rem; /* or any size you want */
  font-weight: 700;  /* make it bold */
  color: #1a3e72;    /* optional: matching your theme */
  margin-bottom: 1rem; /* space below */
  text-align: center;
}

        table { width: 100%; border-collapse: collapse; background: white; 
        border-radius: 8px; 
        overflow: hidden;
        box-shadow: 0 2px 6px rgba(0,0,0,0.1);
        }
        th, td { padding: 12px 15px; border-bottom: 1px solid #ddd; }
        th { background-color: #3b82f6; color: white; }
        select, button { padding: 5px 8px; margin-top: 5px; }
        .message-success { color: green; font-weight: bold; margin-bottom: 1rem; }
        .message-error { color: red; font-weight: bold; margin-bottom: 1rem; }
    </style>
</head>
<body>
<div class="dashboard">
    <?php include 'sidebar.php'; ?>

    <main>
        <h1>Maintenance Requests</h1>

        <?php if ($message): ?>
            <div class="message-success"><?= htmlspecialchars($message) ?></div>
        <?php endif; ?>
        <?php if ($error): ?>
            <div class="message-error"><?= htmlspecialchars($error) ?></div>
        <?php endif; ?>

        <?php if (empty($requests)): ?>
            <p>No maintenance requests found.</p>
        <?php else: ?>
            <table>
                <thead>
                    <tr>
                        <th>Tenant</th>
                        <th>Property</th>
                        <th>Description</th>
                        <th>Status</th>
                        <th>Date Submitted</th>
                        <th>Update Status</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($requests as $req): ?>
                        <tr>
                            <td><?= htmlspecialchars($req['tenant_name']) ?></td>
                            <td><?= htmlspecialchars($req['house_title']) ?></td>
                            <td><?= htmlspecialchars($req['description']) ?></td>
                            <td><?= htmlspecialchars($req['status']) ?></td>
                            <td><?= htmlspecialchars($req['created_at']) ?></td>
                            <td>
                                <form method="POST" style="display:inline-block;">
                                    <input type="hidden" name="request_id" value="<?= $req['request_id'] ?>">
                                    <select name="status" required>
                                        <option value="Pending" <?= $req['status'] === 'Pending' ? 'selected' : '' ?>>Pending</option>
                                        <option value="In Progress" <?= $req['status'] === 'In Progress' ? 'selected' : '' ?>>In Progress</option>
                                        <option value="Completed" <?= $req['status'] === 'Completed' ? 'selected' : '' ?>>Completed</option>
                                    </select>
                                    <button type="submit">Update</button>
                                </form>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        <?php endif; ?>
    </main>
</div>
</body>
</html>
