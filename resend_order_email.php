<?php

session_start();
require_once 'config/db.php';
require_once 'config/mail_helper.php';

// Only allow access if user is logged in (can be restricted to admin later)
if (!isset($_SESSION['user_id'])) {
    header('Location: auth/login.php');
    exit();
}

$message = '';
$messageType = '';

// Handle manual email resend
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['order_id'])) {
    $orderId = intval($_POST['order_id']);
    $userId = $_SESSION['user_id'];

    // Verify order belongs to user
    $stmt = $conn->prepare("SELECT * FROM orders WHERE id = ? AND user_id = ?");
    $stmt->bind_param("ii", $orderId, $userId);
    $stmt->execute();
    $order = $stmt->get_result()->fetch_assoc();
    $stmt->close();

    if ($order) {
        // Fetch order items
        $stmt = $conn->prepare("SELECT oi.quantity, oi.price, p.name FROM order_items oi JOIN products p ON oi.product_id = p.id WHERE oi.order_id = ?");
        $stmt->bind_param("i", $orderId);
        $stmt->execute();
        $itemsResult = $stmt->get_result();
        $items = [];
        while ($item = $itemsResult->fetch_assoc()) {
            $items[] = $item;
        }
        $stmt->close();

        // Prepare order details
        $orderDetailsForEmail = array(
            'items' => $items,
            'total' => $order['total_amount'],
            'address' => $order['billing_address'],
            'phone' => $order['billing_phone'],
            'order_link' => 'http://' . $_SERVER['HTTP_HOST'] . '/order_details.php?id=' . $orderId
        );

        // Send email
        if (sendOrderConfirmationEmail($order['billing_email'], $order['billing_name'], $orderId, $orderDetailsForEmail)) {
            $message = "✅ Confirmation email has been sent to " . htmlspecialchars($order['billing_email']);
            $messageType = 'success';
        } else {
            $message = "❌ Failed to send email. Please try again later.";
            $messageType = 'danger';
        }
    } else {
        $message = "❌ Order not found or access denied.";
        $messageType = 'danger';
    }
}

// Fetch user's recent orders
$userId = $_SESSION['user_id'];
$stmt = $conn->prepare("SELECT id, order_date, total_amount, status, billing_email FROM orders WHERE user_id = ? ORDER BY order_date DESC LIMIT 10");
$stmt->bind_param("i", $userId);
$stmt->execute();
$ordersResult = $stmt->get_result();
$orders = $ordersResult->fetch_all(MYSQLI_ASSOC);
$stmt->close();

include 'partials/header.php';
?>

<div class="container py-5">
    <div class="row">
        <div class="col-md-10 mx-auto">
            <h2 class="mb-4">📧 Resend Order Confirmation Email</h2>

            <?php if ($message): ?>
                <div class="alert alert-<?php echo $messageType; ?> alert-dismissible fade show" role="alert">
                    <?php echo $message; ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
            <?php endif; ?>

            <?php if (count($orders) > 0): ?>
                <div class="card shadow-sm">
                    <div class="card-header bg-success text-white">
                        <h5 class="mb-0">Your Orders</h5>
                    </div>
                    <div class="card-body p-0">
                        <div class="table-responsive">
                            <table class="table table-hover mb-0">
                                <thead class="table-light">
                                    <tr>
                                        <th>Order ID</th>
                                        <th>Date</th>
                                        <th>Amount</th>
                                        <th>Status</th>
                                        <th>Email</th>
                                        <th>Action</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($orders as $order): ?>
                                        <tr>
                                            <td>
                                                <strong>#<?php echo $order['id']; ?></strong>
                                            </td>
                                            <td>
                                                <?php echo date('d M Y, h:i A', strtotime($order['order_date'])); ?>
                                            </td>
                                            <td>
                                                <strong>₹<?php echo number_format($order['total_amount'], 2); ?></strong>
                                            </td>
                                            <td>
                                                <span class="badge bg-<?php 
                                                    echo match($order['status']) {
                                                        'Delivered' => 'success',
                                                        'Out for Delivery' => 'info',
                                                        'Packed' => 'warning',
                                                        'Confirmed' => 'primary',
                                                        default => 'secondary'
                                                    };
                                                ?>">
                                                    <?php echo htmlspecialchars($order['status']); ?>
                                                </span>
                                            </td>
                                            <td>
                                                <small><?php echo htmlspecialchars(substr($order['billing_email'], 0, 25) . '...'); ?></small>
                                            </td>
                                            <td>
                                                <form method="POST" style="display: inline;">
                                                    <input type="hidden" name="order_id" value="<?php echo $order['id']; ?>">
                                                    <button type="submit" class="btn btn-sm btn-outline-success" title="Resend confirmation email">
                                                        📨 Resend
                                                    </button>
                                                </form>
                                                <a href="order_details.php?id=<?php echo $order['id']; ?>" class="btn btn-sm btn-outline-primary">
                                                    👁️ View
                                                </a>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>

                <div class="alert alert-info mt-4">
                    <strong>💡 Tip:</strong> Use the "Resend" button to send the order confirmation email again to the registered email address. 
                    This is useful if the email was missed or needs to be viewed again.
                </div>
            <?php else: ?>
                <div class="alert alert-warning">
                    <strong>ℹ️ No Orders Found</strong><br>
                    You haven't placed any orders yet. Start shopping to receive confirmation emails!
                    <a href="index.php" class="btn btn-success mt-2">Browse Products</a>
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<?php include 'partials/footer.php'; ?>
