<?php 
session_start();
require '../Main/db.php';

if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] !== 'tenant') {
    header('Location: login.php');
    exit();
}

$tenant_id = $_SESSION['user_id'];

// Fetch tenant name
$stmt = $conn->prepare("SELECT name FROM users WHERE id = ?");
$stmt->bind_param("i", $tenant_id);
$stmt->execute();
$result = $stmt->get_result();
$user = $result->fetch_assoc();
$tenant_name = $user['name'] ?? 'Tenant';
$stmt->close();

// Fetch tenant's current booked house info (latest confirmed booking)
$stmt = $conn->prepare("
    SELECT h.*, b.start_date, b.end_date, u.name AS landlord_name 
    FROM bookings b 
    JOIN houses h ON b.house_id = h.id
    JOIN users u ON h.landlord_id = u.id
    WHERE b.tenant_id = ? AND b.status = 'confirmed'
    ORDER BY b.start_date DESC
    LIMIT 1
");
$stmt->bind_param("i", $tenant_id);
$stmt->execute();
$house_result = $stmt->get_result();
$house = $house_result->fetch_assoc();
$stmt->close();

// Fetch maintenance requests for tenant
$stmt = $conn->prepare("
    SELECT * FROM maintenance_requests 
    WHERE tenant_id = ? 
    ORDER BY created_at DESC 
    LIMIT 5
");
$stmt->bind_param("i", $tenant_id);
$stmt->execute();
$maintenance_result = $stmt->get_result();
$maintenance_requests = $maintenance_result->fetch_all(MYSQLI_ASSOC);
$stmt->close();

// Fetch notifications for tenant, unread first then recent
$stmt = $conn->prepare("
    SELECT * FROM notifications 
    WHERE user_id = ? 
    ORDER BY is_read ASC, created_at DESC
    LIMIT 20
");
$stmt->bind_param("i", $tenant_id);
$stmt->execute();
$notifications_result = $stmt->get_result();
$notifications = $notifications_result->fetch_all(MYSQLI_ASSOC);
$stmt->close();

?>

<!DOCTYPE html> 
<html lang="en">
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1" />
    <title>Tenant Dashboard</title>
    <link rel="stylesheet" href="tenant_dashboard.css" />
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" />
</head>
<body>
<div class="dashboard">
    <div class="sidebar">
        <div class="sidebar-header">
            <h2><?= htmlspecialchars($tenant_name) ?></h2>
        </div>
        <ul class="nav-menu">
            <li><a href="#" class="active"><i class="fas fa-home"></i> Dashboard</a></li>
            <li><a href="rent_payments.php"><i class="fas fa-file-invoice-dollar"></i> Rent Payments</a></li>
            <li><a href="maintenance.php"><i class="fas fa-tools"></i> Maintenance</a></li>
            <li><a href="../Main/index.php"><i class="fas fa-home"></i> Home</a></li>
        </ul>
    </div>

    <div class="main-content">
        <div class="header">
            <button class="mobile-menu-btn" aria-label="Toggle menu"><i class="fas fa-bars"></i></button>
            <h1>Dashboard</h1>
            <div class="notification-bell" title="Notifications">
                <i class="fas fa-bell"></i>
                <span class="notification-badge"><?= count(array_filter($notifications, fn($n) => $n['status'] === 'unread')) ?></span>
            </div>
        </div>

        <div class="cards-grid">
            <!-- Property Card -->
            <div class="card">
                <div class="card-header">
                    <h2 class="card-title">My Property</h2>
                    <div class="card-icon"><i class="fas fa-home"></i></div>
                </div>
                <?php if ($house): ?>
                    <img src="<?= htmlspecialchars($house['image']) ?>" alt="images" class="property-image" />
                    <div class="detail-row"><span class="detail-label">Address:</span> <span class="detail-value"><?= htmlspecialchars($house['location']) ?></span></div>
                    <div class="detail-row"><span class="detail-label">Landlord:</span> <span class="detail-value"><?= htmlspecialchars($house['landlord_name']) ?></span></div>
                    <div class="detail-row"><span class="detail-label">Lease End:</span> <span class="detail-value"><?= date('F j, Y', strtotime($house['end_date'] ?? $house['lease_end_date'] ?? '')) ?></span></div>
                    <button class="btn">View Lease Details</button>
                <?php else: ?>
                    <p>No property rented currently.</p>
                <?php endif; ?>
            </div>

            <!-- Rent Payment Card -->
            <div class="card">
                <div class="card-header">
                    <h2 class="card-title">Rent Payment</h2>
                    <div class="card-icon"><i class="fas fa-file-invoice-dollar"></i></div>
                </div>
                <?php if (isset($payment) && $payment): 
                    $status_class = match(strtolower($payment['status'] ?? '' )) {
                        'paid' => 'status-paid',
                        'overdue' => 'status-overdue',
                        default => 'status-due',
                    };
                ?>
                    <div class="detail-row"><span class="detail-label">Amount Due:</span> <span class="detail-value">$<?= number_format($payment['amount_due'], 2) ?></span></div>
                    <div class="detail-row"><span class="detail-label">Due Date:</span> <span class="detail-value"><?= date('F j, Y', strtotime($payment['due_date'])) ?></span></div>
                    <div class="detail-row"><span class="detail-label">Status:</span> <span class="detail-value payment-status <?= $status_class ?>"><?= ucfirst($payment['status']) ?></span></div>
                    <button class="btn" onclick="alert('Redirecting to payment gateway...')">Pay Rent</button>
                <?php else: ?>
                    <p>No rent payment info available.</p>
                <?php endif; ?>
            </div>

            <!-- Maintenance Card -->
            <div class="card">
                <div class="card-header">
                    <h2 class="card-title">Maintenance</h2>
                    <div class="card-icon"><i class="fas fa-tools"></i></div>
                </div>
                <?php if (!empty($maintenance_requests)): 
                    $open_requests = count(array_filter($maintenance_requests, fn($r) => strtolower($r['status']) !== 'completed'));
                    $last_request = $maintenance_requests[0];
                    $last_status = strtolower($last_request['status']);
                    $badge_class = $last_status === 'completed' ? 'badge-completed' : ($last_status === 'in_progress' ? 'badge-in-progress' : 'badge-pending');
                ?>
                    <div class="detail-row"><span class="detail-label">Open Requests:</span> <span class="detail-value"><?= $open_requests ?></span></div>
                    <div class="detail-row"><span class="detail-label">Last Request:</span> <span class="detail-value"><?= date('M j', strtotime($last_request['created_at'] ?? $last_request['request_date'])) ?> (<?= htmlspecialchars($last_request['category']) ?>)</span></div>
                    <div class="detail-row"><span class="detail-label">Status:</span> <span class="detail-value"><span class="badge <?= $badge_class ?>"><?= ucfirst(str_replace('_', ' ', $last_status)) ?></span></span></div>
                    <button class="btn">View Requests</button>
                    <button class="btn btn-secondary" onclick="alert('Opening new maintenance request form...')">New Request</button>
                <?php else: ?>
                    <p>No maintenance requests found.</p>
                    <button class="btn btn-secondary" onclick="alert('Opening new maintenance request form...')">New Request</button>
                <?php endif; ?>
            </div>
        </div>

        <!-- Maintenance Requests Table -->
        <div class="card">
            <div class="card-header"><h2 class="card-title">Recent Maintenance Requests</h2></div>
            <?php if (!empty($maintenance_requests)): ?>
            <table class="table">
                <thead>
                    <tr><th>Date</th><th>Category</th><th>Description</th><th>Status</th><th>Action</th></tr>
                </thead>
                <tbody>
                    <?php foreach ($maintenance_requests as $request): 
                        $status = strtolower($request['status']);
                        $badge_class = $status === 'completed' ? 'badge-completed' : ($status === 'in_progress' ? 'badge-in-progress' : 'badge-pending');
                    ?>
                    <tr>
                        <td><?= date('M j, Y', strtotime($request['created_at'] ?? $request['request_date'])) ?></td>
                        <td><?= htmlspecialchars($request['category']) ?></td>
                        <td><?= htmlspecialchars($request['description']) ?></td>
                        <td><span class="badge <?= $badge_class ?>"><?= ucfirst(str_replace('_', ' ', $status)) ?></span></td>
                        <td><a href="#" style="color: var(--primary-color);">View</a></td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
            <?php else: ?>
                <p>No maintenance requests found.</p>
            <?php endif; ?>
        </div>

        <!-- Notifications -->
        <div class="card">
            <div class="card-header"><h2 class="card-title">Notifications</h2></div>
            <?php if (!empty($notifications)): ?>
                <?php foreach ($notifications as $note): ?>
                    <div class="notification-item <?= $note['status'] === 'unread' ? 'notification-unread' : '' ?>">
                        <div class="notification-icon"><i class="fas fa-bell"></i></div>
                        <div class="notification-content">
                            <p><?= htmlspecialchars($note['content']) ?></p>
                            <span class="notification-time"><?= date('M j, Y', strtotime($note['created_at'])) ?></span>
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php else: ?>
                <p>No notifications.</p>
            <?php endif; ?>
        </div>
    </div>
</div>

<script>
    // Mobile menu toggle
    document.querySelector('.mobile-menu-btn').addEventListener('click', function() {
        document.querySelector('.sidebar').classList.toggle('mobile-visible');
    });
</script>
</body>
</html>
