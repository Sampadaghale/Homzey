<?php
session_start();
require 'db.php';  // expects $conn = new mysqli(...);

if (!isset($_SESSION["user_id"]) || $_SESSION["user_role"] !== "landlord") {
    header("Location: login.php");
    exit();
}

$successMessage = "";
$errorMessage = "";

if (isset($_GET["id"])) {
    $house_id = (int) $_GET["id"];
    $landlord_id = $_SESSION["user_id"];

    // Fetch the existing listing details
    $stmt = mysqli_prepare($conn, "SELECT * FROM houses WHERE id = ? AND landlord_id = ?");
    mysqli_stmt_bind_param($stmt, "ii", $house_id, $landlord_id);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    $house = mysqli_fetch_assoc($result);
    mysqli_stmt_close($stmt);

    if (!$house) {
        die("House not found or you do not have permission to edit this listing.");
    }

    if ($_SERVER["REQUEST_METHOD"] === "POST") {
        $title       = trim($_POST["title"]);
        $description = trim($_POST["description"]);
        $location    = trim($_POST["location"]);
        $price       = floatval($_POST["price"]);
        $imageName   = $house["image"]; // Keep existing image by default

        // Validate required fields
        if (empty($title) || empty($location) || $price <= 0) {
            $errorMessage = "Please fill all required fields with valid data.";
        }

        if (empty($errorMessage) && !empty($_FILES["image"]["name"]) && $_FILES["image"]["error"] === UPLOAD_ERR_OK) {
            $allowedTypes = ['image/jpeg', 'image/png', 'image/webp', 'image/jpg'];
            $mimeType = mime_content_type($_FILES["image"]["tmp_name"]);

            if (in_array($mimeType, $allowedTypes)) {
                $targetDir = __DIR__ . "/images/";
                $originalName = basename($_FILES["image"]["name"]);
                $safeName = preg_replace("/[^A-Za-z0-9\.\-_]/", "_", $originalName);
                $targetFile = $targetDir . $safeName;

                if (!is_dir($targetDir)) {
                    mkdir($targetDir, 0755, true);
                }

                if (move_uploaded_file($_FILES["image"]["tmp_name"], $targetFile)) {
                    // Delete old image file if different and exists
                    if ($imageName && $imageName !== $safeName) {
                        $oldImagePath = $targetDir . $imageName;
                        if (file_exists($oldImagePath)) {
                            unlink($oldImagePath);
                        }
                    }
                    $imageName = $safeName;
                } else {
                    $errorMessage = "Failed to move uploaded file.";
                }
            } else {
                $errorMessage = "Only JPG, PNG, or WEBP image files are allowed.";
            }
        }

        if (empty($errorMessage)) {
            $stmt = mysqli_prepare($conn, "UPDATE houses SET title = ?, description = ?, location = ?, price = ?, image = ? WHERE id = ?");
            mysqli_stmt_bind_param($stmt, "ssdssi", $title, $description, $location, $price, $imageName, $house_id);

            $success = mysqli_stmt_execute($stmt);
            mysqli_stmt_close($stmt);

            if ($success) {
                // Redirect to dashboard or show success message
                header("Location: landlord_dashboard.php?success=1");
                exit();
            } else {
                $errorMessage = "Failed to update listing.";
            }
        }
    }
} else {
    die("Invalid request.");
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <title>Edit Listing</title>
  <link rel="stylesheet" href="styles.css" />
</head>
<body>
  <section class="auth-section" aria-label="Edit listing form">
    <h2>Edit House Listing</h2>

    <div class="auth-forms">
      <section class="auth-card" style="max-width: 600px;">
        <?php if ($successMessage): ?>
          <p style="color: green; font-weight: bold;"><?= htmlspecialchars($successMessage) ?></p>
        <?php elseif ($errorMessage): ?>
          <p style="color: red; font-weight: bold;"><?= htmlspecialchars($errorMessage) ?></p>
        <?php endif; ?>

        <form method="post" action="" enctype="multipart/form-data">
          <label for="title">Title</label>
          <input
            type="text"
            id="title"
            name="title"
            value="<?= htmlspecialchars($house['title']) ?>"
            required
          />

          <label for="description">Description</label>
          <textarea
            id="description"
            name="description"
            rows="4"
            style="padding: 0.75rem; border-radius: 0.5rem;"
          ><?= htmlspecialchars($house['description']) ?></textarea>

          <label for="location">Location</label>
          <input
            type="text"
            id="location"
            name="location"
            value="<?= htmlspecialchars($house['location']) ?>"
            required
          />

          <label for="price">Price</label>
          <input
            type="number"
            id="price"
            name="price"
            step="0.01"
            value="<?= htmlspecialchars($house['price']) ?>"
            required
          />

          <label for="image">Upload New Image (optional)</label>
          <input type="file" id="image" name="image" accept="image/*" />

          <?php if ($house['image']): ?>
            <div style="margin-top: 1rem;">
              <p>Current Image:</p>
              <img
                src="images/<?= htmlspecialchars($house['image']) ?>"
                alt="Current house image"
                style="max-width: 200px; border-radius: 0.5rem;"
              />
            </div>
          <?php endif; ?>

          <button type="submit" style="margin-top: 1rem;">Update Listing</button>
        </form>
      </section>
    </div>
  </section>
</body>
</html>
