<?php

session_start();
require_once 'config/db.php';

// Redirect if not logged in
if (!isset($_SESSION['user_id'])) {
    header('Location: auth/login.php');
    exit();
}

$userId = $_SESSION['user_id'];

// --- Fetch Summary Stats ---
// Total Orders & Total Spent
$stmt = $conn->prepare("SELECT COUNT(*) as total_orders, SUM(total_amount) as total_spent FROM orders WHERE user_id = ?");
$stmt->bind_param("i", $userId);
$stmt->execute();
$result = $stmt->get_result();
$stats = $result->fetch_assoc();
$totalOrders = $stats['total_orders'] ?? 0;
$totalSpent = $stats['total_spent'] ?? 0;
$stmt->close();

// Last Order Date
$stmt = $conn->prepare("SELECT order_date FROM orders WHERE user_id = ? ORDER BY order_date DESC LIMIT 1");
$stmt->bind_param("i", $userId);
$stmt->execute();
$result = $stmt->get_result();
$lastOrder = $result->fetch_assoc();
$lastOrderDate = $lastOrder ? date('M d, Y', strtotime($lastOrder['order_date'])) : 'No orders yet';
$stmt->close();

// Account Type (simple logic: if user has > 5 orders -> 'Premium', else 'Regular')
$accountType = ($totalOrders > 5) ? 'Premium' : 'Regular';

// --- Fetch Recent Orders (last 5) ---
$stmt = $conn->prepare("SELECT id, total_amount, order_date, status FROM orders WHERE user_id = ? ORDER BY order_date DESC LIMIT 5");
$stmt->bind_param("i", $userId);
$stmt->execute();
$recentOrders = $stmt->get_result();
$stmt->close();

include 'partials/header.php';
?>

<div class="mb-4">
    <h1>Welcome, <?php echo htmlspecialchars($_SESSION['username']); ?>!</h1>
    <p class="text-muted">Here's an overview of your Grocify account.</p>
</div>

<!-- Summary Cards -->
<div class="row g-4 mb-5">
    <div class="col-sm-6 col-lg-3">
        <div class="card shadow-sm border-0 h-100">
            <div class="card-body">
                <div class="d-flex align-items-center">
                    <div class="flex-shrink-0 bg-primary bg-opacity-10 p-3 rounded">
                        <i class="bi bi-bag-check fs-1 text-primary"></i>
                    </div>
                    <div class="flex-grow-1 ms-3">
                        <h6 class="text-muted mb-1">Total Orders</h6>
                        <h3 class="mb-0"><?php echo $totalOrders; ?></h3>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <div class="col-sm-6 col-lg-3">
        <div class="card shadow-sm border-0 h-100">
            <div class="card-body">
                <div class="d-flex align-items-center">
                    <div class="flex-shrink-0 bg-success bg-opacity-10 p-3 rounded">
                        <i class="bi bi-currency-rupee fs-1 text-success"></i>
                    </div>
                    <div class="flex-grow-1 ms-3">
                        <h6 class="text-muted mb-1">Total Spent</h6>
                        <h3 class="mb-0">₹<?php echo number_format($totalSpent, 2); ?></h3>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <div class="col-sm-6 col-lg-3">
        <div class="card shadow-sm border-0 h-100">
            <div class="card-body">
                <div class="d-flex align-items-center">
                    <div class="flex-shrink-0 bg-warning bg-opacity-10 p-3 rounded">
                        <i class="bi bi-calendar-check fs-1 text-warning"></i>
                    </div>
                    <div class="flex-grow-1 ms-3">
                        <h6 class="text-muted mb-1">Last Order</h6>
                        <h3 class="mb-0"><?php echo $lastOrderDate; ?></h3>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <div class="col-sm-6 col-lg-3">
        <div class="card shadow-sm border-0 h-100">
            <div class="card-body">
                <div class="d-flex align-items-center">
                    <div class="flex-shrink-0 bg-info bg-opacity-10 p-3 rounded">
                        <i class="bi bi-person-badge fs-1 text-info"></i>
                    </div>
                    <div class="flex-grow-1 ms-3">
                        <h6 class="text-muted mb-1">Account Type</h6>
                        <h3 class="mb-0"><?php echo $accountType; ?></h3>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Quick Links -->
<div class="row g-3 mb-4">
    <div class="col-sm-6">
        <a href="my_reviews.php" class="card shadow-sm border-0 text-decoration-none h-100" style="border-radius:14px;">
            <div class="card-body d-flex align-items-center gap-3">
                <div class="bg-warning bg-opacity-10 p-3 rounded" style="font-size:1.8rem;">⭐</div>
                <div>
                    <div class="fw-bold text-dark">My Reviews</div>
                    <div class="text-muted small">Rate products you've received</div>
                </div>
                <i class="bi bi-chevron-right ms-auto text-muted"></i>
            </div>
        </a>
    </div>
    <div class="col-sm-6">
        <a href="feedback.php" class="card shadow-sm border-0 text-decoration-none h-100" style="border-radius:14px;">
            <div class="card-body d-flex align-items-center gap-3">
                <div class="bg-info bg-opacity-10 p-3 rounded" style="font-size:1.8rem;">💬</div>
                <div>
                    <div class="fw-bold text-dark">Send Feedback</div>
                    <div class="text-muted small">Share your experience with us</div>
                </div>
                <i class="bi bi-chevron-right ms-auto text-muted"></i>
            </div>
        </a>
    </div>
</div>

<!-- Recent Orders -->
<div class="card shadow-sm">
    <div class="card-header bg-white d-flex justify-content-between align-items-center">
        <h5 class="mb-0">Recent Orders</h5>
        <a href="orders.php" class="btn btn-sm btn-outline-success">View All Orders</a>
    </div>
    <div class="card-body p-0">
        <?php if ($recentOrders->num_rows > 0): ?>
            <div class="table-responsive">
                <table class="table table-hover mb-0">
                    <thead class="table-light">
                        <tr>
                            <th>Order ID</th>
                            <th>Date</th>
                            <th>Total</th>
                            <th>Status</th>
                            <th></th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php while ($order = $recentOrders->fetch_assoc()): ?>
                        <tr>
                            <td>#<?php echo $order['id']; ?></td>
                            <td><?php echo date('M d, Y', strtotime($order['order_date'])); ?></td>
                            <td>₹<?php echo number_format($order['total_amount'], 2); ?></td>
                            <td>
                                <span class="badge bg-<?php 
                                    echo $order['status'] == 'Delivered' ? 'success' : 
                                        ($order['status'] == 'Pending' ? 'warning' : 'secondary'); 
                                ?>">
                                    <?php echo htmlspecialchars($order['status']); ?>
                                </span>
                            </td>
                            <td>
                                <a href="order_details.php?id=<?php echo $order['id']; ?>" class="btn btn-sm btn-outline-primary">
                                    View
                                </a>
                            </td>
                        </tr>
                        <?php endwhile; ?>
                    </tbody>
                </table>
            </div>
        <?php else: ?>
            <div class="p-4 text-center text-muted">
                <i class="bi bi-bag-x fs-1"></i>
                <p class="mt-2">No orders yet. <a href="index.php">Start shopping</a>.</p>
            </div>
        <?php endif; ?>
    </div>
</div>

<?php include 'partials/footer.php'; ?>