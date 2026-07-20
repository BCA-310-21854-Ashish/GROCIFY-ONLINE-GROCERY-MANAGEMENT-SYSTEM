<?php

session_start();
require_once 'config/db.php';
include 'partials/header.php';

$id = isset($_GET['id']) ? intval($_GET['id']) : 0;
$product = $conn->query("SELECT p.*,
    COALESCE(AVG(r.rating),0) as avg_rating,
    COUNT(DISTINCT r.id) as review_count
    FROM products p
    LEFT JOIN reviews r ON r.product_id=p.id AND r.status='Approved'
    WHERE p.id=$id GROUP BY p.id")->fetch_assoc();

if (!$product) { echo '<div class="alert alert-danger">Product not found.</div>'; include 'partials/footer.php'; exit(); }

// Gallery images
$gallery = $conn->query("SELECT * FROM product_gallery WHERE product_id=$id ORDER BY sort_order ASC");
$galleryImages = [];
while($g=$gallery->fetch_assoc()) {
    $galleryImages[] = !empty($g['image']) ? $g['image'] : $g['image_url'];
}

if (empty($galleryImages)) {
    $galleryImages[] = $product['image'];
}

// Reviews
$reviews = $conn->query("SELECT r.*, u.username FROM reviews r JOIN users u ON r.user_id=u.id 
    WHERE r.product_id=$id AND r.status='Approved' ORDER BY r.created_at DESC LIMIT 10");

// Wishlist status
$wishlisted = false;
if (isset($_SESSION['user_id'])) {
    $uid = $_SESSION['user_id'];
    $wishlisted = $conn->query("SELECT id FROM wishlist WHERE user_id=$uid AND product_id=$id")->num_rows > 0;
}

$inStock = ($product['stock'] ?? 100) > 0;
?>

<div class="row g-5 mb-5">
    <!-- Gallery -->
    <div class="col-md-6">
        <div id="productCarousel" class="carousel slide rounded-4 overflow-hidden shadow" data-bs-ride="carousel">
            <div class="carousel-inner">
                <?php foreach($galleryImages as $i=>$img): ?>
                <div class="carousel-item <?php echo $i===0?'active':''; ?>">
                    <img src="<?php echo htmlspecialchars($img); ?>" class="d-block w-100" 
                         style="height:380px;object-fit:cover;" 
                         onerror="this.src='https://via.placeholder.com/600x380?text=No+Image'">
                </div>
                <?php endforeach; ?>
            </div>
            <?php if(count($galleryImages)>1): ?>
            <button class="carousel-control-prev" type="button" data-bs-target="#productCarousel" data-bs-slide="prev">
                <span class="carousel-control-prev-icon"></span>
            </button>
            <button class="carousel-control-next" type="button" data-bs-target="#productCarousel" data-bs-slide="next">
                <span class="carousel-control-next-icon"></span>
            </button>
            <?php endif; ?>
        </div>
        <!-- Thumbnail row -->
        <?php if(count($galleryImages)>1): ?>
        <div class="d-flex gap-2 mt-2 flex-wrap">
            <?php foreach($galleryImages as $i=>$img): ?>
            <img src="<?php echo htmlspecialchars($img); ?>" 
                 style="width:64px;height:64px;object-fit:cover;border-radius:8px;cursor:pointer;border:2px solid <?php echo $i===0?'#16a34a':'#e5e7eb'; ?>"
                 onclick="bootstrap.Carousel.getInstance(document.getElementById('productCarousel')).to(<?php echo $i; ?>)"
                 onerror="this.style.display='none'">
            <?php endforeach; ?>
        </div>
        <?php endif; ?>
    </div>

    <!-- Product Info -->
    <div class="col-md-6">
        <?php if(($product['discount'] ?? 0) > 0): ?>

<div class="mb-3">

<span class="badge bg-danger fs-6">

🔥 <?php echo $product['discount']; ?>% OFF

</span>

</div>

<?php endif; ?>
<?php if(($product['bestseller'] ?? 0)==1): ?>

<span class="badge bg-warning text-dark mb-3">

⭐ Best Seller

</span>

<?php endif; ?>
<?php

if(isset($product['created_at'])){

$date=strtotime($product['created_at']);

if(time()-$date<2592000){

?>

<span class="badge bg-primary mb-3">

🆕 New Arrival

</span>

<?php

}

}

?>
        <span class="badge bg-success-subtle text-success border border-success-subtle mb-2"><?php echo htmlspecialchars($product['category']); ?></span>
        <h1 class="fw-bold mb-2"><?php echo htmlspecialchars($product['name']); ?></h1>
        
        <!-- Rating -->
        <div class="d-flex align-items-center gap-2 mb-3">
            <div class="text-warning" style="font-size:1.2rem;">
                <?php for($i=1;$i<=5;$i++) echo $i<=round($product['avg_rating'])?'★':'☆'; ?>
            </div>
            <span class="text-muted"><?php echo number_format($product['avg_rating'],1); ?>/5 
                (<?php echo $product['review_count']; ?> review<?php echo $product['review_count']!=1?'s':''; ?>)</span>
        </div>

        <p class="text-muted mb-3"><?php echo nl2br(htmlspecialchars($product['description'])); ?></p>

        <?php if($product['sku']): ?>
        <div class="text-muted small mb-2">SKU: <code><?php echo $product['sku']; ?></code></div>
        <?php endif; ?>

        <!-- Price -->
        <div class="d-flex align-items-center gap-3 mb-3">
            <h2 class="fw-bold text-success mb-0">₹<?php echo number_format($product['price'],2); ?></h2>
            <?php $gst = $product['gst_rate'] ?? 5; ?>
            <span class="text-muted small">+ ₹<?php echo number_format($product['price']*$gst/100,2); ?> GST (<?php echo $gst; ?>%)</span>
        </div>

        <!-- Stock -->
        <div class="mb-4">
            <?php if(!$inStock): ?>
                <span class="badge bg-danger fs-6">Out of Stock</span>
            <?php elseif(($product['stock']??100) <= ($product['low_stock_alert']??10)): ?>
                <span class="badge bg-warning text-dark">⚠️ Only <?php echo $product['stock']; ?> left!</span>
            <?php else: ?>
                <span class="badge bg-success">✓ In Stock (<?php echo $product['stock']??'Available'; ?> units)</span>
            <?php endif; ?>
        </div>

        <!-- Actions -->
        <div class="d-flex gap-3 mb-4">
            <?php if($inStock): ?>
            <div class="mb-3">
<label class="fw-bold">Quantity</label>
<input type="number" class="form-control" value="1" min="1" max="10" id="qty" style="width:120px;">
</div>
<button class="btn btn-success btn-lg px-5 fw-bold add-btn" data-id="<?php echo $product['id']; ?>">
🛒 Add to Cart
</button>
<a href="checkout.php?buy_now=<?php echo $product['id']; ?>" class="btn btn-warning btn-lg fw-bold">⚡ Buy Now</a>
            <?php else: ?>
            <button class="btn btn-secondary btn-lg px-5 fw-bold" disabled>Out of Stock</button>
            <?php endif; ?>

            <?php if(isset($_SESSION['user_id'])): ?>
            <button class="btn btn-lg <?php echo $wishlisted?'btn-danger':'btn-outline-danger'; ?> wishlist-btn" 
                    id="wishlistBtn" data-product-id="<?php echo $product['id']; ?>">
                <?php echo $wishlisted?'❤️':'🤍'; ?> Wishlist
            </button>
            <?php else: ?>
            <a href="auth/login.php" class="btn btn-outline-danger btn-lg">🤍 Wishlist</a>
            <?php endif; ?>
        </div>

        <!-- GST Info box -->
        <div class="bg-light rounded-3 p-3 mb-3 small">
            <strong>💡 Price Breakup</strong>
            <div class="row mt-2">
                <div class="col-6">Base Price: ₹<?php echo number_format($product['price'],2); ?></div>
                <div class="col-6">GST (<?php echo $gst; ?>%): ₹<?php echo number_format($product['price']*$gst/100,2); ?></div>
                <div class="col-12 mt-1 fw-bold text-success">Total: ₹<?php echo number_format($product['price']*(1+$gst/100),2); ?></div>
            </div>
        </div>
<div class="alert alert-success rounded-4">
🚚 <strong>Free Delivery</strong> on orders above ₹499<br>
🔄 7-Day Easy Replacement<br>
🔒 100% Secure Payment
</div>
    </div>
</div>

<!-- Reviews Section -->
<div class="row g-4">
    <div class="col-md-4">
        <div class="card border-0 shadow-sm rounded-4 p-4 text-center">
            <div style="font-size:3rem;font-weight:800;color:#16a34a;"><?php echo number_format($product['avg_rating'],1); ?></div>
            <div class="text-warning fs-4 mb-1">
                <?php for($i=1;$i<=5;$i++) echo $i<=round($product['avg_rating'])?'★':'☆'; ?>
            </div>
            <div class="text-muted"><?php echo $product['review_count']; ?> reviews</div>
        </div>

        <?php if(isset($_SESSION['user_id'])): ?>
        <div class="card border-0 shadow-sm rounded-4 p-4 mt-3">
            <h6 class="fw-bold">Write a Review</h6>
            <div class="mb-2">
                <div id="starRating" class="d-flex gap-1" style="font-size:1.5rem;cursor:pointer;">
                    <?php for($i=1;$i<=5;$i++): ?>
                    <span data-val="<?php echo $i; ?>" class="star-pick text-warning">☆</span>
                    <?php endfor; ?>
                </div>
                <input type="hidden" id="reviewRating" value="0">
            </div>
            <input type="text" id="reviewTitle" class="form-control mb-2" placeholder="Review title (optional)">
            <textarea id="reviewBody" class="form-control mb-2" rows="3" placeholder="Share your experience..."></textarea>
            <button class="btn btn-success w-100" onclick="submitReview(<?php echo $product['id']; ?>)">Submit Review</button>
            <div id="reviewMsg" class="mt-2 small"></div>
        </div>
        <?php else: ?>
        <div class="alert alert-info mt-3 small"><a href="auth/login.php">Login</a> to write a review.</div>
        <?php endif; ?>
    </div>

    <div class="col-md-8">
        <h5 class="fw-bold mb-3">Customer Reviews</h5>
        <?php if($reviews && $reviews->num_rows > 0): ?>
            <?php while($rv=$reviews->fetch_assoc()): ?>
            <div class="card border-0 shadow-sm rounded-4 p-3 mb-3">
                <div class="d-flex justify-content-between align-items-start mb-2">
                    <div>
                        <div class="fw-bold"><?php echo htmlspecialchars($rv['username']); ?></div>
                        <div class="text-warning"><?php for($i=1;$i<=5;$i++) echo $i<=$rv['rating']?'★':'☆'; ?></div>
                    </div>
                    <span class="text-muted small"><?php echo date('d M Y', strtotime($rv['created_at'])); ?></span>
                </div>
                <?php if($rv['title']): ?><h6 class="mb-1"><?php echo htmlspecialchars($rv['title']); ?></h6><?php endif; ?>
                <p class="mb-0 text-muted small"><?php echo nl2br(htmlspecialchars($rv['body'])); ?></p>
            </div>
            <?php endwhile; ?>
        <?php else: ?>
            <div class="text-muted">No reviews yet. Be the first to review!</div>
        <?php endif; ?>
    </div>
</div>

<script>
// Star rating picker
let selectedRating = 0;
document.querySelectorAll('.star-pick').forEach(star => {
    star.addEventListener('mouseover', function() {
        let val = parseInt(this.dataset.val);
        document.querySelectorAll('.star-pick').forEach((s,i) => s.textContent = i<val?'★':'☆');
    });
    star.addEventListener('click', function() {
        selectedRating = parseInt(this.dataset.val);
        document.getElementById('reviewRating').value = selectedRating;
    });
});
document.getElementById('starRating')?.addEventListener('mouseleave', function() {
    document.querySelectorAll('.star-pick').forEach((s,i) => s.textContent = i<selectedRating?'★':'☆');
});

// Wishlist toggle
document.getElementById('wishlistBtn')?.addEventListener('click', function() {
    const btn = this;
    const pid = btn.dataset.productId;
    fetch('/grocify/wishlist_action.php', {
        method:'POST',
        headers:{'Content-Type':'application/x-www-form-urlencoded'},
        body:`product_id=${pid}&action=toggle`
    }).then(r=>r.json()).then(data => {
        if (data.redirect) { window.location.href='/grocify/'+data.redirect; return; }
        if (data.wishlisted) {
            btn.classList.remove('btn-outline-danger'); btn.classList.add('btn-danger');
            btn.innerHTML = '❤️ Wishlist';
        } else {
            btn.classList.remove('btn-danger'); btn.classList.add('btn-outline-danger');
            btn.innerHTML = '🤍 Wishlist';
        }
    });
});

function submitReview(productId) {
    const rating = document.getElementById('reviewRating').value;
    const title  = document.getElementById('reviewTitle').value;
    const body   = document.getElementById('reviewBody').value;
    const msgEl  = document.getElementById('reviewMsg');
    if (!rating || rating==0) { msgEl.innerHTML='<span class="text-danger">Please select a star rating.</span>'; return; }
    if (!body.trim()) { msgEl.innerHTML='<span class="text-danger">Please write your review.</span>'; return; }
    
    fetch('/grocify/submit_review.php', {
        method:'POST',
        headers:{'Content-Type':'application/x-www-form-urlencoded'},
        body:`product_id=${productId}&rating=${rating}&title=${encodeURIComponent(title)}&body=${encodeURIComponent(body)}`
    }).then(r=>r.json()).then(data => {
        if (data.success) {
            msgEl.innerHTML=`<span class="text-success">${data.msg}</span>`;
            document.getElementById('reviewBody').value='';
            document.getElementById('reviewTitle').value='';
            selectedRating=0;
            document.querySelectorAll('.star-pick').forEach(s=>s.textContent='☆');
        } else {
            msgEl.innerHTML=`<span class="text-danger">${data.msg}</span>`;
        }
    });
}
</script>
<hr class="my-5">

<h2 class="fw-bold mb-4">

You may also like

</h2>

<div class="row">

<?php

$cat=$product['category'];

$pid=$product['id'];

$res=$conn->query("SELECT * FROM products WHERE category='$cat' AND id!=$pid LIMIT 4");

while($p=$res->fetch_assoc()){

?>

<div class="col-md-3">

<div class="card shadow-sm">

<img src="<?php echo $p['image']; ?>"

class="card-img-top"

style="height:200px;object-fit:cover;">

<div class="card-body">

<h6>

<?php echo $p['name']; ?>

</h6>

<p class="fw-bold text-success">

₹<?php echo $p['price']; ?>

</p>

<a href="product.php?id=<?php echo $p['id']; ?>"

class="btn btn-success w-100">

View

</a>

</div>

</div>

</div>

<?php

}

?>

</div>
<?php include 'partials/footer.php'; ?>
