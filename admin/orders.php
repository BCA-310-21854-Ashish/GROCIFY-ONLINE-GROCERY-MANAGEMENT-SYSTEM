<?php

require_once '../config/db.php';

// ── AJAX: update status — MUST be before any output (header.php) ──────────
if (isset($_POST['ajax_update_status'])) {
    header('Content-Type: application/json');
    $orderId = intval($_POST['order_id']);
    $status  = $_POST['status'];
    $allowed = ['Order Placed','Confirmed','Packed','Out for Delivery','Delivered','Cancelled'];
    if (in_array($status, $allowed)) {
        $stmt = $conn->prepare("UPDATE orders SET status=? WHERE id=?");
        $stmt->bind_param("si", $status, $orderId);
        if ($stmt->execute()) {
            echo json_encode(['success' => true]);
        } else {
            echo json_encode(['success' => false, 'msg' => 'DB error: ' . $stmt->error]);
        }
        $stmt->close();
    } else {
        echo json_encode(['success' => false, 'msg' => 'Invalid status: ' . htmlspecialchars($status)]);
    }
    exit();
}

include 'header.php';

// ── Fetch all orders ──────────────────────────────────────────────────────
$orders = $conn->query("
    SELECT o.*, u.username, u.email as user_email
    FROM orders o
    JOIN users u ON o.user_id = u.id
    ORDER BY o.order_date DESC
");
?>

<style>
/* ── Page layout ── */
.admin-orders-header {
    background: linear-gradient(135deg,#1e3a5f,#1a6b3c);
    border-radius: 16px; color:#fff; padding:22px 28px; margin-bottom:28px;
}

/* ── Status pills ── */
.spill {
    display:inline-block; padding:4px 12px; border-radius:20px;
    font-size:0.78rem; font-weight:700; white-space:nowrap;
}
.sp-placed    { background:#e0f2fe; color:#0369a1; }
.sp-confirmed { background:#cffafe; color:#0e7490; }
.sp-packed    { background:#d1fae5; color:#065f46; }
.sp-out       { background:#dbeafe; color:#1d4ed8; }
.sp-delivered { background:#dcfce7; color:#14532d; }
.sp-cancelled { background:#fee2e2; color:#991b1b; }
.sp-pending   { background:#fef9c3; color:#854d0e; }

/* ── Table ── */
#ordersTable { font-size:0.88rem; }
#ordersTable thead th { background:#f8fafc; font-weight:700; border-bottom:2px solid #e9ecef; vertical-align:middle; }
#ordersTable tbody tr { transition: background 0.15s; }
#ordersTable tbody tr:hover { background:#f0fff4; }
#ordersTable td { vertical-align:middle; }

/* ── Stepper inside modal ── */
.modal-stepper { display:flex; align-items:flex-start; position:relative; margin:8px 0 20px; }
.modal-stepper::before {
    content:''; position:absolute; top:20px; left:20px; right:20px;
    height:3px; background:#e9ecef; z-index:0;
}
.ms-bar {
    position:absolute; top:20px; left:20px; height:3px;
    background:linear-gradient(90deg,#198754,#20c997); z-index:0;
    border-radius:3px; transition:width 0.5s ease;
}
.ms-step { flex:1; display:flex; flex-direction:column; align-items:center; z-index:1; cursor:pointer; }
.ms-circle {
    width:42px; height:42px; border-radius:50%; background:#e9ecef;
    display:flex; align-items:center; justify-content:center;
    font-size:1.1rem; border:3px solid #e9ecef; transition:all 0.3s;
}
.ms-step.done   .ms-circle { background:#198754; border-color:#198754; color:#fff; }
.ms-step.active .ms-circle { background:#fff; border-color:#198754; box-shadow:0 0 0 5px rgba(25,135,84,.2); }
.ms-label { font-size:0.68rem; text-align:center; margin-top:6px; color:#6c757d; font-weight:600; }
.ms-step.done .ms-label, .ms-step.active .ms-label { color:#198754; }
.ms-step:hover .ms-circle { border-color:#198754; transform:scale(1.1); }

/* ── Search bar ── */
#searchInput { border-radius:30px; padding-left:18px; }

/* ── Filter buttons ── */
.filter-tab { border-radius:30px; font-size:0.8rem; padding:5px 16px; border:2px solid #dee2e6; background:#fff; cursor:pointer; transition:0.2s; }
.filter-tab:hover, .filter-tab.active { background:#198754; color:#fff; border-color:#198754; }

/* ── Save btn spinner ── */
.save-spinner { display:none; }
</style>

<!-- Header -->
<div class="admin-orders-header d-flex align-items-center justify-content-between flex-wrap gap-3">
    <div>
        <h2 class="fw-bold mb-1">📦 Order Management</h2>
        <p class="mb-0 opacity-75">Update order statuses — customers see changes live on their tracking page</p>
    </div>
    <div class="badge bg-white text-dark fs-6 px-3 py-2" id="total-count-badge">
        Loading…
    </div>
</div>

<!-- Filter + Search bar -->
<div class="d-flex flex-wrap gap-2 align-items-center mb-3">
    <input type="text" id="searchInput" class="form-control" style="max-width:260px;"
           placeholder="🔍 Search order / customer…">
    <span class="filter-tab active" data-filter="all">All</span>
    <span class="filter-tab" data-filter="Pending">Pending</span>
    <span class="filter-tab" data-filter="Order Placed">Placed</span>
    <span class="filter-tab" data-filter="Confirmed">Confirmed</span>
    <span class="filter-tab" data-filter="Packed">Packed</span>
    <span class="filter-tab" data-filter="Out for Delivery">Shipping</span>
    <span class="filter-tab" data-filter="Delivered">Delivered</span>
    <span class="filter-tab" data-filter="Cancelled">Cancelled</span>
</div>

<!-- Toast -->
<div class="position-fixed top-0 end-0 p-3" style="z-index:9999;">
    <div id="adminToast" class="toast align-items-center text-white bg-success border-0" role="alert">
        <div class="d-flex">
            <div class="toast-body fw-semibold" id="adminToastMsg">Done!</div>
            <button type="button" class="btn-close btn-close-white me-2 m-auto" data-bs-dismiss="toast"></button>
        </div>
    </div>
</div>

<!-- Orders Table -->
<div class="card border-0 shadow-sm rounded-4 overflow-hidden">
<table class="table mb-0" id="ordersTable">
    <thead>
        <tr>
            <th>Order</th>
            <th>Customer</th>
            <th>Date</th>
            <th>Amount</th>
            <th>Payment</th>
            <th>Status</th>
            <th>Update Status</th>
            <th>Actions</th>
        </tr>
    </thead>
    <tbody>
    <?php while($order = $orders->fetch_assoc()): ?>
    <?php
        $spMap = [
            'Pending'=>'sp-pending','Order Placed'=>'sp-placed','Confirmed'=>'sp-confirmed',
            'Packed'=>'sp-packed','Out for Delivery'=>'sp-out',
            'Delivered'=>'sp-delivered','Cancelled'=>'sp-cancelled'
        ];
        $sp = $spMap[$order['status']] ?? 'sp-pending';
    ?>
    <tr data-status="<?php echo htmlspecialchars($order['status']); ?>"
        data-order="<?php echo $order['id']; ?>"
        data-customer="<?php echo htmlspecialchars(strtolower($order['username'])); ?>">
        <td><strong>#<?php echo $order['id']; ?></strong></td>
        <td>
            <div class="fw-semibold"><?php echo htmlspecialchars($order['username']); ?></div>
            <small class="text-muted"><?php echo htmlspecialchars($order['user_email']); ?></small>
        </td>
        <td><?php echo date('M d, Y', strtotime($order['order_date'])); ?><br>
            <small class="text-muted"><?php echo date('g:i A', strtotime($order['order_date'])); ?></small>
        </td>
        <td class="fw-bold text-success">₹<?php echo number_format($order['total_amount'], 2); ?></td>
        <td><?php echo htmlspecialchars($order['payment_method'] ?? 'N/A'); ?></td>
        <td>
            <span class="spill <?php echo $sp; ?> status-badge" id="badge-<?php echo $order['id']; ?>">
                <?php echo htmlspecialchars($order['status']); ?>
            </span>
        </td>
        <td>
            <!-- Quick status dropdown + save -->
            <div class="d-flex gap-1 align-items-center">
                <select class="form-select form-select-sm rounded-pill status-select"
                        id="select-<?php echo $order['id']; ?>"
                        style="font-size:0.78rem; max-width:160px;">
                    <?php
                    $statuses = ['Order Placed','Confirmed','Packed','Out for Delivery','Delivered','Cancelled'];
                    foreach($statuses as $s):
                    ?>
                    <option value="<?php echo $s; ?>" <?php echo $order['status']===$s?'selected':''; ?>>
                        <?php echo $s; ?>
                    </option>
                    <?php endforeach; ?>
                </select>
                <button class="btn btn-success btn-sm rounded-pill save-status-btn"
                        data-id="<?php echo $order['id']; ?>"
                        title="Save status">
                    <span class="save-label">✓ Save</span>
                    <span class="save-spinner spinner-border spinner-border-sm"></span>
                </button>
            </div>
        </td>
        <td>
            <div class="d-flex gap-1">
                <!-- Detail / Track modal trigger -->
                <button class="btn btn-outline-primary btn-sm rounded-pill view-order-btn"
                        data-id="<?php echo $order['id']; ?>"
                        data-name="<?php echo htmlspecialchars($order['username'], ENT_QUOTES); ?>"
                        data-email="<?php echo htmlspecialchars($order['billing_email'] ?? '', ENT_QUOTES); ?>"
                        data-phone="<?php echo htmlspecialchars($order['billing_phone'] ?? '', ENT_QUOTES); ?>"
                        data-address="<?php echo htmlspecialchars($order['billing_address'] ?? '', ENT_QUOTES); ?>"
                        data-payment="<?php echo htmlspecialchars($order['payment_method'] ?? '', ENT_QUOTES); ?>"
                        data-total="<?php echo number_format($order['total_amount'], 2); ?>"
                        data-status="<?php echo htmlspecialchars($order['status'], ENT_QUOTES); ?>"
                        data-date="<?php echo date('F j, Y \a\t g:i A', strtotime($order['order_date'])); ?>">
                    🔍 Details
                </button>
                <a href="../gst_invoice.php?order_id=<?php echo $order['id']; ?>"
                   target="_blank" class="btn btn-outline-primary btn-sm rounded-pill mb-1">
                    🧾 Invoice
                </a>
                <a href="../order_details.php?id=<?php echo $order['id']; ?>"
                   target="_blank" class="btn btn-outline-secondary btn-sm rounded-pill"
                   title="View customer tracking page">
                    📍
                </a>
            </div>
        </td>
    </tr>
    <?php endwhile; ?>
    </tbody>
</table>
</div>

<!-- ══════════ ORDER DETAIL + TRACK MODAL ══════════ -->
<div class="modal fade" id="orderDetailModal" tabindex="-1">
    <div class="modal-dialog modal-lg modal-dialog-centered">
        <div class="modal-content rounded-4 border-0 shadow-lg">
            <div class="modal-header border-0 pb-0">
                <h5 class="modal-title fw-bold" id="modal-order-title">Order #—</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body pt-2">

                <!-- Stepper tracker (clickable) -->
                <div class="p-3 rounded-3 mb-3" style="background:#f8fafb;">
                    <div class="fw-semibold mb-3">📍 Order Tracking — click a step to jump to that stage</div>
                    <div class="position-relative">
                        <div class="modal-stepper" id="modal-stepper">
                            <div class="ms-bar" id="ms-bar" style="width:0"></div>
                            <?php
                            $steps = ['Order Placed','Confirmed','Packed','Out for Delivery','Delivered'];
                            $icons = ['📋','✅','📦','🚚','🏠'];
                            foreach($steps as $si => $sl):
                            ?>
                            <div class="ms-step" data-step="<?php echo $si; ?>" data-label="<?php echo $sl; ?>">
                                <div class="ms-circle"><?php echo $icons[$si]; ?></div>
                                <div class="ms-label"><?php echo $sl; ?></div>
                            </div>
                            <?php endforeach; ?>
                        </div>
                    </div>
                    <!-- Status select + save inside modal -->
                    <div class="d-flex align-items-center gap-2 mt-3 flex-wrap">
                        <label class="fw-semibold small mb-0">Set Status:</label>
                        <select class="form-select form-select-sm rounded-pill" id="modal-status-select" style="max-width:200px;">
                            <?php foreach($statuses as $s): ?>
                            <option value="<?php echo $s; ?>"><?php echo $s; ?></option>
                            <?php endforeach; ?>
                        </select>
                        <button class="btn btn-success btn-sm rounded-pill px-4" id="modal-save-btn">
                            <span id="modal-save-label">✓ Update Order</span>
                            <span id="modal-save-spinner" class="spinner-border spinner-border-sm" style="display:none;"></span>
                        </button>
                        <span id="modal-saved-tick" class="text-success fw-bold" style="display:none;">✅ Saved!</span>
                    </div>
                </div>

                <!-- Billing + Info -->
                <div class="row g-3">
                    <div class="col-md-6">
                        <div class="p-3 rounded-3 border h-100">
                            <div class="fw-bold mb-2">👤 Customer Info</div>
                            <p class="mb-1 small"><strong>Name:</strong> <span id="modal-name"></span></p>
                            <p class="mb-1 small"><strong>Email:</strong> <span id="modal-email"></span></p>
                            <p class="mb-1 small"><strong>Phone:</strong> <span id="modal-phone"></span></p>
                            <p class="mb-0 small"><strong>Address:</strong> <span id="modal-address"></span></p>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="p-3 rounded-3 border h-100">
                            <div class="fw-bold mb-2">💳 Order Info</div>
                            <p class="mb-1 small"><strong>Total:</strong> <span class="text-success fw-bold" id="modal-total"></span></p>
                            <p class="mb-1 small"><strong>Payment:</strong> <span id="modal-payment"></span></p>
                            <p class="mb-1 small"><strong>Date:</strong> <span id="modal-date"></span></p>
                            <p class="mb-0 small"><strong>Current Status:</strong>
                                <span class="spill" id="modal-status-badge"></span>
                            </p>
                        </div>
                    </div>
                </div>

                <!-- Order items loaded via AJAX -->
                <div class="mt-3 p-3 rounded-3 border" id="modal-items-wrap">
                    <div class="fw-bold mb-2">🛍 Items</div>
                    <div id="modal-items"><div class="text-center py-3"><div class="spinner-border text-success"></div></div></div>
                </div>

            </div>
            <div class="modal-footer border-0 pt-0">
                <a id="modal-track-link" href="#" target="_blank" class="btn btn-outline-success rounded-pill">
                    📍 Open Customer Tracking Page
                </a>
                <button class="btn btn-secondary rounded-pill" data-bs-dismiss="modal">Close</button>
            </div>
        </div>
    </div>
</div>

<script>
const stepLabels = ['Order Placed','Confirmed','Packed','Out for Delivery','Delivered'];
const spMap = {
    'Pending':'sp-pending','Order Placed':'sp-placed','Confirmed':'sp-confirmed',
    'Packed':'sp-packed','Out for Delivery':'sp-out',
    'Delivered':'sp-delivered','Cancelled':'sp-cancelled'
};
var currentModalOrderId = null;

// ── Update total badge ─────────────────────────────────────────────────────
function updateTotalBadge() {
    var visible = document.querySelectorAll('#ordersTable tbody tr:not([style*="display: none"])').length;
    document.getElementById('total-count-badge').textContent = visible + ' orders';
}
updateTotalBadge();

// ── Search ─────────────────────────────────────────────────────────────────
document.getElementById('searchInput').addEventListener('input', function() {
    filterTable();
});

// ── Filter tabs ────────────────────────────────────────────────────────────
document.querySelectorAll('.filter-tab').forEach(tab => {
    tab.addEventListener('click', function() {
        document.querySelectorAll('.filter-tab').forEach(t => t.classList.remove('active'));
        this.classList.add('active');
        filterTable();
    });
});

function filterTable() {
    var search  = document.getElementById('searchInput').value.toLowerCase();
    var filter  = document.querySelector('.filter-tab.active').dataset.filter;
    document.querySelectorAll('#ordersTable tbody tr').forEach(row => {
        var status   = row.dataset.status;
        var customer = row.dataset.customer || '';
        var orderId  = row.dataset.order || '';
        var matchFilter = (filter === 'all') || (status === filter);
        var matchSearch = !search || customer.includes(search) || orderId.includes(search);
        row.style.display = (matchFilter && matchSearch) ? '' : 'none';
    });
    updateTotalBadge();
}

// ── Quick save status (table row) ─────────────────────────────────────────
document.querySelectorAll('.save-status-btn').forEach(btn => {
    btn.addEventListener('click', function() {
        var id  = this.dataset.id;
        var sel = document.getElementById('select-' + id);
        var newStatus = sel.value;
        var label   = this.querySelector('.save-label');
        var spinner = this.querySelector('.save-spinner');
        label.style.display = 'none';
        spinner.style.display = 'inline-block';
        this.disabled = true;

        saveStatus(id, newStatus, () => {
            label.style.display = 'inline';
            spinner.style.display = 'none';
            this.disabled = false;
            // Update badge in row
            updateRowBadge(id, newStatus);
            showAdminToast('✅ Order #' + id + ' → ' + newStatus, 'success');
        });
    });
});

function saveStatus(orderId, status, cb) {
    var fd = new FormData();
    fd.append('ajax_update_status', '1');
    fd.append('order_id', orderId);
    fd.append('status', status);
    fetch('orders.php', { method:'POST', body: fd })
        .then(r => r.json())
        .then(data => { if(data.success && cb) cb(); })
        .catch(() => showAdminToast('❌ Network error', 'danger'));
}

function updateRowBadge(id, status) {
    var badge = document.getElementById('badge-' + id);
    if (!badge) return;
    badge.className = 'spill ' + (spMap[status] || 'sp-pending') + ' status-badge';
    badge.textContent = status;
    // Also update the tr data-status for filtering
    var row = document.querySelector('tr[data-order="' + id + '"]');
    if (row) row.dataset.status = status;
}

// ── Open order detail modal ────────────────────────────────────────────────
document.querySelectorAll('.view-order-btn').forEach(btn => {
    btn.addEventListener('click', function() {
        var id      = this.dataset.id;
        var status  = this.dataset.status;
        currentModalOrderId = id;

        document.getElementById('modal-order-title').textContent = 'Order #' + id;
        document.getElementById('modal-name').textContent    = this.dataset.name;
        document.getElementById('modal-email').textContent   = this.dataset.email;
        document.getElementById('modal-phone').textContent   = this.dataset.phone;
        document.getElementById('modal-address').textContent = this.dataset.address;
        document.getElementById('modal-payment').textContent = this.dataset.payment;
        document.getElementById('modal-total').textContent   = '₹' + this.dataset.total;
        document.getElementById('modal-date').textContent    = this.dataset.date;
        document.getElementById('modal-track-link').href     = '../order_details.php?id=' + id;

        // Status badge
        var sb = document.getElementById('modal-status-badge');
        sb.className = 'spill ' + (spMap[status] || 'sp-pending');
        sb.textContent = status;

        // Status select
        document.getElementById('modal-status-select').value = status;

        // Render stepper
        renderModalStepper(status);

        // Reset saved tick
        document.getElementById('modal-saved-tick').style.display = 'none';

        // Load items via AJAX
        document.getElementById('modal-items').innerHTML =
            '<div class="text-center py-3"><div class="spinner-border text-success"></div></div>';
        fetch('get_order_items.php?id=' + id)
            .then(r => r.json())
            .then(items => {
                if (!items.length) {
                    document.getElementById('modal-items').innerHTML = '<p class="text-muted small">No items found.</p>';
                    return;
                }
                var html = items.map(item => `
                    <div class="d-flex align-items-center gap-3 py-2 border-bottom">
                        <img src="${item.image_url}" width="48" height="48"
                             style="border-radius:8px;object-fit:cover;background:#f0f0f0"
                             onerror="this.src='https://via.placeholder.com/48?text=?'">
                        <div class="flex-grow-1">
                            <div class="fw-semibold small">${item.name}</div>
                            <div class="text-muted" style="font-size:0.78rem;">Qty: ${item.quantity} × ₹${parseFloat(item.price).toFixed(2)}</div>
                        </div>
                        <div class="fw-bold text-success small">₹${(item.quantity * item.price).toFixed(2)}</div>
                    </div>`).join('');
                document.getElementById('modal-items').innerHTML = html;
            })
            .catch(() => {
                document.getElementById('modal-items').innerHTML = '<p class="text-danger small">Failed to load items.</p>';
            });

        new bootstrap.Modal(document.getElementById('orderDetailModal')).show();
    });
});

// ── Stepper renderer ──────────────────────────────────────────────────────
function renderModalStepper(status) {
    var stepIdx = stepLabels.indexOf(status);
    if (stepIdx < 0) stepIdx = 0;
    var steps = document.querySelectorAll('#modal-stepper .ms-step');
    steps.forEach((s, i) => {
        s.classList.remove('done', 'active');
        var circle = s.querySelector('.ms-circle');
        var icons = ['📋','✅','📦','🚚','🏠'];
        if (i < stepIdx)       { s.classList.add('done');   circle.textContent = '✓'; }
        else if (i === stepIdx) { s.classList.add('active'); circle.textContent = icons[i]; }
        else                    { circle.textContent = icons[i]; }
    });
    // Progress bar width
    var total = stepLabels.length - 1;
    var pct = stepIdx === 0 ? 0 : (stepIdx / total) * (100 - 100/stepLabels.length);
    document.getElementById('ms-bar').style.width = 'calc(' + pct + '% )';
}

// ── Click on stepper step sets the select ────────────────────────────────
document.querySelectorAll('#modal-stepper .ms-step').forEach(step => {
    step.addEventListener('click', function() {
        var label = this.dataset.label;
        document.getElementById('modal-status-select').value = label;
        renderModalStepper(label);
    });
});

// ── Modal save button ─────────────────────────────────────────────────────
document.getElementById('modal-save-btn').addEventListener('click', function() {
    var newStatus = document.getElementById('modal-status-select').value;
    var id = currentModalOrderId;
    document.getElementById('modal-save-label').style.display = 'none';
    document.getElementById('modal-save-spinner').style.display = 'inline-block';
    this.disabled = true;

    saveStatus(id, newStatus, () => {
        document.getElementById('modal-save-label').style.display = 'inline';
        document.getElementById('modal-save-spinner').style.display = 'none';
        this.disabled = false;

        // Update status badge inside modal
        var sb = document.getElementById('modal-status-badge');
        sb.className = 'spill ' + (spMap[newStatus] || 'sp-pending');
        sb.textContent = newStatus;

        // Update row in table
        updateRowBadge(id, newStatus);
        document.getElementById('select-' + id).value = newStatus;

        // Re-render stepper
        renderModalStepper(newStatus);

        // Show tick
        var tick = document.getElementById('modal-saved-tick');
        tick.style.display = 'inline';
        setTimeout(() => tick.style.display = 'none', 3000);

        showAdminToast('✅ Order #' + id + ' updated to ' + newStatus, 'success');
    });
});

// ── Status select in modal → live preview stepper ─────────────────────────
document.getElementById('modal-status-select').addEventListener('change', function() {
    renderModalStepper(this.value);
});

// ── Toast ─────────────────────────────────────────────────────────────────
function showAdminToast(msg, type) {
    var toast = document.getElementById('adminToast');
    document.getElementById('adminToastMsg').textContent = msg;
    toast.className = 'toast align-items-center text-white border-0 bg-' + (type === 'success' ? 'success' : 'danger');
    new bootstrap.Toast(toast, { delay: 3000 }).show();
}
</script>

<?php include 'footer.php'; ?>
