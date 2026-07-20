<?php

session_start();
require_once 'config/db.php';

if (!isset($_SESSION['user_id'])) {
    header('Location: auth/login.php');
    exit();
}

$userId = $_SESSION['user_id'];
$stmt = $conn->prepare("SELECT id, total_amount, order_date, status, payment_method FROM orders WHERE user_id = ? ORDER BY order_date DESC");
$stmt->bind_param("i", $userId);
$stmt->execute();
$orders = $stmt->get_result();
$stmt->close();

include 'partials/header.php';
?>

<style>
.orders-header { background:linear-gradient(135deg,#d1fae5,#a7f3d0); border-radius:18px; padding:24px 28px; margin-bottom:28px; }
.order-card { border:none; border-radius:16px; box-shadow:0 3px 14px rgba(0,0,0,0.06); margin-bottom:16px; transition:transform 0.2s, box-shadow 0.2s; overflow:hidden; }
.order-card:hover { transform:translateY(-3px); box-shadow:0 8px 24px rgba(0,0,0,0.1); }
.order-card-header { background:#f9fafb; border-bottom:1px solid #f0f0f0; padding:14px 20px; display:flex; align-items:center; justify-content:space-between; flex-wrap:wrap; gap:8px; }
.order-card-body { padding:16px 20px; }
.status-pill { padding:4px 14px; border-radius:20px; font-size:0.8rem; font-weight:700; display:inline-block; }
.status-Pending          { background:#fff3cd; color:#856404; }
.status-Confirmed        { background:#cff4fc; color:#055160; }
.status-Packed           { background:#d1fae5; color:#065f46; }
.status-Out\.for\.Delivery { background:#dbeafe; color:#1e40af; }
.status-Delivered        { background:#dcfce7; color:#14532d; }

/* Mini tracker */
.mini-track { display:flex; align-items:center; gap:0; margin:6px 0 0; }
.mini-dot { width:14px; height:14px; border-radius:50%; background:#e9ecef; flex-shrink:0; position:relative; }
.mini-dot.done  { background:#198754; }
.mini-dot.active { background:#198754; box-shadow:0 0 0 3px rgba(25,135,84,0.25); }
.mini-line { flex:1; height:3px; background:#e9ecef; }
.mini-line.done { background:#198754; }
</style>

<div class="orders-header d-flex align-items-center justify-content-between flex-wrap gap-3">
    <div>
        <h2 class="fw-bold mb-1">📦 My Orders</h2>
        <p class="mb-0 text-muted">Track and manage all your Grocify orders</p>
    </div>
    <a href="index.php" class="btn btn-success rounded-pill px-4">+ Shop More</a>
</div>

<?php
$statusSteps = ['Pending' => 0, 'Order Placed' => 0, 'Confirmed' => 1, 'Packed' => 2, 'Out for Delivery' => 3, 'Delivered' => 4];
$stepLabels = ['Placed', 'Confirmed', 'Packed', 'Shipped', 'Delivered'];

if ($orders->num_rows > 0):
    while ($order = $orders->fetch_assoc()):
        $step = $statusSteps[$order['status']] ?? 0;
        $statusKey = str_replace(' ', '-', $order['status']);
?>
<div class="order-card card">
    <div class="order-card-header">
        <div>
            <span class="fw-bold text-dark">Order #<?php echo $order['id']; ?></span>
            <span class="text-muted ms-2 small"><?php echo date('M d, Y • g:i A', strtotime($order['order_date'])); ?></span>
        </div>
        <div class="d-flex align-items-center gap-2">
            <span class="status-pill status-<?php echo htmlspecialchars($statusKey); ?>">
                <?php echo htmlspecialchars($order['status']); ?>
            </span>
            <span class="fw-bold text-success">₹<?php echo number_format($order['total_amount'], 2); ?></span>
        </div>
    </div>
    <div class="order-card-body">
        <!-- Mini progress tracker -->
        <div class="mini-track mb-3">
            <?php for($i = 0; $i < 5; $i++): ?>
            <div class="mini-dot <?php echo $i < $step ? 'done' : ($i === $step ? 'active' : ''); ?>"></div>
            <?php if($i < 4): ?>
            <div class="mini-line <?php echo $i < $step ? 'done' : ''; ?>"></div>
            <?php endif; ?>
            <?php endfor; ?>
        </div>
        <div class="d-flex justify-content-between align-items-center flex-wrap gap-2">
            <small class="text-muted">💳 <?php echo htmlspecialchars($order['payment_method'] ?? 'N/A'); ?></small>
            <div class="d-flex gap-2">
                <a href="order_details.php?id=<?php echo $order['id']; ?>"
                   class="btn btn-sm btn-outline-success rounded-pill px-3">
                    🔍 View Details
                </a>
                <a href="gst_invoice.php?order_id=<?php echo $order['id']; ?>"
                   class="btn btn-sm btn-outline-primary rounded-pill px-3" target="_blank">
                    🧾 GST Invoice
                </a>
                <a href="order_details.php?id=<?php echo $order['id']; ?>#track"
                   class="btn btn-sm btn-success rounded-pill px-3">
                    📍 Track Order
                </a>
            </div>
        </div>
    </div>
</div>
<?php
    endwhile;
else:
?>
<div class="text-center py-5">
    <div style="font-size:4rem;">🛒</div>
    <h4 class="mt-3 fw-bold">No orders yet!</h4>
    <p class="text-muted">Looks like you haven't placed any orders. Start shopping now.</p>
    <a href="index.php" class="btn btn-success rounded-pill px-5 mt-2">Shop Now</a>
</div>
<?php endif; ?>

<?php include 'partials/footer.php'; ?>
