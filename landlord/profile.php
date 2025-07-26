<?php
session_start();
include '../Main/db.php';

if (!isset($_SESSION['user_id'])) {
    header('Location: ../login.php');
    exit();
}

$user_id = $_SESSION['user_id'];
$message = '';

// Handle form update
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = $_POST['name'];

    if (isset($_FILES['profile_picture']) && $_FILES['profile_picture']['error'] === 0) {
        $imageName = uniqid() . '_' . basename($_FILES['profile_picture']['name']);
        $uploadPath = '../image/' . $imageName;

        if (move_uploaded_file($_FILES['profile_picture']['tmp_name'], $uploadPath)) {
            $stmt = $conn->prepare("UPDATE users SET name = ?, profile_picture = ? WHERE id = ?");
            $stmt->bind_param("ssi", $name, $imageName, $user_id);
        } else {
            $message = "Failed to upload image.";
        }
    } else {
        $stmt = $conn->prepare("UPDATE users SET name = ? WHERE id = ?");
        $stmt->bind_param("si", $name, $user_id);
    }

    if (isset($stmt) && $stmt->execute()) {
        $message = "Profile updated successfully.";
    }
}

// Fetch user info
$stmt = $conn->prepare("SELECT name, email, profile_picture FROM users WHERE id = ?");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result();
$user = $result->fetch_assoc();
$stmt->close();

$name = $user['name'] ?? '';
$email = $user['email'] ?? 'Email not found';
$profile_pic = !empty($user['profile_picture']) ? '../image/' . $user['profile_picture'] : '../image/default_profile.png';
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Profile Settings</title>
     <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" />
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-gray-100 min-h-screen flex">
    <?php include 'sidebar.php'; ?>
    <main class="flex-grow p-8">
        <div class="max-w-xl mx-auto bg-white p-6 rounded shadow-md">
            <h2 class="text-2xl font-bold mb-6 text-center">Profile Settings</h2>

            <?php if ($message): ?>
                <div class="mb-4 text-center text-green-600 font-semibold"><?= htmlspecialchars($message) ?></div>
            <?php endif; ?>

            <form action="" method="POST" enctype="multipart/form-data">
                <div class="mb-4 text-center">
                    <img src="<?= htmlspecialchars($profile_pic) ?>" class="w-24 h-24 rounded-full object-cover mx-auto" alt="Profile">
                </div>

                <div class="mb-4">
                    <label class="block mb-1 font-medium">Full Name</label>
                    <input type="text" name="name" value="<?= htmlspecialchars($name) ?>" class="w-full border px-3 py-2 rounded" required>
                </div>

                <div class="mb-4">
                    <label class="block mb-1 font-medium">Email</label>
                    <input type="email" value="<?= htmlspecialchars($email) ?>" class="w-full border px-3 py-2 rounded bg-gray-100" readonly>
                </div>

                <div class="mb-4">
                    <label class="block mb-1 font-medium">Change Profile Picture</label>
                    <input type="file" name="profile_picture" class="w-full">
                </div>

                <button type="submit" class="w-full bg-indigo-600 text-white py-2 rounded hover:bg-indigo-700">Update Profile</button>
            </form>
        </div>
    </main>
</body>
</html>
