<?php 
session_start();
require 'db.php'; // This should now provide $conn (mysqli connection)

if (!isset($_SESSION["user_id"]) || $_SESSION["user_role"] !== "landlord") {
    header("Location: ../login.php");
    exit();
}

$errorMessage = "";
$successMessage = "";

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $title = trim($_POST["title"]);
    $description = trim($_POST["description"]);
    $location = trim($_POST["location"]);
    $price = floatval($_POST["price"]);
    $imageName = "default_house.jpg";

    if (!empty($_FILES["image"]["name"]) && $_FILES["image"]["error"] === UPLOAD_ERR_OK) {
        $allowedTypes = ['image/jpeg', 'image/png', 'image/webp', 'image/jpg'];
        $mimeType = mime_content_type($_FILES["image"]["tmp_name"]);

        if (in_array($mimeType, $allowedTypes)) {
            $targetDir = __DIR__ . "/images/";
            if (!is_dir($targetDir)) {
                mkdir($targetDir, 0755, true);
            }
            $safeName = uniqid() . "_" . preg_replace("/[^A-Za-z0-9\.\-_]/", "_", basename($_FILES["image"]["name"]));
            $targetFile = $targetDir . $safeName;

            if (move_uploaded_file($_FILES["image"]["tmp_name"], $targetFile)) {
                $imageName = $safeName;
            } else {
                $errorMessage = "Failed to upload image.";
            }
        } else {
            $errorMessage = "Invalid image type.";
        }
    }

    if (empty($errorMessage)) {
        // Prepare the SQL statement
        $stmt = mysqli_prepare($conn, "INSERT INTO houses (landlord_id, title, description, location, price, image) VALUES (?, ?, ?, ?, ?, ?)");
        if ($stmt) {
            // Bind parameters: i = integer, s = string, d = double (float)
            mysqli_stmt_bind_param($stmt, "isssds", $_SESSION["user_id"], $title, $description, $location, $price, $imageName);
            
            // Execute
            $executed = mysqli_stmt_execute($stmt);
            
            if ($executed) {
                $successMessage = "Listing added successfully!";
            } else {
                $errorMessage = "Database error: could not add listing.";
            }

            mysqli_stmt_close($stmt);
        } else {
            $errorMessage = "Failed to prepare the database statement.";
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8" />
    <title>Add Listing - Landlord</title>
    <style>
        body { font-family: Arial, sans-serif; padding: 2rem; background: #f9fafb; }
        form { max-width: 600px; background: white; padding: 20px; border-radius: 8px; box-shadow: 0 2px 8px rgba(0,0,0,0.1);}
        label { display: block; margin-top: 1rem; font-weight: bold; }
        input, textarea { width: 100%; padding: 0.5rem; border-radius: 4px; border: 1px solid #ccc; }
        button { margin-top: 1rem; padding: 0.7rem 1.5rem; background: #2563eb; color: white; border: none; border-radius: 6px; cursor: pointer; }
        button:hover { background: #1e40af; }
        .message { margin-top: 1rem; font-weight: bold; }
        .error { color: red; }
        .success { color: green; }
        nav a { margin-right: 10px; }
    </style>
</head>
<body>
    <nav>
        <a href="landlord_dashboard.php">Dashboard</a> | 
        <a href="../logout.php">Logout</a>
    </nav>
    <h1>Add New House Listing</h1>

    <?php if ($errorMessage): ?>
    <div class="message error"><?= htmlspecialchars($errorMessage) ?></div>
    <?php elseif ($successMessage): ?>
    <div class="message success"><?= htmlspecialchars($successMessage) ?></div>
    <?php endif; ?>

    <form method="post" enctype="multipart/form-data">
        <label for="title">Title<span style="color:red;">*</span></label>
        <input id="title" name="title" required />

        <label for="description">Description</label>
        <textarea id="description" name="description"></textarea>

        <label for="location">Location<span style="color:red;">*</span></label>
        <input id="location" name="location" required />

        <label for="price">Price (per month)<span style="color:red;">*</span></label>
        <input id="price" name="price" type="number" step="0.01" min="0" required />

        <label for="image">House Image (JPG, PNG, WEBP)</label>
        <input id="image" name="image" type="file" accept="image/jpeg,image/png,image/webp" />

        <button type="submit">Add Listing</button>
    </form>
</body>
</html>
