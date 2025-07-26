<?php 
session_start();
require 'db.php'; // your DB connection

// Get filters from URL (same names as form inputs)
$location = isset($_GET['location']) ? trim($_GET['location']) : '';
$min_price = isset($_GET['min_price']) && is_numeric($_GET['min_price']) ? (float)$_GET['min_price'] : 0;
$max_price = isset($_GET['max_price']) && is_numeric($_GET['max_price']) ? (float)$_GET['max_price'] : 0;

// Build WHERE clauses for filtering
$whereClauses = ["status IN ('available', 'booked')"];
$params = [];
$types = "";

if ($location !== '') {
    $whereClauses[] = "location LIKE ?";
    $params[] = "%$location%";
    $types .= "s";
}

if ($min_price > 0) {
    $whereClauses[] = "price >= ?";
    $params[] = $min_price;
    $types .= "d";
}

if ($max_price > 0 && $max_price >= $min_price) {
    $whereClauses[] = "price <= ?";
    $params[] = $max_price;
    $types .= "d";
}

$whereSQL = implode(" AND ", $whereClauses);

$sql = "SELECT * FROM houses WHERE $whereSQL ORDER BY created_at DESC";

$stmt = $conn->prepare($sql);

if ($params) {
    $stmt->bind_param($types, ...$params);
}

$stmt->execute();
$result = $stmt->get_result();

$houses = [];
while ($row = $result->fetch_assoc()) {
    $houses[] = $row;
}

$stmt->close();
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

</style>
</head>
<body>

<header>
    <nav class="container nav-flex">
        <div class="logo">
            <a href="index.php"><img src="../image/house.png" alt="Homzey logo" /></a>
        </div>

        
        <div class="nav-links">
            <a href="index.php">Home</a>
            <a href="browse.php">Browse</a>

             <?php if (!isset($_SESSION['user_name'])): ?>
                     <a href="login.php" tabindex="0" class="login">Login</a>
            <?php endif; ?>

            <?php if (isset($_SESSION['user_name'])): ?>
    <div class="user-dropdown-container">
        <button class="user-toggle" onclick="toggleDropdown()">
            <span><?= htmlspecialchars($_SESSION['user_name']); ?></span>
            <i class="fa fa-caret-down"></i>
        </button>
        <div id="userDropdown" class="user-dropdown hidden">
            <strong class="user-name"><?= htmlspecialchars($_SESSION['user_name']); ?></strong>

            <!-- Dashboard Button with Role-Based Redirect -->
            <?php if ($_SESSION['user_role'] === 'admin'): ?>
                <a href="admin_dashboard.php" class="account-button dashboard-btn">
                    <i class="fa fa-user-shield"></i> Admin Dashboard
                </a>
            <?php elseif ($_SESSION['user_role'] === 'tenant'): ?>
                <a href="tenant_dashboard.php" class="account-button dashboard-btn">
                    <i class="fa fa-home"></i> Tenant Dashboard
                </a>
                <a href="tenant_dashboard.php#bookings" class="account-button booking-btn">
                    <i class="fa fa-calendar-check"></i> My Bookings
                </a>
            <?php elseif ($_SESSION['user_role'] === 'landlord'): ?>
                <a href="landlord_dashboard.php" class="account-button dashboard-btn">
                    <i class="fa fa-building"></i> Landlord Dashboard
                </a>
            <?php endif; ?>

            <!-- Logout Button -->
            <a href="logout.php" class="account-button logout-btn">
                <i class="fa fa-sign-out-alt"></i> Logout
            </a>
        </div>
    </div>
<?php endif; ?>
        </div>
    </nav>
</header>

<main> 
    <section class="browse" id="browse">
        <h1>Browse Our Rentals</h1>

        <?php if ($location !== '' || $min_price > 0 || $max_price > 0): ?>
            <p>Search results:
                <?php if ($location !== ''): ?>
                    <strong>Location: <?= htmlspecialchars($location); ?></strong>
                <?php endif; ?>
                <?php if ($min_price > 0): ?>
                    <strong>Min Price: Rs<?= htmlspecialchars($min_price); ?></strong>
                <?php endif; ?>
                <?php if ($max_price > 0): ?>
                    <strong>Max Price: Rs<?= htmlspecialchars($max_price); ?></strong>
                <?php endif; ?>
            </p>
        <?php endif; ?>

        <?php if (count($houses) > 0): ?>
            <div class="browse-grid">
                <?php foreach ($houses as $index => $house): ?>
                    <article class="property-card">
                        <?php if ($house['status'] === 'booked'): ?>
                            <span class="badge-booked">Booked</span>
                        <?php endif; ?>

                        <?php 
                        $imageList = explode(',', $house['image']);
                        $sliderId = "slider-" . $index;
                        ?>
                        <div class="slider" id="<?= $sliderId ?>">
                            <div class="slider-images">
                                <?php foreach ($imageList as $img): ?>
                                    <img src="/homzey/images/<?= htmlspecialchars($img); ?>" alt="<?= htmlspecialchars($house['title']); ?>">
                                <?php endforeach; ?>
                            </div>
                            <?php if (count($imageList) > 1): ?>
                                <div class="slider-dots">
                                  <?php foreach ($imageList as $dotIndex => $img): ?>
                                    <span class="slider-dot" onclick="goToSlide('<?= $sliderId ?>', <?= $dotIndex ?>)" aria-label="Go to slide <?= $dotIndex + 1 ?>"></span>
                                  <?php endforeach; ?>
                                </div>
                            <?php endif; ?>
                        </div>

                        <div class="property-info">
                            <h3><?= htmlspecialchars($house['title']); ?></h3>
                            <p><?= htmlspecialchars($house['location']); ?></p>
                            <p>Rs<?= htmlspecialchars($house['price']); ?> / month</p>

                            <?php if ($house['status'] === 'booked'): ?>
                                <button class="btn-disabled">Booked</button>
                            <?php else: ?>
                                <a href="details.php?id=<?= $house['id']; ?>" class="btn btn-primary">View Details</a>
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
  const sliders = {};

  function updateDots(id, activeIndex) {
    const slider = document.getElementById(id);
    const dots = slider.querySelectorAll('.slider-dot');
    dots.forEach((dot, i) => {
      dot.classList.toggle('active', i === activeIndex);
    });
  }

  function goToSlide(id, index) {
    const slider = document.querySelector(`#${id} .slider-images`);
    if (!sliders[id]) sliders[id] = 0;
    sliders[id] = index;
    slider.style.transform = `translateX(-${index * 100}%)`;
    updateDots(id, index);
  }

  // Initialize all sliders dots active state to first slide on page load
  window.addEventListener('DOMContentLoaded', () => {
    document.querySelectorAll('.slider').forEach(slider => {
      const id = slider.id;
      sliders[id] = 0;
      updateDots(id, 0);
    });
  });

  function toggleDropdown() {
    const dropdown = document.getElementById('userDropdown');
    dropdown.classList.toggle('hidden');
  }
</script>

</body>
</html>