<?php
// reviews.php
// Make sure $conn, $house_id, and session are already available when included

$user_id = $_SESSION['user_id'] ?? null;
$user_role = $_SESSION['user_role'] ?? null;
$user_name = $_SESSION['user_name'] ?? '';

// Handle review submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['review_submit']) && $user_id && $user_role === 'tenant') {
    $rating = intval($_POST['rating'] ?? 0);
    $comment = trim($_POST['comment'] ?? '');

    if ($rating >= 1 && $rating <= 5) {
        // Check for duplicate review
        $check_sql = "SELECT id FROM reviews WHERE house_id = ? AND tenant_id = ?";
        $stmt = mysqli_prepare($conn, $check_sql);
        mysqli_stmt_bind_param($stmt, "ii", $house_id, $user_id);
        mysqli_stmt_execute($stmt);
        mysqli_stmt_store_result($stmt);
        $has_review = mysqli_stmt_num_rows($stmt) > 0;
        mysqli_stmt_close($stmt);

        if (!$has_review) {
            $insert_sql = "INSERT INTO reviews (house_id, tenant_id, rating, comment) VALUES (?, ?, ?, ?)";
            $stmt = mysqli_prepare($conn, $insert_sql);
            mysqli_stmt_bind_param($stmt, "iiis", $house_id, $user_id, $rating, $comment);
            mysqli_stmt_execute($stmt);
            mysqli_stmt_close($stmt);
            echo "<p>Thank you for your review, " . htmlspecialchars($user_name) . "!</p>";
        } else {
            echo "<p>You have already reviewed this house.</p>";
        }
    } else {
        echo "<p>Please provide a valid rating between 1 and 5.</p>";
    }
}

// Fetch existing reviews
$reviews_sql = "SELECT r.rating, r.comment, r.created_at, u.name AS tenant_name 
                FROM reviews r 
                JOIN users u ON r.tenant_id = u.id 
                WHERE r.house_id = ? 
                ORDER BY r.created_at DESC";
$stmt = mysqli_prepare($conn, $reviews_sql);
mysqli_stmt_bind_param($stmt, "i", $house_id);
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);
?>

<div class="reviews-section">
    <h3>Reviews</h3>

    <?php if ($user_id && $user_role === 'tenant'): ?>
    <form method="post" class="review-form" aria-label="Add a review">
        <label for="rating">Rating:</label>
        <select name="rating" id="rating" required>
            <option value="">Select rating</option>
            <?php for ($i = 5; $i >= 1; $i--): ?>
                <option value="<?= $i ?>"><?= $i ?> star<?= $i > 1 ? 's' : '' ?></option>
            <?php endfor; ?>
        </select>

        <label for="comment">Comment (optional):</label>
        <textarea name="comment" id="comment" rows="4" placeholder="Write your review here..."></textarea>

        <button type="submit" name="review_submit">Submit Review</button>
    </form>
    <?php else: ?>
        <p><a href="login.php">Log in</a> as a tenant to add a review.</p>
    <?php endif; ?>

    <div class="reviews-list" aria-live="polite">
        <?php
        if (mysqli_num_rows($result) > 0) {
            while ($row = mysqli_fetch_assoc($result)) {
                $stars = str_repeat('★', $row['rating']) . str_repeat('☆', 5 - $row['rating']);
                $comment = htmlspecialchars($row['comment']);
                $tenant_name = htmlspecialchars($row['tenant_name']);
                $date = date("F j, Y", strtotime($row['created_at']));

                echo "<div class='review'>";
                echo "<p class='review-rating' aria-label='{$row['rating']} stars'>{$stars}</p>";
                echo "<p class='review-comment'>" . nl2br($comment) . "</p>";
                echo "<p class='review-author'>By <strong>{$tenant_name}</strong> on {$date}</p>";
                echo "</div>";
            }
        } else {
            echo "<p>No reviews yet. Be the first to review this house!</p>";
        }
        ?>
    </div>
</div>
