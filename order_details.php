<?php

session_start();
require_once 'config/db.php';

if (!isset($_SESSION['user_id'])) {
    header('Location: auth/login.php');
    exit();
}

$userId  = $_SESSION['user_id'];
$orderId = isset($_GET['id']) ? intval($_GET['id']) : 0;

$stmt = $conn->prepare("SELECT id, total_amount, order_date, status, billing_name, billing_email, billing_phone, billing_address, payment_method FROM orders WHERE id = ? AND user_id = ?");
$stmt->bind_param("ii", $orderId, $userId);
$stmt->execute();
$order = $stmt->get_result()->fetch_assoc();
$stmt->close();

if (!$order) { header('Location: orders.php'); exit(); }

$stmt = $conn->prepare("SELECT oi.quantity, oi.price, p.name, p.image_url FROM order_items oi JOIN products p ON oi.product_id = p.id WHERE oi.order_id = ?");
$stmt->bind_param("i", $orderId);
$stmt->execute();
$items = $stmt->get_result();
$stmt->close();

// Map status to step index (0-based)
$steps = ['Order Placed', 'Confirmed', 'Packed', 'Out for Delivery', 'Delivered'];
$stepIcons = ['📋', '✅', '📦', '🚚', '🏠'];
$statusMap = [
    'Pending'          => 0,
    'Order Placed'     => 0,
    'Confirmed'        => 1,
    'Packed'           => 2,
    'Out for Delivery' => 3,
    'Delivered'        => 4,
];
$currentStep = $statusMap[$order['status']] ?? 0;

include 'partials/header.php';
?>
<a href="invoice.php?id=<?php echo $orderId; ?>" target="_blank" class="btn btn-success mb-3">Download Invoice (PDF)</a>

<style>
/* ---- Tracking Stepper ---- */
.track-section { background:#fff; border-radius:20px; box-shadow:0 4px 20px rgba(0,0,0,0.07); padding:28px 24px; margin-bottom:28px; }
.track-title { font-size:1.1rem; font-weight:700; margin-bottom:20px; }

.stepper { display:flex; align-items:flex-start; position:relative; }
.stepper::before {
    content:''; position:absolute; top:22px; left:22px; right:22px;
    height:4px; background:#e9ecef; z-index:0;
}
.step { flex:1; display:flex; flex-direction:column; align-items:center; position:relative; z-index:1; }
.step-circle {
    width:46px; height:46px; border-radius:50%; background:#e9ecef;
    display:flex; align-items:center; justify-content:center;
    font-size:1.3rem; border:3px solid #e9ecef;
    transition: all 0.4s ease;
}
.step.done .step-circle  { background:#198754; border-color:#198754; }
.step.active .step-circle { background:#fff; border-color:#198754; box-shadow:0 0 0 5px rgba(25,135,84,0.15); animation: pulse-ring 1.5s infinite; }
.step-label { font-size:0.72rem; text-align:center; margin-top:8px; color:#6c757d; font-weight:500; }
.step.done .step-label, .step.active .step-label { color:#198754; font-weight:700; }

/* Progress bar fill */
.stepper-bar {
    position:absolute; top:22px; left:22px; height:4px;
    background: linear-gradient(90deg, #198754, #20c997);
    z-index:0; border-radius:4px;
    transition: width 1s ease;
}

@keyframes pulse-ring {
    0%   { box-shadow: 0 0 0 0 rgba(25,135,84,0.4); }
    70%  { box-shadow: 0 0 0 10px rgba(25,135,84,0); }
    100% { box-shadow: 0 0 0 0 rgba(25,135,84,0); }
}

/* ETA badge */
.eta-badge { background:linear-gradient(135deg,#d1fae5,#a7f3d0); color:#065f46; border-radius:12px; padding:12px 18px; font-size:0.9rem; }

/* Order detail cards */
.detail-card { border:none; border-radius:16px; box-shadow:0 3px 14px rgba(0,0,0,0.06); margin-bottom:20px; }
.detail-card .card-header { background:transparent; border-bottom:1px solid #f0f0f0; font-weight:700; padding:16px 20px; }
.detail-card .card-body { padding:20px; }

/* Item row */
.item-row { display:flex; align-items:center; gap:14px; padding:10px 0; border-bottom:1px solid #f5f5f5; }
.item-row:last-child { border:none; }
.item-thumb { width:56px; height:56px; border-radius:10px; object-fit:cover; background:#f0f0f0; }
.item-info { flex:1; }
.item-name { font-weight:600; font-size:0.9rem; }
.item-meta { color:#888; font-size:0.8rem; }
.item-price { font-weight:700; color:#198754; }

/* Status badge */
.status-pill {
    padding:5px 14px; border-radius:20px; font-size:0.82rem; font-weight:600; display:inline-block;
}
.status-pending   { background:#fff3cd; color:#856404; }
.status-confirmed { background:#cff4fc; color:#055160; }
.status-packed    { background:#d1fae5; color:#065f46; }
.status-out       { background:#dbeafe; color:#1e40af; }
.status-delivered { background:#dcfce7; color:#14532d; }
</style>

<div class="mb-3">
    <a href="orders.php" class="btn btn-outline-secondary btn-sm rounded-pill">← Back to Orders</a>
</div>

<div class="d-flex flex-wrap align-items-center justify-content-between mb-4 gap-2">
    <div>
        <h2 class="fw-bold mb-1">Order #<?php echo $order['id']; ?></h2>
        <span class="text-muted small">Placed on <?php echo date('F j, Y \a\t g:i A', strtotime($order['order_date'])); ?></span>
    </div>
    <?php
    $statusClass = ['Pending'=>'status-pending','Confirmed'=>'status-confirmed','Packed'=>'status-packed','Out for Delivery'=>'status-out','Delivered'=>'status-delivered'];
    $sc = $statusClass[$order['status']] ?? 'status-pending';
    ?>
    <span class="status-pill <?php echo $sc; ?>"><?php echo htmlspecialchars($order['status']); ?></span>
</div>

<!-- ===== TRACK ORDER SECTION ===== -->
<div class="track-section">
    <div class="track-title">📍 Track Your Order</div>

    <!-- ETA info -->
    <div class="eta-badge mb-4">
        <?php if($order['status'] === 'Delivered'): ?>
            🎉 Your order has been delivered! Thank you for shopping with Grocify.
        <?php elseif($order['status'] === 'Out for Delivery'): ?>
            🚚 Your order is out for delivery and will arrive <strong>today</strong>. Stay home!
        <?php elseif($order['status'] === 'Packed'): ?>
            📦 Your order is packed and will be picked up for delivery <strong>soon</strong>.
        <?php else: ?>
            ⏳ Your order is being processed. Estimated delivery: <strong>within 2–4 hours</strong>.
        <?php endif; ?>
    </div>

    <!-- Stepper -->
    <div class="position-relative">
        <div class="stepper" id="order-stepper">
            <!-- Progress fill bar -->
            <div class="stepper-bar" id="stepper-bar" style="width:0"></div>
            <?php foreach($steps as $i => $label): ?>
            <?php
                $cls = '';
                if($i < $currentStep) $cls = 'done';
                elseif($i === $currentStep) $cls = 'active';
            ?>
            <div class="step <?php echo $cls; ?>">
                <div class="step-circle">
                    <?php if($i < $currentStep): ?>✓<?php else: ?><?php echo $stepIcons[$i]; ?><?php endif; ?>
                </div>
                <div class="step-label"><?php echo $label; ?></div>
            </div>
            <?php endforeach; ?>
        </div>
    </div>

    <!-- Timeline log below stepper -->
    <div class="mt-4 ps-2" id="track-log">
        <?php
        $logs = [
            0 => ['Order placed successfully. Waiting for confirmation.', 'text-muted'],
            1 => ['Seller confirmed your order.', 'text-primary'],
            2 => ['Items packed and ready to ship.', 'text-warning'],
            3 => ['Out for delivery. Rider is on the way!', 'text-info'],
            4 => ['Order delivered successfully. Enjoy!', 'text-success'],
        ];
        for ($i = 0; $i <= $currentStep; $i++): ?>
        <div class="d-flex align-items-start gap-2 mb-2 track-log-item" style="opacity:0; transform:translateY(10px); transition: all 0.4s ease <?php echo $i*0.1; ?>s">
            <span class="<?php echo $logs[$i][1]; ?> fw-bold" style="min-width:18px;">●</span>
            <div>
                <div class="fw-semibold small"><?php echo $steps[$i]; ?></div>
                <div class="text-muted" style="font-size:0.8rem;"><?php echo $logs[$i][0]; ?></div>
            </div>
        </div>
        <?php endfor; ?>
    </div>
</div>

<div class="row">
<div class="col-md-8">

    <!-- Items Card -->
    <div class="card detail-card">
        <div class="card-header">🛍 Order Items</div>
        <div class="card-body">
        <?php
        $subtotal = 0;
        while($item = $items->fetch_assoc()):
            $itemTotal = $item['price'] * $item['quantity'];
            $subtotal += $itemTotal;
        ?>
        <div class="item-row">
            <img src="<?php echo htmlspecialchars($item['image_url']); ?>"
                 class="item-thumb"
                 onerror="this.src='https://via.placeholder.com/56?text=?'"
                 alt="<?php echo htmlspecialchars($item['name']); ?>">
            <div class="item-info">
                <div class="item-name"><?php echo htmlspecialchars($item['name']); ?></div>
                <div class="item-meta">Qty: <?php echo $item['quantity']; ?> × ₹<?php echo number_format($item['price'], 2); ?></div>
            </div>
            <div class="item-price">₹<?php echo number_format($itemTotal, 2); ?></div>
        </div>
        <?php endwhile; ?>
        <div class="d-flex justify-content-between pt-3 mt-2 border-top fw-bold fs-5">
            <span>Total</span>
            <span class="text-success">₹<?php echo number_format($order['total_amount'], 2); ?></span>
        </div>
        </div>
    </div>

</div>
<div class="col-md-4">

    <!-- Billing Card -->
    <div class="card detail-card">
        <div class="card-header">📋 Billing Details</div>
        <div class="card-body">
            <p class="mb-1"><strong>Name:</strong> <?php echo htmlspecialchars($order['billing_name'] ?? 'N/A'); ?></p>
            <p class="mb-1"><strong>Email:</strong> <?php echo htmlspecialchars($order['billing_email'] ?? 'N/A'); ?></p>
            <p class="mb-1"><strong>Phone:</strong> <?php echo htmlspecialchars($order['billing_phone'] ?? 'N/A'); ?></p>
            <p class="mb-0"><strong>Address:</strong><br><?php echo nl2br(htmlspecialchars($order['billing_address'] ?? 'N/A')); ?></p>
        </div>
    </div>

    <!-- Payment Card -->
    <div class="card detail-card">
        <div class="card-header">💳 Payment</div>
        <div class="card-body">
            <p class="mb-0"><?php echo htmlspecialchars($order['payment_method'] ?? 'N/A'); ?></p>
        </div>
    </div>

    <!-- Need help? -->
    <div class="card detail-card border border-success-subtle">
        <div class="card-body text-center py-3">
            <div class="fs-3 mb-1">🤝</div>
            <div class="fw-semibold">Need help with this order?</div>
            <small class="text-muted">Contact our support team</small><br>
            <a href="mailto:support@grocify.com" class="btn btn-outline-success btn-sm mt-2 rounded-pill">
                Contact Support
            </a>
        </div>
    </div>

</div>
</div>

<script>
// Animate stepper bar
var total = <?php echo count($steps) - 1; ?>;
var current = <?php echo $currentStep; ?>;
var pct = current === 0 ? 0 : (current / total) * (100 - (100 / total));

window.addEventListener('load', function() {
    setTimeout(function() {
        document.getElementById('stepper-bar').style.width = 'calc(' + pct + '% - 0px)';
    }, 300);

    // Animate log items
    document.querySelectorAll('.track-log-item').forEach(function(el, i) {
        setTimeout(function() {
            el.style.opacity = '1';
            el.style.transform = 'translateY(0)';
        }, 400 + i * 120);
    });
});

// ---- Live status polling ----
var lastStatus = <?php echo json_encode($order['status']); ?>;
var orderId    = <?php echo $orderId; ?>;
var stepLabels = ['Order Placed','Confirmed','Packed','Out for Delivery','Delivered'];
var stepIcons  = ['📋','✅','📦','🚚','🏠'];
var statusMap  = {'Pending':0,'Order Placed':0,'Confirmed':1,'Packed':2,'Out for Delivery':3,'Delivered':4};
var totalSteps = stepLabels.length - 1;

function applyStatus(status) {
    var idx = statusMap[status] ?? 0;

    // Update stepper circles
    document.querySelectorAll('#order-stepper .step').forEach(function(el, i) {
        el.classList.remove('done','active');
        var circle = el.querySelector('.step-circle');
        if (i < idx)      { el.classList.add('done');   circle.textContent = '✓'; }
        else if (i === idx){ el.classList.add('active'); circle.textContent = stepIcons[i]; }
        else               { circle.textContent = stepIcons[i]; }
    });

    // Update progress bar
    var pct = idx === 0 ? 0 : (idx / totalSteps) * (100 - 100/stepLabels.length);
    var bar = document.getElementById('stepper-bar');
    if (bar) bar.style.width = 'calc(' + pct + '% )';

    // Update status pill at top
    var pill = document.querySelector('.status-pill');
    if (pill) {
        var pillMap = {
            'Pending':'status-pending','Order Placed':'status-pending',
            'Confirmed':'status-confirmed','Packed':'status-packed',
            'Out for Delivery':'status-out','Delivered':'status-delivered'
        };
        pill.className = 'status-pill ' + (pillMap[status] || 'status-pending');
        pill.textContent = status;
    }

    // Update ETA badge
    var eta = document.querySelector('.eta-badge');
    if (eta) {
        if (status === 'Delivered')
            eta.innerHTML = '🎉 Your order has been delivered! Thank you for shopping with Grocify.';
        else if (status === 'Out for Delivery')
            eta.innerHTML = '🚚 Your order is out for delivery and will arrive <strong>today</strong>. Stay home!';
        else if (status === 'Packed')
            eta.innerHTML = '📦 Your order is packed and will be picked up for delivery <strong>soon</strong>.';
        else
            eta.innerHTML = '⏳ Your order is being processed. Estimated delivery: <strong>within 2–4 hours</strong>.';
    }

    // Refresh log items
    var logEl = document.getElementById('track-log');
    if (logEl) {
        var logs = [
            ['Order Placed', 'Order placed successfully. Waiting for confirmation.', 'text-muted'],
            ['Confirmed',    'Seller confirmed your order.',                          'text-primary'],
            ['Packed',       'Items packed and ready to ship.',                       'text-warning'],
            ['Out for Delivery','Out for delivery. Rider is on the way!',             'text-info'],
            ['Delivered',    'Order delivered successfully. Enjoy!',                  'text-success']
        ];
        var html = '';
        for (var i = 0; i <= idx; i++) {
            html += '<div class="d-flex align-items-start gap-2 mb-2">' +
                '<span class="' + logs[i][2] + ' fw-bold" style="min-width:18px;">●</span>' +
                '<div><div class="fw-semibold small">' + logs[i][0] + '</div>' +
                '<div class="text-muted" style="font-size:0.8rem;">' + logs[i][1] + '</div></div></div>';
        }
        logEl.innerHTML = html;
    }
}

function pollStatus() {
    fetch('get_order_status.php?id=' + orderId)
        .then(function(r) { return r.json(); })
        .then(function(data) {
            if (data.status && data.status !== lastStatus) {
                lastStatus = data.status;
                applyStatus(data.status);
                // Show a live update toast
                showLiveToast('🔄 Order status updated: ' + data.status);
            }
        })
        .catch(function() {}); // silent fail
}

function showLiveToast(msg) {
    var container = document.getElementById('live-toast-container');
    if (!container) {
        container = document.createElement('div');
        container.id = 'live-toast-container';
        container.className = 'position-fixed bottom-0 end-0 p-3';
        container.style.zIndex = '9999';
        document.body.appendChild(container);
    }
    var id = 'lt-' + Date.now();
    container.insertAdjacentHTML('beforeend',
        '<div id="' + id + '" class="toast align-items-center text-white bg-primary border-0 mb-2 show" role="alert">' +
        '<div class="d-flex"><div class="toast-body fw-semibold">' + msg + '</div>' +
        '<button type="button" class="btn-close btn-close-white me-2 m-auto" data-bs-dismiss="toast"></button></div></div>'
    );
    var el = document.getElementById(id);
    new bootstrap.Toast(el, {delay: 4000}).show();
    el.addEventListener('hidden.bs.toast', function() { el.remove(); });
}

// Poll every 15 seconds
setInterval(pollStatus, 15000);
// Also poll immediately once (catches status changes since page loaded)
setTimeout(pollStatus, 2000);
</script>

<?php include 'partials/footer.php'; ?>
