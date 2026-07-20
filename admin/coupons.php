<?php

require_once '../config/db.php';
include 'header.php';

$msg = '';

// Toggle active
if (isset($_GET['toggle'])) {
    $id = intval($_GET['toggle']);
    $conn->query("UPDATE coupons SET is_active = NOT is_active WHERE id=$id");
    header('Location: coupons.php?msg=toggled'); exit();
}
// Delete
if (isset($_GET['delete'])) {
    $id = intval($_GET['delete']);
    $conn->query("DELETE FROM coupons WHERE id=$id");
    header('Location: coupons.php?msg=deleted'); exit();
}
// Create
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $code    = strtoupper(trim($_POST['code']));
    $type    = $_POST['type'];
    $value   = floatval($_POST['value']);
    $min     = floatval($_POST['min_order']);
    $uses    = intval($_POST['max_uses']);
    $expires = $_POST['expires_at'] ?: null;
    $exp_val = $expires ? "'$expires'" : 'NULL';
    $stmt = $conn->prepare("INSERT INTO coupons (code,type,value,min_order,max_uses,expires_at) VALUES (?,?,?,?,?,$exp_val)");
    $stmt->bind_param("ssddi", $code, $type, $value, $min, $uses);
    if ($stmt->execute()) { $msg = "Coupon <strong>$code</strong> created!"; }
    else { $msg = "Error: Code already exists."; }
    $stmt->close();
}

if (isset($_GET['msg'])) {
    if ($_GET['msg']==='deleted') $msg = 'Coupon deleted.';
    if ($_GET['msg']==='toggled') $msg = 'Coupon status updated.';
}

$coupons = $conn->query("SELECT c.*, 
    (SELECT COUNT(*) FROM coupon_usage cu WHERE cu.coupon_id=c.id) as actual_used
    FROM coupons c ORDER BY c.created_at DESC");
?>
<h2 class="fw-bold mb-4">🏷️ Coupon Management</h2>
<?php if ($msg): ?><div class="alert alert-success"><?php echo $msg; ?></div><?php endif; ?>

<div class="row g-4">
    <div class="col-md-4">
        <div class="card shadow-sm border-0 rounded-4">
            <div class="card-header fw-bold">➕ Create Coupon</div>
            <div class="card-body">
                <form method="POST">
                    <div class="mb-3"><label class="form-label">Code</label>
                        <input type="text" name="code" class="form-control text-uppercase" placeholder="e.g. SAVE20" required></div>
                    <div class="mb-3"><label class="form-label">Discount Type</label>
                        <select name="type" class="form-select">
                            <option value="percent">Percentage (%)</option>
                            <option value="fixed">Fixed Amount (₹)</option>
                        </select>
                    </div>
                    <div class="mb-3"><label class="form-label">Discount Value</label>
                        <input type="number" name="value" step="0.01" class="form-control" placeholder="e.g. 10 or 50" required></div>
                    <div class="mb-3"><label class="form-label">Min Order Amount (₹)</label>
                        <input type="number" name="min_order" step="0.01" class="form-control" value="0"></div>
                    <div class="mb-3"><label class="form-label">Max Uses</label>
                        <input type="number" name="max_uses" class="form-control" value="100"></div>
                    <div class="mb-3"><label class="form-label">Expiry Date</label>
                        <input type="date" name="expires_at" class="form-control"></div>
                    <button type="submit" class="btn btn-primary w-100">Create Coupon</button>
                </form>
            </div>
        </div>
    </div>
    <div class="col-md-8">
        <div class="card shadow-sm border-0 rounded-4">
            <table class="table table-hover align-middle mb-0">
            <thead class="table-dark">
                <tr><th>Code</th><th>Type</th><th>Value</th><th>Min Order</th><th>Uses</th><th>Expires</th><th>Status</th><th>Actions</th></tr>
            </thead>
            <tbody>
            <?php while($c = $coupons->fetch_assoc()): 
                $expired = $c['expires_at'] && strtotime($c['expires_at']) < time();
            ?>
            <tr class="<?php echo $expired ? 'table-secondary' : ''; ?>">
                <td><code class="fs-6 fw-bold"><?php echo $c['code']; ?></code></td>
                <td><?php echo $c['type']==='percent' ? '%' : '₹'; ?></td>
                <td><?php echo $c['type']==='percent' ? $c['value'].'%' : '₹'.number_format($c['value'],2); ?></td>
                <td>₹<?php echo number_format($c['min_order'],0); ?></td>
                <td><?php echo $c['actual_used']; ?>/<?php echo $c['max_uses']; ?></td>
                <td><?php echo $c['expires_at'] ? date('d M Y', strtotime($c['expires_at'])) : '∞'; ?>
                    <?php if($expired): ?><span class="badge bg-secondary ms-1">Expired</span><?php endif; ?></td>
                <td>
                    <?php if($expired): ?>
                        <span class="badge bg-secondary">Expired</span>
                    <?php elseif($c['is_active']): ?>
                        <span class="badge bg-success">Active</span>
                    <?php else: ?>
                        <span class="badge bg-warning text-dark">Inactive</span>
                    <?php endif; ?>
                </td>
                <td class="d-flex gap-1">
                    <a href="?toggle=<?php echo $c['id']; ?>" class="btn btn-sm btn-outline-warning">Toggle</a>
                    <a href="?delete=<?php echo $c['id']; ?>" class="btn btn-sm btn-outline-danger"
                       onclick="return confirm('Delete coupon?')">🗑</a>
                </td>
            </tr>
            <?php endwhile; ?>
            </tbody>
            </table>
        </div>
    </div>
</div>
<?php include 'footer.php'; ?>
