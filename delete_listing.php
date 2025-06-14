<?php  
session_start(); 
require 'db.php';  // should define $conn as MySQLi connection

// Only landlords can access
if (!isset($_SESSION["user_id"]) || $_SESSION["user_role"] !== "landlord") { 
    header("Location: login.php"); 
    exit(); 
} 

if (isset($_GET["id"])) { 
    $house_id = (int) $_GET["id"]; 
    $landlord_id = $_SESSION["user_id"];

    // Prepare a statement to delete the house
    $stmt = mysqli_prepare($conn, "DELETE FROM houses WHERE id = ? AND landlord_id = ?");
    mysqli_stmt_bind_param($stmt, "ii", $house_id, $landlord_id);
    $success = mysqli_stmt_execute($stmt);
    mysqli_stmt_close($stmt);

    if ($success) { 
        header("Location: landlord_dashboard.php?success=1"); 
        exit(); 
    } else { 
        // Provide feedback if deletion fails
        header("Location: landlord_dashboard.php?error=delete_failed"); 
        exit(); 
    } 
} else { 
    header("Location: landlord_dashboard.php?error=invalid_request"); 
    exit(); 
} 
?>
