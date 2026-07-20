<?php

session_start();
require_once 'config/db.php';

echo "<h1>🛒 Checkout Debug Mode</h1>";

// 1. Check login
echo "<h3>1. Login Check</h3>";
if (!isset($_SESSION['user_id'])) {
    die("<p style='color:red'>❌ Not logged in. <a href='auth/login.php'>Login</a></p>");
}
echo "<p style='color:green'>✅ User ID: " . $_SESSION['user_id'] . "</p>";

// 2. Check cart
echo "<h3>2. Cart Contents</h3>";
if (empty($_SESSION['cart'])) {
    die("<p style='color:red'>❌ Cart is empty. <a href='index.php'>Add items</a></p>");
}
echo "<pre>";
print_r($_SESSION['cart']);
echo "</pre>";

// 3. Calculate total
$total = 0;
foreach ($_SESSION['cart'] as $item) {
    $total += $item['price'] * $item['quantity'];
}
echo "<h3>3. Calculated Total: $" . number_format($total, 2) . "</h3>";

// 4. Process if POST
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    echo "<h3>4. Processing Order...</h3>";
    $userId = $_SESSION['user_id'];

    // Insert order
    $stmt = $conn->prepare("INSERT INTO orders (user_id, total_amount) VALUES (?, ?)");
    if (!$stmt) {
        die("<p style='color:red'>❌ Prepare failed (orders): " . $conn->error . "</p>");
    }
    $stmt->bind_param("id", $userId, $total);
    if ($stmt->execute()) {
        $orderId = $conn->insert_id;
        echo "<p style='color:green'>✅ Order inserted. ID: $orderId</p>";
        $stmt->close();

        // Insert items
        $stmtItem = $conn->prepare("INSERT INTO order_items (order_id, product_id, quantity, price) VALUES (?, ?, ?, ?)");
        if (!$stmtItem) {
            echo "<p style='color:red'>❌ Prepare failed (items): " . $conn->error . "</p>";
            $conn->query("DELETE FROM orders WHERE id = $orderId");
            die();
        }

        $allItemsOk = true;
        foreach ($_SESSION['cart'] as $productId => $item) {
            $stmtItem->bind_param("iiid", $orderId, $productId, $item['quantity'], $item['price']);
            if ($stmtItem->execute()) {
                echo "<p style='color:green'>✅ Added product $productId (qty: {$item['quantity']})</p>";
            } else {
                echo "<p style='color:red'>❌ Failed to add product $productId: " . $stmtItem->error . "</p>";
                $allItemsOk = false;
                break;
            }
        }
        $stmtItem->close();

        if ($allItemsOk) {
            $_SESSION['cart'] = [];
            echo "<h2 style='color:green'>🎉 Order #$orderId completed successfully!</h2>";
            echo "<p><a href='dashboard.php'>Go to Dashboard</a> | <a href='index.php'>Continue Shopping</a></p>";
        } else {
            $conn->query("DELETE FROM orders WHERE id = $orderId");
            echo "<p style='color:red'>Order rolled back due to item errors.</p>";
        }
    } else {
        echo "<p style='color:red'>❌ Order insert failed: " . $stmt->error . "</p>";
        $stmt->close();
    }
} else {
    // Show order summary and form
    echo "<h3>Order Summary</h3>";
    echo "<table border='1' cellpadding='5'>";
    echo "<tr><th>Product</th><th>Price</th><th>Qty</th><th>Total</th></tr>";
    foreach ($_SESSION['cart'] as $item) {
        $itemTotal = $item['price'] * $item['quantity'];
        echo "<tr><td>{$item['name']}</td><td>\${$item['price']}</td><td>{$item['quantity']}</td><td>\${$itemTotal}</td></tr>";
    }
    echo "<tr><th colspan='3'>Total</th><th>\${$total}</th></tr>";
    echo "</table>";
    echo "<form method='post'>";
    echo "<button type='submit' style='padding:10px 20px; background:green; color:white; border:none;'>Place Order</button>";
    echo "</form>";
}

echo "<hr><p><a href='cart.php'>Back to Cart</a></p>";
?>