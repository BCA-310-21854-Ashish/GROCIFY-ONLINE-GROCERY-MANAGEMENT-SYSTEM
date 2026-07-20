<?php

session_start();
require_once 'config/db.php';
header('Content-Type: application/json');

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success'=>false,'msg'=>'Please login to submit a review']);
    exit();
}

$userId    = $_SESSION['user_id'];
$productId = intval($_POST['product_id'] ?? 0);
$rating    = intval($_POST['rating'] ?? 0);
$title     = trim($_POST['title'] ?? '');
$body      = trim($_POST['body'] ?? '');

if (!$productId || $rating < 1 || $rating > 5 || empty($body)) {
    echo json_encode(['success'=>false,'msg'=>'Please fill all fields with a valid rating']);
    exit();
}

// Check if user has ordered this product
$ordered = $conn->query("SELECT COUNT(*) FROM order_items oi 
    JOIN orders o ON oi.order_id=o.id 
    WHERE o.user_id=$userId AND oi.product_id=$productId AND o.status='Delivered'")->fetch_row()[0];

// Allow review even if not ordered (just mark as needing approval)
$safeTitle = $conn->real_escape_string($title);
$safeBody  = $conn->real_escape_string($body);

// Check for duplicate review
$existing = $conn->query("SELECT id FROM reviews WHERE user_id=$userId AND product_id=$productId")->num_rows;
if ($existing > 0) {
    echo json_encode(['success'=>false,'msg'=>'You already reviewed this product']);
    exit();
}

$conn->query("INSERT INTO reviews (product_id,user_id,rating,title,body) 
    VALUES ($productId,$userId,$rating,'$safeTitle','$safeBody')");

echo json_encode(['success'=>true,'msg'=>'Review submitted! It will appear after approval.']);
