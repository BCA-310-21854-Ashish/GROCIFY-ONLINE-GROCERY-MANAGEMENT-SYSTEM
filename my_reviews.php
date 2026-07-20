<?php

session_start();
require_once 'config/db.php';

if (!isset($_SESSION['user_id'])) {
    header('Location: auth/login.php');
    exit();
}

$userId = $_SESSION['user_id'];

// Fetch user's submitted reviews
$myReviews = $conn->query("
    SELECT r.*, p.name as product_name, p.image as product_image, p.image_url
    FROM reviews r
    JOIN products p ON r.product_id = p.id
    WHERE r.user_id = $userId
    ORDER BY r.created_at DESC
");

// Fetch delivered order items that haven't been reviewed yet
$pendingReviews = $conn->query("
    SELECT DISTINCT p.id as product_id, p.name as product_name, p.image as product_image, p.image_url, o.id as order_id
    FROM orders o
    JOIN order_items oi ON oi.order_id = o.id
    JOIN products p ON p.id = oi.product_id
    WHERE o.user_id = $userId
      AND o.status = 'Delivered'
      AND NOT EXISTS (
          SELECT 1 FROM reviews r WHERE r.user_id = $userId AND r.product_id = p.id
      )
    ORDER BY o.order_date DESC
    LIMIT 20
");

include 'partials/header.php';
?>

<style>
.reviews-hero { background:linear-gradient(135deg,#fef9c3,#fde68a); border-radius:18px; padding:28px 32px; margin-bottom:32px; }
.review-card { border:none; border-radius:16px; box-shadow:0 3px 14px rgba(0,0,0,0.07); transition:transform 0.2s; }
.review-card:hover { transform:translateY(-2px); }
.star-row button { background:none; border:none; font-size:1.6rem; color:#d1d5db; cursor:pointer; padding:0 3px; }
.star-row button.active { color:#f59e0b; }
.status-Pending  { background:#fff3cd; color:#856404; }
.status-Approved { background:#dcfce7; color:#14532d; }
.status-Rejected { background:#fee2e2; color:#991b1b; }
</style>

<div class="reviews-hero d-flex align-items-center gap-3">
    <div style="font-size:2.8rem;">⭐</div>
    <div>
        <h2 class="fw-bold mb-1">My Reviews</h2>
        <p class="mb-0 text-muted">Manage your product reviews and share new ones.</p>
    </div>
</div>

<!-- Pending Reviews (unreviewed delivered products) -->
<?php if ($pendingReviews && $pendingReviews->num_rows > 0): ?>
<div class="mb-5">
    <h5 class="fw-bold mb-3">✍️ Products Awaiting Your Review</h5>
    <div class="row g-3">
        <?php while ($item = $pendingReviews->fetch_assoc()):
            $img = !empty($item['product_image']) ? $item['product_image'] : ($item['image_url'] ?? '');
        ?>
        <div class="col-md-6 col-lg-4">
            <div class="card review-card p-3">
                <div class="d-flex gap-3 align-items-center mb-3">
                    <img src="<?php echo htmlspecialchars($img); ?>" width="56" height="56"
                         style="object-fit:cover;border-radius:10px;flex-shrink:0;"
                         onerror="this.src='https://via.placeholder.com/56x56?text=📦'">
                    <div>
                        <div class="fw-semibold"><?php echo htmlspecialchars($item['product_name']); ?></div>
                        <small class="text-muted">Order #<?php echo $item['order_id']; ?></small>
                    </div>
                </div>
                <button class="btn btn-outline-warning btn-sm rounded-pill"
                        onclick="openReviewModal(<?php echo $item['product_id']; ?>, '<?php echo addslashes(htmlspecialchars($item['product_name'])); ?>')">
                    ⭐ Write a Review
                </button>
            </div>
        </div>
        <?php endwhile; ?>
    </div>
</div>
<?php endif; ?>

<!-- My Submitted Reviews -->
<h5 class="fw-bold mb-3">📋 My Submitted Reviews</h5>
<?php if ($myReviews && $myReviews->num_rows > 0): ?>
<div class="row g-3">
    <?php while ($r = $myReviews->fetch_assoc()):
        $img = !empty($r['product_image']) ? $r['product_image'] : ($r['image_url'] ?? '');
        $statusClass = 'status-' . $r['status'];
    ?>
    <div class="col-md-6">
        <div class="card review-card p-4">
            <div class="d-flex gap-3 align-items-center mb-3">
                <img src="<?php echo htmlspecialchars($img); ?>" width="56" height="56"
                     style="object-fit:cover;border-radius:10px;flex-shrink:0;"
                     onerror="this.src='https://via.placeholder.com/56x56?text=📦'">
                <div>
                    <div class="fw-semibold"><?php echo htmlspecialchars($r['product_name']); ?></div>
                    <div class="text-warning">
                        <?php for($i=1;$i<=5;$i++) echo $i<=$r['rating']?'★':'☆'; ?>
                        <span class="text-muted small ms-1"><?php echo $r['rating']; ?>/5</span>
                    </div>
                    <small class="text-muted"><?php echo date('M d, Y', strtotime($r['created_at'])); ?></small>
                </div>
                <div class="ms-auto">
                    <span class="badge rounded-pill px-3 py-2 <?php echo $statusClass; ?>">
                        <?php echo $r['status']; ?>
                    </span>
                </div>
            </div>
            <?php if ($r['title']): ?>
                <div class="fw-semibold mb-1"><?php echo htmlspecialchars($r['title']); ?></div>
            <?php endif; ?>
            <p class="text-muted small mb-0"><?php echo nl2br(htmlspecialchars($r['body'])); ?></p>
        </div>
    </div>
    <?php endwhile; ?>
</div>
<?php else: ?>
<div class="text-center py-5">
    <div style="font-size:3.5rem;">📝</div>
    <h5 class="mt-3 fw-bold">No reviews yet</h5>
    <p class="text-muted">After your orders are delivered, you can review the products here.</p>
    <a href="index.php" class="btn btn-success rounded-pill px-4 mt-1">Shop Now</a>
</div>
<?php endif; ?>

<!-- Review Modal -->
<div class="modal fade" id="reviewModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content rounded-4">
            <div class="modal-header border-0 pb-0">
                <h5 class="modal-title fw-bold">⭐ Rate & Review</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <p class="text-muted mb-3" id="modalProductName"></p>
                <div class="star-row mb-3">
                    <?php for($i=1;$i<=5;$i++): ?>
                    <button type="button" data-val="<?php echo $i; ?>" onclick="setModalRating(<?php echo $i; ?>)">★</button>
                    <?php endfor; ?>
                    <span class="ms-2 small text-muted" id="modalRatingLabel">Select rating</span>
                </div>
                <input type="text" id="modalTitle" class="form-control mb-2" placeholder="Review title (optional)">
                <textarea id="modalBody" class="form-control mb-2" rows="4" placeholder="Share your experience..."></textarea>
                <div id="modalMsg" class="small mt-1"></div>
            </div>
            <div class="modal-footer border-0">
                <button class="btn btn-success rounded-pill px-4" onclick="submitModalReview()">Submit Review</button>
            </div>
        </div>
    </div>
</div>

<script>
let currentProductId = 0;
let currentRating = 0;
const ratingLabels = ['','Poor','Fair','Good','Very Good','Excellent'];

function openReviewModal(productId, productName) {
    currentProductId = productId;
    currentRating = 0;
    document.getElementById('modalProductName').textContent = productName;
    document.getElementById('modalTitle').value = '';
    document.getElementById('modalBody').value = '';
    document.getElementById('modalMsg').innerHTML = '';
    document.getElementById('modalRatingLabel').textContent = 'Select rating';
    document.querySelectorAll('.star-row button').forEach(b => b.classList.remove('active'));
    new bootstrap.Modal(document.getElementById('reviewModal')).show();
}

function setModalRating(val) {
    currentRating = val;
    document.getElementById('modalRatingLabel').textContent = ratingLabels[val] + ' (' + val + '/5)';
    document.querySelectorAll('.star-row button').forEach(b => {
        b.classList.toggle('active', parseInt(b.dataset.val) <= val);
    });
}

function submitModalReview() {
    const body = document.getElementById('modalBody').value.trim();
    const title = document.getElementById('modalTitle').value.trim();
    const msgEl = document.getElementById('modalMsg');
    if (!body) { msgEl.innerHTML = '<span class="text-danger">Please write your review.</span>'; return; }
    if (!currentRating) { msgEl.innerHTML = '<span class="text-danger">Please select a rating.</span>'; return; }

    fetch('submit_review.php', {
        method: 'POST',
        headers: {'Content-Type': 'application/x-www-form-urlencoded'},
        body: `product_id=${currentProductId}&rating=${currentRating}&title=${encodeURIComponent(title)}&body=${encodeURIComponent(body)}`
    })
    .then(r => r.json())
    .then(data => {
        if (data.success) {
            msgEl.innerHTML = '<span class="text-success">✅ ' + data.msg + '</span>';
            setTimeout(() => location.reload(), 1500);
        } else {
            msgEl.innerHTML = '<span class="text-danger">' + data.msg + '</span>';
        }
    });
}
</script>

<?php include 'partials/footer.php'; ?>
