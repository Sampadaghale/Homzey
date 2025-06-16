<?php
session_start();
require 'db.php';

if (!isset($_SESSION["user_id"]) || $_SESSION["user_role"] !== "landlord") {
    header("Location: login.php");
    exit();
}

if (isset($_GET["id"])) {
    $house_id = (int) $_GET["id"];
    $landlord_id = $_SESSION["user_id"];

    mysqli_begin_transaction($conn);

    try {
        // Get image filename before deleting house
        $stmt = mysqli_prepare($conn, "SELECT image FROM houses WHERE id = ? AND landlord_id = ?");
        mysqli_stmt_bind_param($stmt, "ii", $house_id, $landlord_id);
        mysqli_stmt_execute($stmt);
        mysqli_stmt_bind_result($stmt, $imageName);
        mysqli_stmt_fetch($stmt);
        mysqli_stmt_close($stmt);

        // Delete related bookings first
        $stmt = mysqli_prepare($conn, "DELETE FROM bookings WHERE house_id = ?");
        mysqli_stmt_bind_param($stmt, "i", $house_id);
        mysqli_stmt_execute($stmt);
        $deletedBookings = mysqli_stmt_affected_rows($stmt);
        mysqli_stmt_close($stmt);

        // Delete house
        $stmt = mysqli_prepare($conn, "DELETE FROM houses WHERE id = ? AND landlord_id = ?");
        mysqli_stmt_bind_param($stmt, "ii", $house_id, $landlord_id);
        $success = mysqli_stmt_execute($stmt);
        $affectedRows = mysqli_stmt_affected_rows($stmt);
        mysqli_stmt_close($stmt);

        if ($success && $affectedRows > 0) {
            // Delete image file from server if exists
            if ($imageName) {
                $imagePath = __DIR__ . "/images/" . $imageName;
                if (file_exists($imagePath)) {
                    unlink($imagePath);
                }
            }

            mysqli_commit($conn);
            header("Location: landlord_dashboard.php?success=1");
            exit();
        } else {
            mysqli_rollback($conn);
            header("Location: landlord_dashboard.php?error=delete_failed");
            exit();
        }
    } catch (Exception $e) {
        mysqli_rollback($conn);
        header("Location: landlord_dashboard.php?error=exception");
        exit();
    }
} else {
    header("Location: landlord_dashboard.php?error=invalid_request");
    exit();
}
?>
