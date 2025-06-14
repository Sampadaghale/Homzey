<?php  
session_start();
include 'db.php';

// Check if tenant is logged in
if (!isset($_SESSION["user_id"]) || $_SESSION["user_role"] != "tenant") {
    header("Location: login.php");
    exit();
}

// Validate the house ID from URL
if (isset($_GET["id"]) && is_numeric($_GET["id"])) {
    $house_id = intval($_GET["id"]);

    // Use prepared statement to prevent SQL injection
    $stmt = $conn->prepare("SELECT * FROM houses WHERE id = ?");
    $stmt->bind_param("i", $house_id);
    $stmt->execute();
    $result = $stmt->get_result();

    // Check if house was found
    if ($result->num_rows === 1) {
        $row = $result->fetch_assoc();
        ?>
        <!DOCTYPE html>
        <html lang="en">
        <head>
            <meta charset="UTF-8" />
            <meta name="viewport" content="width=device-width, initial-scale=1.0" />
            <title>House Details</title>
            <link rel="stylesheet" href="styles.css" />
            <style>
                /* Reset common browser spacing */
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

                img {
                    max-width: 100%;
                    height: auto;
                    display: block;
                    margin-bottom: 1rem;
                    border-radius: 5px;
                }

                p {
                    margin-bottom: 1rem;
                    line-height: 1.5;
                    color: #333;
                }

                .btn-primary, .btn-secondary {
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

                .btn-primary:hover, .btn-secondary:hover {
                    background-color: #0056b3;
                }
            </style>
        </head>
        <body>
            <header>
                <!-- You can add a header here if needed -->
            </header>
            <main>
                <div class="container">
                    <h2><?php echo htmlspecialchars($row["title"]); ?></h2>
                    <img src="images/<?php echo htmlspecialchars($row["image"]); ?>" alt="<?php echo htmlspecialchars($row["title"]); ?>" />
                    <p><strong>Description:</strong> <?php echo nl2br(htmlspecialchars($row["description"])); ?></p>
                    <p><strong>Location:</strong> <?php echo htmlspecialchars($row["location"]); ?></p>
                    <p><strong>Price:</strong> $<?php echo htmlspecialchars($row["price"]); ?> / month</p>

                    <a href="booking.php?id=<?php echo $row["id"]; ?>" class="btn-primary">Rent Now</a>
                    <a href="browse.php" class="btn-secondary">Back to Browse</a>
                </div>
            </main>
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
