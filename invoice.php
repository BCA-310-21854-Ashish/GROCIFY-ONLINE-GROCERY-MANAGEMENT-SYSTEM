<?php

session_start();
require_once 'config/db.php';
if (!isset($_SESSION['user_id'])) die('Login required');

$orderId = isset($_GET['id']) ? intval($_GET['id']) : 0;
$userId = $_SESSION['user_id'];

$stmt = $conn->prepare("SELECT * FROM orders WHERE id=? AND user_id=?");
$stmt->bind_param("ii",$orderId,$userId);
$stmt->execute();
$order=$stmt->get_result()->fetch_assoc();
if(!$order) die('Order not found');

$stmt = $conn->prepare("SELECT oi.quantity, oi.price, p.name FROM order_items oi JOIN products p ON oi.product_id=p.id WHERE oi.order_id=?");
$stmt->bind_param("i",$orderId);
$stmt->execute();
$items=$stmt->get_result();

header("Content-Type: text/html");
?>
<!DOCTYPE html>
<html><head><title>Invoice #<?php echo $orderId; ?></title>
<style>
body{font-family:Arial;padding:30px} table{width:100%;border-collapse:collapse}
th,td{border:1px solid #ccc;padding:8px} .btn{padding:10px 15px;background:#198754;color:#fff;text-decoration:none}
</style></head>
<body>
<h1>INVOICE</h1>
<p><b>Invoice No:</b> INV-<?php echo $orderId; ?><br>
<b>Date:</b> <?php echo $order['order_date']; ?></p>
<h3>Customer</h3>
<p><?php echo htmlspecialchars($order['billing_name']); ?><br><?php echo htmlspecialchars($order['billing_email']); ?><br><?php echo htmlspecialchars($order['billing_address']); ?></p>
<table>
<tr><th>Item</th><th>Qty</th><th>Price</th><th>Total</th></tr>
<?php while($i=$items->fetch_assoc()){ ?>
<tr><td><?php echo htmlspecialchars($i['name']); ?></td><td><?php echo $i['quantity']; ?></td><td>₹<?php echo number_format($i['price'],2); ?></td><td>₹<?php echo number_format($i['price']*$i['quantity'],2); ?></td></tr>
<?php } ?>
</table>
<h2 style="text-align:right">Grand Total: ₹<?php echo number_format($order['total_amount'],2); ?></h2>
<script>window.print();</script>
</body></html>