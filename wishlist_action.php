<?php

session_start();
require_once 'config/db.php';
header('Content-Type: application/json');

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success'=>false,'msg'=>'Login required','redirect'=>'auth/login.php']);
    exit();
}

$userId    = $_SESSION['user_id'];
$productId = intval($_POST['product_id'] ?? 0);
$action    = $_POST['action'] ?? 'toggle';

if ($action === 'toggle') {
    $check = $conn->query("SELECT id FROM wishlist WHERE user_id=$userId AND product_id=$productId");
    if ($check->num_rows > 0) {
        $conn->query("DELETE FROM wishlist WHERE user_id=$userId AND product_id=$productId");
        echo json_encode(['success'=>true,'wishlisted'=>false,'msg'=>'Removed from wishlist']);
    } else {
        $conn->query("INSERT IGNORE INTO wishlist (user_id, product_id) VALUES ($userId, $productId)");
        echo json_encode(['success'=>true,'wishlisted'=>true,'msg'=>'Added to wishlist']);
    }
} elseif ($action === 'check') {
    $check = $conn->query("SELECT id FROM wishlist WHERE user_id=$userId AND product_id=$productId");
    echo json_encode(['wishlisted'=>$check->num_rows > 0]);
}
