<?php
session_start();
require 'Main/db.php'; // your DB connection

// Fetch 3 random available properties (optional; remove if not needed here)
$sql = "SELECT id, title, location, price, image, rooms, bathroom, bhk, wifi, parking, laundry, furnished, air_conditioning, balcony FROM houses WHERE status = 'available' ORDER BY RAND() LIMIT 3";
$result = mysqli_query($conn, $sql);

$houses = [];
if ($result) {
    while ($row = mysqli_fetch_assoc($result)) {
        $houses[] = $row;
    }
    mysqli_free_result($result);
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1" />
  <title>Add Review - Homzey</title>

  <!-- FontAwesome -->
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css" />

  <style>
    /* Reset and base */
    * {
      margin: 0;
      padding: 0;
      box-sizing: border-box;
    }
    body {
      font-family: Arial, sans-serif;
      background-color: #d9d9d9;
      line-height: 1.6;
      color: #333;
    }
    a {
      text-decoration: none;
      color: inherit;
    }
    img {
      max-width: 100%;
      height: auto;
      display: block;
    }

    /* Container */
    .container {
      width: 90%;
      max-width: 1200px;
      margin: 0 auto;
    }

    /* Header/Nav */
    header {
      background-color: white; 
      padding: 10px 0;
      box-shadow: 0 2px 5px rgba(0,0,0,0.1);
    }
    nav.container {
      display: flex;
      align-items: center;
      justify-content: space-between;
      gap: 10px;
      flex-wrap: wrap;
    }
    .logo img {
      height: 40px;
    }
    .nav-links a,
    .login {
      color: black;
      font-weight: 600;
      padding: 8px 15px;
      border-radius: 5px;
      transition: background-color 0.3s ease;
    }
    .nav-links a:hover,
    .login:hover {
      background-color: #b91c1c; /* darker red */
    }

    /* Search form */
    form.search-form {
      display: flex;
      gap: 5px;
      color: black;
      flex-grow: 1;
      max-width: 400px;
    }
    form.search-form input[type="text"] {
      flex-grow: 1;
      padding: 7px 10px;
      border: none;
      color: black;
      border-radius: 4px 0 0 4px;
      font-size: 14px;
    }


    form.search-form button {
      background-color: #3b82f6; /* blue */
      border: none;
      color: white;
      padding: 7px 15px;
      border-radius: 0 4px 4px 0;
      cursor: pointer;
      font-weight: bold;
      transition: background-color 0.3s ease;
    }
    form.search-form button:hover {
      background-color: #2563eb;
    }

    /* User dropdown */
    .user-dropdown-container {
      position: relative;
      display: inline-block;
    }
    .user-toggle {
      background: transparent;
      border: none;
      color: white;
      font-weight: 600;
      cursor: pointer;
      display: flex;
      align-items: center;
      gap: 4px;
      font-size: 14px;
    }
    .user-dropdown {
      position: absolute;
      right: 0;
      top: 100%;
      background: white;
      color: #333;
      border-radius: 5px;
      box-shadow: 0 2px 8px rgba(0,0,0,0.2);
      min-width: 180px;
      padding: 10px;
      display: none;
      z-index: 100;
    }
    .user-dropdown.visible {
      display: block;
    }
    .user-dropdown strong {
      display: block;
      margin-bottom: 10px;
      font-weight: 700;
    }
    .account-button {
      display: block;
      padding: 8px 10px;
      border-radius: 4px;
      color: #333;
      font-weight: 600;
      margin-bottom: 5px;
      transition: background-color 0.2s ease;
    }
    .account-button:hover {
      background-color: #f3f4f6;
    }

    /* Main */
    main {
      padding: 40px 0;
      min-height: 70vh;
    }

    /* Review form section */
    .review-section {
      max-width: 500px;
      margin: 0 auto;
      background: #f9f9f9;
      padding: 30px 25px;
      border-radius: 10px;
      box-shadow: 0 4px 12px rgba(0,0,0,0.1);
    }
    .review-section h2 {
      margin-bottom: 20px;
      text-align: center;
      color: #ef4444;
    }
    .review-section form {
      display: flex;
      flex-direction: column;
      gap: 15px;
    }
    .review-section input[type="text"],
    .review-section input[type="email"],
    .review-section select,
    .review-section textarea {
      padding: 10px 15px;
      font-size: 16px;
      border: 1px solid #ccc;
      border-radius: 6px;
      resize: vertical;
      transition: border-color 0.3s ease;
      font-family: inherit;
    }
    .review-section input[type="text"]:focus,
    .review-section input[type="email"]:focus,
    .review-section select:focus,
    .review-section textarea:focus {
      border-color: #ef4444;
      outline: none;
    }
    .review-section textarea {
      min-height: 120px;
    }
    .review-section button[type="submit"] {
      background-color: #ef4444;
      border: none;
      color: white;
      font-size: 18px;
      font-weight: 700;
      padding: 12px;
      border-radius: 8px;
      cursor: pointer;
      transition: background-color 0.3s ease;
    }
    .review-section button[type="submit"]:hover {
      background-color: #b91c1c;
    }

    /* Footer */
    footer {
      background-color: #d9d9d9;
      color: black;
      text-align: center;
      padding: 15px 0;
      font-weight: 600;
      margin-top: 40px;
      bottom: 0;
    }
  </style>
</head>
<body>

<header>
  <nav class="container nav-flex" aria-label="Primary Navigation">
    <div class="logo" tabindex="0">
     <img src="image/house.png" alt="Homzey logo" /></a>
    </div>

    <form action="Main/browse.php" method="get" class="search-form" role="search" aria-label="Search houses">
      <input type="text" name="search" placeholder="Search by location, title or price" required />
      <button type="submit" aria-label="Search">Search</button>
    </form>

    <div class="nav-links" role="navigation" aria-label="Main Navigation Links">
      <a href="Main/index.php" tabindex="0">Home</a>
      <a href="Main/browse.php" tabindex="0">Browse</a>
      <a href="#about" tabindex="0">About</a>
      <a href="#contact" tabindex="0">Contact</a>

      <?php if (!isset($_SESSION['user_name'])): ?>
          <a href="login.php" tabindex="0" class="login">Login</a>
      <?php else: ?>
      <div class="user-dropdown-container">
        <button class="user-toggle" onclick="toggleDropdown()">
          <span><?= htmlspecialchars($_SESSION['user_name']); ?></span>
          <i class="fa fa-caret-down"></i>
        </button>
        <div id="userDropdown" class="user-dropdown">
          <strong class="user-name"><?= htmlspecialchars($_SESSION['user_name']); ?></strong>
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
  <!-- Review Form Section -->
  <section class="review-section" aria-labelledby="review-title">
    <h2 id="review-title">Submit a Review</h2>
    <form method="POST" action="submit_review.php">
      <input type="text" name="name" placeholder="Your Name" required />
      <input type="email" name="email" placeholder="Your Email (optional)" />
      <select name="rating" required>
        <option value="">Rate us</option>
        <option value="5">★★★★★</option>
        <option value="4">★★★★☆</option>
        <option value="3">★★★☆☆</option>
        <option value="2">★★☆☆☆</option>
        <option value="1">★☆☆☆☆</option>
      </select>
      <textarea name="comment" placeholder="Write your review here..." required></textarea>
      <button type="submit">Submit Review</button>
    </form>
  </section>
</main>

<footer>
  &copy; 2025 Homzey. All rights reserved.
</footer>

<script>
  // Toggle user dropdown menu
  function toggleDropdown() {
    const dropdown = document.getElementById('userDropdown');
    dropdown.classList.toggle('visible');
  }
  // Close dropdown on outside click
  window.addEventListener('click', (e) => {
    const dropdown = document.getElementById('userDropdown');
    if (!e.target.closest('.user-dropdown-container')) {
      dropdown.classList.remove('visible');
    }
  });
</script>

</body>
</html>
