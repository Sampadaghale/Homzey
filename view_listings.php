<?php
session_start();
require 'db.php';

// Only landlords can access
if (!isset($_SESSION["user_id"]) || $_SESSION["user_role"] !== "landlord") {
    header("Location: login.php");
    exit();
}

// Fetch landlord's listings
$stmt = $pdo->prepare("SELECT * FROM houses WHERE landlord_id = ?");
$stmt->execute([$_SESSION["user_id"]]);
$listings = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>My Listings</title>
  <link rel="stylesheet" href="styles.css">
</head>
<body>
  <section class="listings-section">
    <h2>My Listings</h2>
    <div class="listings-grid">
      <?php if ($listings): ?>
        <?php foreach ($listings as $house): ?>
          <div class="listing-card">
            <h3><?= htmlspecialchars($house['title']) ?></h3>
            <p>Location: <?= htmlspecialchars($house['location']) ?></p>
            <p>Price: <strong>Rs <?= number_format($house['price'], 2) ?></strong> / month</p>
            <img src="images/<?= htmlspecialchars($house['image']) ?>" alt="<?= htmlspecialchars($house['title']) ?>" style="width: 100%; height: auto;">
            <a href="edit_listing.php?id=<?= $house['id'] ?>">Edit</a>
            <a href="delete_listing.php?id=<?= $house['id'] ?>" onclick="return confirm('Are you sure you want to delete this listing?');">Delete</a>
          </div>
        <?php endforeach; ?>
      <?php else: ?>
        <p>No listings found.</p>
      <?php endif; ?>
    </div>
  </section>
</body>
</html>
