<?php

session_start();
require_once 'config/db.php';
require_once 'config/mail_helper.php';

if (!isset($_SESSION['user_id'])) { header('Location: auth/login.php'); exit(); }
if (empty($_SESSION['cart']))     { header('Location: cart.php'); exit(); }

$userId = $_SESSION['user_id'];
$stmt = $conn->prepare("SELECT username, email, phone, address FROM users WHERE id=?");
$stmt->bind_param("i", $userId); $stmt->execute();
$user = $stmt->get_result()->fetch_assoc(); $stmt->close();

// Build cart with GST
$subtotal = 0;
$cartItems = [];
foreach ($_SESSION['cart'] as $pid => $item) {
    $result = $conn->query(
    "SELECT stock_quantity FROM products WHERE id=" . intval($pid)
);

$pRow = $result ? $result->fetch_assoc() : null;
    $gstRate = 5;
    $base = $item['price'] * $item['quantity'];
    $gst  = round($base * $gstRate / 100, 2);
    $subtotal += $base;
    $cartItems[] = array_merge($item, ['id'=>$pid,'base'=>$base,'gst'=>$gst,'gst_rate'=>$gstRate]);
}

$gstTotal = round(array_sum(array_column($cartItems,'gst')), 2);
$coupon   = $_SESSION['coupon'] ?? null;
$discount = $coupon ? floatval($coupon['discount']) : 0;
$grandTotal = $subtotal + $gstTotal - $discount;

$error = '';

// Handle POST - place order after payment
if ($_SERVER['REQUEST_METHOD']==='POST' && !empty($_POST['payment_id'])) {
    $billingName    = trim($_POST['billing_name']);
        $billingEmail   = trim($_POST['billing_email']);
        $billingPhone   = trim($_POST['billing_phone']);
        $billingAddress = trim($_POST['billing_address']);
        $paymentMethod  = $_POST['payment_method'] ?? 'Online';
        $paymentId      = trim($_POST['payment_id']);
        $couponCode     = $_POST['coupon_code'] ?? '';
        $discountAmt    = floatval($_POST['discount_amount'] ?? 0);

        if (empty($billingName)||empty($billingEmail)||empty($billingPhone)||empty($billingAddress)) {
            $error = "Please fill all billing details.";
        } else {
            $stmt = $conn->prepare("INSERT INTO orders (user_id,total_amount,billing_name,billing_email,billing_phone,billing_address,payment_method,coupon_code,discount_amount,gst_amount,payment_id) VALUES (?,?,?,?,?,?,?,?,?,?,?)");
            $stmt->bind_param("idssssssdds",$userId,$grandTotal,$billingName,$billingEmail,$billingPhone,$billingAddress,$paymentMethod,$couponCode,$discountAmt,$gstTotal,$paymentId);
            if ($stmt->execute()) {
                $orderId = $conn->insert_id;
                $stmtItem = $conn->prepare("INSERT INTO order_items (order_id,product_id,quantity,price) VALUES (?,?,?,?)");
                $orderItemsForEmail = [];
                foreach ($_SESSION['cart'] as $pid => $item) {
                    $stmtItem->bind_param("iiid",$orderId,$pid,$item['quantity'],$item['price']);
                    $stmtItem->execute();
                    $conn->query("UPDATE products SET stock_quantity=GREATEST(0,stock_quantity-{$item['quantity']}) WHERE id=".intval($pid));
                    $orderItemsForEmail[] = ['name'=>$item['name'],'quantity'=>$item['quantity'],'price'=>$item['price']];
                }
                // Record coupon usage
                if ($couponCode && $coupon) {
                    $cRes = $conn->query("SELECT id FROM coupons WHERE code='".addslashes($couponCode)."'")->fetch_assoc();
                    if ($cRes) {
                        $cId = $cRes['id'];
                        $conn->query("INSERT IGNORE INTO coupon_usage (coupon_id,user_id,order_id) VALUES ($cId,$userId,$orderId)");
                        $conn->query("UPDATE coupons SET used_count=used_count+1 WHERE id=$cId");
                    }
            }
            // Email
            try {
                sendOrderConfirmationEmail($billingEmail, $billingName, $orderId, [
                    'items'=>$orderItemsForEmail,'total'=>$grandTotal,
                    'address'=>$billingAddress,'phone'=>$billingPhone,
                    'payment_method'=>$paymentMethod,'estimated_delivery'=>'2-3 business days',
                    'order_link'=>'http://'.$_SERVER['HTTP_HOST'].'/grocify/order_details.php?id='.$orderId
                ]);
            } catch(Exception $e) { /* silent */ }

            $_SESSION['cart']   = [];
            $_SESSION['coupon'] = null;
            unset($_SESSION['checkout_billing']);
            header("Location: payment/success.php?payment_id=".urlencode($paymentId)."&order_id=$orderId");
            exit();
        } else { $error = "Failed to save order. Please try again."; }
    }
}

// Handle redirect to demo payment
if ($_SERVER['REQUEST_METHOD']==='POST' && empty($_POST['payment_id'])) {
    $billingName    = trim($_POST['billing_name'] ?? '');
    $billingEmail   = trim($_POST['billing_email'] ?? '');
    $billingPhone   = trim($_POST['billing_phone'] ?? '');
    $billingAddress = trim($_POST['billing_address'] ?? '');
    if (empty($billingName)||empty($billingEmail)||empty($billingPhone)||empty($billingAddress)) {
        $error = "Please fill all billing details.";
    } else {
        $_SESSION['checkout_billing'] = [
            'name'=>$billingName,'email'=>$billingEmail,
            'phone'=>$billingPhone,'address'=>$billingAddress
        ];
        header("Location: payment/demo_payment.php?total=".urlencode($grandTotal));
        exit();
    }
}

include 'partials/header.php';
?>
<div class="row g-4">
    <!-- Billing Form -->
    <div class="col-md-7">
        <div class="card border-0 shadow-sm rounded-4">
            <div class="card-body p-4">
                <h5 class="fw-bold mb-4">📋 Billing Details</h5>
                <?php if ($error): ?><div class="alert alert-danger"><?php echo $error; ?></div><?php endif; ?>
                <form method="POST" id="checkoutForm">
                    <div class="mb-3">
                        <label class="form-label fw-semibold">Full Name</label>
                        <input type="text" name="billing_name" class="form-control"
                            value="<?php echo htmlspecialchars($_SESSION['checkout_billing']['name'] ?? $user['username']); ?>" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-semibold">Email</label>
                        <input type="email" name="billing_email" class="form-control"
                            value="<?php echo htmlspecialchars($_SESSION['checkout_billing']['email'] ?? $user['email']); ?>" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-semibold">Phone</label>
                        <input type="text" name="billing_phone" class="form-control"
                            value="<?php echo htmlspecialchars($_SESSION['checkout_billing']['phone'] ?? $user['phone']); ?>" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-semibold">Delivery Address</label>
                        <textarea name="billing_address" class="form-control" rows="3" required><?php echo htmlspecialchars($_SESSION['checkout_billing']['address'] ?? $user['address']); ?></textarea>
                    </div>

                    <!-- Coupon -->
                    <div class="mb-4">
                        <label class="form-label fw-semibold">🏷️ Coupon Code</label>
                        <div class="input-group">
                            <input type="text" id="couponInput" class="form-control" 
                                   placeholder="Enter coupon code"
                                   value="<?php echo htmlspecialchars($coupon['code'] ?? ''); ?>">
                            <button type="button" class="btn btn-outline-success" onclick="applyCoupon()">Apply</button>
                            <?php if($coupon): ?>
                            <a href="?remove_coupon=1" class="btn btn-outline-danger">✕</a>
                            <?php endif; ?>
                        </div>
                        <div id="couponMsg" class="small mt-1 <?php echo $coupon?'text-success':''; ?>">
                            <?php echo $coupon ? "✅ Coupon applied! Saving ₹".number_format($coupon['discount'],2) : ''; ?>
                        </div>
                    </div>

                    <button type="submit" class="btn btn-success btn-lg w-100 fw-bold py-3 rounded-4">
                        🔒 Proceed to Payment — ₹<?php echo number_format($grandTotal,2); ?>
                    </button>
                </form>
            </div>
        </div>
    </div>

    <!-- Order Summary -->
    <div class="col-md-5">
        <div class="card border-0 shadow-sm rounded-4">
            <div class="card-body p-4">
                <h5 class="fw-bold mb-4">🛒 Order Summary</h5>
                <?php foreach($cartItems as $item): ?>
                <div class="d-flex justify-content-between align-items-center mb-3">
                    <div class="d-flex align-items-center gap-3">
                        <img src="<?php echo htmlspecialchars($item['image'] ?? ''); ?>" 
                             width="48" height="48" style="object-fit:cover;border-radius:10px;"
                             onerror="this.src='https://via.placeholder.com/48?text=?'">
                        <div>
                            <div class="fw-semibold small"><?php echo htmlspecialchars($item['name']); ?></div>
                            <div class="text-muted x-small">Qty: <?php echo $item['quantity']; ?>
                                &nbsp;|&nbsp;GST: <?php echo $item['gst_rate']; ?>%</div>
                        </div>
                    </div>
                    <div class="fw-bold text-success">₹<?php echo number_format($item['base'],2); ?></div>
                </div>
                <?php endforeach; ?>
                <hr>
                <div class="d-flex justify-content-between text-muted mb-1"><span>Subtotal</span><span>₹<?php echo number_format($subtotal,2); ?></span></div>
                <div class="d-flex justify-content-between text-muted mb-1"><span>GST</span><span>₹<?php echo number_format($gstTotal,2); ?></span></div>
                <?php if($discount > 0): ?>
                <div class="d-flex justify-content-between text-success mb-1"><span>Discount (<?php echo htmlspecialchars($coupon['code']); ?>)</span><span>- ₹<?php echo number_format($discount,2); ?></span></div>
                <?php endif; ?>
                <hr>
                <div class="d-flex justify-content-between fw-bold fs-5">
                    <span>Total</span>
                    <span class="text-success">₹<?php echo number_format($grandTotal,2); ?></span>
                </div>
                <div class="text-muted small mt-3 text-center">
                    🔒 Secure payment &nbsp;|&nbsp; 🚚 2-3 day delivery
                </div>
            </div>
        </div>
    </div>
</div>

<script>
function applyCoupon() {
    const code = document.getElementById('couponInput').value.trim();
    const msg  = document.getElementById('couponMsg');
    if (!code) { msg.innerHTML='<span class="text-danger">Enter a coupon code.</span>'; return; }
    msg.innerHTML = '<span class="text-muted">Verifying...</span>';
    fetch('apply_coupon.php', {
        method:'POST',
        headers:{'Content-Type':'application/x-www-form-urlencoded'},
        body:`code=${encodeURIComponent(code)}&total=<?php echo $grandTotal; ?>`
    }).then(r=>r.json()).then(data => {
        if (data.success) {
            msg.innerHTML='<span class="text-success">'+data.msg+'</span>';
            setTimeout(()=>location.reload(), 1000);
        } else {
            msg.innerHTML='<span class="text-danger">'+data.msg+'</span>';
        }
    });
}
</script>

<?php 
// Remove coupon
if (isset($_GET['remove_coupon'])) { unset($_SESSION['coupon']); header('Location: new_checkout.php'); exit(); }
include 'partials/footer.php'; 
?>
