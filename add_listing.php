<?php  
session_start();
require 'db.php'; // This should now provide $conn (mysqli connection)

if (!isset($_SESSION["user_id"]) || $_SESSION["user_role"] !== "landlord") {
    header("Location: login.php");
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
        $stmt = mysqli_prepare($conn, "INSERT INTO houses (landlord_id, title, description, location, price, image) VALUES (?, ?, ?, ?, ?, ?)");
        if ($stmt) {
            mysqli_stmt_bind_param($stmt, "isssds", $_SESSION["user_id"], $title, $description, $location, $price, $imageName);
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
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            margin: 0;
            background: #f9fafb;
            color: #111827;
        }

        /* Header */
        header {
            background: white;
            display: flex;
            align-items: center;
            justify-content: space-between;
            padding: 1rem 2rem;
            color: #fff;
            box-shadow: 0 2px 5px rgba(0,0,0,0.1);
        }

        header .logo {
            display: flex;
            align-items: center;
        }

        header .logo img {
              width: 60px;
  height: auto;
        }

        header nav a {
            color: black;
            margin-left: 20px;
            text-decoration: none;
            font-weight: bold;
        }

        header nav a:hover {
            text-decoration: underline;
        }

        h1 {
            text-align: center;
            margin: 2rem 0 1rem;
            color: #1f2937;
        }

        form {
            max-width: 600px;
            margin: 0 auto;
            background: white;
            padding: 2rem;
            border-radius: 8px;
            box-shadow: 0 2px 8px rgba(0,0,0,0.1);
        }

        label {
            display: block;
            margin-top: 1rem;
            font-weight: 600;
        }

        input, textarea {
            width: 100%;
            padding: 0.6rem;
            margin-top: 0.3rem;
            border: 1px solid #ccc;
            border-radius: 6px;
            font-size: 1rem;
        }

        input[type="file"] {
            padding: 0.3rem;
        }

        button {
            margin-top: 1.5rem;
            padding: 0.75rem 2rem;
            background: #3b82f6;
            color: white;
            border: none;
            border-radius: 6px;
            font-size: 1rem;
            cursor: pointer;
        }

        button:hover {
            background: #1e40af;
        }

        .message {
            max-width: 600px;
            margin: 1rem auto;
            padding: 1rem;
            font-weight: bold;
            text-align: center;
        }

        .error {
            background: #fee2e2;
            color: #b91c1c;
            border: 1px solid #fca5a5;
        }

        .success {
            background: #dcfce7;
            color: #166534;
            border: 1px solid #86efac;
        }

        span.required {
            color: red;
        }
    </style>
</head>
<body>
    <header>
        <div class="logo">
            <img src="image/house.png" alt="Homzey Logo">
        </div>
        <nav>
            <a href="landlord_dashboard.php">Dashboard</a>
            <a href="logout.php">Logout</a>
        </nav>
    </header>

    <h1>Add New House Listing</h1>

    <?php if ($errorMessage): ?>
        <div class="message error"><?= htmlspecialchars($errorMessage) ?></div>
    <?php elseif ($successMessage): ?>
        <div class="message success"><?= htmlspecialchars($successMessage) ?></div>
    <?php endif; ?>

    <form method="post" enctype="multipart/form-data">
        <label for="title">Title<span class="required">*</span></label>
        <input id="title" name="title" required />

        <label for="description">Description</label>
        <textarea id="description" name="description" rows="4"></textarea>

        <label for="location">Location<span class="required">*</span></label>
        <input id="location" name="location" required />

        <label for="price">Price (Rs per month)<span class="required">*</span></label>
        <input id="price" name="price" type="number" step="0.01" min="0" required />

        <label for="image">House Image (JPG, PNG, WEBP)</label>
        <input id="image" name="image" type="file" accept="image/jpeg,image/png,image/webp" />

        <button type="submit">Add Listing</button>
    </form>
</body>
</html>
