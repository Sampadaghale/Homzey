<?php 
session_start();
require 'db.php';

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
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" />
    <style>
        /* Your CSS from the code you provided, fully included */
        :root {
            --primary-color: #4a6fa5;
            --secondary-color: #166088;
            --accent-color: #4fc3f7;
            --background-color: #f5f7fa;
            --card-color: #ffffff;
            --text-dark: #333333;
            --text-light: #666666;
            --success-color: #43a047;
            --warning-color: #ff9800;
            --danger-color: #e53935;
        }
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        }
        body {
            background-color: var(--background-color);
            color: var(--text-dark);
            line-height: 1.6;
        }
        .dashboard {
            display: grid;
            grid-template-columns: 250px 1fr;
            min-height: 100vh;
        }
        .sidebar {
            background-color: var(--primary-color);
            color: white;
            padding: 2rem 1rem;
            position: relative;
        }
        .sidebar-header {
            display: flex;
            align-items: center;
            margin-bottom: 2rem;
            padding-left: 0.5rem;
        }
        .sidebar-header h2 {
            font-size: 1.2rem;
        }
        .nav-menu {
            list-style: none;
        }
        .nav-menu li {
            margin-bottom: 1rem;
        }
        .nav-menu li a {
            color: white;
            text-decoration: none;
            display: block;
            padding: 0.5rem;
            border-radius: 4px;
            transition: background-color 0.3s;
        }
        .nav-menu li a:hover,
        .nav-menu li a.active {
            background-color: rgba(255, 255, 255, 0.2);
        }
        .nav-menu li a i {
            margin-right: 0.5rem;
            width: 20px;
            text-align: center;
        }
        .main-content {
            padding: 2rem;
        }
        .header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 2rem;
        }
        .header h1 {
            font-size: 1.8rem;
            color: var(--primary-color);
        }
        .notification-bell {
            position: relative;
            font-size: 1.2rem;
            color: var(--text-light);
            cursor: pointer;
        }
        .notification-badge {
            position: absolute;
            top: -5px;
            right: -5px;
            background-color: var(--danger-color);
            color: white;
            border-radius: 50%;
            width: 18px;
            height: 18px;
            font-size: 0.7rem;
            display: flex;
            align-items: center;
            justify-content: center;
        }
        .cards-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
            gap: 1.5rem;
            margin-bottom: 2rem;
        }
        .card {
            background-color: var(--card-color);
            border-radius: 8px;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
            padding: 1.5rem;
        }
        .card-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 1rem;
        }
        .card-title {
            font-size: 1.2rem;
            font-weight: 600;
        }
        .card-icon {
            width: 40px;
            height: 40px;
            border-radius: 50%;
            background-color: var(--accent-color);
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
        }
        .property-image {
            width: 100%;
            height: 180px;
            border-radius: 8px;
            margin-bottom: 1rem;
            object-fit: cover;
        }
        .detail-row {
            display: flex;
            justify-content: space-between;
            margin-bottom: 0.8rem;
        }
        .detail-label {
            color: var(--text-light);
        }
        .detail-value {
            font-weight: 500;
        }
        .payment-status {
            display: inline-block;
            padding: 0.3rem 0.8rem;
            border-radius: 20px;
            font-size: 0.8rem;
            font-weight: 500;
        }
        .status-paid {
            background-color: #e8f5e9;
            color: var(--success-color);
        }
        .status-due {
            background-color: #fff3e0;
            color: var(--warning-color);
        }
        .status-overdue {
            background-color: #ffebee;
            color: var(--danger-color);
        }
        .btn {
            display: inline-block;
            padding: 0.6rem 1.2rem;
            border-radius: 4px;
            background-color: var(--primary-color);
            color: white;
            text-decoration: none;
            border: none;
            cursor: pointer;
            font-size: 0.9rem;
            transition: background-color 0.3s;
        }
        .btn:hover {
            background-color: var(--secondary-color);
        }
        .btn-secondary {
            background-color: #e0e0e0;
            color: var(--text-dark);
        }
        .btn-secondary:hover {
            background-color: #d0d0d0;
        }
        .table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 1rem;
        }
        .table th, .table td {
            padding: 0.8rem;
            text-align: left;
            border-bottom: 1px solid #e0e0e0;
        }
        .table th {
            color: var(--text-light);
            font-weight: 500;
        }
        .badge {
            display: inline-block;
            padding: 0.2rem 0.6rem;
            border-radius: 20px;
            font-size: 0.7rem;
            font-weight: 500;
        }
        .badge-pending {
            background-color: #fff3e0;
            color: var(--warning-color);
        }
        .badge-in-progress {
            background-color: #e3f2fd;
            color: var(--secondary-color);
        }
        .badge-completed {
            background-color: #e8f5e9;
            color: var(--success-color);
        }
        .notification-item {
            display: flex;
            align-items: flex-start;
            padding: 0.8rem 0;
            border-bottom: 1px solid #e0e0e0;
        }
        .notification-icon {
            margin-right: 1rem;
            font-size: 1.2rem;
            color: var(--accent-color);
        }
        .notification-content {
            flex-grow: 1;
        }
        .notification-time {
            font-size: 0.8rem;
            color: var(--text-light);
        }
        .notification-unread {
            background-color: #f5f5f5;
            border-left: 3px solid var(--accent-color);
            padding-left: 5px;
        }
        @media (max-width: 768px) {
            .dashboard {
                grid-template-columns: 1fr;
            }
            .sidebar {
                display: none;
            }
            .main-content {
                padding: 1rem;
            }
            .cards-grid {
                grid-template-columns: 1fr;
            }
            .mobile-menu-btn {
                display: block;
            }
            .sidebar.mobile-visible {
                display: block;
                position: fixed;
                top: 0;
                left: 0;
                width: 250px;
                height: 100%;
                z-index: 1000;
                background-color: var(--primary-color);
                padding: 2rem 1rem;
                color: white;
            }
        }
        .mobile-menu-btn {
            display: none;
            background: none;
            border: none;
            font-size: 1.5rem;
            color: var(--primary-color);
            cursor: pointer;
        }
    </style>
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
            <li><a href="#"><i class="fas fa-tools"></i> Maintenance</a></li>
            <li><a href="#"><i class="fas fa-envelope"></i> Messages</a></li>
            <li><a href="#"><i class="fas fa-file-signature"></i> Lease Documents</a></li>
            <li><a href="#"><i class="fas fa-cog"></i> Settings</a></li>
            <li><a href="index.php"><i class="fas fa-sign-out-alt"></i> Home</a></li>
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
                    <img src="<?= htmlspecialchars($house['image']) ?>" alt="Property image" class="property-image" />
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
