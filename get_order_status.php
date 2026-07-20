<?php

// Public endpoint — returns only status for a specific order belonging to logged-in user
session_start();
require_once 'config/db.php';
header('Content-Type: application/json');

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['error' => 'Not logged in']);
    exit();
}

$orderId = isset($_GET['id']) ? intval($_GET['id']) : 0;
$userId  = $_SESSION['user_id'];

if ($orderId <= 0) {
    echo json_encode(['error' => 'Invalid order']);
    exit();
}

$stmt = $conn->prepare("SELECT status FROM orders WHERE id = ? AND user_id = ?");
$stmt->bind_param("ii", $orderId, $userId);
$stmt->execute();
$row = $stmt->get_result()->fetch_assoc();
$stmt->close();

if (!$row) {
    echo json_encode(['error' => 'Order not found']);
    exit();
}

echo json_encode(['status' => $row['status']]);
