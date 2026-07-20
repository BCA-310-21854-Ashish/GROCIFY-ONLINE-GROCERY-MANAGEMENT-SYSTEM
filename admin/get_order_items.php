<?php

require_once '../config/db.php';
if (session_status() === PHP_SESSION_NONE) session_start();

// Admin only
if (!isset($_SESSION['is_admin']) || $_SESSION['is_admin'] != 1) {
    http_response_code(403);
    echo json_encode([]);
    exit();
}

$orderId = isset($_GET['id']) ? intval($_GET['id']) : 0;

if ($orderId <= 0) {
    echo json_encode([]);
    exit();
}

$stmt = $conn->prepare("
    SELECT oi.quantity, oi.price, p.name, p.image_url
    FROM order_items oi
    JOIN products p ON oi.product_id = p.id
    WHERE oi.order_id = ?
");
$stmt->bind_param("i", $orderId);
$stmt->execute();
$result = $stmt->get_result();

$items = [];
while ($row = $result->fetch_assoc()) {
    $items[] = $row;
}
$stmt->close();

header('Content-Type: application/json');
echo json_encode($items);
