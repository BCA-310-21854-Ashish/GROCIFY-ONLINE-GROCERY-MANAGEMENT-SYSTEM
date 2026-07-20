<?php

session_start();
require_once 'config/db.php';

if (!isset($_SESSION['user_id'])) { header('Location: auth/login.php'); exit(); }

$orderId = isset($_GET['order_id']) ? intval($_GET['order_id']) : 0;
$userId  = $_SESSION['user_id'];
$isAdmin = isset($_SESSION['is_admin']) && $_SESSION['is_admin'];

$orderQuery = $conn->prepare("SELECT o.*, u.username, u.email, u.phone, u.address 
    FROM orders o JOIN users u ON o.user_id=u.id 
    WHERE o.id=? " . ($isAdmin ? '' : 'AND o.user_id=?'));
if ($isAdmin) {
    $orderQuery->bind_param("i", $orderId);
} else {
    $orderQuery->bind_param("ii", $orderId, $userId);
}
if (!$orderQuery->execute()) {
    die("Order Query Error: " . $orderQuery->error);
}

$result = $orderQuery->get_result();

if (!$result) {
    die("Result Error: " . $conn->error);
}

$order = $result->fetch_assoc();
if (!$order) { die('<div class="container mt-5"><div class="alert alert-danger">Order not found.</div></div>'); }

$items = $conn->query("
    SELECT oi.*, p.name, p.gst_rate, p.category
    FROM order_items oi
    JOIN products p ON oi.product_id=p.id
    WHERE oi.order_id=$orderId
");

if (!$items) {
    die("SQL Error: " . $conn->error);
}

$subtotal  = 0;
$gstTotal  = 0;
$itemsList = [];
while($item = $items->fetch_assoc()) {
    $base    = $item['price'] * $item['quantity'];
    $gstRate = $item['gst_rate'] ?? 5;
    $gst     = round($base * $gstRate / 100, 2);
    $subtotal += $base;
    $gstTotal += $gst;
    $itemsList[] = array_merge($item, ['base'=>$base,'gst'=>$gst,'gst_rate'=>$gstRate]);
}
$discount = $order['discount_amount'] ?? 0;
$grandTotal = $subtotal + $gstTotal - $discount;
$invoiceNo  = 'INV-' . date('Y') . '-' . str_pad($orderId, 5, '0', STR_PAD_LEFT);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>GST Invoice <?php echo $invoiceNo; ?> - Grocify</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        @media print { .no-print { display:none!important; } body { font-size:12px; } }
        .invoice-box { max-width:850px; margin:auto; }
        .brand-green { color:#16a34a; }
        .header-bg { background:linear-gradient(135deg,#16a34a,#15803d); }
        table th { background:#f1f5f9; }
        .gst-table td, .gst-table th { font-size:0.85rem; }
    </style>
</head>
<body class="bg-light py-4">
<div class="invoice-box card shadow border-0 rounded-4 p-4 p-md-5 mx-auto bg-white">
    <!-- Header -->
    <div class="header-bg text-white rounded-4 p-4 mb-4">
        <div class="row align-items-center">
            <div class="col-8">
                <h2 class="fw-bold mb-0">🛒 Grocify</h2>
                <div class="opacity-75 small">Fresh Groceries, Delivered Fast</div>
                <div class="mt-2 small">123 Market Street, Mumbai - 400001<br>GST No: 27AABCG1234K1Z5 | CIN: U52190MH2024</div>
            </div>
            <div class="col-4 text-end">
                <h5 class="fw-bold mb-1">TAX INVOICE</h5>
                <div class="small"><?php echo $invoiceNo; ?></div>
                <div class="small">Date: <?php echo date('d/m/Y', strtotime($order['order_date'])); ?></div>
            </div>
        </div>
    </div>

    <!-- Bill To / Order Info -->
    <div class="row g-3 mb-4">
        <div class="col-md-6">
            <div class="bg-light rounded-3 p-3">
                <div class="text-muted small fw-bold mb-2 text-uppercase">Bill To</div>
                <div class="fw-bold"><?php echo htmlspecialchars($order['billing_name'] ?: $order['username']); ?></div>
                <div class="small text-muted">
                    <?php echo htmlspecialchars($order['billing_email'] ?: $order['email']); ?><br>
                    <?php if($order['billing_phone'] ?: $order['phone']): ?>📞 <?php echo $order['billing_phone'] ?: $order['phone']; ?><br><?php endif; ?>
                    <?php echo nl2br(htmlspecialchars($order['billing_address'] ?: $order['address'] ?: '')); ?>
                </div>
            </div>
        </div>
        <div class="col-md-6">
            <div class="bg-light rounded-3 p-3">
                <div class="text-muted small fw-bold mb-2 text-uppercase">Order Details</div>
                <table class="table table-sm mb-0">
                    <tr><td class="text-muted">Order #</td><td class="fw-bold">#<?php echo $order['id']; ?></td></tr>
                    <tr><td class="text-muted">Invoice</td><td><?php echo $invoiceNo; ?></td></tr>
                    <tr><td class="text-muted">Payment</td><td><?php echo ucfirst($order['payment_method'] ?: 'Online'); ?></td></tr>
                    <tr><td class="text-muted">Status</td>
                        <td><span class="badge bg-<?php echo $order['status']==='Delivered'?'success':'warning text-dark'; ?>">
                            <?php echo $order['status']; ?></span></td>
                    </tr>
                </table>
            </div>
        </div>
    </div>

    <!-- Items Table -->
    <table class="table table-bordered align-middle mb-4">
        <thead>
            <tr class="text-center">
                <th class="text-start">#</th>
                <th class="text-start">Item</th>
                <th>HSN/SAC</th>
                <th>Qty</th>
                <th>Rate (₹)</th>
                <th>Taxable (₹)</th>
                <th>GST%</th>
                <th>GST (₹)</th>
                <th>Total (₹)</th>
            </tr>
        </thead>
        <tbody>
        <?php foreach($itemsList as $i => $item): ?>
        <tr>
            <td><?php echo $i+1; ?></td>
            <td>
                <div class="fw-semibold"><?php echo htmlspecialchars($item['name']); ?></div>
<?php if (!empty($item['sku'])): ?>
    <small class="text-muted">SKU: <?php echo htmlspecialchars($item['sku']); ?></small>
<?php endif; ?>
            </td>
            <td class="text-center text-muted small">0407</td>
            <td class="text-center"><?php echo $item['quantity']; ?></td>
            <td class="text-end">₹<?php echo number_format($item['price'],2); ?></td>
            <td class="text-end">₹<?php echo number_format($item['base'],2); ?></td>
            <td class="text-center"><?php echo $item['gst_rate']; ?>%</td>
            <td class="text-end text-danger">₹<?php echo number_format($item['gst'],2); ?></td>
            <td class="text-end fw-bold">₹<?php echo number_format($item['base']+$item['gst'],2); ?></td>
        </tr>
        <?php endforeach; ?>
        </tbody>
        <tfoot class="table-light">
            <tr><td colspan="5" class="text-end fw-bold">Subtotal</td>
                <td class="text-end" colspan="3">₹<?php echo number_format($subtotal,2); ?></td><td></td></tr>
            <tr><td colspan="5" class="text-end fw-bold text-danger">Total GST</td>
                <td class="text-end text-danger" colspan="3">₹<?php echo number_format($gstTotal,2); ?></td><td></td></tr>
            <?php if($discount > 0): ?>
            <tr><td colspan="5" class="text-end fw-bold text-success">Discount 
                <?php if($order['coupon_code']): ?>(<?php echo $order['coupon_code']; ?>)<?php endif; ?></td>
                <td class="text-end text-success" colspan="3">- ₹<?php echo number_format($discount,2); ?></td><td></td></tr>
            <?php endif; ?>
            <tr class="table-success"><td colspan="5" class="text-end fw-bold fs-5">Grand Total</td>
                <td colspan="4" class="text-end fw-bold fs-5">₹<?php echo number_format($grandTotal,2); ?></td></tr>
        </tfoot>
    </table>

    <!-- GST Summary -->
    <div class="row g-3 mb-4">
        <div class="col-md-6">
            <div class="border rounded-3 p-3 gst-table">
                <div class="fw-bold mb-2 text-uppercase small text-muted">GST Breakup</div>
                <table class="table table-sm mb-0">
                    <thead><tr><th>Rate</th><th>Taxable</th><th>CGST</th><th>SGST</th><th>Total GST</th></tr></thead>
                    <tbody>
                    <?php 
                    $gstGroups = [];
                    foreach($itemsList as $item) {
                        $rate = $item['gst_rate'];
                        if (!isset($gstGroups[$rate])) $gstGroups[$rate] = ['taxable'=>0,'gst'=>0];
                        $gstGroups[$rate]['taxable'] += $item['base'];
                        $gstGroups[$rate]['gst']     += $item['gst'];
                    }
                    foreach($gstGroups as $rate => $g): ?>
                    <tr>
                        <td><?php echo $rate; ?>%</td>
                        <td>₹<?php echo number_format($g['taxable'],2); ?></td>
                        <td>₹<?php echo number_format($g['gst']/2,2); ?></td>
                        <td>₹<?php echo number_format($g['gst']/2,2); ?></td>
                        <td>₹<?php echo number_format($g['gst'],2); ?></td>
                    </tr>
                    <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
        <div class="col-md-6">
            <div class="bg-success bg-opacity-10 border border-success rounded-3 p-3 text-center">
                <div class="text-muted small fw-bold">AMOUNT PAYABLE</div>
                <div class="display-5 fw-bold brand-green">₹<?php echo number_format($grandTotal,2); ?></div>
                <div class="text-muted small">(Including all taxes)</div>
                <?php if($order['payment_id']): ?>
                <div class="mt-2 small text-success">✓ Paid | TXN: <?php echo htmlspecialchars($order['payment_id']); ?></div>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <!-- Footer -->
    <div class="border-top pt-3 text-center text-muted small">
        <p class="mb-1">This is a computer-generated invoice and does not require a signature.</p>
        <p class="mb-0">Thank you for shopping with <strong class="brand-green">Grocify</strong>! 🛒</p>
    </div>

    <!-- Action Buttons -->
    <div class="no-print d-flex gap-2 justify-content-center mt-4">
        <button onclick="window.print()" class="btn btn-success px-4">🖨️ Print / Save PDF</button>
        <a href="orders.php" class="btn btn-outline-secondary">← Back to Orders</a>
    </div>
</div>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
