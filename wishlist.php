<?php

session_start();
require_once 'config/db.php';
include 'partials/header.php';

if (!isset($_SESSION['user_id'])) {
    header('Location: auth/login.php?redirect=wishlist.php');
    exit();
}
$userId = $_SESSION['user_id'];

// Remove from wishlist
if (isset($_GET['remove'])) {
    $pid = intval($_GET['remove']);
    $conn->query("DELETE FROM wishlist WHERE user_id=$userId AND product_id=$pid");
    header('Location: wishlist.php?msg=removed'); exit();
}

// Move to cart
if (isset($_GET['to_cart'])) {
    $pid = intval($_GET['to_cart']);
    $pResult = $conn->query("SELECT * FROM products WHERE id=$pid");
    if ($p = $pResult->fetch_assoc()) {
        if (!isset($_SESSION['cart'][$pid])) {
            $_SESSION['cart'][$pid] = ['name'=>$p['name'],'price'=>$p['price'],'quantity'=>1,'image'=>$p['image_url']];
        } else {
            $_SESSION['cart'][$pid]['quantity']++;
        }
        $conn->query("DELETE FROM wishlist WHERE user_id=$userId AND product_id=$pid");
        header('Location: wishlist.php?msg=carted'); exit();
    }
}

$msg = $_GET['msg'] ?? '';
$wishItems = $conn->query("
    SELECT p.*, w.added_at 
    FROM wishlist w 
    JOIN products p ON w.product_id=p.id 
    WHERE w.user_id=$userId 
    ORDER BY w.added_at DESC
");
?>
<h2 class="fw-bold mb-4">❤️ My Wishlist</h2>
<?php if ($msg === 'removed'): ?>
    <div class="alert alert-info">Item removed from wishlist.</div>
<?php elseif ($msg === 'carted'): ?>
    <div class="alert alert-success">Item moved to cart! <a href="cart.php">View Cart</a></div>
<?php endif; ?>

<?php if ($wishItems && $wishItems->num_rows > 0): ?>
<div class="row g-4">
<?php while($p = $wishItems->fetch_assoc()): ?>
<div class="col-6 col-md-4 col-lg-3">
    <div class="card h-100 border-0 shadow-sm rounded-4 overflow-hidden">
        <div class="position-relative">
            <img src="<?php echo htmlspecialchars($p['image_url']); ?>" class="card-img-top" style="height:180px;object-fit:cover;"
                 onerror="this.src='https://via.placeholder.com/300x180?text=No+Image'">
            <a href="wishlist.php?remove=<?php echo $p['id']; ?>" 
               class="position-absolute top-0 end-0 m-2 btn btn-sm btn-light rounded-circle shadow" title="Remove">❤️</a>
        </div>
        <div class="card-body d-flex flex-column">
            <h6 class="card-title fw-bold"><?php echo htmlspecialchars($p['name']); ?></h6>
            <p class="text-muted small mb-2"><?php echo htmlspecialchars(substr($p['description'],0,50)); ?>…</p>
            <div class="mt-auto">
                <div class="fw-bold text-success mb-2">₹<?php echo number_format($p['price'],2); ?></div>
                <?php $inStock = ($p['stock'] ?? 100) > 0; ?>
                <?php if($inStock): ?>
                    <a href="wishlist.php?to_cart=<?php echo $p['id']; ?>" 
                       class="btn btn-success btn-sm w-100">🛒 Move to Cart</a>
                <?php else: ?>
                    <button class="btn btn-secondary btn-sm w-100" disabled>Out of Stock</button>
                <?php endif; ?>
                <a href="wishlist.php?remove=<?php echo $p['id']; ?>" 
                   class="btn btn-outline-danger btn-sm w-100 mt-1">Remove</a>
            </div>
        </div>
        <div class="card-footer text-muted small bg-transparent border-0 pt-0">
            Added <?php echo date('d M Y', strtotime($p['added_at'])); ?>
        </div>
    </div>
</div>
<?php endwhile; ?>
</div>
<?php else: ?>
<div class="text-center py-5">
    <div style="font-size:4rem;">💔</div>
    <h4 class="mt-3 text-muted">Your wishlist is empty</h4>
    <p class="text-muted">Save products you love and buy them later.</p>
    <a href="index.php" class="btn btn-success px-4">Shop Now</a>
</div>
<?php endif; ?>

<?php include 'partials/footer.php'; ?>
