<?php

session_start();
require_once 'config/db.php';

echo "<h2>Debugging Order Issue</h2>";

// 1. Check if user logged in
echo "<p><strong>Session user_id:</strong> " . ($_SESSION['user_id'] ?? 'NOT SET') . "</p>";

// 2. Check cart contents
echo "<p><strong>Cart contents:</strong></p><pre>";
print_r($_SESSION['cart'] ?? []);
echo "</pre>";

// 3. Try a direct insert
if (isset($_SESSION['user_id']) && !empty($_SESSION['cart'])) {
    $userId = $_SESSION['user_id'];
    $total = 0;
    foreach ($_SESSION['cart'] as $item) {
        $total += $item['price'] * $item['quantity'];
    }
    
    echo "<p><strong>Calculated Total:</strong> $$total</p>";
    
    // Attempt insert
    $conn->begin_transaction();
    try {
        $stmt = $conn->prepare("INSERT INTO orders (user_id, total_amount) VALUES (?, ?)");
        $stmt->bind_param("id", $userId, $total);
        if ($stmt->execute()) {
            $orderId = $conn->insert_id;
            echo "<p style='color:green'><strong>Order inserted with ID:</strong> $orderId</p>";
            
            // Insert items
            $stmtItem = $conn->prepare("INSERT INTO order_items (order_id, product_id, quantity, price) VALUES (?, ?, ?, ?)");
            foreach ($_SESSION['cart'] as $productId => $item) {
                $stmtItem->bind_param("iiid", $orderId, $productId, $item['quantity'], $item['price']);
                if (!$stmtItem->execute()) {
                    throw new Exception("Item insert error: " . $stmtItem->error);
                }
            }
            $conn->commit();
            echo "<p style='color:green'>All items inserted. Transaction committed.</p>";
        } else {
            throw new Exception("Order insert error: " . $stmt->error);
        }
    } catch (Exception $e) {
        $conn->rollback();
        echo "<p style='color:red'>Error: " . $e->getMessage() . "</p>";
    }
} else {
    echo "<p style='color:orange'>You must be logged in and have items in cart to test.</p>";
}

// 4. Show current orders in DB
echo "<h3>Current Orders in Database:</h3>";
$result = $conn->query("SELECT * FROM orders");
if ($result->num_rows > 0) {
    while ($row = $result->fetch_assoc()) {
        echo "<pre>"; print_r($row); echo "</pre>";
    }
} else {
    echo "<p>No orders found in database.</p>";
}
?>