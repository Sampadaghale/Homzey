<?php
session_start();
require '../Main/db.php';

if (!isset($_SESSION["user_id"]) || $_SESSION["user_role"] !== "landlord") {
    header("Location: login.php");
    exit();
}

if (!isset($_GET["id"])) {
    header("Location: landlord_dashboard.php?error=invalid_request");
    exit();
}

$house_id = (int) $_GET["id"];
$landlord_id = $_SESSION["user_id"];

mysqli_begin_transaction($conn);

try {
    // Get image filenames
    $stmt = $conn->prepare("SELECT image FROM houses WHERE id = ? AND landlord_id = ?");
    $stmt->bind_param("ii", $house_id, $landlord_id);
    $stmt->execute();
    $stmt->bind_result($imageName);
    $stmt->fetch();
    $stmt->close();

    // Delete bookings
    $stmt = $conn->prepare("DELETE FROM bookings WHERE house_id = ?");
    $stmt->bind_param("i", $house_id);
    $stmt->execute();
    $stmt->close();

    // Delete house
    $stmt = $conn->prepare("DELETE FROM houses WHERE id = ? AND landlord_id = ?");
    $stmt->bind_param("ii", $house_id, $landlord_id);
    $stmt->execute();
    $affectedRows = $stmt->affected_rows;
    $stmt->close();

    if ($affectedRows > 0) {
        // Delete all images
        $imageList = explode(',', $imageName);
        foreach ($imageList as $img) {
            $imgPath = dirname(__DIR__) . "/images/" . trim($img);
            if (file_exists($imgPath)) {
                unlink($imgPath);
            }
        }

        mysqli_commit($conn);
        header("Location: landlord_dashboard.php?msg=Listing deleted successfully");
        exit();
    } else {
        mysqli_rollback($conn);
        header("Location: properties.php?error=delete_failed");
        exit();
    }
} catch (Exception $e) {
    mysqli_rollback($conn);
    header("Location: landlord_dashboard.php?error=exception_occurred");
    exit();
}
?>
