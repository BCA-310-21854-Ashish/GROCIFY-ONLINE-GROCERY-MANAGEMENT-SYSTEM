<?php

require_once '../config/db.php';
include 'header.php';

$msg = '';

// Handle assign delivery boy
if ($_SERVER['REQUEST_METHOD']==='POST' && isset($_POST['assign'])) {
    $orderId = intval($_POST['order_id']);
    $dbId    = intval($_POST['delivery_boy_id']);
    $conn->query("DELETE FROM delivery_assignments WHERE order_id=$orderId");
    $stmt = $conn->prepare("INSERT INTO delivery_assignments (order_id, delivery_boy_id) VALUES (?,?)");
    $stmt->bind_param("ii", $orderId, $dbId);
    $stmt->execute();
    $conn->query("UPDATE orders SET status='Out for Delivery' WHERE id=$orderId");
    $conn->query("UPDATE delivery_boys SET status='Busy' WHERE id=$dbId");
    $msg = "✅ Delivery boy assigned to Order #$orderId";
}

// Mark delivered
if (isset($_GET['mark_delivered'])) {
    $aId = intval($_GET['mark_delivered']);
    $conn->query("UPDATE delivery_assignments SET status='Delivered', delivered_at=NOW() WHERE id=$aId");
    $row = $conn->query("SELECT order_id, delivery_boy_id FROM delivery_assignments WHERE id=$aId")->fetch_assoc();
    if ($row) {
        $conn->query("UPDATE orders SET status='Delivered' WHERE id={$row['order_id']}");
        $conn->query("UPDATE delivery_boys SET status='Available', total_deliveries=total_deliveries+1 WHERE id={$row['delivery_boy_id']}");
    }
    $msg = "✅ Order marked as Delivered!";
}

// Add/Edit delivery boy
if ($_SERVER['REQUEST_METHOD']==='POST' && isset($_POST['save_boy'])) {
    $id     = intval($_POST['id']);
    $name   = $conn->real_escape_string(trim($_POST['name']));
    $phone  = $conn->real_escape_string(trim($_POST['phone']));
    $email  = $conn->real_escape_string(trim($_POST['email']));
    $vehicle= $conn->real_escape_string(trim($_POST['vehicle_number']));
    if ($id > 0) {
        $conn->query("UPDATE delivery_boys SET name='$name',phone='$phone',email='$email',vehicle_number='$vehicle' WHERE id=$id");
        $msg = "Delivery boy updated.";
    } else {
        $conn->query("INSERT INTO delivery_boys (name,phone,email,vehicle_number) VALUES ('$name','$phone','$email','$vehicle')");
        $msg = "New delivery boy added.";
    }
}

// Toggle status
if (isset($_GET['toggle'])) {
    $id = intval($_GET['toggle']);
    $conn->query("UPDATE delivery_boys SET status = CASE WHEN status='Available' THEN 'Offline' ELSE 'Available' END WHERE id=$id");
    header('Location: delivery_boys.php'); exit();
}
// Delete boy
if (isset($_GET['delete_boy'])) {
    $id = intval($_GET['delete_boy']);
    $conn->query("DELETE FROM delivery_boys WHERE id=$id");
    header('Location: delivery_boys.php?msg=deleted'); exit();
}

if (isset($_GET['msg']) && $_GET['msg']==='deleted') $msg = 'Delivery boy removed.';

$editBoy = null;
if (isset($_GET['edit'])) {
    $id = intval($_GET['edit']);
    $editBoy = $conn->query("SELECT * FROM delivery_boys WHERE id=$id")->fetch_assoc();
}

$boys = $conn->query("SELECT
    id,name,phone,vehicle_number,status,
    IFNULL(rating,5.0) AS rating,
    (SELECT COUNT(*) FROM delivery_assignments WHERE delivery_boy_id=delivery_boys.id AND status='Delivered') AS completed
    FROM delivery_boys ORDER BY status ASC, name ASC");
if(!$boys){
    die("SQL Error: ".$conn->error);
}

$activeAssignments = $conn->query("
    SELECT da.*, o.total_amount, o.order_date, o.billing_address,
           db.name as boy_name, db.phone as boy_phone, u.username
    FROM delivery_assignments da
    JOIN orders o ON da.order_id = o.id
    JOIN delivery_boys db ON da.delivery_boy_id = db.id
    JOIN users u ON o.user_id = u.id
    WHERE da.status != 'Delivered'
    ORDER BY da.assigned_at DESC
");

$pendingOrders = $conn->query("
    SELECT o.*, u.username FROM orders o JOIN users u ON o.user_id=u.id
    WHERE o.status IN ('Processing','Shipped')
    AND o.id NOT IN (SELECT order_id FROM delivery_assignments WHERE status != 'Delivered')
    ORDER BY o.order_date ASC LIMIT 20
");

$boyList = $conn->query("SELECT id,name,status FROM delivery_boys WHERE status='Available' ORDER BY name");
?>

<div class="d-flex justify-content-between align-items-center mb-4">
    <h2 class="fw-bold mb-0">🛵 Delivery Boy Module</h2>
    <button class="btn btn-success" data-bs-toggle="modal" data-bs-target="#addBoyModal">+ Add Delivery Boy</button>
</div>
<?php if ($msg): ?><div class="alert alert-success"><?php echo $msg; ?></div><?php endif; ?>

<!-- Add/Edit Modal -->
<div class="modal fade" id="addBoyModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content rounded-4">
            <div class="modal-header">
                <h5 class="modal-title"><?php echo $editBoy ? 'Edit' : 'Add'; ?> Delivery Boy</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form method="POST">
                <div class="modal-body">
                    <input type="hidden" name="save_boy" value="1">
                    <input type="hidden" name="id" value="<?php echo $editBoy['id'] ?? 0; ?>">
                    <div class="mb-3"><label class="form-label">Full Name</label>
                        <input type="text" name="name" class="form-control" value="<?php echo htmlspecialchars($editBoy['name'] ?? ''); ?>" required></div>
                    <div class="mb-3"><label class="form-label">Phone</label>
                        <input type="text" name="phone" class="form-control" value="<?php echo htmlspecialchars($editBoy['phone'] ?? ''); ?>" required></div>
                    <div class="mb-3"><label class="form-label">Email (optional)</label>
                        <input type="email" name="email" class="form-control" value="<?php echo htmlspecialchars($editBoy['email'] ?? ''); ?>"></div>
                    <div class="mb-3"><label class="form-label">Vehicle Number</label>
                        <input type="text" name="vehicle_number" class="form-control" value="<?php echo htmlspecialchars($editBoy['vehicle_number'] ?? ''); ?>" placeholder="MH-01-AB-1234"></div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-success"><?php echo $editBoy ? 'Update' : 'Add'; ?></button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Delivery Boys Table -->
<div class="card border-0 shadow-sm rounded-4 mb-4">
    <div class="card-header bg-transparent fw-bold border-0 pt-3 px-4">👤 Delivery Team</div>
    <table class="table table-hover align-middle mb-0">
        <thead class="table-dark">
            <tr><th>Name</th><th>Phone</th><th>Vehicle</th><th>Status</th><th>Deliveries</th><th>Rating</th><th>Actions</th></tr>
        </thead>
        <tbody>
        <?php while($b=$boys->fetch_assoc()):
            $badge = match($b['status']){'Available'=>'success','Busy'=>'warning text-dark',default=>'secondary'}; ?>
        <tr>
            <td><strong><?php echo htmlspecialchars($b['name']); ?></strong></td>
            <td><?php echo $b['phone']; ?></td>
            <td><code><?php echo $b['vehicle_number'] ?: '—'; ?></code></td>
            <td><span class="badge bg-<?php echo $badge; ?>"><?php echo $b['status']; ?></span></td>
            <td><span class="badge bg-primary"><?php echo $b['completed']; ?> done</span></td>
            <td><span class="text-warning">★</span> <?php echo $b['rating']; ?></td>
            <td class="d-flex gap-1">
                <a href="?toggle=<?php echo $b['id']; ?>" class="btn btn-sm btn-outline-warning">Toggle</a>
                <a href="?edit=<?php echo $b['id']; ?>" class="btn btn-sm btn-outline-primary" 
                   data-bs-toggle="modal" data-bs-target="#addBoyModal">Edit</a>
                <a href="?delete_boy=<?php echo $b['id']; ?>" class="btn btn-sm btn-outline-danger"
                   onclick="return confirm('Remove this delivery boy?')">🗑</a>
            </td>
        </tr>
        <?php endwhile; ?>
        </tbody>
    </table>
</div>

<!-- Assign Orders -->
<div class="row g-4">
    <div class="col-md-5">
        <div class="card border-0 shadow-sm rounded-4">
            <div class="card-header bg-transparent fw-bold border-0 pt-3 px-4">📋 Assign Pending Orders</div>
            <div class="card-body">
                <form method="POST">
                    <input type="hidden" name="assign" value="1">
                    <div class="mb-3"><label class="form-label">Order</label>
                        <select name="order_id" class="form-select" required>
                            <option value="">-- Select Order --</option>
                            <?php while($o=$pendingOrders->fetch_assoc()): ?>
                            <option value="<?php echo $o['id']; ?>">
                                #<?php echo $o['id']; ?> — <?php echo htmlspecialchars($o['username']); ?> 
                                (₹<?php echo number_format($o['total_amount'],0); ?>)
                            </option>
                            <?php endwhile; ?>
                        </select>
                    </div>
                    <div class="mb-3"><label class="form-label">Delivery Boy</label>
                        <select name="delivery_boy_id" class="form-select" required>
                            <option value="">-- Select Available Boy --</option>
                            <?php while($b=$boyList->fetch_assoc()): ?>
                            <option value="<?php echo $b['id']; ?>"><?php echo htmlspecialchars($b['name']); ?></option>
                            <?php endwhile; ?>
                        </select>
                    </div>
                    <button type="submit" class="btn btn-primary w-100">Assign Delivery</button>
                </form>
            </div>
        </div>
    </div>

    <div class="col-md-7">
        <div class="card border-0 shadow-sm rounded-4">
            <div class="card-header bg-transparent fw-bold border-0 pt-3 px-4">🚴 Active Assignments</div>
            <?php if($activeAssignments->num_rows > 0): ?>
            <table class="table table-sm align-middle mb-0">
                <thead class="table-light"><tr><th>Order</th><th>Customer</th><th>Boy</th><th>Status</th><th>Action</th></tr></thead>
                <tbody>
                <?php while($a=$activeAssignments->fetch_assoc()): ?>
                <tr>
                    <td>#<?php echo $a['order_id']; ?><br><small class="text-muted">₹<?php echo number_format($a['total_amount'],0); ?></small></td>
                    <td><?php echo htmlspecialchars($a['username']); ?></td>
                    <td><?php echo htmlspecialchars($a['boy_name']); ?><br><small class="text-muted"><?php echo $a['boy_phone']; ?></small></td>
                    <td><span class="badge bg-warning text-dark"><?php echo $a['status']; ?></span></td>
                    <td><a href="?mark_delivered=<?php echo $a['id']; ?>" class="btn btn-sm btn-success"
                           onclick="return confirm('Mark as Delivered?')">✓ Delivered</a></td>
                </tr>
                <?php endwhile; ?>
                </tbody>
            </table>
            <?php else: ?>
            <div class="p-4 text-muted text-center">No active deliveries right now.</div>
            <?php endif; ?>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<?php include 'footer.php'; ?>
