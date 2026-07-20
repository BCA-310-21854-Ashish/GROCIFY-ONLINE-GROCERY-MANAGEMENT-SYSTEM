<?php

require_once '../config/db.php';
include 'header.php';

// Handle approve/reject/delete
if (isset($_GET['action']) && isset($_GET['id'])) {
    $id = intval($_GET['id']);
    $action = $_GET['action'];
    if ($action === 'approve') {
        $conn->query("UPDATE reviews SET status='Approved' WHERE id=$id");
    } elseif ($action === 'reject') {
        $conn->query("UPDATE reviews SET status='Rejected' WHERE id=$id");
    } elseif ($action === 'delete') {
        $conn->query("DELETE FROM reviews WHERE id=$id");
    }
    header('Location: reviews.php');
    exit();
}

$filter = isset($_GET['status']) ? $_GET['status'] : 'Pending';
$safeFilter = $conn->real_escape_string($filter);
$reviews = $conn->query("
    SELECT r.*, u.username, p.name as product_name, p.image_url as product_img
    FROM reviews r 
    JOIN users u ON r.user_id = u.id
    JOIN products p ON r.product_id = p.id
    WHERE r.status='$safeFilter'
    ORDER BY r.created_at DESC
");

$counts = [];
foreach (['Pending','Approved','Rejected'] as $s) {
    $res = $conn->query("SELECT COUNT(*) FROM reviews WHERE status='$s'");
    $counts[$s] = $res->fetch_row()[0];
}
?>
<div class="d-flex justify-content-between align-items-center mb-4">
    <h2 class="fw-bold mb-0">⭐ Product Reviews</h2>
    <div class="btn-group">
        <?php foreach(['Pending'=>'warning','Approved'=>'success','Rejected'=>'danger'] as $s=>$color): ?>
        <a href="?status=<?php echo $s; ?>" class="btn btn-sm <?php echo $filter==$s ? "btn-$color" : "btn-outline-$color"; ?>">
            <?php echo $s; ?> <span class="badge bg-light text-dark"><?php echo $counts[$s]; ?></span>
        </a>
        <?php endforeach; ?>
    </div>
</div>

<?php if ($reviews && $reviews->num_rows > 0): ?>
<div class="row g-3">
<?php while($r = $reviews->fetch_assoc()): ?>
<div class="col-md-6">
<div class="card shadow-sm border-0 rounded-4">
    <div class="card-body">
        <div class="d-flex align-items-center gap-3 mb-3">
            <img src="<?php echo htmlspecialchars($r['product_img']); ?>" width="56" height="56" 
                 style="object-fit:cover;border-radius:12px;" onerror="this.src='https://via.placeholder.com/56'">
            <div>
                <div class="fw-bold"><?php echo htmlspecialchars($r['product_name']); ?></div>
                <div class="text-muted small">by <?php echo htmlspecialchars($r['username']); ?> · 
                    <?php echo date('d M Y', strtotime($r['created_at'])); ?>
                </div>
                <div class="text-warning">
                    <?php for($i=1;$i<=5;$i++) echo $i<=$r['rating']?'★':'☆'; ?>
                    <span class="text-dark small ms-1"><?php echo $r['rating']; ?>/5</span>
                </div>
            </div>
        </div>
        <?php if($r['title']): ?>
            <h6 class="mb-1"><?php echo htmlspecialchars($r['title']); ?></h6>
        <?php endif; ?>
        <p class="text-muted small mb-3"><?php echo nl2br(htmlspecialchars($r['body'])); ?></p>
        <div class="d-flex gap-2">
            <?php if($r['status']==='Pending'): ?>
                <a href="?action=approve&id=<?php echo $r['id']; ?>" class="btn btn-sm btn-success">✓ Approve</a>
                <a href="?action=reject&id=<?php echo $r['id']; ?>" class="btn btn-sm btn-warning">✗ Reject</a>
            <?php elseif($r['status']==='Rejected'): ?>
                <a href="?action=approve&id=<?php echo $r['id']; ?>" class="btn btn-sm btn-success">✓ Approve</a>
            <?php endif; ?>
            <a href="?action=delete&id=<?php echo $r['id']; ?>" class="btn btn-sm btn-outline-danger"
               onclick="return confirm('Delete this review?')">🗑 Delete</a>
        </div>
    </div>
</div>
</div>
<?php endwhile; ?>
</div>
<?php else: ?>
<div class="alert alert-info">No <?php echo strtolower($filter); ?> reviews found.</div>
<?php endif; ?>

<?php include 'footer.php'; ?>
