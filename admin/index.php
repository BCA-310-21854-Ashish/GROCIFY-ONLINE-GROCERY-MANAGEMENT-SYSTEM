<?php

require_once '../config/db.php';
include 'header.php';

// Safe query helper — returns 0 if query fails or table missing
function safeCount($conn, $sql) {
    $result = $conn->query($sql);
    if (!$result) return 0;
    $row = $result->fetch_row();
    return $row ? (int)$row[0] : 0;
}

function safeSum($conn, $sql) {
    $result = $conn->query($sql);
    if (!$result) return 0;
    $row = $result->fetch_row();
    return $row && $row[0] !== null ? (float)$row[0] : 0;
}

// Stats
$totalOrders     = safeCount($conn, "SELECT COUNT(*) FROM orders");
$totalRevenue    = safeSum($conn,   "SELECT SUM(total_amount) FROM orders");
$totalUsers      = safeCount($conn, "SELECT COUNT(*) FROM users");
$totalProducts   = safeCount($conn, "SELECT COUNT(*) FROM products");
$pendingOrders   = safeCount($conn, "SELECT COUNT(*) FROM orders WHERE status='Pending'");

// Feedback count — table may not exist on fresh installs
$pendingFeedback = 0;
if ($conn->query("SHOW TABLES LIKE 'feedback'")->num_rows > 0) {
    $pendingFeedback = safeCount($conn, "SELECT COUNT(*) FROM feedback WHERE status='Pending'");
}
// New feature stats
$lowStockCount    = safeCount($conn, "SELECT COUNT(*) FROM products WHERE stock>0 AND stock<=low_stock_alert");
$outStockCount    = safeCount($conn, "SELECT COUNT(*) FROM products WHERE stock=0");
$pendingReviews   = safeCount($conn, "SELECT COUNT(*) FROM reviews WHERE status='Pending'");
$activeCoupons    = safeCount($conn, "SELECT COUNT(*) FROM coupons WHERE is_active=1");
$wishlistCount    = safeCount($conn, "SELECT COUNT(*) FROM wishlist");
$availDelivery    = safeCount($conn, "SELECT COUNT(*) FROM delivery_boys WHERE status='Available'");

// Recent orders
$recentOrders = $conn->query("
    SELECT o.id, o.total_amount, o.order_date, o.status, u.username
    FROM orders o JOIN users u ON o.user_id = u.id
    ORDER BY o.order_date DESC LIMIT 5
");

// Monthly sales for chart
$chartLabels = [];
$chartData   = [];
$monthlySales = $conn->query("
    SELECT DATE_FORMAT(order_date, '%Y-%m') as month, SUM(total_amount) as total
    FROM orders GROUP BY month ORDER BY month DESC LIMIT 6
");
if ($monthlySales) {
    while ($row = $monthlySales->fetch_assoc()) {
        $chartLabels[] = date('M Y', strtotime($row['month'] . '-01'));
        $chartData[]   = round($row['total'], 2);
    }
}
?>

<style>
.stat-card { border:none; border-radius:16px; padding:20px; color:#fff; display:flex; align-items:center; gap:16px; box-shadow:0 4px 14px rgba(0,0,0,0.1); }
.stat-icon { font-size:2.2rem; opacity:0.85; }
.stat-label { font-size:0.82rem; opacity:0.85; font-weight:600; text-transform:uppercase; letter-spacing:0.5px; }
.stat-value { font-size:1.9rem; font-weight:800; line-height:1; }
</style>

<h2 class="fw-bold mb-4">📊 Dashboard</h2>

<!-- Stats Cards -->
<div class="row g-3 mb-4">
    <div class="col-6 col-md-4 col-lg-2">
        <div class="stat-card" style="background:linear-gradient(135deg,#3b82f6,#2563eb);">
            <div><div class="stat-label">Orders</div><div class="stat-value"><?php echo $totalOrders; ?></div></div>
            <div class="stat-icon ms-auto">📦</div>
        </div>
    </div>
    <div class="col-6 col-md-4 col-lg-2">
        <div class="stat-card" style="background:linear-gradient(135deg,#10b981,#059669);">
            <div><div class="stat-label">Revenue</div><div class="stat-value" style="font-size:1.3rem;">₹<?php echo number_format($totalRevenue, 0); ?></div></div>
            <div class="stat-icon ms-auto">💰</div>
        </div>
    </div>
    <div class="col-6 col-md-4 col-lg-2">
        <div class="stat-card" style="background:linear-gradient(135deg,#8b5cf6,#7c3aed);">
            <div><div class="stat-label">Users</div><div class="stat-value"><?php echo $totalUsers; ?></div></div>
            <div class="stat-icon ms-auto">👥</div>
        </div>
    </div>
    <div class="col-6 col-md-4 col-lg-2">
        <div class="stat-card" style="background:linear-gradient(135deg,#f59e0b,#d97706);">
            <div><div class="stat-label">Products</div><div class="stat-value"><?php echo $totalProducts; ?></div></div>
            <div class="stat-icon ms-auto">🛒</div>
        </div>
    </div>
    <div class="col-6 col-md-4 col-lg-2">
        <div class="stat-card" style="background:linear-gradient(135deg,#ef4444,#dc2626);">
            <div><div class="stat-label">Pending</div><div class="stat-value"><?php echo $pendingOrders; ?></div></div>
            <div class="stat-icon ms-auto">⏳</div>
        </div>
    </div>
    <div class="col-6 col-md-4 col-lg-2">
        <div class="stat-card" style="background:linear-gradient(135deg,#64748b,#475569);">
            <div><div class="stat-label">Feedback</div><div class="stat-value"><?php echo $pendingFeedback; ?></div></div>
            <div class="stat-icon ms-auto">💬</div>
        </div>
    </div>
    <div class="col-6 col-md-4 col-lg-2">
        <a href="stock.php" class="text-decoration-none">
        <div class="stat-card" style="background:linear-gradient(135deg,#f97316,#ea580c);">
            <div><div class="stat-label">Low Stock</div><div class="stat-value"><?php echo $lowStockCount; ?></div></div>
            <div class="stat-icon ms-auto">📦</div>
        </div></a>
    </div>
    <div class="col-6 col-md-4 col-lg-2">
        <a href="reviews.php" class="text-decoration-none">
        <div class="stat-card" style="background:linear-gradient(135deg,#eab308,#ca8a04);">
            <div><div class="stat-label">Reviews</div><div class="stat-value"><?php echo $pendingReviews; ?></div></div>
            <div class="stat-icon ms-auto">⭐</div>
        </div></a>
    </div>
    <div class="col-6 col-md-4 col-lg-2">
        <a href="delivery_boys.php" class="text-decoration-none">
        <div class="stat-card" style="background:linear-gradient(135deg,#06b6d4,#0891b2);">
            <div><div class="stat-label">Delivery</div><div class="stat-value"><?php echo $availDelivery; ?></div></div>
            <div class="stat-icon ms-auto">🛵</div>
        </div></a>
    </div>
    <div class="col-6 col-md-4 col-lg-2">
        <a href="coupons.php" class="text-decoration-none">
        <div class="stat-card" style="background:linear-gradient(135deg,#14b8a6,#0d9488);">
            <div><div class="stat-label">Coupons</div><div class="stat-value"><?php echo $activeCoupons; ?></div></div>
            <div class="stat-icon ms-auto">🏷️</div>
        </div></a>
    </div>
    <div class="col-6 col-md-4 col-lg-2">
        <a href="analytics.php" class="text-decoration-none">
        <div class="stat-card" style="background:linear-gradient(135deg,#a855f7,#9333ea);">
            <div><div class="stat-label">Wishlist</div><div class="stat-value"><?php echo $wishlistCount; ?></div></div>
            <div class="stat-icon ms-auto">❤️</div>
        </div></a>
    </div>
</div>

<!-- Chart + Recent Orders -->
<div class="row g-4">
    <div class="col-lg-7">
        <div class="card border-0 shadow-sm rounded-4">
            <div class="card-header bg-transparent fw-bold border-0 pt-3 pb-0">📈 Monthly Revenue (Last 6 Months)</div>
            <div class="card-body">
                <?php if (empty($chartData)): ?>
                    <div class="text-center text-muted py-4">No sales data yet.</div>
                <?php else: ?>
                    <canvas id="salesChart" height="200"></canvas>
                <?php endif; ?>
            </div>
        </div>
    </div>
    <div class="col-lg-5">
        <div class="card border-0 shadow-sm rounded-4 h-100">
            <div class="card-header bg-transparent fw-bold border-0 pt-3 pb-0">🕐 Recent Orders</div>
            <div class="card-body p-0">
                <?php if (!$recentOrders || $recentOrders->num_rows === 0): ?>
                    <div class="text-center text-muted py-4">No orders yet.</div>
                <?php else: ?>
                <table class="table table-sm table-hover mb-0">
                    <thead class="table-light">
                        <tr><th>ID</th><th>Customer</th><th>Total</th><th>Status</th></tr>
                    </thead>
                    <tbody>
                    <?php while($order = $recentOrders->fetch_assoc()): ?>
                    <tr>
                        <td><a href="orders.php" class="text-decoration-none fw-bold">#<?php echo $order['id']; ?></a></td>
                        <td><?php echo htmlspecialchars($order['username']); ?></td>
                        <td class="text-success fw-semibold">₹<?php echo number_format($order['total_amount'], 2); ?></td>
                        <td>
                            <?php
                            $bgs = ['Pending'=>'warning','Confirmed'=>'info','Packed'=>'primary','Out for Delivery'=>'primary','Delivered'=>'success','Cancelled'=>'danger'];
                            $bg  = $bgs[$order['status']] ?? 'secondary';
                            ?>
                            <span class="badge bg-<?php echo $bg; ?>"><?php echo htmlspecialchars($order['status']); ?></span>
                        </td>
                    </tr>
                    <?php endwhile; ?>
                    </tbody>
                </table>
                <?php endif; ?>
            </div>
            <div class="card-footer bg-transparent border-0 text-end">
                <a href="orders.php" class="btn btn-sm btn-outline-success rounded-pill">View All Orders →</a>
            </div>
        </div>
    </div>
</div>

<?php if (!empty($chartData)): ?>
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
const ctx = document.getElementById('salesChart').getContext('2d');
new Chart(ctx, {
    type: 'bar',
    data: {
        labels: <?php echo json_encode(array_reverse($chartLabels)); ?>,
        datasets: [{
            label: 'Revenue (₹)',
            data: <?php echo json_encode(array_reverse($chartData)); ?>,
            backgroundColor: 'rgba(16,185,129,0.5)',
            borderColor: 'rgba(5,150,105,1)',
            borderWidth: 2,
            borderRadius: 8,
        }]
    },
    options: {
        responsive: true,
        plugins: { legend: { display: false } },
        scales: { y: { beginAtZero: true, ticks: { callback: v => '₹' + v } } }
    }
});
</script>
<?php endif; ?>

<?php include 'footer.php'; ?>
