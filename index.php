<?php

session_start();
require_once 'config/db.php';
include 'partials/header.php';

$sql = "SELECT p.*,
    COALESCE(AVG(r.rating),0) as avg_rating,
    COUNT(DISTINCT r.id) as review_count
    FROM products p
    LEFT JOIN reviews r ON r.product_id=p.id AND r.status='Approved'
    GROUP BY p.id
    ORDER BY p.created_at DESC";
$result = $conn->query($sql);
?>

<!-- Hero Banner -->
<div class="grocify-hero mb-5">
    <div class="hero-content">
        <h1 class="hero-title">🛒 Fresh Groceries,<br>Delivered Fast</h1>
        <p class="hero-sub">Farm-fresh fruits, vegetables, dairy & more — at your doorstep.</p>
        <a href="#products" class="btn btn-success btn-lg px-4 rounded-pill shadow">Shop Now ↓</a>
    </div>
</div>


<!-- Promo Strip -->
<div class="container mb-4">
<div class="row g-3 justify-content-center category-row">

<div class="col-md-4">
<div class="card feature-card border-0 shadow-sm p-3 text-center h-100">
<h5>🚚 Free Delivery</h5>
<small>On orders above ₹499</small>
</div>
</div>

<div class="col-md-4">
<div class="card feature-card border-0 shadow-sm p-3 text-center h-100">
<h5>⚡ 10 Minute Dispatch</h5>
<small>Fast grocery processing</small>
</div>
</div>

<div class="col-md-4">
<div class="card feature-card border-0 shadow-sm p-3 text-center h-100">
<h5>🎁 Daily Offers</h5>
<small>Fresh discounts every day</small>
</div>
</div>

</div>
</div>

<div class="container mb-4"><div class="row g-3"><div class="col-md-3 col-6">
    <div class="card deal-card bg-success text-white border-0 p-3 h-100">
        <h5>🔥 Today's Deals</h5>
    </div>
</div>

<div class="col-md-3 col-6">
    <div class="card deal-card bg-warning border-0 p-3 h-100">
        <h5>⭐ Best Sellers</h5>
    </div>
</div>

<div class="col-md-3 col-6">
    <div class="card deal-card bg-info border-0 p-3 h-100">
        <h5>🥬 Farm Fresh</h5>
    </div>
</div>

<div class="col-md-3 col-6">
    <div class="card deal-card bg-danger text-white border-0 p-3 h-100">
        <h5>💥 Mega Offers</h5>
    </div>
</div></div></div>

<!-- ── CATEGORY STRIP ── -->
<style>
/* ── section wrapper ── */
.cat-section{padding:0 0 48px;}
.cat-section-head{display:flex;align-items:flex-end;justify-content:space-between;margin-bottom:28px;flex-wrap:wrap;gap:12px;}
.cat-section-head h2{font-size:1.75rem;font-weight:800;color:#0d1b0f;margin:0;line-height:1.15;}
.cat-section-head h2 span{color:#16a34a;}
.cat-count-badge{background:#f0fdf4;color:#15803d;border:1.5px solid #bbf7d0;border-radius:20px;padding:5px 14px;font-size:.78rem;font-weight:700;white-space:nowrap;}

/* ── horizontal scroll track ── */
.cat-track-wrap{position:relative;}
.cat-track{display:flex;gap:14px;overflow-x:auto;padding:6px 4px 18px;scroll-snap-type:x mandatory;-webkit-overflow-scrolling:touch;scrollbar-width:none;}
.cat-track::-webkit-scrollbar{display:none;}

/* ── individual tile ── */
.cat-tile{flex:0 0 auto;width:130px;scroll-snap-align:start;cursor:pointer;text-decoration:none;outline:none;}
.cat-tile-inner{
    border-radius:20px;
    padding:22px 12px 16px;
    text-align:center;
    position:relative;
    overflow:hidden;
    border:2px solid transparent;
    transition:transform .22s cubic-bezier(.34,1.56,.64,1), box-shadow .22s, border-color .22s;
    background:#fff;
    box-shadow:0 2px 10px rgba(0,0,0,.06);
}
.cat-tile:hover .cat-tile-inner{transform:translateY(-5px) scale(1.03);box-shadow:0 14px 32px rgba(0,0,0,.12);}
.cat-tile.active .cat-tile-inner{border-color:var(--ct-accent,#16a34a);box-shadow:0 0 0 4px rgba(22,163,74,.12),0 8px 24px rgba(0,0,0,.1);}

/* colored background blob */
.cat-tile-inner::before{
    content:'';
    position:absolute;top:-20px;right:-20px;
    width:70px;height:70px;border-radius:50%;
    background:var(--ct-blob,rgba(22,163,74,.08));
    transition:transform .3s;
}
.cat-tile:hover .cat-tile-inner::before{transform:scale(1.6);}

.cat-emoji{font-size:2.4rem;line-height:1;display:block;margin-bottom:10px;transition:transform .25s cubic-bezier(.34,1.56,.64,1);}
.cat-tile:hover .cat-emoji{transform:scale(1.18) rotate(-6deg);}
.cat-tile.active .cat-emoji{transform:scale(1.14);}

.cat-label{font-size:.82rem;font-weight:700;color:#1f2937;display:block;margin-bottom:3px;}
.cat-sub{font-size:.68rem;color:#9ca3af;display:block;}

/* active color dot */
.cat-dot{
    width:6px;height:6px;border-radius:50%;
    background:var(--ct-accent,#16a34a);
    margin:8px auto 0;
    opacity:0;transform:scale(0);
    transition:opacity .2s,transform .2s;
}
.cat-tile.active .cat-dot{opacity:1;transform:scale(1);}

/* scroll fade edges */
.cat-fade-l,.cat-fade-r{
    position:absolute;top:0;bottom:18px;width:40px;pointer-events:none;z-index:2;
}
.cat-fade-l{left:0;background:linear-gradient(to right,rgba(255,255,255,.9),transparent);}
.cat-fade-r{right:0;background:linear-gradient(to left,rgba(255,255,255,.9),transparent);}

/* ── "All products" sort bar under categories ── */
.sort-bar{display:flex;align-items:center;justify-content:space-between;flex-wrap:wrap;gap:10px;margin-bottom:24px;}
.sort-bar .active-cat-label{font-size:1.05rem;font-weight:700;color:#111827;}
.sort-bar .active-cat-label span{color:#16a34a;}
.sort-select{border:1.5px solid #e5e7eb;border-radius:10px;padding:7px 14px;font-size:.82rem;font-weight:600;color:#374151;background:#fff;cursor:pointer;outline:none;}
.sort-select:focus{border-color:#16a34a;}
</style>

<div class="container cat-section">
    <div class="cat-section-head">
        <h2>Browse by <span>Category</span></h2>
        <span class="cat-count-badge">7 categories</span>
    </div>

    <div class="cat-track-wrap">
        <div class="cat-fade-l"></div>
        <div class="cat-fade-r"></div>
        <div class="cat-track" id="catTrack">
<?php
$cats=[
  ['all',      '🛒','All Items',   'all products',  '#16a34a','rgba(22,163,74,.09)'],
  ['fruits',   '🍎','Fruits',      'fresh picks',   '#ef4444','rgba(239,68,68,.09)'],
  ['vegetables','🥦','Vegetables', 'garden fresh',  '#22c55e','rgba(34,197,94,.09)'],
  ['dairy',    '🥛','Dairy',       'farm to table', '#3b82f6','rgba(59,130,246,.09)'],
  ['bakery',   '🍞','Bakery',      'baked daily',   '#f97316','rgba(249,115,22,.09)'],
  ['grains',   '🌾','Grains',      'wholesome',     '#a16207','rgba(161,98,7,.09)'],
  ['pantry',   '🛍️','Pantry',     'essentials',    '#8b5cf6','rgba(139,92,246,.09)'],
  ['beverages','🧃','Beverages',   'stay fresh',    '#06b6d4','rgba(6,182,212,.09)'],
];
foreach($cats as $c):
?>
        <a class="cat-tile <?php echo $c[0]==='all'?'active':''; ?>"
           href="javascript:void(0)"
           data-cat="<?php echo $c[0]; ?>"
           data-label="<?php echo $c[2]; ?>"
           style="--ct-accent:<?php echo $c[4]; ?>;--ct-blob:<?php echo $c[5]; ?>;">
            <div class="cat-tile-inner">
                <span class="cat-emoji"><?php echo $c[1]; ?></span>
                <span class="cat-label"><?php echo $c[2]; ?></span>
                <span class="cat-sub"><?php echo $c[3]; ?></span>
                <div class="cat-dot"></div>
            </div>
        </a>
<?php endforeach; ?>
        </div><!-- /cat-track -->
    </div><!-- /cat-track-wrap -->

    <!-- sort bar -->
    <div class="sort-bar mt-3" id="featured">
        <div class="active-cat-label">Showing: <span id="activeCatLabel">All Items</span></div>
        <select class="sort-select" id="sortSelect" onchange="applyCatSort()">
            <option value="default">Sort: Default</option>
            <option value="price_asc">Price: Low → High</option>
            <option value="price_desc">Price: High → Low</option>
            <option value="rating">Top Rated</option>
        </select>
    </div>
</div><!-- /container -->

<script>
// wire up category tiles
document.querySelectorAll('.cat-tile').forEach(tile=>{
    tile.addEventListener('click',()=>{
        document.querySelectorAll('.cat-tile').forEach(t=>t.classList.remove('active'));
        tile.classList.add('active');
        const cat = tile.dataset.cat;
        const label = tile.dataset.label;
        document.getElementById('activeCatLabel').textContent = label;
        // filter products
        document.querySelectorAll('.product-item').forEach(p=>{
            const show = cat==='all' || p.dataset.cat===cat || p.dataset.cat.includes(cat);
            p.style.display = show ? '' : 'none';
        });
    });
});
function applyCatSort(){
    const val = document.getElementById('sortSelect').value;
    const grid = document.getElementById('product-grid');
    const items = [...grid.querySelectorAll('.product-item')];
    items.sort((a,b)=>{
        const pa = parseFloat(a.querySelector('[data-price]')?.dataset.price||0);
        const pb = parseFloat(b.querySelector('[data-price]')?.dataset.price||0);
        const ra = parseFloat(a.querySelector('[data-rating]')?.dataset.rating||0);
        const rb = parseFloat(b.querySelector('[data-rating]')?.dataset.rating||0);
        if(val==='price_asc') return pa-pb;
        if(val==='price_desc') return pb-pa;
        if(val==='rating') return rb-ra;
        return 0;
    });
    items.forEach(i=>grid.appendChild(i));
}
</script>

<!-- Products Grid -->

<h2 id="products" class="fw-bold mb-4">
⭐ Featured Products
</h2>
<div class="row g-4" id="product-grid">
<?php if ($result && $result->num_rows > 0): ?>
    <?php while($product = $result->fetch_assoc()): ?>
    <div class="col-6 col-md-4 col-lg-3 product-item" data-cat="<?php echo strtolower(trim($product['category'])); ?>" data-price="<?php echo $product['price']; ?>" data-rating="<?php echo round($product['avg_rating'],1); ?>">
        <div class="gcard" data-id="<?php echo $product['id']; ?>"
             data-name="<?php echo htmlspecialchars($product['name'], ENT_QUOTES); ?>"
             data-price="<?php echo $product['price']; ?>"
             data-desc="<?php echo htmlspecialchars($product['description'], ENT_QUOTES); ?>"
             data-img="<?php echo htmlspecialchars($product['image'], ENT_QUOTES); ?>"
             data-cat="<?php echo strtolower(trim($product['category'])); ?>">
            <!-- Badge -->
            <?php if(($product['discount'] ?? 0) > 0): ?>
            <div class="gcard-badge bg-danger">
                <?php echo $product['discount']; ?>% OFF
            </div>
            <?php else: ?>
            <div class="gcard-badge">
                Fresh
            </div>
            <?php endif; ?>

            <!-- Wishlist -->
            <?php if(isset($_SESSION['user_id'])): ?>
            <button class="btn btn-sm wishlist-btn position-absolute" 
                    style="top:8px;right:8px;z-index:5;background:rgba(255,255,255,0.9);border:none;border-radius:50%;width:32px;height:32px;padding:0;font-size:1rem;"
                    data-product-id="<?php echo $product['id']; ?>"
                    title="Add to Wishlist">🤍</button>
            <?php endif; ?>
            <!-- Image -->
            <div class="gcard-img-wrap">
                <img src="<?php echo htmlspecialchars($product['image']); ?>"
                     alt="<?php echo htmlspecialchars($product['name']); ?>"
                     class="gcard-img"
                     onerror="this.src='https://via.placeholder.com/300x200?text=No+Image'">
                <!-- Quick View overlay -->
                <div class="gcard-overlay">
                    <button class="btn btn-light btn-sm rounded-pill quick-view-btn"
                            data-id="<?php echo $product['id']; ?>">
                        👁 Quick View
                    </button>
                </div>
            </div>
            <!-- Body -->
            <div class="gcard-body">
                <h6 class="gcard-title"><?php echo htmlspecialchars($product['name']); ?></h6>
                <p class="gcard-desc"><?php echo htmlspecialchars(substr($product['description'], 0, 45)); ?>…</p>
                <?php if($product['review_count'] > 0): ?>
                <div class="d-flex align-items-center gap-1 mb-1" style="font-size:0.75rem;">
                    <span class="text-warning"><?php echo str_repeat('★', round($product['avg_rating'])); ?><?php echo str_repeat('☆', 5-round($product['avg_rating'])); ?></span>
                    <span class="text-muted">(<?php echo $product['review_count']; ?>)</span>
                </div>
                <?php endif; ?>
                <div class="gcard-footer">
                    <span class="gcard-price">₹<?php echo number_format($product['price'], 2); ?></span>
                    <div class="qty-ctrl" id="qty-ctrl-<?php echo $product['id']; ?>" style="display:none;">
                        <button class="qty-btn qty-minus" data-id="<?php echo $product['id']; ?>">−</button>
                        <span class="qty-val" id="qty-val-<?php echo $product['id']; ?>">1</span>
                        <button class="qty-btn qty-plus" data-id="<?php echo $product['id']; ?>">+</button>
                    </div>
                    <button class="add-btn" data-id="<?php echo $product['id']; ?>">
                        <span class="add-btn-icon">+</span>
                    </button>
                </div>
            </div>
        </div>
    </div>
    <?php endwhile; ?>
<?php else: ?>
    <div class="col-12">
        <div class="alert alert-info">No products available at the moment.</div>
    </div>
<?php endif; ?>
</div>

<!-- Quick View Modal -->
<div class="modal fade" id="quickViewModal" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered modal-lg">
        <div class="modal-content rounded-4 border-0 shadow-lg">
            <button type="button" class="btn-close position-absolute top-0 end-0 m-3 z-3"
                    data-bs-dismiss="modal"></button>
            <div class="row g-0">
                <div class="col-md-5">
                    <img id="qv-img" src="" alt=""
                         class="img-fluid rounded-start-4 w-100 h-100"
                         style="object-fit:cover; min-height:280px;"
                         onerror="this.src='https://via.placeholder.com/400x300?text=No+Image'">
                </div>
                <div class="col-md-7 p-4 d-flex flex-column justify-content-center">
                    <span class="badge bg-success-subtle text-success mb-2" style="width:fit-content;">✅ In Stock</span>
                    <h3 id="qv-name" class="fw-bold mb-1"></h3>
                    <p id="qv-desc" class="text-muted mb-3"></p>
                    <div class="d-flex align-items-center gap-3 mb-4">
                        <span id="qv-price" class="fs-3 fw-bold text-success"></span>
                        <span class="text-muted text-decoration-line-through small" id="qv-original"></span>
                    </div>
                    <!-- Qty selector inside modal -->
                    <div class="d-flex align-items-center gap-3 mb-3">
                        <div class="d-flex align-items-center border rounded-pill px-2" style="gap:8px;">
                            <button class="btn btn-sm p-1 border-0" id="qv-minus">−</button>
                            <span id="qv-qty" class="fw-bold px-2">1</span>
                            <button class="btn btn-sm p-1 border-0" id="qv-plus">+</button>
                        </div>
                        <button class="btn btn-success rounded-pill px-4 flex-grow-1" id="qv-add-btn">
                            🛒 Add to Cart
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Toast Notification -->
<div class="position-fixed bottom-0 end-0 p-3" style="z-index:9999">
    <div id="cartToast" class="toast align-items-center text-white bg-success border-0" role="alert">
        <div class="d-flex">
            <div class="toast-body fw-semibold" id="toast-msg">✅ Added to cart!</div>
            <button type="button" class="btn-close btn-close-white me-2 m-auto" data-bs-dismiss="toast"></button>
        </div>
    </div>
</div>

<script>
// ---- Quick View ----
var qvProductId = null;
var qvQty = 1;

document.querySelectorAll('.quick-view-btn').forEach(btn => {
    btn.addEventListener('click', function(e) {
        e.stopPropagation();
        var card = document.querySelector('.gcard[data-id="' + this.dataset.id + '"]');
        openQuickView(card);
    });
});

document.querySelectorAll('.gcard-img-wrap').forEach(wrap => {
    wrap.addEventListener('click', function() {
        var card = this.closest('.gcard');
        openQuickView(card);
    });
});

function openQuickView(card) {
    qvProductId = card.dataset.id;
    qvQty = 1;
    document.getElementById('qv-img').src = card.dataset.img;
    document.getElementById('qv-name').textContent = card.dataset.name;
    document.getElementById('qv-desc').textContent = card.dataset.desc;
    document.getElementById('qv-price').textContent = '₹' + parseFloat(card.dataset.price).toFixed(2);
    document.getElementById('qv-qty').textContent = 1;
    new bootstrap.Modal(document.getElementById('quickViewModal')).show();
}

document.getElementById('qv-minus').onclick = function() {
    if (qvQty > 1) { qvQty--; document.getElementById('qv-qty').textContent = qvQty; }
};
document.getElementById('qv-plus').onclick = function() {
    qvQty++;
    document.getElementById('qv-qty').textContent = qvQty;
};
document.getElementById('qv-add-btn').onclick = function() {
    addToCartQty(qvProductId, qvQty, this);
    bootstrap.Modal.getInstance(document.getElementById('quickViewModal')).hide();
};

// ---- Add to Cart (card button) ----
document.querySelectorAll('.add-btn').forEach(btn => {
    btn.addEventListener('click', function(e) {
        e.stopPropagation();
        var id = this.dataset.id;
        var qtyCtrl = document.getElementById('qty-ctrl-' + id);
        var qtyVal  = document.getElementById('qty-val-' + id);

        // Show quantity control, hide + button
        this.style.display = 'none';
        qtyCtrl.style.display = 'flex';

        addToCartQty(id, 1, this);
    });
});

document.querySelectorAll('.qty-plus').forEach(btn => {
    btn.addEventListener('click', function(e) {
        e.stopPropagation();
        var id = this.dataset.id;
        var qtyEl = document.getElementById('qty-val-' + id);
        var newQty = parseInt(qtyEl.textContent) + 1;
        qtyEl.textContent = newQty;
        fetch('cart_actions.php?action=update&id=' + id + '&qty=' + newQty)
            .then(r => r.json()).then(() => updateCartCount());
    });
});

document.querySelectorAll('.qty-minus').forEach(btn => {
    btn.addEventListener('click', function(e) {
        e.stopPropagation();
        var id = this.dataset.id;
        var qtyEl = document.getElementById('qty-val-' + id);
        var newQty = parseInt(qtyEl.textContent) - 1;
        if (newQty <= 0) {
            // Remove from cart, reset button
            fetch('cart_actions.php?action=remove&id=' + id)
                .then(r => r.json()).then(() => updateCartCount());
            document.getElementById('qty-ctrl-' + id).style.display = 'none';
            document.querySelector('.add-btn[data-id="' + id + '"]').style.display = 'flex';
        } else {
            qtyEl.textContent = newQty;
            fetch('cart_actions.php?action=update&id=' + id + '&qty=' + newQty)
                .then(r => r.json()).then(() => updateCartCount());
        }
    });
});

function addToCartQty(id, qty, btn) {
    fetch('cart_actions.php?action=add&id=' + id + '&qty=' + qty)
        .then(r => r.json())
        .then(data => {
            if (data.success) {
                updateCartCount();
                showToast('🛒 Added to cart!', 'success');
            }
        });
}

function updateCartCount() {
    fetch('cart_actions.php?action=get')
        .then(r => r.json())
        .then(cart => {
            var count = cart.reduce((s, i) => s + i.quantity, 0);
            var badge = document.getElementById('cart-count');
            if (badge) badge.textContent = count;
        });
}

function showToast(msg, type) {
    var toast = document.getElementById('cartToast');
    document.getElementById('toast-msg').textContent = msg;
    toast.className = 'toast align-items-center text-white border-0 bg-' + (type === 'success' ? 'success' : 'danger');
    new bootstrap.Toast(toast, {delay: 2500}).show();
}


document.querySelectorAll(".filter-pill").forEach(function(btn){
btn.addEventListener("click",function(){
document.querySelectorAll(".filter-pill").forEach(function(x){x.classList.remove("active");});
this.classList.add("active");
var cat=this.dataset.cat.toLowerCase();
document.querySelectorAll(".product-item").forEach(function(item){
var pcat=(item.dataset.cat||"").toLowerCase().trim();
item.style.display=(cat==="all"||pcat===cat)?"block":"none";
});
});
});
</script>

<?php include 'partials/footer.php'; ?>
