<?php

session_start();
require_once 'config/db.php';

if (!isset($_SESSION['user_id'])) {
    header('Location: auth/login.php');
    exit();
}

$userId = $_SESSION['user_id'];
$success = '';
$error = '';

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $subject = trim($_POST['subject'] ?? '');
    $message = trim($_POST['message'] ?? '');
    $rating  = intval($_POST['rating'] ?? 0);

    if (empty($subject) || empty($message) || $rating < 1 || $rating > 5) {
        $error = 'Please fill in all fields and select a rating.';
    } else {
        $safeSubject = $conn->real_escape_string($subject);
        $safeMessage = $conn->real_escape_string($message);
        $conn->query("INSERT INTO feedback (user_id, subject, message, rating, status, created_at)
                      VALUES ($userId, '$safeSubject', '$safeMessage', $rating, 'Pending', NOW())");
        $success = 'Thank you! Your feedback has been submitted successfully.';
    }
}

// Fetch user's previous feedback
$myFeedback = $conn->query("SELECT * FROM feedback WHERE user_id=$userId ORDER BY created_at DESC LIMIT 10");

include 'partials/header.php';
?>

<style>
.feedback-hero { background: linear-gradient(135deg,#d1fae5,#a7f3d0); border-radius:18px; padding:28px 32px; margin-bottom:32px; }
.star-btn { background:none; border:none; font-size:2rem; color:#d1d5db; cursor:pointer; transition:color 0.15s; padding:0 4px; }
.star-btn.active, .star-btn:hover { color:#f59e0b; }
.feedback-card { border:none; border-radius:16px; box-shadow:0 3px 14px rgba(0,0,0,0.07); }
</style>

<div class="feedback-hero d-flex align-items-center gap-3">
    <div style="font-size:2.8rem;">💬</div>
    <div>
        <h2 class="fw-bold mb-1">Share Your Feedback</h2>
        <p class="mb-0 text-muted">We love hearing from you! Help us improve Grocify.</p>
    </div>
</div>

<?php if ($success): ?>
<div class="alert alert-success alert-dismissible fade show rounded-3" role="alert">
    <i class="bi bi-check-circle-fill me-2"></i><?php echo $success; ?>
    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
</div>
<?php endif; ?>

<?php if ($error): ?>
<div class="alert alert-danger alert-dismissible fade show rounded-3" role="alert">
    <i class="bi bi-exclamation-circle-fill me-2"></i><?php echo $error; ?>
    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
</div>
<?php endif; ?>

<div class="row g-4">
    <!-- Feedback Form -->
    <div class="col-lg-7">
        <div class="card feedback-card p-4">
            <h5 class="fw-bold mb-4">📝 Submit Feedback</h5>
            <form method="POST">
                <div class="mb-3">
                    <label class="form-label fw-semibold">Overall Rating <span class="text-danger">*</span></label>
                    <div id="starRating" class="mb-1">
                        <?php for($i=1;$i<=5;$i++): ?>
                        <button type="button" class="star-btn" data-val="<?php echo $i; ?>" onclick="setRating(<?php echo $i; ?>)">★</button>
                        <?php endfor; ?>
                    </div>
                    <input type="hidden" name="rating" id="ratingInput" value="0">
                    <small class="text-muted" id="ratingLabel">Click to rate</small>
                </div>
                <div class="mb-3">
                    <label class="form-label fw-semibold">Subject <span class="text-danger">*</span></label>
                    <input type="text" name="subject" class="form-control" placeholder="e.g. Delivery experience, App suggestion..." maxlength="150" required>
                </div>
                <div class="mb-4">
                    <label class="form-label fw-semibold">Message <span class="text-danger">*</span></label>
                    <textarea name="message" class="form-control" rows="5" placeholder="Tell us about your experience in detail..." required></textarea>
                </div>
                <button type="submit" class="btn btn-success rounded-pill px-5 fw-semibold">
                    <i class="bi bi-send me-2"></i>Submit Feedback
                </button>
            </form>
        </div>
    </div>

    <!-- Quick Links & Tips -->
    <div class="col-lg-5">
        <div class="card feedback-card p-4 mb-3">
            <h6 class="fw-bold mb-3">🚀 Quick Actions</h6>
            <a href="orders.php" class="btn btn-outline-success rounded-pill w-100 mb-2 text-start">
                <i class="bi bi-bag-check me-2"></i>View My Orders
            </a>
            <a href="profile.php" class="btn btn-outline-primary rounded-pill w-100 mb-2 text-start">
                <i class="bi bi-person me-2"></i>Edit My Profile
            </a>
            <a href="index.php" class="btn btn-outline-secondary rounded-pill w-100 text-start">
                <i class="bi bi-shop me-2"></i>Continue Shopping
            </a>
        </div>
        <div class="card feedback-card p-4 bg-light border-0">
            <h6 class="fw-bold mb-2">💡 Tips</h6>
            <ul class="list-unstyled mb-0 small text-muted">
                <li class="mb-2"><i class="bi bi-check2-circle text-success me-2"></i>Be specific — the more detail, the better we can help.</li>
                <li class="mb-2"><i class="bi bi-check2-circle text-success me-2"></i>Include your order ID if it's about a specific order.</li>
                <li><i class="bi bi-check2-circle text-success me-2"></i>Our team reviews all feedback within 2 business days.</li>
            </ul>
        </div>
    </div>
</div>

<!-- Previous Feedback -->
<?php if ($myFeedback && $myFeedback->num_rows > 0): ?>
<div class="mt-5">
    <h5 class="fw-bold mb-3">📋 My Previous Feedback</h5>
    <div class="table-responsive">
        <table class="table table-hover align-middle rounded-3 overflow-hidden">
            <thead class="table-light">
                <tr>
                    <th>Subject</th>
                    <th>Rating</th>
                    <th>Date</th>
                    <th>Status</th>
                </tr>
            </thead>
            <tbody>
                <?php while ($f = $myFeedback->fetch_assoc()): ?>
                <tr>
                    <td><?php echo htmlspecialchars($f['subject']); ?></td>
                    <td>
                        <?php for($i=1;$i<=5;$i++) echo $i<=$f['rating']?'<span style="color:#f59e0b">★</span>':'<span style="color:#d1d5db">★</span>'; ?>
                    </td>
                    <td class="text-muted small"><?php echo date('M d, Y', strtotime($f['created_at'])); ?></td>
                    <td>
                        <span class="badge rounded-pill bg-<?php echo $f['status']=='Pending'?'warning text-dark':($f['status']=='Reviewed'?'info':'success'); ?>">
                            <?php echo $f['status']; ?>
                        </span>
                    </td>
                </tr>
                <?php endwhile; ?>
            </tbody>
        </table>
    </div>
</div>
<?php endif; ?>

<script>
const ratingLabels = ['','Poor','Fair','Good','Very Good','Excellent'];
function setRating(val) {
    document.getElementById('ratingInput').value = val;
    document.getElementById('ratingLabel').textContent = ratingLabels[val] + ' (' + val + '/5)';
    document.querySelectorAll('.star-btn').forEach(btn => {
        btn.classList.toggle('active', parseInt(btn.dataset.val) <= val);
    });
}
</script>

<?php include 'partials/footer.php'; ?>
