<?php

session_start();
echo "<h1>🩺 Grocify Order System Diagnostic</h1>";

// 1. Check PHP version and extensions
echo "<h3>1. PHP Environment</h3>";
echo "PHP Version: " . phpversion() . "<br>";
echo "MySQLi Extension: " . (extension_loaded('mysqli') ? '✅ Loaded' : '❌ Not loaded') . "<br>";
echo "Session Status: " . (session_status() === PHP_SESSION_ACTIVE ? '✅ Active' : '❌ Inactive') . "<br>";
echo "Session User ID: " . ($_SESSION['user_id'] ?? '❌ Not set') . "<br>";

// 2. Test database connection
echo "<h3>2. Database Connection</h3>";
require_once 'config/db.php';
if ($conn) {
    echo "✅ Connected to MySQL<br>";
    echo "Database: " . $conn->query("SELECT DATABASE()")->fetch_row()[0] . "<br>";
} else {
    echo "❌ Connection failed: " . $conn->connect_error . "<br>";
    exit;
}

// 3. Check if required tables exist and their engine
echo "<h3>3. Table Check</h3>";
$tables = ['users', 'products', 'orders', 'order_items'];
foreach ($tables as $table) {
    $result = $conn->query("SHOW TABLES LIKE '$table'");
    if ($result->num_rows > 0) {
        $engine = $conn->query("SELECT ENGINE FROM information_schema.TABLES WHERE TABLE_SCHEMA = 'grocify_db' AND TABLE_NAME = '$table'")->fetch_row()[0];
        echo "✅ $table exists (Engine: $engine)<br>";
    } else {
        echo "❌ $table does NOT exist<br>";
    }
}

// 4. Check foreign key relationships
echo "<h3>4. Foreign Key Check</h3>";
$fkQuery = "SELECT TABLE_NAME, COLUMN_NAME, CONSTRAINT_NAME, REFERENCED_TABLE_NAME, REFERENCED_COLUMN_NAME 
            FROM information_schema.KEY_COLUMN_USAGE 
            WHERE TABLE_SCHEMA = 'grocify_db' AND REFERENCED_TABLE_NAME IS NOT NULL";
$fkResult = $conn->query($fkQuery);
if ($fkResult->num_rows > 0) {
    while ($row = $fkResult->fetch_assoc()) {
        echo "✅ {$row['TABLE_NAME']}.{$row['COLUMN_NAME']} → {$row['REFERENCED_TABLE_NAME']}.{$row['REFERENCED_COLUMN_NAME']}<br>";
    }
} else {
    echo "❌ No foreign keys found. This may cause orphan records.<br>";
}

// 5. Show current users and products count
echo "<h3>5. Data Overview</h3>";
$userCount = $conn->query("SELECT COUNT(*) FROM users")->fetch_row()[0];
echo "Users: $userCount<br>";
$productCount = $conn->query("SELECT COUNT(*) FROM products")->fetch_row()[0];
echo "Products: $productCount<br>";
$orderCount = $conn->query("SELECT COUNT(*) FROM orders")->fetch_row()[0];
echo "Orders: $orderCount<br>";

// 6. Check session user existence in DB
echo "<h3>6. Session User Validation</h3>";
if (isset($_SESSION['user_id'])) {
    $uid = $_SESSION['user_id'];
    $stmt = $conn->prepare("SELECT username FROM users WHERE id = ?");
    $stmt->bind_param("i", $uid);
    $stmt->execute();
    $stmt->store_result();
    if ($stmt->num_rows > 0) {
        echo "✅ User ID $uid exists in users table<br>";
    } else {
        echo "❌ User ID $uid does NOT exist in users table. This is a critical error!<br>";
    }
    $stmt->close();
} else {
    echo "⚠️ No user logged in.<br>";
}

// 7. Show cart structure
echo "<h3>7. Cart Session</h3>";
echo "<pre>";
print_r($_SESSION['cart'] ?? 'Empty');
echo "</pre>";

// 8. Test direct order insertion with current user (if logged in)
echo "<h3>8. Direct Order Insertion Test</h3>";
if (isset($_SESSION['user_id']) && !empty($_SESSION['cart'])) {
    $userId = $_SESSION['user_id'];
    $total = 0;
    foreach ($_SESSION['cart'] as $item) {
        $total += $item['price'] * $item['quantity'];
    }
    echo "Would attempt to insert order for user $userId with total $$total<br>";
    // Actually try to insert a test order (will rollback)
    $conn->begin_transaction();
    try {
        $stmt = $conn->prepare("INSERT INTO orders (user_id, total_amount) VALUES (?, ?)");
        $stmt->bind_param("id", $userId, $total);
        if ($stmt->execute()) {
            $orderId = $conn->insert_id;
            echo "✅ Test order inserted with ID: $orderId<br>";
            // Insert one item
            $firstItem = reset($_SESSION['cart']);
            $productId = key($_SESSION['cart']);
            $stmtItem = $conn->prepare("INSERT INTO order_items (order_id, product_id, quantity, price) VALUES (?, ?, ?, ?)");
            $stmtItem->bind_param("iiid", $orderId, $productId, $firstItem['quantity'], $firstItem['price']);
            if ($stmtItem->execute()) {
                echo "✅ Test order item inserted<br>";
            } else {
                echo "❌ Order item insert failed: " . $stmtItem->error . "<br>";
            }
            $stmtItem->close();
        } else {
            echo "❌ Order insert failed: " . $stmt->error . "<br>";
        }
        $stmt->close();
        $conn->rollback();
        echo "🔄 Test transaction rolled back (no permanent changes).<br>";
    } catch (Exception $e) {
        $conn->rollback();
        echo "❌ Exception: " . $e->getMessage() . "<br>";
    }
} else {
    echo "⚠️ Need to be logged in and have items in cart to test insertion.<br>";
}

// 9. Show MySQL error log (last error)
echo "<h3>9. Last MySQL Error</h3>";
echo $conn->error ?: 'No errors reported.';

$conn->close();
?>