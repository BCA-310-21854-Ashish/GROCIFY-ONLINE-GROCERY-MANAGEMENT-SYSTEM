<?php

require_once '../config/db.php';
include 'header.php';

// Handle stock update
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_stock'])) {
    $id    = intval($_POST['product_id']);
    $stock = intval($_POST['stock']);
    $alert = intval($_POST['low_stock_alert']);
    $sku   = $conn->real_escape_string(trim($_POST['sku']));
    $conn->query("UPDATE products SET stock=$stock, low_stock_alert=$alert, sku='$sku' WHERE id=$id");
    $msg = 'Stock updated!';
}

$filter = isset($_GET['filter']) ? $_GET['filter'] : 'all';
$where = '';
if ($filter === 'low')      $where = 'WHERE p.stock > 0 AND p.stock <= p.low_stock_alert';
elseif ($filter === 'out')  $where = 'WHERE p.stock = 0';

$products = $conn->query("SELECT p.*, 
    COALESCE(SUM(oi.quantity),0) as total_sold
    FROM products p 
    LEFT JOIN order_items oi ON oi.product_id = p.id
    $where
    GROUP BY p.id ORDER BY p.stock ASC");
?>
<div class="d-flex justify-content-between align-items-center mb-4">
    <h2 class="fw-bold mb-0">📦 Stock Management</h2>
    <div class="btn-group">
        <a href="?filter=all" class="btn btn-sm <?php echo $filter=='all'?'btn-dark':'btn-outline-dark'; ?>">All</a>
        <a href="?filter=low" class="btn btn-sm <?php echo $filter=='low'?'btn-warning':'btn-outline-warning'; ?>">Low Stock</a>
        <a href="?filter=out" class="btn btn-sm <?php echo $filter=='out'?'btn-danger':'btn-outline-danger'; ?>">Out of Stock</a>
    </div>
</div>

<?php if (isset($msg)): ?><div class="alert alert-success"><?php echo $msg; ?></div><?php endif; ?>

<div class="card shadow-sm">
<table class="table table-hover align-middle mb-0">
<thead class="table-dark">
    <tr>
        <th>Product</th><th>SKU</th><th>Category</th><th>Price</th>
        <th>Stock</th><th>Sold</th><th>Status</th><th>Action</th>
    </tr>
</thead>
<tbody>
<?php while($p = $products->fetch_assoc()):
    $low_stock_alert = isset($p['low_stock_alert']) ? (int)$p['low_stock_alert'] : 10;
    $status = $p['stock']==0 ? 'Out of Stock' : ($p['stock'] <= $low_stock_alert ? 'Low Stock' : 'In Stock');
    $badge  = $p['stock']==0 ? 'danger' : ($p['stock'] <= $low_stock_alert ? 'warning text-dark' : 'success');
?>
<tr>
    <td>
        <div class="d-flex align-items-center gap-2">
            <img src="<?php echo htmlspecialchars($p['image_url']); ?>" width="40" height="40" 
                 style="object-fit:cover;border-radius:8px;" onerror="this.src='https://via.placeholder.com/40'">
            <strong><?php echo htmlspecialchars($p['name']); ?></strong>
        </div>
    </td>
    <td><code><?php echo $p['sku'] ?: '—'; ?></code></td>
    <td><?php echo $p['category']; ?></td>
    <td>₹<?php echo number_format($p['price'],2); ?></td>
    <td>
        <div class="progress" style="height:6px;width:80px;">
            <div class="progress-bar bg-<?php echo $badge; ?>" style="width:<?php echo min(100, ($p['stock']/200)*100); ?>%"></div>
        </div>
        <small class="fw-bold"><?php echo $p['stock']; ?> units</small>
    </td>
    <td><?php echo $p['total_sold']; ?></td>
    <td><span class="badge bg-<?php echo $badge; ?>"><?php echo $status; ?></span></td>
    <td>
        <button class="btn btn-sm btn-outline-primary" data-bs-toggle="modal" 
                data-bs-target="#stockModal<?php echo $p['id']; ?>">Update</button>

        <!-- Modal -->
        <div class="modal fade" id="stockModal<?php echo $p['id']; ?>" tabindex="-1">
            <div class="modal-dialog">
                <div class="modal-content">
                    <div class="modal-header"><h5>Update Stock: <?php echo htmlspecialchars($p['name']); ?></h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                    </div>
                    <form method="POST">
                        <div class="modal-body">
                            <input type="hidden" name="product_id" value="<?php echo $p['id']; ?>">
                            <input type="hidden" name="update_stock" value="1">
                            <div class="mb-3"><label class="form-label">SKU</label>
                                <input type="text" name="sku" class="form-control" value="<?php echo htmlspecialchars($p['sku']); ?>"></div>
                            <div class="mb-3"><label class="form-label">Current Stock (units)</label>
                                <input type="number" name="stock" class="form-control" value="<?php echo $p['stock']; ?>" min="0" required></div>
                            <div class="mb-3"><label class="form-label">Low Stock Alert Level</label>
                                <input type="number" name="low_stock_alert" class="form-control" value="<?php echo isset($p['low_stock_alert']) ? $p['low_stock_alert'] : 10; ?>" min="1"></div>
                        </div>
                        <div class="modal-footer">
                            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                            <button type="submit" class="btn btn-primary">Save Changes</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </td>
</tr>
<?php endwhile; ?>
</tbody>
</table>
</div>

<?php include 'footer.php'; ?>
