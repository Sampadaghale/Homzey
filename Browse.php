<?php  
session_start();
require 'db.php'; // $conn = mysqli connection

// Handle search query (optional)
$search = '';
if (isset($_GET['search'])) {
    $search = trim($_GET['search']);
}

// Prepare SQL with search filter if any
if ($search !== '') {
    // If numeric, add price filter in SQL
    $priceFilter = is_numeric($search) ? " OR price = " . floatval($search) : "";
    $sql = "SELECT * FROM houses WHERE (title LIKE ? OR location LIKE ? $priceFilter) ORDER BY created_at DESC";
    $stmt = $conn->prepare($sql);
    $likeSearch = "%$search%";
    $stmt->bind_param("ss", $likeSearch, $likeSearch);
    $stmt->execute();
    $result = $stmt->get_result();
} else {
    $sql = "SELECT * FROM houses ORDER BY created_at DESC";
    $result = $conn->query($sql);
}

$houses = [];
if ($result) {
    while ($row = $result->fetch_assoc()) {
        $houses[] = $row;
    }
    if (isset($stmt)) $stmt->close();
} else {
    $houses = [];
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1" />
    <title>Browse Rentals - Homzey</title>
    <link rel="stylesheet" href="styles.css" />
    <link rel="stylesheet" href="browse.css" />
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css" />
    <style>
      /* Badge for booked properties */
      .badge-booked {
        background-color: #ef4444; /* Red */
        color: white;
        padding: 4px 8px;
        border-radius: 5px;
        font-size: 0.85rem;
        font-weight: 700;
        position: absolute;
        top: 10px;
        left: 10px;
        z-index: 2;
      }
      /* Positioning relative for card to place badge */
      .property-card {
        position: relative;
      }
      /* Disabled button styling */
      .btn-disabled {
        background-color: #999;
        cursor: not-allowed;
        pointer-events: none;
        color: #eee;
        border: none;
        padding: 0.5rem 1rem;
        border-radius: 4px;
        text-align: center;
        display: inline-block;
      }
    </style>
</head>
<body>

<header>
    <nav class="container nav-flex" aria-label="Primary Navigation">
        <div class="logo" tabindex="0">
            <img src="image/house.png" alt="Homzey logo" />
        </div>

        <form action="browse.php" method="get" class="search-form" role="search" aria-label="Search houses">
            <input type="text" name="search" placeholder="Search by location, title or price" value="<?= htmlspecialchars($search); ?>" />
            <button type="submit" aria-label="Search">Search</button>
        </form>

        <div class="nav-links" role="navigation" aria-label="Main Navigation Links">
            <a href="index.php" tabindex="0">Home</a>
            <a href="browse.php" tabindex="0">Browse</a>

            <?php if (isset($_SESSION['user_name'])): ?>
                <div class="user-dropdown-container" aria-haspopup="true" aria-expanded="false">
                    <button class="user-toggle" onclick="toggleDropdown()" aria-label="User menu">
                        <span><?= htmlspecialchars($_SESSION['user_name']); ?></span>
                        <i class="fa fa-caret-down" aria-hidden="true"></i>
                    </button>
                    <div id="userDropdown" class="user-dropdown hidden" role="menu" aria-label="User Menu">
                        <strong class="user-name" role="presentation"><?= htmlspecialchars($_SESSION['user_name']); ?></strong>
                        <?php if ($_SESSION['user_role'] === 'tenant'): ?>
                            <a href="tenant_dashboard.php" class="account-button booking-btn" role="menuitem">My Bookings</a>
                        <?php endif; ?>
                        <a href="logout.php" class="account-button logout-btn" role="menuitem">Logout</a>
                    </div>
                </div>
            <?php else: ?>
                <a href="login.php" tabindex="0" aria-label="Login page">Login</a>
            <?php endif; ?>
        </div>
    </nav>
</header>

<main>
    <section class="browse" aria-labelledby="browse-title" id="browse">
        <h1 id="browse-title">Browse Our Rentals</h1>
        <?php if ($search !== ''): ?>
            <p>Search results for: <strong><?= htmlspecialchars($search); ?></strong></p>
        <?php endif; ?>

        <?php if (count($houses) > 0): ?>
            <div class="browse-grid" role="list">
                <?php foreach ($houses as $house): ?>
                    <article class="property-card" role="listitem" tabindex="0" aria-label="<?= htmlspecialchars($house['title']) . ' in ' . htmlspecialchars($house['location']) . ' for Rs' . htmlspecialchars($house['price']) . ' per month'; ?>">
                        <?php if ($house['status'] === 'booked'): ?>
                            <span class="badge-booked" aria-label="This property is booked">Booked</span>
                        <?php endif; ?>
                        <img class="property-image" src="images/<?= htmlspecialchars($house['image']); ?>" alt="<?= htmlspecialchars($house['title']); ?>" />
                        <div class="property-info">
                            <h3 class="property-title"><?= htmlspecialchars($house['title']); ?></h3>
                            <p class="property-location"><?= htmlspecialchars($house['location']); ?></p>
                            <p class="property-price">Rs<?= htmlspecialchars($house['price']); ?> / month</p>
                            <?php if ($house['status'] === 'booked'): ?>
                                <button class="btn-disabled" aria-disabled="true">Booked</button>
                            <?php else: ?>
                                <a href="details.php?id=<?= $house['id']; ?>" class="btn btn-primary" aria-label="View details of <?= htmlspecialchars($house['title']); ?>">View Details</a>
                            <?php endif; ?>
                        </div>
                    </article>
                <?php endforeach; ?>
            </div>
        <?php else: ?>
            <p>No houses found matching your search.</p>
        <?php endif; ?>
    </section>
</main>

<footer>
    &copy; 2025 Homzey. All rights reserved.
</footer>

<script>
function toggleDropdown() {
    const dropdown = document.getElementById('userDropdown');
    dropdown.classList.toggle('hidden');
}
</script>

</body>
</html>
