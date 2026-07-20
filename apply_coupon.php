<?php

session_start();
require_once 'config/db.php';
header('Content-Type: application/json');

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success'=>false,'msg'=>'Login required']); exit();
}

$code    = strtoupper(trim($_POST['code'] ?? ''));
$total   = floatval($_POST['total'] ?? 0);
$userId  = $_SESSION['user_id'];

if (!$code) { echo json_encode(['success'=>false,'msg'=>'Enter a coupon code']); exit(); }

$safeCode = $conn->real_escape_string($code);
$coupon = $conn->query("SELECT * FROM coupons WHERE code='$safeCode' AND is_active=1")->fetch_assoc();

if (!$coupon) { echo json_encode(['success'=>false,'msg'=>'Invalid or expired coupon']); exit(); }
if ($coupon['expires_at'] && strtotime($coupon['expires_at']) < time()) {
    echo json_encode(['success'=>false,'msg'=>'Coupon has expired']); exit();
}
if ($coupon['used_count'] >= $coupon['max_uses']) {
    echo json_encode(['success'=>false,'msg'=>'Coupon usage limit reached']); exit();
}
if ($total < $coupon['min_order']) {
    echo json_encode(['success'=>false,'msg'=>'Min order ₹'.number_format($coupon['min_order'],0).' required']); exit();
}
// Check if user already used this coupon
$used = $conn->query("SELECT id FROM coupon_usage WHERE coupon_id={$coupon['id']} AND user_id=$userId")->num_rows;
if ($used > 0) { echo json_encode(['success'=>false,'msg'=>'You already used this coupon']); exit(); }

$discount = $coupon['type']==='percent' ? round($total * $coupon['value'] / 100, 2) : $coupon['value'];
$discount = min($discount, $total);

$_SESSION['coupon'] = ['code'=>$code,'discount'=>$discount,'coupon_id'=>$coupon['id']];

echo json_encode([
    'success'  => true,
    'discount' => $discount,
    'msg'      => "✅ Coupon applied! You save ₹" . number_format($discount, 2)
]);
