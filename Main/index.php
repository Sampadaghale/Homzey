<?php    
session_start();
require 'db.php'; // expects $conn = mysqli_connect(...);

// Fetch 3 random available properties (not booked) with all amenity details
$sql = "SELECT id, title, location, price, image, rooms, bathroom, bhk, wifi, parking, laundry, furnished, air_conditioning, balcony FROM houses WHERE status = 'available' ORDER BY RAND() LIMIT 3";
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

<header>
    <nav class="container nav-flex" aria-label="Primary Navigation">
        <div class="logo" tabindex="0">
           <a href="index.php"><img src="../image/house.png" alt="Homzey logo" />
           </a>
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
            
            <?php if (!isset($_SESSION['user_name'])): ?>
                     <a href="login.php" tabindex="0" class="login">Login</a>
            <?php endif; ?>

            <!-- <a href="login.php" tabindex="0">Login</a> -->
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
                <a href="../Tenants/tenant_dashboard.php" class="account-button dashboard-btn">
                    <i class="fa fa-home"></i> Tenant Dashboard
                </a>
                <a href="../mybooking.php#bookings" class="account-button booking-btn">
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
    <section class="hero" role="banner" aria-label="Hero banner with tagline and call to action" id="hero">
        <div class="slideshow" aria-hidden="true">
            <div class="slide" style="background-image: url('../image/house2.jpg');"></div>
            <div class="slide" style="background-image: url('../image/house3.jpg');"></div>
            <div class="slide" style="background-image: url('../image/house1.jpg');"></div>
            <div class="slide" style="background-image: url('../image/house4.jpg');"></div>
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
  <div class="container">
    <h2 id="browse-title">Featured Properties</h2>
    
    <?php if (empty($houses)): ?>
      <div class="no-properties">
        <p>No properties available at the moment. Please check back later.</p>
      </div>
    <?php else: ?>
      <div class="modern-browse-grid" role="list">
        <?php foreach ($houses as $house):  
          // Sanitize and prepare image data
          $imageList = !empty($house['image']) ? explode(',', $house['image']) : [];
          $imageList = array_map('trim', $imageList);
          $imageList = array_filter($imageList, function($img) {
            return !empty($img) && strlen($img) > 0;
          });
          $imageList = array_slice($imageList, 0, 4); // Limit to 4 images
          $sliderId = "slider_" . intval($house['id']); // Ensure ID is integer
          
          // Prepare amenities array for cleaner display
          $amenities = [
            'rooms' => ['value' => $house['rooms'], 'label' => 'bed', 'icon' => 'fas fa-bed'],
            'bathroom' => ['value' => $house['bathroom'], 'label' => 'bath', 'icon' => 'fas fa-bath'],
            'wifi' => ['value' => $house['wifi'], 'label' => 'WiFi', 'icon' => 'fas fa-wifi'],
            'parking' => ['value' => $house['parking'], 'label' => 'Parking', 'icon' => 'fas fa-car'],
            'laundry' => ['value' => $house['laundry'], 'label' => 'Laundry', 'icon' => 'fas fa-tshirt'],
            'furnished' => ['value' => $house['furnished'], 'label' => 'Furnished', 'icon' => 'fas fa-couch'],
            'air_conditioning' => ['value' => $house['air_conditioning'], 'label' => 'A/C', 'icon' => 'fas fa-snowflake'],
            'balcony' => ['value' => $house['balcony'], 'label' => 'Balcony', 'icon' => 'fas fa-building']
          ];
        ?>

        <article class="modern-property-card" role="listitem" data-property-id="<?= intval($house['id']) ?>">
          <div class="modern-property-image">
            <?php if (!empty($imageList)): ?>
              <div class="slider" id="<?= $sliderId ?>" role="region" aria-label="Property images">
                <div class="slider-images">
                  <?php foreach ($imageList as $index => $img): ?>
                    <img src="../images/<?= htmlspecialchars($img, ENT_QUOTES, 'UTF-8'); ?>" 
                         alt="<?= htmlspecialchars($house['title'] . ' - Image ' . ($index + 1), ENT_QUOTES, 'UTF-8'); ?>"
                         class="slider-img <?= $index === 0 ? 'active' : '' ?>" 
                         data-index="<?= $index ?>"
                         loading="<?= $index === 0 ? 'eager' : 'lazy' ?>" />
                  <?php endforeach; ?>
                </div>

                <?php if (count($imageList) > 1): ?>
                  <button type="button" class="slider-nav prev" onclick="slidePrev('<?= $sliderId ?>')" 
                          aria-label="Previous image">
                    <i class="fas fa-chevron-left" aria-hidden="true"></i>
                  </button>
                  <button type="button" class="slider-nav next" onclick="slideNext('<?= $sliderId ?>')" 
                          aria-label="Next image">
                    <i class="fas fa-chevron-right" aria-hidden="true"></i>
                  </button>

                  <div class="slider-dots" role="tablist" aria-label="Image navigation">
                    <?php foreach ($imageList as $index => $img): ?>
                      <button type="button" 
                              class="slider-dot <?= $index === 0 ? 'active' : '' ?>" 
                              onclick="goToSlide('<?= $sliderId ?>', <?= $index ?>)" 
                              role="tab"
                              aria-selected="<?= $index === 0 ? 'true' : 'false' ?>"
                              aria-label="View image <?= $index + 1 ?> of <?= count($imageList) ?>"></button>
                    <?php endforeach; ?>
                  </div>
                  
                  <div class="image-counter" aria-live="polite">
                    <span class="current-image">1</span> / <?= count($imageList) ?>
                  </div>
                <?php endif; ?>
              </div>
            <?php else: ?>
              <div class="property-placeholder" role="img" aria-label="No image available">
                <i class="fas fa-home" aria-hidden="true"></i>
                <span>No Image Available</span>
              </div>
            <?php endif; ?>
          </div>

          <div class="modern-property-info">
            <h3 class="modern-property-title">
              <a href="details.php?id=<?= intval($house['id']) ?>" class="property-title-link">
                <?= htmlspecialchars($house['title'], ENT_QUOTES, 'UTF-8'); ?>
              </a>
            </h3>
            
            <div class="modern-property-location">
              <i class="fas fa-map-marker-alt" aria-hidden="true"></i>
              <span><?= htmlspecialchars($house['location'], ENT_QUOTES, 'UTF-8'); ?></span>
            </div>

            <div class="amenities-tags" role="list" aria-label="Property amenities">
              <?php foreach ($amenities as $key => $amenity): ?>
                <?php if (!empty($amenity['value'])): ?>
                  <span class="amenity-tag <?= $key ?>" role="listitem">
                    <i class="<?= $amenity['icon'] ?>" aria-hidden="true"></i>
                    <?php if (in_array($key, ['rooms', 'bathroom'])): ?>
                      <?= intval($amenity['value']) ?> <?= $amenity['label'] ?>
                    <?php else: ?>
                      <?= $amenity['label'] ?>
                    <?php endif; ?>
                  </span>
                <?php endif; ?>
              <?php endforeach; ?>
            </div>

            <div class="modern-property-price" aria-label="Monthly rent">
              <span class="currency">Rs</span>
              <span class="amount"><?= number_format(floatval($house['price'])); ?></span>
              <span class="period">/month</span>
            </div>

            <div class="property-actions">
              <a href="details.php?id=<?= intval($house['id']) ?>" 
                 class="btn btn-view-details"
                 aria-label="View details for <?= htmlspecialchars($house['title'], ENT_QUOTES, 'UTF-8') ?>">
                <i class="fas fa-eye" aria-hidden="true"></i>
                View Details
              </a>
              <a href="Tenants/booking.php?id=<?= intval($house['id']) ?>" 
                 class="btn btn-contact btn-primary"
                 aria-label="Rent <?= htmlspecialchars($house['title'], ENT_QUOTES, 'UTF-8') ?> now">
                <i class="fas fa-home" aria-hidden="true"></i>
                Rent Now
              </a>
            </div>
          </div>
        </article>

        <?php endforeach; ?>
      </div>
    <?php endif; ?>
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
                <img src="../image/house 1.png" alt="About House" />
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
<script src="script.js"></script>

</body>
</html>