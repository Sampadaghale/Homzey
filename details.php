<?php
session_start();
include 'db.php';

if (isset($_GET["id"]) && is_numeric($_GET["id"])) {
    $house_id = intval($_GET["id"]);

    $stmt = $conn->prepare("SELECT * FROM houses WHERE id = ?");
    $stmt->bind_param("i", $house_id);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows === 1) {
        $row = $result->fetch_assoc();
        $imageList = explode(',', $row["image"]);
        ?>
        <!DOCTYPE html>
        <html lang="en">
        <head>
            <meta charset="UTF-8" />
            <meta name="viewport" content="width=device-width, initial-scale=1.0" />
            <title>House Details</title>
            <link rel="stylesheet" href="styles.css" />
            <style>
                * {
                    margin: 0;
                    padding: 0;
                    box-sizing: border-box;
                }

                body {
                    font-family: Arial, sans-serif;
                    background-color: #fff;
                }

                header {
                    margin-bottom: 0;
                }

                main {
                    margin-top: 0;
                }

                h2 {
                    margin-top: 0;
                    margin-bottom: 1rem;
                }

                .container {
                    max-width: 800px;
                    margin: 2rem auto;
                    padding: 20px;
                    border: 1px solid #ccc;
                    border-radius: 10px;
                    background-color: #f9f9f9;
                }

                .slider {
                    position: relative;
                    overflow: hidden;
                    border-radius: 8px;
                    height: 400px;
                    margin-bottom: 10px;
                }

                .slider-images {
                    display: flex;
                    transition: transform 0.5s ease;
                    height: 100%;
                }

                .slider-images img {
                    width: 100%;
                    flex-shrink: 0;
                    object-fit: cover;
                    height: 100%;
                }

                .slider-dots {
                    text-align: center;
                    margin-top: 10px;
                }

                .slider-dot {
                    display: inline-block;
                    width: 12px;
                    height: 12px;
                    margin: 0 5px;
                    background-color: #ccc;
                    border-radius: 50%;
                    cursor: pointer;
                    transition: background-color 0.3s ease;
                }

                .slider-dot.active {
                    background-color: #3b82f6;
                }

                p {
                    margin-bottom: 1rem;
                    line-height: 1.5;
                    color: #333;
                }

                .btn-primary,
                .btn-secondary {
                    display: inline-block;
                    margin: 10px 10px 0 0;
                    padding: 10px 15px;
                    border: none;
                    background-color: #007bff;
                    color: white;
                    text-decoration: none;
                    border-radius: 5px;
                    transition: background-color 0.3s ease;
                    cursor: pointer;
                }

                .btn-primary:hover,
                .btn-secondary:hover {
                    background-color: #0056b3;
                }

                .btn-disabled {
                    background-color: #999 !important;
                    cursor: not-allowed;
                    color: #eee !important;
                }
            </style>
        </head>
        <body>
            <header>
                <!-- Your site header if needed -->
            </header>
            <main>
                <div class="container">
                    <h2><?= htmlspecialchars($row["title"]) ?></h2>

                    <!-- Image Slider -->
                    <div class="slider">
                        <div class="slider-images" id="sliderImages">
                            <?php foreach ($imageList as $img): ?>
                                <img src="images/<?= htmlspecialchars(trim($img)) ?>" alt="House Image">
                            <?php endforeach; ?>
                        </div>
                    </div>
                    <div class="slider-dots" id="sliderDots">
                        <?php foreach ($imageList as $i => $img): ?>
                            <span class="slider-dot" onclick="goToSlide(<?= $i ?>)"></span>
                        <?php endforeach; ?>
                    </div>

                    <p><strong>Description:</strong> <?= nl2br(htmlspecialchars($row["description"])) ?></p>
                    <p><strong>Location:</strong> <?= htmlspecialchars($row["location"]) ?></p>
                    <p><strong>Price:</strong> Rs<?= htmlspecialchars($row["price"]) ?> / month</p>

                    <?php if ($row["status"] === "booked"): ?>
                        <button class="btn-primary btn-disabled" disabled>This property is already booked</button>
                    <?php else: ?>
                        <a href="booking.php?id=<?= $row["id"] ?>" class="btn-primary">Rent Now</a>
                    <?php endif; ?>
                    <a href="browse.php" class="btn-secondary">Back to Browse</a>
                </div>
            </main>

            <!-- ✅ Restore your review section -->
            <?php include 'review.php'; ?>

            <script>
                const sliderImages = document.getElementById('sliderImages');
                const dots = document.querySelectorAll('.slider-dot');
                let currentSlide = 0;
                const totalSlides = <?= count($imageList) ?>;

                function goToSlide(index) {
                    currentSlide = index;
                    sliderImages.style.transform = `translateX(-${index * 100}%)`;
                    dots.forEach((dot, i) => {
                        dot.classList.toggle('active', i === index);
                    });
                }

                if (dots.length > 0) {
                    goToSlide(0);
                }
            </script>
        </body>
        </html>
        <?php
    } else {
        echo "<p>House not found.</p>";
    }

    $stmt->close();
} else {
    echo "<p>Invalid request.</p>";
}
?>
