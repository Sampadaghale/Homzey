<?php
session_start();
require 'db.php';

// Include PHPMailer
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

require 'PHPMailer/PHPMailer.php';
require 'PHPMailer/SMTP.php';
require 'PHPMailer/Exception.php';

if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] !== 'tenant') {
    header("Location: login.php");
    exit();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['booking_id'])) {
    $booking_id = intval($_POST['booking_id']);
    $tenant_id = $_SESSION['user_id'];

    // Verify booking belongs to tenant and is confirmed
    $stmt = $conn->prepare("SELECT house_id FROM bookings WHERE id = ? AND tenant_id = ? AND status = 'confirmed'");
    $stmt->bind_param("ii", $booking_id, $tenant_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $booking = $result->fetch_assoc();
    $stmt->close();

    if ($booking) {
        $house_id = $booking['house_id'];

        // Fetch landlord and house info
        $stmt2 = $conn->prepare("SELECT u.email AS landlord_email, u.name AS landlord_name, h.title 
                                 FROM users u 
                                 JOIN houses h ON u.id = h.landlord_id 
                                 WHERE h.id = ?");
        $stmt2->bind_param("i", $house_id);
        $stmt2->execute();
        $result2 = $stmt2->get_result();
        $landlord = $result2->fetch_assoc();
        $stmt2->close();

        // Fetch tenant info
        $stmt3 = $conn->prepare("SELECT name, email FROM users WHERE id = ?");
        $stmt3->bind_param("i", $tenant_id);
        $stmt3->execute();
        $result3 = $stmt3->get_result();
        $tenant = $result3->fetch_assoc();
        $stmt3->close();

        // Start transaction
        $conn->begin_transaction();

        try {
            // Cancel the booking
            $update = $conn->prepare("UPDATE bookings SET status = 'cancelled' WHERE id = ?");
            $update->bind_param("i", $booking_id);
            $update->execute();
            $update->close();

            // Make house available again
            $houseUpdate = $conn->prepare("UPDATE houses SET status = 'available' WHERE id = ?");
            $houseUpdate->bind_param("i", $house_id);
            $houseUpdate->execute();
            $houseUpdate->close();

            $conn->commit();

            // Send email to landlord
            $mail = new PHPMailer(true);
            try {
                $mail->isSMTP();
                $mail->Host       = 'smtp.gmail.com';
                $mail->SMTPAuth   = true;
                $mail->Username   = 'homzeyrent@gmail.com';           // ✅ your Gmail
                $mail->Password   = 'yrvk pqxh hsxa stsk';            // ✅ your App Password
                $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
                $mail->Port       = 587;

                $mail->setFrom('homzeyrent@gmail.com', 'Homzey');
                $mail->addAddress($landlord['landlord_email'], $landlord['landlord_name']);
                $mail->isHTML(true);
                $mail->Subject = 'Booking Cancelled: ' . $landlord['title'];
                $mail->Body    = "
                    <p>Hello {$landlord['landlord_name']},</p>
                    <p>The tenant <strong>{$tenant['name']}</strong> has cancelled their booking for <strong>{$landlord['title']}</strong>.</p>
                    <p>The house is now marked as available.</p>
                    <p>Regards,<br>Homzey Team</p>
                ";
                $mail->send();

            } catch (Exception $e) {
                error_log("Landlord email failed: " . $mail->ErrorInfo);
                $_SESSION['message'] = "Booking cancelled, but failed to email landlord.";
            }

            // Send email to tenant
            $mail2 = new PHPMailer(true);
            try {
                $mail2->isSMTP();
                $mail2->Host       = 'smtp.gmail.com';
                $mail2->SMTPAuth   = true;
                $mail2->Username   = 'homzeyrent@gmail.com';
                $mail2->Password   = 'yrvk pqxh hsxa stsk';
                $mail2->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
                $mail2->Port       = 587;

                $mail2->setFrom('homzeyrent@gmail.com', 'Homzey');
                $mail2->addAddress($tenant['email'], $tenant['name']);
                $mail2->isHTML(true);
                $mail2->Subject = 'Your Booking Has Been Cancelled';
                $mail2->Body    = "
                    <p>Hello {$tenant['name']},</p>
                    <p>Your booking for <strong>{$landlord['title']}</strong> has been successfully cancelled.</p>
                    <p>If this was a mistake, feel free to book again.</p>
                    <p>Thank you,<br>Homzey Team</p>
                ";
                $mail2->send();

                $_SESSION['message'] = "Booking cancelled and both parties notified.";

            } catch (Exception $e) {
                error_log("Tenant email failed: " . $mail2->ErrorInfo);
                $_SESSION['message'] = "Booking cancelled, but tenant email failed.";
            }

        } catch (Exception $e) {
            $conn->rollback();
            $_SESSION['message'] = "Error cancelling booking: " . $e->getMessage();
        }

    } else {
        $_SESSION['message'] = "Booking not found or already cancelled.";
    }

    header("Location: tenant_dashboard.php");
    exit();
}
?>
