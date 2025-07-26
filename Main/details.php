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
            <link rel="stylesheet" href="details.css" />
        </head>
        <body>
            <header>
                <!-- Your site header if needed -->
            </header>
            <main>
                <div class="container">
                    <h2><?= htmlspecialchars($row["title"]) ?></h2>
                    <div class="slider">
                        <div class="slider-images" id="sliderImages">
                            <?php foreach ($imageList as $img): ?>
                                <?php if (trim($img) !== ''): ?>
                                    <img src="/homzey/images/<?= htmlspecialchars(trim($img)) ?>" alt="House Image" />
                                <?php endif; ?>
                            <?php endforeach; ?>
                        </div>

                        <div class="slider-dots" id="sliderDots">
                            <?php foreach ($imageList as $i => $img): ?>
                                <span class="slider-dot" onclick="goToSlide(<?= $i ?>)"></span>
                            <?php endforeach; ?>
                        </div>
                    </div>

                    <p><strong>Description:</strong> <?= nl2br(htmlspecialchars($row["description"])) ?></p>
                    <p><strong>Location:</strong> <?= htmlspecialchars($row["location"]) ?></p>
                    <p><strong>Price:</strong> Rs<?= htmlspecialchars($row["price"]) ?> / month</p>
                    <p><strong>Number of Rooms:</strong> <?= htmlspecialchars($row["rooms"]) ?></p>
                    <p><strong>Number of Kitchens:</strong> <?= htmlspecialchars($row["kitchens"]) ?></p>
                    <p><strong>Number of Bathrooms:</strong> <?= htmlspecialchars($row["bathrooms"]) ?></p>
                    <p><strong>BHK:</strong> <?= htmlspecialchars($row["bhk"]) ?></p>

                    <?php if ($row["status"] === "booked"): ?>
                        <button class="btn-primary btn-disabled" disabled>This property is already booked</button>
                    <?php else: ?>
                        <a href="../Tenants/booking.php?id=<?= $row["id"] ?>" class="btn-primary">Rent Now</a>
                    <?php endif; ?>
                    <a href="browse.php" class="btn-secondary">Back to Browse</a>
                    <a href="../add_review.php?id=<?= $row['id'] ?>" class="btn-secondary">Add Review</a>
                </div>
            </main>

            <script>
                const sliderImages = document.getElementById('sliderImages');
                const dots = document.querySelectorAll('.slider-dot');
                let currentSlide = 0;

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
