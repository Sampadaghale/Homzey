<?php    
session_start();
require 'db.php'; // expects $conn = mysqli_connect(...);

// Fetch 3 random available properties (not booked)
$sql = "SELECT id, title, location, price, image FROM houses WHERE status = 'available' ORDER BY RAND() LIMIT 3";
$result = mysqli_query($conn, $sql);

$houses = [];
if ($result) {
    while ($row = mysqli_fetch_assoc($result)) {
        $houses[] = $row;
    }
    mysqli_free_result($result);
} else {
    // Query error handling (optional)
    $houses = [];
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1" />
    <title>Homzey</title>
    <link rel="stylesheet" href="styles.css" />
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css" />
</head>
<body>
<script src="script.js"></script>

<header>
    <nav class="container nav-flex" aria-label="Primary Navigation">
        <div class="logo" tabindex="0">
            <img src="image/house.png" alt="Homzey logo" />
        </div>

        <!-- SEARCH MOVED HERE -->
        <form action="browse.php" method="get" class="search-form" role="search" aria-label="Search houses">
          <input type="text" name="search" placeholder="Search by location, title or price" required />
          <button type="submit" aria-label="Search">Search</button>
        </form>

        <div class="nav-links" role="navigation" aria-label="Main Navigation Links">
            <a href="#hero" tabindex="0">Home</a>
            <a href="browse.php" tabindex="0">Browse</a>
            <a href="#about" tabindex="0">About</a>
            <a href="#contact" tabindex="0">Contact</a>

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
                <a href="login.php" style="color:black;" tabindex="0" aria-label="Login page">Login</a>
            <?php endif; ?>
        </div>
    </nav>
</header>

<main>
    <section class="hero" role="banner" aria-label="Hero banner with tagline and call to action" id="hero">
        <div class="slideshow" aria-hidden="true">
            <div class="slide" style="background-image: url('image/house2.jpg');"></div>
            <div class="slide" style="background-image: url('image/house3.jpg');"></div>
            <div class="slide" style="background-image: url('image/house1.jpg');"></div>
            <div class="slide" style="background-image: url('image/house4.jpg');"></div>
        </div>
        <div class="container hero-content">
            <h1>Find Your Perfect Home To Rent</h1>
            <p>Discover verified rental houses with ease. From cozy apartments to spacious homes — your dream rental is just a click away.</p>
            <button class="btn-primary" type="button" aria-label="Browse house rentals" onclick="location.href='Browse.php'">
                Browse Rentals
            </button>
        </div>
    </section>

    <section class="browse" aria-labelledby="browse-title" id="browse">
        <h2 id="browse-title">Browse Our Rentals</h2>
        <div class="browse-grid" role="list">
            <?php foreach ($houses as $house): ?>
                <article class="property-card" role="listitem" tabindex="0" aria-label="<?= htmlspecialchars($house['title']) . ' in ' . htmlspecialchars($house['location']) . ' for $' . htmlspecialchars($house['price']) . ' per month'; ?>">
                   <img class="property-image" src="images/<?= htmlspecialchars($house['image']); ?>" alt="<?= htmlspecialchars($house['title']); ?>" />
                    <div class="property-info">
                        <h3 class="property-title"><?= htmlspecialchars($house['title']); ?></h3>
                        <p class="property-location"><?= htmlspecialchars($house['location']); ?></p>
                        <p class="property-price">Rs<?= htmlspecialchars($house['price']); ?> / month</p>
                        <a href="details.php?id=<?= $house['id']; ?>" class="btn btn-primary" aria-label="View details of <?= htmlspecialchars($house['title']); ?>">View Details</a>
                    </div>
                </article>
            <?php endforeach; ?>
        </div>
    </section>

    <section class="features" aria-labelledby="features-title">
        <h2 id="features-title">Why Choose HouseRent?</h2>
        <div class="features-grid">
            <article class="feature-card" tabindex="0" aria-describedby="feature1-desc" role="region" aria-label="Verified Listings">
                <svg class="feature-icon" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true" focusable="false">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M9 12l2 2 4-4m5 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                </svg>
                <h3 class="feature-title">Verified Listings</h3>
                <p id="feature1-desc" class="feature-desc">All homes are quality verified so you can browse with confidence.</p>
            </article>
            <article class="feature-card" tabindex="0" aria-describedby="feature2-desc" role="region" aria-label="Easy Booking">
                <svg class="feature-icon" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true" focusable="false">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M3 8h18M3 12h18M12 16h6" />
                </svg>
                <h3 class="feature-title">Easy Booking</h3>
                <p id="feature2-desc" class="feature-desc">Book your rental quickly through a smooth and hassle-free process.</p>
            </article>
            <article class="feature-card" tabindex="0" aria-describedby="feature3-desc" role="region" aria-label="24/7 Support">
                <svg class="feature-icon" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true" focusable="false">
                    <circle cx="12" cy="12" r="10" />
                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 6v6l4 2" />
                </svg>
                <h3 class="feature-title">24/7 Support</h3>
                <p id="feature3-desc" class="feature-desc">Our support team is always ready to help you with any questions.</p>
            </article>
            <article class="feature-card" tabindex="0" aria-describedby="feature4-desc" role="region" aria-label="Multiple Locations">
                <svg class="feature-icon" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true" focusable="false">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 11c1.104 0 2 .896 2 2 0 3-4 6-4 6s-4-3-4-6c0-1.104.896-2 2-2z" />
                    <circle cx="12" cy="11" r="2" />
                </svg>
                <h3 class="feature-title">Multiple Locations</h3>
                <p id="feature4-desc" class="feature-desc">Find your next home in popular cities across the country.</p>
            </article>
        </div>
    </section>

    <section class="about" id="about" aria-labelledby="about-title">
        <div class="container about-content">
            <div class="about-text">
                <h2 id="about-title">About Us</h2>
                 <p>
            At HouseRent, we are committed to connecting renters with their dream homes through an easy-to-use, trustworthy platform. Our team carefully vets each listing for quality and authenticity to ensure you find a perfect place to call home effortlessly.
          </p>
                <p>We provide quality rental houses that suit your lifestyle and budget. Explore your next home with us.</p>
            </div>
            <div class="about-image">
                <img src="image/house 1.png" alt="About House" />
            </div>
        </div>
    </section>

    <section class="contact" id="contact">
        <div class="section__container contact__container">
          <div class="contact__col">
            <h4>Contact a travel researcher</h4>
            <p>We always aim to reply within 24 hours.</p>
          </div>  
          <div class="contact__col">
            <div class="contact__card">
                <span>
                    <a href="#"><i class="ri-phone-line"></i></a></span>
                <h4>call us</h4>
                <h5>9742515636</h5>
                <p>We are online now</p>
            </div>
          </div>
          <div class="contact__col">
            <div class="contact__card">
                <span>
                    <a href="#"><i class="ri-mail-line"></i></a></span>
                <h4>Send us enquiry</h4>
            </div>
          </div>
        </div>
     </section>

    <section class="how-it-works" aria-labelledby="how-title">
        <div class="container">
            <h2 id="how-title">How It Works</h2>
            <div class="steps">
                <article class="step" tabindex="0" aria-describedby="step1-desc" role="region" aria-label="Browse listings">
                    <svg class="step-icon" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true" focusable="false">
                        <circle cx="12" cy="12" r="10" />
                        <path stroke-linecap="round" stroke-linejoin="round" d="M8 12h8" />
                    </svg>
                    <h3 class="step-title">Browse Listings</h3>
                    <p id="step1-desc" class="step-desc">Explore hundreds of available rental homes tailored to your preferences.</p>
                </article>
                <article class="step" tabindex="0" aria-describedby="step2-desc" role="region" aria-label="Select a home">
                    <svg class="step-icon" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true" focusable="false">
                        <rect x="7" y="7" width="10" height="10" rx="1" ry="1" />
                    </svg>
                    <h3 class="step-title">Select a Home</h3>
                    <p id="step2-desc" class="step-desc">Choose the rental that fits your needs after reading detailed descriptions.</p>
                </article>
                <article class="step" tabindex="0" aria-describedby="step3-desc" role="region" aria-label="Book with ease">
                    <svg class="step-icon" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true" focusable="false">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M16 8v6a4 4 0 01-8 0v-6" />
                        <rect x="8" y="8" width="8" height="6" rx="2" ry="2" />
                    </svg>
                    <h3 class="step-title">Book with Ease</h3>
                    <p id="step3-desc" class="step-desc">Secure your rental with our secure and quick booking platform.</p>
                </article>
            </div>
        </div>
    </section>
</main>

<footer>
    &copy; 2025 Homzey. All rights reserved.
</footer>

</body>
</html>
