<?php    
session_start();
require 'db.php'; // $conn = mysqli connection

$search = '';
if (isset($_GET['search'])) {
    $search = trim($_GET['search']);
}

if ($search !== '') {
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
  .badge-booked {
    background-color: #ef4444;
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
  .property-card {
    position: relative;
    background: #fff;
    border-radius: 8px;
    overflow: hidden;
    box-shadow: 0 2px 8px rgba(0, 0, 0, 0.1);
  }

  .slider {
    position: relative;
    overflow: hidden;
    height: 200px;
  }

  .slider-images {
    display: flex;
    transition: transform 0.3s ease-in-out;
  }

  .slider img {
    width: 100%;
    flex-shrink: 0;
    object-fit: cover;
    height: 200px;
  }

  /* Dots container */
  .slider-dots {
    text-align: center;
    position: absolute;
    bottom: 8px;
    width: 100%;
  }

  /* Each dot */
  .slider-dot {
  display: inline-block;
  width: 8px;      /* smaller width */
  height: 8px;     /* smaller height */
  margin: 0 5px;
  background-color: rgba(255, 255, 255, 0.6);
  border-radius: 50%;
  cursor: pointer;
  transition: background-color 0.3s ease;
}


  /* Active dot */
  .slider-dot.active {
    background-color: #3b82f6;
  }

  /* Remove background color on hover for dots */
  .slider-dot:hover {
    background-color: rgba(255, 255, 255, 0.8);
  }

  .browse-grid {
    display: grid;
    gap: 2rem;
    padding: 2rem;
    grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
  }

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
    <nav class="container nav-flex">
        <div class="logo">
            <img src="image/house.png" alt="Homzey logo" />
        </div>

        <form action="browse.php" method="get" class="search-form">
            <input type="text" name="search" placeholder="Search by location, title or price" value="<?= htmlspecialchars($search); ?>" />
            <button type="submit">Search</button>
        </form>

        <div class="nav-links">
            <a href="index.php">Home</a>
            <a href="browse.php">Browse</a>

            <?php if (isset($_SESSION['user_name'])): ?>
                <div class="user-dropdown-container">
                    <button class="user-toggle" onclick="toggleDropdown()">
                        <span><?= htmlspecialchars($_SESSION['user_name']); ?></span>
                        <i class="fa fa-caret-down"></i>
                    </button>
                    <div id="userDropdown" class="user-dropdown hidden">
                        <strong class="user-name"><?= htmlspecialchars($_SESSION['user_name']); ?></strong>
                        <?php if ($_SESSION['user_role'] === 'tenant'): ?>
                            <a href="tenant_dashboard.php" class="account-button booking-btn">My Bookings</a>
                        <?php endif; ?>
                        <a href="logout.php" class="account-button logout-btn">Logout</a>
                    </div>
                </div>
            <?php else: ?>
                <a href="login.php">Login</a>
            <?php endif; ?>
        </div>
    </nav>
</header>

<main>
    <section class="browse" id="browse">
        <h1>Browse Our Rentals</h1>
        <?php if ($search !== ''): ?>
            <p>Search results for: <strong><?= htmlspecialchars($search); ?></strong></p>
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
                                    <img src="images/<?= htmlspecialchars($img); ?>" alt="<?= htmlspecialchars($house['title']); ?>">
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
