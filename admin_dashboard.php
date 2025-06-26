<?php
session_start();
if (!isset($_SESSION['user_role']) || $_SESSION['user_role'] !== 'admin') {
    header('login.php');
    echo   "login gar";
    exit();
}

$conn = new mysqli("localhost:3309", "root", "", "houserent");
if ($conn->connect_error) die("DB Connection failed: " . $conn->connect_error);

//Counting all the details 
$totalHouses = $conn->query("SELECT COUNT(*) FROM houses")->fetch_row()[0];
$bookedHouses = $conn->query("SELECT COUNT(*) FROM houses WHERE status IN ('booked', 'sold')")->fetch_row()[0];
$availableHouses = $conn->query("SELECT COUNT(*) FROM houses WHERE status = 'available' AND is_approved = 1")->fetch_row()[0];
$pendingHouses = $conn->query("SELECT COUNT(*) FROM houses WHERE is_approved = 0")->fetch_row()[0];

$totalUsers = $conn->query("SELECT COUNT(*) FROM users WHERE role != 'admin' AND role!='superadmin' ")->fetch_row()[0];
$blockedUsers = $conn->query("SELECT COUNT(*) FROM users WHERE status = 'blocked'")->fetch_row()[0];

$totalBookings = $conn->query("SELECT COUNT(*) FROM bookings")->fetch_row()[0];
$pendingBookings = $conn->query("SELECT COUNT(*) FROM bookings WHERE status = 'pending'")->fetch_row()[0];

//aaprove and reject system
if (isset($_GET['house_action'], $_GET['house_id'])) {
    $house_id = intval($_GET['house_id']);
    $action = ($_GET['house_action'] === 'approve') ? 1 : 0;
    $conn->query("UPDATE houses SET is_approved = $action WHERE id = $house_id");
    header("Location: " . strtok($_SERVER["REQUEST_URI"], '?'));
    exit();
}

//block and unblock
if (isset($_GET['user_action'], $_GET['user_id'])) {
    $user_id = intval($_GET['user_id']);
    if ($_GET['user_action'] === 'block') {
        $conn->query("UPDATE users SET status = 'blocked' WHERE id = $user_id");
    } elseif ($_GET['user_action'] === 'unblock') {
        $conn->query("UPDATE users SET status = 'active' WHERE id = $user_id");
    } elseif ($_GET['user_action'] === 'delete') {
        $conn->query("DELETE FROM users WHERE id = $user_id");
    }
    header("Location: " . strtok($_SERVER["REQUEST_URI"], '?'));
    exit();
}

//Fetch pending houses
$pendingHousesRes = $conn->query("
    SELECT h.*, u.name AS landlord_name 
    FROM houses h 
    JOIN users u ON h.landlord_id = u.id 
    WHERE h.is_approved = 0
    ORDER BY h.created_at DESC
");

// All house show
$allHousesRes = $conn->query("
    SELECT h.*, u.name AS landlord_name 
    FROM houses h 
    JOIN users u ON h.landlord_id = u.id 
    ORDER BY h.created_at DESC
");

//Fetch users
$usersRes = $conn->query("SELECT * FROM users WHERE role != 'admin' AND role != 'superadmin' ORDER BY id DESC");

//Bookings
$bookingsRes = $conn->query("
    SELECT b.*, u.name AS tenant_name, u.phone AS tenant_phone, 
           h.title AS house_title, h.location AS house_location
    FROM bookings b
    JOIN users u ON b.tenant_id = u.id
    JOIN houses h ON b.house_id = h.id
    ORDER BY b.created_at DESC
");
?>

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8" />
<meta name="viewport" content="width=device-width, initial-scale=1" />
<title>Admin Dashboard - House Rental System</title>

<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet" />
<style>
    body { background: #f8f9fa; }
    .summary-card {
        border-radius: 10px;
        box-shadow: 0 3px 6px rgba(0,0,0,0.1);
        padding: 20px;
        color: #fff;
        text-align: center;
        margin-bottom: 1rem;
    }
    .summary-total { background-color: #0d6efd; }
    .summary-booked { background-color: #dc3545; }
    .summary-available { background-color: #198754; }
    .summary-pending { background-color: #ffc107; color: #212529; }
    .summary-users { background-color: #6610f2; }
    .summary-blocked { background-color: #6c757d; }
    .summary-bookings { background-color: #20c997; }
    .summary-pending-bookings { background-color: #fd7e14; }

    .house-image {
        width: 120px;
        height: 80px;
        object-fit: cover;
        border-radius: 6px;
    }
    .table-wrapper { margin-top: 20px; }
</style>
</head>
<body>
<div class="container my-4">
    
        <!-- hellp adminn  -->

        <div class="d-flex justify-content-between align-items-center p-3 mb-4 bg-light rounded shadow-sm">
    <!-- Left: Logo -->
    <div class="d-flex align-items-center">
        <a href="index.php"><img src="image/house.png" alt="Logo" height="50" class="me-3"></a>
    </div>

    <!-- Center: Greeting and Title -->
    <div class="text-center flex-grow-1">
        <h1 class="mb-1">👋 Hello, <?php echo htmlspecialchars($_SESSION['user_name']); ?></h1>
        <h5 class="text-muted">Admin Dashboard</h5>
    </div>

    <!-- Right: Logout -->
    <div>
        <a href="logout.php" class="btn btn-danger">Logout</a>
        
    </div>
    
</div>


    <!-- Summary cards -->
    <div class="row g-3 mb-4">
        <div class="col-md-3"><div class="summary-card summary-total"><h3><?= $totalHouses ?></h3><p>Total Houses</p></div></div>
        <div class="col-md-3"><div class="summary-card summary-booked"><h3><?= $bookedHouses ?></h3><p>Houses Booked/Sold</p></div></div>
        <div class="col-md-3"><div class="summary-card summary-available"><h3><?= $availableHouses ?></h3><p>Available Houses</p></div></div>
        <div class="col-md-3"><div class="summary-card summary-pending"><h3><?= $pendingHouses ?></h3><p>Pending Approval</p></div></div>

        <div class="col-md-3"><div class="summary-card summary-users"><h3><?= $totalUsers ?></h3><p>Total Users</p></div></div>
        <div class="col-md-3"><div class="summary-card summary-blocked"><h3><?= $blockedUsers ?></h3><p>Blocked Users</p></div></div>
        <div class="col-md-3"><div class="summary-card summary-bookings"><h3><?= $totalBookings ?></h3><p>Total Bookings</p></div></div>
        <div class="col-md-3"><div class="summary-card summary-pending-bookings"><h3><?= $pendingBookings ?></h3><p>Pending Bookings</p></div></div>
    </div>

    <!-- Tabs Navigation -->
    <ul class="nav nav-tabs" id="adminTabs" role="tablist">
        <li class="nav-item" role="presentation">
            <button class="nav-link active" id="houses-tab" data-bs-toggle="tab" data-bs-target="#houses" type="button" role="tab">Manage Houses</button>
        </li>
        <li class="nav-item" role="presentation">
            <button class="nav-link" id="users-tab" data-bs-toggle="tab" data-bs-target="#users" type="button" role="tab">Manage Users</button>
        </li>
        <li class="nav-item" role="presentation">
            <button class="nav-link" id="bookings-tab" data-bs-toggle="tab" data-bs-target="#bookings" type="button" role="tab">Bookings</button>
        </li>
    </ul>

    <!-- Tabs Content -->
    <div class="tab-content" id="adminTabsContent">

        <!-- Manage Houses Tab -->
        <div class="tab-pane fade show active" id="houses" role="tabpanel" aria-labelledby="houses-tab">
            <div class="table-wrapper mt-3">
                <h3>Houses Pending Approval</h3>
                <?php if ($pendingHousesRes && $pendingHousesRes->num_rows > 0): ?>
                    <table class="table table-striped table-bordered align-middle shadow-sm">
                        <thead class="table-dark">
                            <tr>
                                <th>ID</th>
                                <th>Landlord</th>
                                <th>Title</th>
                                <th>Description</th>
                                <th>Location</th>
                                <th>Price (Rs.)</th>
                                <th>Image</th>
                                <th>Status</th>
                                <th>Created At</th>
                                <th>Action</th>
                                
                            </tr>
                        </thead>
                        <tbody>
                            <?php while ($row = $pendingHousesRes->fetch_assoc()): ?>
                            <tr>
                                <td><?= htmlspecialchars($row['id']) ?></td>
                                <td><?= htmlspecialchars($row['landlord_name']) ?></td>
                                <td><?= htmlspecialchars($row['title']) ?></td>
                                <td><?= nl2br(htmlspecialchars(substr($row['description'], 0, 100))) ?>...</td>
                                <td><?= htmlspecialchars($row['location']) ?></td>
                                <td><?= number_format($row['price'], 2) ?></td>
                                <td>
                                    <?php if ($row['image'] && file_exists($row['image'])): ?>
                                        <img src="<?= htmlspecialchars($row['image']) ?>" alt="House Image" class="house-image" />
                                    <?php else: ?>
                                        <span class="text-muted">No Image</span>
                                    <?php endif; ?>
                                </td>
                                <td><span class="badge bg-warning">Pending</span></td>
                                <td><?= date('d M Y, H:i', strtotime($row['created_at'])) ?></td>
                                <td>
                                    <a href="?house_action=approve&house_id=<?= $row['id'] ?>" class="btn btn-success btn-sm" onclick="return confirm('Approve this house?')">Approve</a>
                                    <a href="?house_action=reject&house_id=<?= $row['id'] ?>" class="btn btn-danger btn-sm" onclick="return confirm('Reject this house?')">Reject</a>
                                </td>
                            </tr>
                            <?php endwhile; ?>
                        </tbody>
                    </table>
                <?php else: ?>
                    <div class="alert alert-info">No houses pending approval.</div>
                <?php endif; ?>

                <hr class="my-4">

                <h3>All Houses</h3>
                <?php if ($allHousesRes && $allHousesRes->num_rows > 0): ?>
                    <table class="table table-striped table-bordered align-middle shadow-sm">
                        <thead class="table-secondary">
                            <tr>
                                <th>ID</th>
                                <th>Landlord</th>
                                <th>Title</th>
                                <th>Description</th>
                                <th>Location</th>
                                <th>Price (Rs.)</th>
                                <th>Image</th>
                                <th>Approved</th>
                                <th>Status</th>
                                <th>Created At</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php while ($row = $allHousesRes->fetch_assoc()): ?>
                            <tr>
                                <td><?= htmlspecialchars($row['id']) ?></td>
                                <td><?= htmlspecialchars($row['landlord_name']) ?></td>
                                <td><?= htmlspecialchars($row['title']) ?></td>
                                <td><?= nl2br(htmlspecialchars(substr($row['description'], 0, 100))) ?>...</td>
                                <td><?= htmlspecialchars($row['location']) ?></td>
                                <td><?= number_format($row['price'], 2) ?></td>
                                <td>
                                    <?php if ($row['image'] && file_exists($row['image'])): ?>
                                        <img src="<?= htmlspecialchars($row['image']) ?>" alt="House Image" class="house-image" />
                                    <?php else: ?>
                                        <span class="text-muted">No Image</span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <?php if ($row['is_approved'] == 1): ?>
                                        <span class="badge bg-success">Approved</span>
                                    <?php else: ?>
                                        <span class="badge bg-secondary">Not Approved</span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <?php
                                        $status = $row['status'];
                                        $badgeClass = 'secondary';
                                        if ($status === 'available') $badgeClass = 'success';
                                        elseif ($status === 'pending') $badgeClass = 'warning';
                                        elseif ($status === 'booked' || $status === 'sold') $badgeClass = 'info';
                                        elseif ($status === 'blocked') $badgeClass = 'danger';
                                    ?>
                                    <span class="badge bg-<?= $badgeClass ?>"><?= htmlspecialchars(ucfirst($status)) ?></span>
                                </td>
                                <td><?= date('d M Y, H:i', strtotime($row['created_at'])) ?></td>
                            </tr>
                            <?php endwhile; ?>
                        </tbody>
                    </table>
                <?php else: ?>
                    <div class="alert alert-info">No houses found.</div>
                <?php endif; ?>
            </div>
        </div>

        <!-- Manage Users Tab -->
        <div class="tab-pane fade" id="users" role="tabpanel" aria-labelledby="users-tab">
            <div class="table-wrapper mt-3">
                <h3>Users</h3>
                <?php if ($usersRes && $usersRes->num_rows > 0): ?>
                <table class="table table-striped table-bordered align-middle shadow-sm">
                    <thead class="table-dark">
                        <tr>
                            <th>ID</th>
                            <th>Name</th>
                            <th>Email</th>
                            <th>Phone</th>
                            <th>Role</th>
                            <th>Status</th>
                            <th>Action</th>
                        </tr>
                    </thead>
                    <tbody>
                    <?php while ($row = $usersRes->fetch_assoc()): ?>
                        <tr>
                            <td><?= htmlspecialchars($row['id']) ?></td>
                            <td><?= htmlspecialchars($row['name']) ?></td>
                            <td><?= htmlspecialchars($row['email']) ?></td>
                            <td><?= htmlspecialchars($row['phone']) ?></td>
                            <td><?= htmlspecialchars(ucfirst($row['role'])) ?></td>
                            <td>
                                <?php if ($row['status'] === 'active'): ?>
                                    <span class="badge bg-success">Active</span>
                                <?php else: ?>
                                    <span class="badge bg-danger">Blocked</span>
                                <?php endif; ?>
                            </td>
                            <td>
                                <?php if ($row['status'] === 'active'): ?>
                                    <a href="?user_action=block&user_id=<?= $row['id'] ?>" class="btn btn-warning btn-sm" onclick="return confirm('Block this user?')">Block</a>
                                <?php else: ?>
                                    <a href="?user_action=unblock&user_id=<?= $row['id'] ?>" class="btn btn-success btn-sm" onclick="return confirm('Unblock this user?')">Unblock</a>
                                <?php endif; ?>
                                <a href="?user_action=delete&user_id=<?= $row['id'] ?>" class="btn btn-danger btn-sm" onclick="return confirm('Delete this user? This action is irreversible.')">Delete</a>
                            </td>
                        </tr>
                    <?php endwhile; ?>
                    </tbody>
                </table>
                <?php else: ?>
                    <div class="alert alert-info">No users found.</div>
                <?php endif; ?>
            </div>
        </div>

        <!-- Bookings Tab -->
        <div class="tab-pane fade" id="bookings" role="tabpanel" aria-labelledby="bookings-tab">
            <div class="table-wrapper mt-3">
                <h3>Bookings</h3>
                <?php if ($bookingsRes && $bookingsRes->num_rows > 0): ?>
                <table class="table table-striped table-bordered align-middle shadow-sm">
                    <thead class="table-dark">
                        <tr>
                            <th>ID</th>
                            <th>Tenant Name</th>
                            <th>Phone</th>
                            <th>House Title</th>
                            <th>Location</th>
                            <th>Booking Dates</th>
                            <th>Total Price (Rs.)</th>
                            <th>Commission (Rs.)</th>
                            <th>Status</th>
                            <th>Booked At</th>
                        </tr>
                    </thead>
                    <tbody>
                    <?php while ($row = $bookingsRes->fetch_assoc()): ?>
                        <tr>
                            <td><?= htmlspecialchars($row['id']) ?></td>
                            <td><?= htmlspecialchars($row['tenant_name']) ?></td>
                            <td><?= htmlspecialchars($row['tenant_phone']) ?></td>
                            <td><?= htmlspecialchars($row['house_title']) ?></td>
                            <td><?= htmlspecialchars($row['house_location']) ?></td>
                            <td>
                                <?= date('d M Y', strtotime($row['start_date'])) ?> - <?= date('d M Y', strtotime($row['end_date'])) ?>
                            </td>
                            <td><?= number_format($row['total_price'], 2) ?></td>
                            <td><?= number_format($row['commission'], 2) ?></td>
                            <td>
                                <?php
                                $status = strtolower($row['status']);
                                $badgeClass = 'secondary';
                                if ($status === 'pending') $badgeClass = 'warning';
                                elseif ($status === 'confirmed') $badgeClass = 'success';
                                elseif ($status === 'cancelled') $badgeClass = 'danger';
                                ?>
                                <span class="badge bg-<?= $badgeClass ?>"><?= ucfirst($status) ?></span>
                            </td>
                            <td><?= date('d M Y, H:i', strtotime($row['created_at'])) ?></td>
                        </tr>
                    <?php endwhile; ?>
                    </tbody>
                </table>
                <?php else: ?>
                    <div class="alert alert-info">No bookings found.</div>
                <?php endif; ?>
            </div>
        </div>

    </div>

</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
