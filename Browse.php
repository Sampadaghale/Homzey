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
    // Search by title, location or price (price as number, so we search only if numeric)
    $priceFilter = is_numeric($search) ? " OR price = " . floatval($search) : "";
    $sql = "SELECT * FROM houses WHERE status = 'available' AND (title LIKE ? OR location LIKE ? $priceFilter) ORDER BY created_at DESC";
    $stmt = $conn->prepare($sql);
    $likeSearch = "%$search%";
    $stmt->bind_param("ss", $likeSearch, $likeSearch);
    $stmt->execute();
    $result = $stmt->get_result();
} else {
    $sql = "SELECT * FROM houses WHERE status = 'available' ORDER BY created_at DESC";
    $result = $conn->query($sql);
}

// Fetch all houses
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
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css" />
     <style>
* {
                    margin: 0;
                    padding: 0;
                    box-sizing: border-box;
                }
                
                body {
                    margin: 0;
                    padding: 0;
                    font-family: Arial, sans-serif;
                    background-color: #fff;
                }
                header {
                    margin-bottom: 0;
                    padding-bottom: 0;
                }

                main {
                    margin-top: 0;
                    padding-top: 0;
                }

                h2 {
                    margin-top: 0;
                    padding-top: 0;
                    margin-bottom: 1rem;
                }
                .search-form {
  display: flex;
  align-items: center;
  gap: 0.5rem;
  flex-wrap: nowrap;
  max-width: 500px;
}

.search-form input[type="text"] {
  flex: 1;
  padding: 0.5rem 1rem;
  font-size: 1rem;
  border: 1px solid #ccc;
  border-radius: 0.5rem;
}

.search-form button {
  padding: 0.5rem 1rem;
  font-size: 1rem;
  background-color: #92400e;
  color: #fff;
  border: none;
  border-radius: 0.5rem;
  cursor: pointer;
  white-space: nowrap;
  margin-top: -40px;
}
.property-card img,
.property-image {
  width: 100%;
  height: 220px; /* Adjust to your preferred height */
  object-fit: cover;
  border-radius: 0.5rem;
}
.browse-grid {
  display: grid;
  grid-template-columns: repeat(auto-fit, minmax(280px, 1fr));
  gap: 1.5rem;
  padding: 1rem;
}

.property-card {
  background: #fff;
  border: 1px solid #ddd;
  border-radius: 0.75rem;
  overflow: hidden;
  box-shadow: 0 2px 6px rgba(0,0,0,0.1);
  transition: transform 0.3s ease;
}
.property-card .btn {
  display: inline-block;
  margin-top: 0.5rem; /* adjust the spacing as you like */
  margin-bottom: 1rem;
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
                    <article class="property-card" role="listitem" tabindex="0" aria-label="<?= htmlspecialchars($house['title']) . ' in ' . htmlspecialchars($house['location']) . ' for $' . htmlspecialchars($house['price']) . ' per month'; ?>">
    <img class="property-image" src="images/<?= htmlspecialchars($house['image']); ?>" alt="<?= htmlspecialchars($house['title']); ?>" />
    <div class="property-info">
        <h3 class="property-title"><?= htmlspecialchars($house['title']); ?></h3>
        <p class="property-location"><?= htmlspecialchars($house['location']); ?></p>
        <p class="property-price">$<?= htmlspecialchars($house['price']); ?> / month</p>
        <a href="details.php?id=<?= $house['id']; ?>" class="btn btn-primary" aria-label="View details of <?= htmlspecialchars($house['title']); ?>">View Details</a>
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
    &copy; 2024 HouseRent. All rights reserved.
</footer>

<script>
function toggleDropdown() {
    const dropdown = document.getElementById('userDropdown');
    dropdown.classList.toggle('hidden');
}
</script>

</body>
</html>
