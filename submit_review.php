<?php
require '../db.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = trim($_POST['name']);
    $email = trim($_POST['email']);
    $rating = (int)$_POST['rating'];
    $comment = trim($_POST['comment']);

    if ($name && $rating && $comment) {
        $stmt = $conn->prepare("INSERT INTO reviews (name, email, rating, comment) VALUES (?, ?, ?, ?)");
        $stmt->bind_param("ssis", $name, $email, $rating, $comment);
        $stmt->execute();
        $stmt->close();
        header("Location: view_reviews.php?success=1");
        exit;
    } else {
        header("Location: add_review.php?error=1");
        exit;
    }
}
?>
