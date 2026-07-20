<?php
session_start();
require_once 'config/db.php';

if (!isset($_SESSION['user_id'])) {
    header('Location: auth/login.php');
    exit();
}

$userId = $_SESSION['user_id'];

// Fetch current user data
$stmt = $conn->prepare("SELECT full_name, username, email, phone, address, city, pincode, created_at FROM users WHERE id = ?");
$stmt->bind_param("i", $userId);
$stmt->execute();
$user = $stmt->get_result()->fetch_assoc();
$stmt->close();

// Stats
$totalOrders   = $conn->query("SELECT COUNT(*) FROM orders WHERE user_id=$userId")->fetch_row()[0];
$totalSpent    = $conn->query("SELECT COALESCE(SUM(total_amount),0) FROM orders WHERE user_id=$userId")->fetch_row()[0];
$totalReviews  = $conn->query("SELECT COUNT(*) FROM reviews WHERE user_id=$userId")->fetch_row()[0];
$wishlistCount = $conn->query("SELECT COUNT(*) FROM wishlist WHERE user_id=$userId")->fetch_row()[0];

// Recent orders for sidebar
$recentOrders = $conn->query("SELECT id, status, total_amount, order_date FROM orders WHERE user_id=$userId ORDER BY order_date DESC LIMIT 3");

// My reviews
$myReviews = $conn->query("SELECT r.rating, r.title, r.status, p.name as pname FROM reviews r JOIN products p ON p.id=r.product_id WHERE r.user_id=$userId ORDER BY r.created_at DESC LIMIT 5");

include 'partials/header.php';
?>

<style>
:root { --profile-green:#16a34a; }
.profile-hero {
    background: linear-gradient(135deg, #052e16 0%, #14532d 60%, #16a34a 100%);
    border-radius: 20px;
    padding: 36px 32px 28px;
    color: #fff;
    margin-bottom: 28px;
    position: relative;
    overflow: hidden;
}
.profile-hero::before {
    content:'';
    position:absolute;
    top:-60px;right:-60px;
    width:220px;height:220px;
    border-radius:50%;
    background:rgba(255,255,255,0.05);
}
.avatar-ring {
    width: 90px; height: 90px; border-radius: 50%;
    background: linear-gradient(135deg,#4ade80,#86efac);
    display: flex; align-items: center; justify-content: center;
    font-size: 2.4rem; font-weight: 800; color: #052e16;
    flex-shrink: 0; border: 4px solid rgba(255,255,255,0.3);
}
.stat-pill {
    background: rgba(255,255,255,0.12);
    border-radius: 12px; padding: 10px 18px; text-align: center;
    backdrop-filter: blur(4px);
}
.stat-pill .num { font-size: 1.4rem; font-weight: 800; }
.stat-pill .lbl { font-size: 0.72rem; opacity: 0.8; }

.tab-section { display: none; }
.tab-section.active { display: block; }

.tab-btn {
    border: none; background: none;
    padding: 10px 20px; border-radius: 10px;
    font-weight: 600; color: #6b7280; cursor: pointer;
    transition: all 0.2s;
}
.tab-btn.active { background: #16a34a; color: #fff; }
.tab-btn:hover:not(.active) { background: #f0fdf4; color: #16a34a; }

.form-label { font-weight: 600; font-size: 0.88rem; color: #374151; }
.form-control, .form-select { border-radius: 10px; border: 1.5px solid #e5e7eb; padding: 10px 14px; }
.form-control:focus, .form-select:focus { border-color: #16a34a; box-shadow: 0 0 0 3px rgba(22,163,74,0.12); }

.section-card { border: none; border-radius: 18px; box-shadow: 0 3px 16px rgba(0,0,0,0.07); margin-bottom: 20px; }
.section-card .card-body { padding: 28px; }

.pw-strength { height: 5px; border-radius: 3px; transition: all 0.3s; margin-top: 6px; }

.order-mini { border-radius: 10px; border: 1.5px solid #f0fdf4; padding: 10px 14px; margin-bottom: 8px; }
.review-star { color: #f59e0b; }

.danger-zone { border: 2px solid #fee2e2; border-radius: 18px; padding: 24px; background: #fff5f5; }
</style>

<!-- Profile Hero -->
<div class="profile-hero">
    <div class="d-flex align-items-center gap-4 flex-wrap mb-4">
        <div class="avatar-ring"><?php echo strtoupper(substr($user['username'],0,1)); ?></div>
        <div>
            <h2 class="fw-bold mb-1"><?php echo htmlspecialchars($user['full_name'] ?: $user['username']); ?></h2>
            <div style="opacity:.8;font-size:.9rem;">
                <i class="bi bi-envelope me-1"></i><?php echo htmlspecialchars($user['email']); ?>
                &nbsp;·&nbsp;
                <i class="bi bi-calendar3 me-1"></i>Joined <?php echo date('M Y', strtotime($user['created_at'])); ?>
            </div>
        </div>
    </div>
    <div class="row g-2">
        <div class="col-6 col-sm-3"><div class="stat-pill"><div class="num"><?php echo $totalOrders; ?></div><div class="lbl">Orders</div></div></div>
        <div class="col-6 col-sm-3"><div class="stat-pill"><div class="num">₹<?php echo number_format($totalSpent,0); ?></div><div class="lbl">Total Spent</div></div></div>
        <div class="col-6 col-sm-3"><div class="stat-pill"><div class="num"><?php echo $totalReviews; ?></div><div class="lbl">Reviews</div></div></div>
        <div class="col-6 col-sm-3"><div class="stat-pill"><div class="num"><?php echo $wishlistCount; ?></div><div class="lbl">Wishlist</div></div></div>
    </div>
</div>

<!-- Alert messages -->
<?php if (isset($_SESSION['profile_message'])): ?>
<div class="alert alert-success alert-dismissible fade show rounded-3"><i class="bi bi-check-circle-fill me-2"></i><?php echo $_SESSION['profile_message']; unset($_SESSION['profile_message']); ?><button type="button" class="btn-close" data-bs-dismiss="alert"></button></div>
<?php endif; ?>
<?php if (isset($_SESSION['profile_error'])): ?>
<div class="alert alert-danger alert-dismissible fade show rounded-3"><i class="bi bi-exclamation-circle-fill me-2"></i><?php echo $_SESSION['profile_error']; unset($_SESSION['profile_error']); ?><button type="button" class="btn-close" data-bs-dismiss="alert"></button></div>
<?php endif; ?>

<!-- Tabs -->
<div class="d-flex gap-2 flex-wrap mb-4">
    <button class="tab-btn active" onclick="switchTab('info', this)"><i class="bi bi-person me-1"></i>Personal Info</button>
    <button class="tab-btn" onclick="switchTab('address', this)"><i class="bi bi-geo-alt me-1"></i>Address</button>
    <button class="tab-btn" onclick="switchTab('password', this)"><i class="bi bi-shield-lock me-1"></i>Password</button>
    <button class="tab-btn" onclick="switchTab('activity', this)"><i class="bi bi-clock-history me-1"></i>Activity</button>
    <button class="tab-btn" onclick="switchTab('danger', this)"><i class="bi bi-exclamation-triangle me-1"></i>Account</button>
</div>

<!-- TAB: Personal Info -->
<div id="tab-info" class="tab-section active">
    <div class="card section-card">
        <div class="card-body">
            <h5 class="fw-bold mb-4"><i class="bi bi-person-fill text-success me-2"></i>Personal Information</h5>
            <form action="update_profile.php" method="post">
                <div class="row g-3">
                    <div class="col-md-6">
                        <label class="form-label">Full Name</label>
                        <input type="text" class="form-control" name="full_name" value="<?php echo htmlspecialchars($user['full_name'] ?? ''); ?>" placeholder="Your full name">
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">Username <span class="text-danger">*</span></label>
                        <div class="input-group">
                            <span class="input-group-text bg-light border-end-0"><i class="bi bi-at"></i></span>
                            <input type="text" class="form-control border-start-0" name="username" value="<?php echo htmlspecialchars($user['username']); ?>" required>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">Email Address <span class="text-danger">*</span></label>
                        <div class="input-group">
                            <span class="input-group-text bg-light border-end-0"><i class="bi bi-envelope"></i></span>
                            <input type="email" class="form-control border-start-0" name="email" value="<?php echo htmlspecialchars($user['email']); ?>" required>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">Phone Number</label>
                        <div class="input-group">
                            <span class="input-group-text bg-light border-end-0"><i class="bi bi-phone"></i></span>
                            <input type="tel" class="form-control border-start-0" name="phone" value="<?php echo htmlspecialchars($user['phone'] ?? ''); ?>" placeholder="10-digit mobile number" maxlength="10">
                        </div>
                    </div>
                </div>
                <div class="mt-4 d-flex gap-2">
                    <button type="submit" name="update_info" class="btn btn-success rounded-pill px-4"><i class="bi bi-check-lg me-2"></i>Save Changes</button>
                    <a href="profile.php" class="btn btn-outline-secondary rounded-pill px-4">Cancel</a>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- TAB: Address -->
<div id="tab-address" class="tab-section">
    <div class="card section-card">
        <div class="card-body">
            <h5 class="fw-bold mb-4"><i class="bi bi-geo-alt-fill text-success me-2"></i>Delivery Address</h5>
            <form action="update_profile.php" method="post">
                <div class="row g-3">
                    <div class="col-12">
                        <label class="form-label">Street Address</label>
                        <textarea class="form-control" name="address" rows="3" placeholder="House/Flat no., Street, Area..."><?php echo htmlspecialchars($user['address'] ?? ''); ?></textarea>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">City</label>
                        <div class="input-group">
                            <span class="input-group-text bg-light border-end-0"><i class="bi bi-buildings"></i></span>
                            <input type="text" class="form-control border-start-0" name="city" value="<?php echo htmlspecialchars($user['city'] ?? ''); ?>" placeholder="City name">
                        </div>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">Pincode</label>
                        <div class="input-group">
                            <span class="input-group-text bg-light border-end-0"><i class="bi bi-mailbox"></i></span>
                            <input type="text" class="form-control border-start-0" name="pincode" value="<?php echo htmlspecialchars($user['pincode'] ?? ''); ?>" placeholder="6-digit pincode" maxlength="6">
                        </div>
                    </div>
                </div>
                <div class="mt-4 d-flex gap-2">
                    <button type="submit" name="update_address" class="btn btn-success rounded-pill px-4"><i class="bi bi-check-lg me-2"></i>Save Address</button>
                    <a href="profile.php" class="btn btn-outline-secondary rounded-pill px-4">Cancel</a>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- TAB: Password -->
<div id="tab-password" class="tab-section">
    <div class="card section-card">
        <div class="card-body">
            <h5 class="fw-bold mb-4"><i class="bi bi-shield-lock-fill text-success me-2"></i>Change Password</h5>
            <form action="update_profile.php" method="post" style="max-width:480px;">
                <div class="mb-3">
                    <label class="form-label">Current Password</label>
                    <div class="input-group">
                        <input type="password" class="form-control" name="current_password" id="cur_pw" required placeholder="Enter current password">
                        <button type="button" class="input-group-text bg-light" onclick="togglePw('cur_pw',this)"><i class="bi bi-eye"></i></button>
                    </div>
                </div>
                <div class="mb-3">
                    <label class="form-label">New Password</label>
                    <div class="input-group">
                        <input type="password" class="form-control" name="new_password" id="new_pw" required minlength="6" placeholder="Minimum 6 characters" oninput="checkStrength(this.value)">
                        <button type="button" class="input-group-text bg-light" onclick="togglePw('new_pw',this)"><i class="bi bi-eye"></i></button>
                    </div>
                    <div class="pw-strength mt-2" id="pw_strength_bar" style="width:0;background:#e5e7eb;height:5px;border-radius:3px;"></div>
                    <small id="pw_strength_label" class="text-muted">Enter a password</small>
                </div>
                <div class="mb-4">
                    <label class="form-label">Confirm New Password</label>
                    <div class="input-group">
                        <input type="password" class="form-control" name="confirm_password" id="conf_pw" required placeholder="Re-enter new password" oninput="checkMatch()">
                        <button type="button" class="input-group-text bg-light" onclick="togglePw('conf_pw',this)"><i class="bi bi-eye"></i></button>
                    </div>
                    <small id="match_label"></small>
                </div>
                <div class="alert alert-light border small mb-3">
                    <i class="bi bi-info-circle text-success me-1"></i>
                    Use at least 8 characters with a mix of letters, numbers and symbols for a strong password.
                </div>
                <button type="submit" name="change_password" class="btn btn-warning rounded-pill px-4 fw-semibold"><i class="bi bi-shield-check me-2"></i>Update Password</button>
            </form>
        </div>
    </div>
</div>

<!-- TAB: Activity -->
<div id="tab-activity" class="tab-section">
    <div class="row g-4">
        <div class="col-lg-6">
            <div class="card section-card h-100">
                <div class="card-body">
                    <h6 class="fw-bold mb-3"><i class="bi bi-bag-check text-success me-2"></i>Recent Orders</h6>
                    <?php if ($recentOrders->num_rows > 0): ?>
                        <?php while ($o = $recentOrders->fetch_assoc()): ?>
                        <div class="order-mini d-flex align-items-center justify-content-between">
                            <div>
                                <div class="fw-semibold small">Order #<?php echo $o['id']; ?></div>
                                <div class="text-muted" style="font-size:.78rem;"><?php echo date('M d, Y', strtotime($o['order_date'])); ?></div>
                            </div>
                            <div class="text-end">
                                <div class="text-success fw-bold small">₹<?php echo number_format($o['total_amount'],2); ?></div>
                                <span class="badge rounded-pill bg-<?php echo $o['status']=='Delivered'?'success':($o['status']=='Pending'?'warning text-dark':'secondary'); ?>" style="font-size:.7rem;">
                                    <?php echo $o['status']; ?>
                                </span>
                            </div>
                        </div>
                        <?php endwhile; ?>
                        <a href="orders.php" class="btn btn-outline-success btn-sm rounded-pill mt-2 w-100">View All Orders</a>
                    <?php else: ?>
                        <p class="text-muted small">No orders yet. <a href="index.php">Start shopping</a>!</p>
                    <?php endif; ?>
                </div>
            </div>
        </div>
        <div class="col-lg-6">
            <div class="card section-card h-100">
                <div class="card-body">
                    <h6 class="fw-bold mb-3"><i class="bi bi-star text-warning me-2"></i>My Reviews</h6>
                    <?php if ($myReviews->num_rows > 0): ?>
                        <?php while ($r = $myReviews->fetch_assoc()): ?>
                        <div class="order-mini">
                            <div class="d-flex justify-content-between">
                                <div class="fw-semibold small"><?php echo htmlspecialchars($r['pname']); ?></div>
                                <span class="badge rounded-pill bg-<?php echo $r['status']=='Approved'?'success':($r['status']=='Pending'?'warning text-dark':'danger'); ?>" style="font-size:.7rem;"><?php echo $r['status']; ?></span>
                            </div>
                            <div>
                                <?php for($i=1;$i<=5;$i++) echo "<span class='review-star' style='font-size:.85rem;".($i>$r['rating']?'opacity:.3':'')."'>★</span>"; ?>
                                <?php if($r['title']): ?><span class="text-muted small ms-1"><?php echo htmlspecialchars($r['title']); ?></span><?php endif; ?>
                            </div>
                        </div>
                        <?php endwhile; ?>
                        <a href="my_reviews.php" class="btn btn-outline-warning btn-sm rounded-pill mt-2 w-100">View All Reviews</a>
                    <?php else: ?>
                        <p class="text-muted small">No reviews yet. <a href="my_reviews.php">Write a review</a>!</p>
                    <?php endif; ?>
                </div>
            </div>
        </div>
        <div class="col-12">
            <div class="card section-card">
                <div class="card-body">
                    <h6 class="fw-bold mb-3"><i class="bi bi-lightning text-info me-2"></i>Quick Actions</h6>
                    <div class="row g-2">
                        <div class="col-6 col-md-3"><a href="orders.php" class="btn btn-outline-success w-100 rounded-3 py-3"><i class="bi bi-bag d-block fs-4 mb-1"></i><span class="small">My Orders</span></a></div>
                        <div class="col-6 col-md-3"><a href="wishlist.php" class="btn btn-outline-danger w-100 rounded-3 py-3"><i class="bi bi-heart d-block fs-4 mb-1"></i><span class="small">Wishlist</span></a></div>
                        <div class="col-6 col-md-3"><a href="my_reviews.php" class="btn btn-outline-warning w-100 rounded-3 py-3"><i class="bi bi-star d-block fs-4 mb-1"></i><span class="small">My Reviews</span></a></div>
                        <div class="col-6 col-md-3"><a href="feedback.php" class="btn btn-outline-info w-100 rounded-3 py-3"><i class="bi bi-chat-dots d-block fs-4 mb-1"></i><span class="small">Feedback</span></a></div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- TAB: Account / Danger Zone -->
<div id="tab-danger" class="tab-section">
    <div class="card section-card mb-4">
        <div class="card-body">
            <h5 class="fw-bold mb-3"><i class="bi bi-info-circle text-success me-2"></i>Account Details</h5>
            <table class="table table-borderless mb-0">
                <tr><td class="text-muted fw-semibold" style="width:180px;">User ID</td><td>#<?php echo $userId; ?></td></tr>
                <tr><td class="text-muted fw-semibold">Username</td><td><?php echo htmlspecialchars($user['username']); ?></td></tr>
                <tr><td class="text-muted fw-semibold">Email</td><td><?php echo htmlspecialchars($user['email']); ?></td></tr>
                <tr><td class="text-muted fw-semibold">Phone</td><td><?php echo htmlspecialchars($user['phone'] ?? '—'); ?></td></tr>
                <tr><td class="text-muted fw-semibold">City</td><td><?php echo htmlspecialchars($user['city'] ?? '—'); ?></td></tr>
                <tr><td class="text-muted fw-semibold">Pincode</td><td><?php echo htmlspecialchars($user['pincode'] ?? '—'); ?></td></tr>
                <tr><td class="text-muted fw-semibold">Member Since</td><td><?php echo date('F d, Y', strtotime($user['created_at'])); ?></td></tr>
            </table>
        </div>
    </div>

    <div class="danger-zone">
        <h5 class="fw-bold text-danger mb-1"><i class="bi bi-exclamation-triangle-fill me-2"></i>Danger Zone</h5>
        <p class="text-muted small mb-4">These actions are irreversible. Please be careful.</p>

        <div class="d-flex align-items-center justify-content-between flex-wrap gap-3 border-bottom pb-3 mb-3">
            <div>
                <div class="fw-semibold">Sign out from all devices</div>
                <div class="text-muted small">This will log you out everywhere.</div>
            </div>
            <a href="auth/logout.php" class="btn btn-outline-danger rounded-pill px-4">Sign Out</a>
        </div>

        <div class="d-flex align-items-center justify-content-between flex-wrap gap-3">
            <div>
                <div class="fw-semibold">Delete My Account</div>
                <div class="text-muted small">Permanently delete your account and all associated data.</div>
            </div>
            <button class="btn btn-danger rounded-pill px-4" onclick="confirmDelete()">Delete Account</button>
        </div>
    </div>
</div>

<script>
function switchTab(name, btn) {
    document.querySelectorAll('.tab-section').forEach(s => s.classList.remove('active'));
    document.querySelectorAll('.tab-btn').forEach(b => b.classList.remove('active'));
    document.getElementById('tab-' + name).classList.add('active');
    btn.classList.add('active');
}

// Restore active tab after page reload via hash
document.addEventListener('DOMContentLoaded', () => {
    const hash = location.hash.replace('#','');
    if (hash) {
        const btn = document.querySelector(`.tab-btn[onclick*="'${hash}'"]`);
        if (btn) switchTab(hash, btn);
    }
});

function togglePw(id, btn) {
    const el = document.getElementById(id);
    const showing = el.type === 'text';
    el.type = showing ? 'password' : 'text';
    btn.innerHTML = showing ? '<i class="bi bi-eye"></i>' : '<i class="bi bi-eye-slash"></i>';
}

function checkStrength(pw) {
    const bar = document.getElementById('pw_strength_bar');
    const lbl = document.getElementById('pw_strength_label');
    let score = 0;
    if (pw.length >= 6) score++;
    if (pw.length >= 10) score++;
    if (/[A-Z]/.test(pw) && /[a-z]/.test(pw)) score++;
    if (/[0-9]/.test(pw)) score++;
    if (/[^A-Za-z0-9]/.test(pw)) score++;
    const levels = [
        {w:'0%',c:'#e5e7eb',t:''},
        {w:'20%',c:'#ef4444',t:'Very Weak'},
        {w:'40%',c:'#f97316',t:'Weak'},
        {w:'60%',c:'#eab308',t:'Fair'},
        {w:'80%',c:'#22c55e',t:'Strong'},
        {w:'100%',c:'#16a34a',t:'Very Strong'},
    ];
    const l = levels[score];
    bar.style.width = l.w; bar.style.background = l.c; bar.style.height='5px';
    lbl.textContent = l.t; lbl.style.color = l.c;
}

function checkMatch() {
    const np = document.getElementById('new_pw').value;
    const cp = document.getElementById('conf_pw').value;
    const lbl = document.getElementById('match_label');
    if (!cp) { lbl.textContent = ''; return; }
    if (np === cp) { lbl.innerHTML = '<span class="text-success"><i class="bi bi-check-circle-fill me-1"></i>Passwords match</span>'; }
    else { lbl.innerHTML = '<span class="text-danger"><i class="bi bi-x-circle-fill me-1"></i>Passwords do not match</span>'; }
}

function confirmDelete() {
    if (confirm('Are you absolutely sure? This will permanently delete your account and cannot be undone.')) {
        if (confirm('Last chance — all your orders, reviews and data will be erased. Continue?')) {
            window.location.href = 'update_profile.php?action=delete_account';
        }
    }
}
</script>

<?php include 'partials/footer.php'; ?>
