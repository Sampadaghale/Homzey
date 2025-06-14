<?php
// Function to calculate commission
function calculateCommission($total_price, $commission_rate) {
    return $total_price * ($commission_rate / 100);
}

// Function to get commission rate from settings table
function getCommissionRate() {
    global $conn;
    $sql = "SELECT value FROM settings WHERE name = 'commission_rate'";
    $result = $conn->query($sql);
    if ($result && $result->num_rows == 1) {
        $row = $result->fetch_assoc();
        return (float)$row["value"];
    }
    return 5.0; // Default commission rate (5%)
}

// Function to send booking confirmation email to tenant
function sendBookingConfirmationEmail($tenant_id, $house_id) {
    global $conn;

    // Fetch tenant info
    $tenant_sql = "SELECT name, email FROM users WHERE id = '$tenant_id'";
    $tenant_result = $conn->query($tenant_sql);
    if (!$tenant_result || $tenant_result->num_rows == 0) return;

    $tenant = $tenant_result->fetch_assoc();

    // Fetch house and landlord info
    $house_sql = "SELECT title, user_id FROM houses WHERE id = '$house_id'";
    $house_result = $conn->query($house_sql);
    if (!$house_result || $house_result->num_rows == 0) return;

    $house = $house_result->fetch_assoc();
    $landlord_id = $house["user_id"];

    $landlord_sql = "SELECT name, email, phone FROM users WHERE id = '$landlord_id'";
    $landlord_result = $conn->query($landlord_sql);
    $landlord = $landlord_result->fetch_assoc();

    // Email tenant
    $to = $tenant["email"];
    $subject = "Booking Confirmation - " . $house["title"];
    $message = "Dear " . $tenant["name"] . ",\n\nYour booking for '" . $house["title"] . "' has been confirmed.\n\n";
    $message .= "Here is the landlord's contact info:\n";
    $message .= "Name: " . $landlord["name"] . "\n";
    $message .= "Email: " . $landlord["email"] . "\n";
    $message .= "Phone: " . $landlord["phone"] . "\n\n";
    $message .= "Thank you for using Homzey!";
    $headers = "From: noreply@homzey.com";

    mail($to, $subject, $message, $headers);
}

// Function to send booking alert email to landlord
function sendNewBookingNotificationEmail($house_id) {
    global $conn;

    // Fetch house and landlord info
    $house_sql = "SELECT title, user_id FROM houses WHERE id = '$house_id'";
    $house_result = $conn->query($house_sql);
    if (!$house_result || $house_result->num_rows == 0) return;

    $house = $house_result->fetch_assoc();
    $landlord_id = $house["user_id"];

    $landlord_sql = "SELECT name, email FROM users WHERE id = '$landlord_id'";
    $landlord_result = $conn->query($landlord_sql);
    if (!$landlord_result || $landlord_result->num_rows == 0) return;

    $landlord = $landlord_result->fetch_assoc();

    // Optionally fetch tenant info from the latest booking
    $booking_sql = "SELECT u.name, u.email, u.phone FROM bookings b
                    JOIN users u ON b.tenant_id = u.id
                    WHERE b.house_id = '$house_id' ORDER BY b.id DESC LIMIT 1";
    $booking_result = $conn->query($booking_sql);
    $tenant = $booking_result->fetch_assoc();

    // Email landlord
    $to = $landlord["email"];
    $subject = "New Booking - " . $house["title"];
    $message = "Dear " . $landlord["name"] . ",\n\nYour house '" . $house["title"] . "' has been booked.\n\n";
    $message .= "Tenant's Contact Info:\n";
    $message .= "Name: " . $tenant["name"] . "\n";
    $message .= "Email: " . $tenant["email"] . "\n";
    $message .= "Phone: " . $tenant["phone"] . "\n\n";
    $message .= "Please get in touch with the tenant to finalize the rental.\n\n";
    $message .= "Regards,\nHomzey Team";
    $headers = "From: noreply@homzey.com";

    mail($to, $subject, $message, $headers);
}
?>
