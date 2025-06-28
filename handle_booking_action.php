<?php
session_start();
include 'db.php';

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

require 'PHPMailer/PHPMailer.php';
require 'PHPMailer/SMTP.php';
require 'PHPMailer/Exception.php';

// Only landlords allowed here
if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] !== 'landlord') {
    header('Location: login.php');
    exit();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $booking_id = $_POST['booking_id'] ?? 0;
    $action = $_POST['action'] ?? '';

    if (!$booking_id || !in_array($action, ['accept', 'reject'])) {
        die('Invalid request.');
    }

    // Verify the booking belongs to this landlord
    $landlord_id = $_SESSION['user_id'];

    // Fetch booking with house info and tenant info
    $stmt = $conn->prepare("
        SELECT b.*, h.title AS house_title, h.id AS house_id, u.name AS tenant_name, u.email AS tenant_email
        FROM bookings b
        JOIN houses h ON b.house_id = h.id
        JOIN users u ON b.tenant_id = u.id
        WHERE b.id = ? AND h.landlord_id = ?
    ");
    $stmt->bind_param("ii", $booking_id, $landlord_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $booking = $result->fetch_assoc();
    $stmt->close();

    if (!$booking) {
        die('Booking not found or unauthorized.');
    }

    // Update booking status
    $new_status = $action === 'accept' ? 'confirmed' : 'rejected';
    $stmt = $conn->prepare("UPDATE bookings SET status = ? WHERE id = ?");
    $stmt->bind_param("si", $new_status, $booking_id);
    $stmt->execute();
    $stmt->close();

    // If accepted, update house status to booked, else free
    if ($new_status === 'confirmed') {
        $stmt = $conn->prepare("UPDATE houses SET status = 'booked' WHERE id = ?");
        $stmt->bind_param("i", $booking['house_id']);
        $stmt->execute();
        $stmt->close();
    } else {
        $stmt = $conn->prepare("UPDATE houses SET status = 'available' WHERE id = ?");
        $stmt->bind_param("i", $booking['house_id']);
        $stmt->execute();
        $stmt->close();
    }

    // Send email notification to tenant about action
    if (!empty($booking['tenant_email'])) {
        $mail = new PHPMailer(true);

        try {
            $mail->isSMTP();
            $mail->Host = 'smtp.gmail.com';
            $mail->SMTPAuth = true;
            $mail->Username = 'homzeyrent@gmail.com';  // Your SMTP username
            $mail->Password = 'yrvk pqxh hsxa stsk';  // Your SMTP password
            $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
            $mail->Port = 587;

            $mail->setFrom('homzeyrent@gmail.com', 'Homzey');

            $mail->addAddress($booking['tenant_email'], $booking['tenant_name']);

            if ($new_status === 'confirmed') {
                $mail->Subject = "Booking Confirmed - {$booking['house_title']} (Ref: {$booking['ref_code']})";
                $mail->Body = "Hello {$booking['tenant_name']},

Your booking request for '{$booking['house_title']}' has been accepted by the landlord.

Booking Details:
Start Date: {$booking['start_date']}
End Date: {$booking['end_date']}
Reference Code: {$booking['ref_code']}

Please contact the landlord for further details.

Thank you,
Homzey Team";
            } else {
                $mail->Subject = "Booking Rejected - {$booking['house_title']} (Ref: {$booking['ref_code']})";
                $mail->Body = "Hello {$booking['tenant_name']},

We regret to inform you that your booking request for '{$booking['house_title']}' has been rejected by the landlord.

Reference Code: {$booking['ref_code']}

You may browse other listings on our site.

Thank you,
Homzey Team";
            }

            $mail->send();
        } catch (Exception $e) {
            // Log error or ignore for now
            error_log("Mailer Error: " . $mail->ErrorInfo);
        }
    }

    // Redirect back to landlord dashboard with message (optional)
    header("Location: landlord_dashboard.php?msg=Booking+{$new_status}");
    exit();
} else {
    die('Invalid request method.');
}
