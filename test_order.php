<?php

session_start();
require_once 'config/db.php';

echo "<h2>Test Order Insertion</h2>";

if (!isset($_SESSION['user_id'])) {
    die("<p style='color:red'>You are not logged in. Please login first.</p>");
}
if (empty($_SESSION['cart'])) {
    die("<p style='color:red'>Your cart is empty. Add items first.</p>");
}

$userId = $_SESSION['user_id'];
$total = 0;
foreach ($_SESSION['cart'] as $item) {
    $total += $item['price'] * $item['quantity'];
}

echo "<p>User ID: $userId</p>";
echo "<p>Total: $$total</p>";
echo "<p>Cart: <pre>" . print_r($_SESSION['cart'], true) . "</pre></p>";

// Try to insert order
$stmt = $conn->prepare("INSERT INTO orders (user_id, total_amount) VALUES (?, ?)");
if (!$stmt) {
    die("<p style='color:red'>Order prepare failed: " . $conn->error . "</p>");
}
$stmt->bind_param("id", $userId, $total);
if ($stmt->execute()) {
    $orderId = $conn->insert_id;
    echo "<p style='color:green'>Order inserted! ID: $orderId</p>";
    $stmt->close();

    // Insert items
    $stmtItem = $conn->prepare("INSERT INTO order_items (order_id, product_id, quantity, price) VALUES (?, ?, ?, ?)");
    if (!$stmtItem) {
        die("<p style='color:red'>Item prepare failed: " . $conn->error . "</p>");
    }
    foreach ($_SESSION['cart'] as $productId => $item) {
        $stmtItem->bind_param("iiid", $orderId, $productId, $item['quantity'], $item['price']);
        if ($stmtItem->execute()) {
            echo "<p style='color:green'>Item inserted: product $productId, qty {$item['quantity']}</p>";
        } else {
            echo "<p style='color:red'>Item insert failed: " . $stmtItem->error . "</p>";
        }
    }
    $stmtItem->close();
    echo "<p><strong>Order placed successfully! Check phpMyAdmin.</strong></p>";
} else {
    echo "<p style='color:red'>Order execute failed: " . $stmt->error . "</p>";
    $stmt->close();
}
?>