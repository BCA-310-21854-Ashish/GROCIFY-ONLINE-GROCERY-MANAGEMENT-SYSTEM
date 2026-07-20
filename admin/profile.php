<?php
require_once '../config/db.php';
include 'header.php';

$userId = $_SESSION['user_id'];

// Fetch admin data
$stmt = $conn->prepare("SELECT full_name, username, email, phone, created_at FROM users WHERE id=?");
$stmt->bind_param("i", $userId);
$stmt->execute();
$admin = $stmt->get_result()->fetch_assoc();
$stmt->close();

// Stats
$totalOrders   = $conn->query("SELECT COUNT(*) FROM orders")->fetch_row()[0];
$totalUsers    = $conn->query("SELECT COUNT(*) FROM users WHERE is_admin=0")->fetch_row()[0];
$totalRevenue  = $conn->query("SELECT COALESCE(SUM(total_amount),0) FROM orders WHERE status='Delivered'")->fetch_row()[0];
$pendingOrders = $conn->query("SELECT COUNT(*) FROM orders WHERE status='Pending'")->fetch_row()[0];
$pendingReviews= $conn->query("SELECT COUNT(*) FROM reviews WHERE status='Pending'")->fetch_row()[0];
$pendingFeedback=$conn->query("SELECT COUNT(*) FROM feedback WHERE status='Pending'")->fetch_row()[0];

// Success/error from session
$msg = $_SESSION['admin_profile_msg'] ?? ''; unset($_SESSION['admin_profile_msg']);
$err = $_SESSION['admin_profile_err'] ?? ''; unset($_SESSION['admin_profile_err']);

// Handle POST
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['update_info'])) {
        $full_name = trim($_POST['full_name'] ?? '');
        $username  = trim($_POST['username']);
        $email     = trim($_POST['email']);
        $phone     = trim($_POST['phone'] ?? '');

        if ($phone && !preg_match('/^[0-9]{10}$/', $phone)) {
            $_SESSION['admin_profile_err'] = "Phone must be 10 digits.";
        } else {
            $chk = $conn->prepare("SELECT id FROM users WHERE (username=? OR email=?) AND id!=?");
            $chk->bind_param("ssi", $username, $email, $userId);
            $chk->execute();
            if ($chk->get_result()->num_rows > 0) {
                $_SESSION['admin_profile_err'] = "Username or email already taken.";
            } else {
                $s = $conn->prepare("UPDATE users SET full_name=?, username=?, email=?, phone=? WHERE id=?");
                $s->bind_param("ssssi", $full_name, $username, $email, $phone, $userId);
                $s->execute();
                $_SESSION['username'] = $username;
                $_SESSION['admin_profile_msg'] = "Profile updated successfully.";
            }
        }
        header('Location: profile.php'); exit();
    }

    if (isset($_POST['change_password'])) {
        $cur  = $_POST['current_password'];
        $new  = $_POST['new_password'];
        $conf = $_POST['confirm_password'];
        if ($new !== $conf) { $_SESSION['admin_profile_err'] = "New passwords do not match."; }
        elseif (strlen($new) < 6) { $_SESSION['admin_profile_err'] = "Password must be at least 6 characters."; }
        else {
            $s = $conn->prepare("SELECT password FROM users WHERE id=?");
            $s->bind_param("i", $userId); $s->execute();
            $row = $s->get_result()->fetch_assoc();
            if (password_verify($cur, $row['password'])) {
                $hash = password_hash($new, PASSWORD_DEFAULT);
                $s = $conn->prepare("UPDATE users SET password=? WHERE id=?");
                $s->bind_param("si", $hash, $userId); $s->execute();
                $_SESSION['admin_profile_msg'] = "Password changed successfully.";
            } else {
                $_SESSION['admin_profile_err'] = "Current password is incorrect.";
            }
        }
        header('Location: profile.php'); exit();
    }
}
?>

<style>
.admin-profile-hero {
    background: linear-gradient(135deg, #1e3a5f 0%, #1e40af 100%);
    border-radius: 16px; padding: 28px 32px; color:#fff; margin-bottom:28px;
    position:relative; overflow:hidden;
}
.admin-profile-hero::after {
    content:''; position:absolute; bottom:-40px; right:-40px;
    width:180px; height:180px; border-radius:50%;
    background:rgba(255,255,255,0.05);
}
.admin-avatar {
    width:80px; height:80px; border-radius:50%;
    background:linear-gradient(135deg,#60a5fa,#3b82f6);
    display:flex; align-items:center; justify-content:center;
    font-size:2.2rem; font-weight:800; color:#fff; flex-shrink:0;
    border:3px solid rgba(255,255,255,0.3);
}
.stat-box {
    background:rgba(255,255,255,0.1); border-radius:12px;
    padding:12px 16px; text-align:center; backdrop-filter:blur(4px);
}
.stat-box .n { font-size:1.5rem; font-weight:800; }
.stat-box .l { font-size:0.7rem; opacity:.8; }

.ap-card { border:none; border-radius:16px; box-shadow:0 3px 16px rgba(0,0,0,0.08); margin-bottom:20px; }
.ap-card .card-body { padding:28px; }

.tab-pill { border:none; background:none; padding:9px 18px; border-radius:8px; font-weight:600; color:#6b7280; cursor:pointer; transition:all .2s; }
.tab-pill.active { background:#1d4ed8; color:#fff; }
.tab-pill:hover:not(.active) { background:#eff6ff; color:#1d4ed8; }

.tab-sec { display:none; }
.tab-sec.active { display:block; }

.form-control:focus, .form-select:focus { border-color:#3b82f6; box-shadow:0 0 0 3px rgba(59,130,246,.12); }

.notif-row { display:flex; align-items:center; justify-content:space-between; padding:14px 0; border-bottom:1px solid #f3f4f6; }
.notif-row:last-child { border-bottom:none; }

.pw-bar { height:5px; border-radius:3px; transition:all .3s; margin-top:6px; }
</style>

<!-- Hero -->
<div class="admin-profile-hero d-flex align-items-center gap-4 flex-wrap">
    <div class="admin-avatar"><?php echo strtoupper(substr($admin['username'],0,1)); ?></div>
    <div class="flex-grow-1">
        <h3 class="fw-bold mb-1"><?php echo htmlspecialchars($admin['full_name'] ?: $admin['username']); ?></h3>
        <div style="opacity:.8;font-size:.88rem;">
            <span class="badge bg-warning text-dark me-2"><i class="bi bi-shield-fill me-1"></i>Administrator</span>
            <?php echo htmlspecialchars($admin['email']); ?>
            &nbsp;·&nbsp; Since <?php echo date('M Y', strtotime($admin['created_at'])); ?>
        </div>
    </div>
    <div class="row g-2 mt-2 mt-md-0">
        <div class="col-6 col-md-3"><div class="stat-box"><div class="n"><?php echo $totalOrders; ?></div><div class="l">Total Orders</div></div></div>
        <div class="col-6 col-md-3"><div class="stat-box"><div class="n"><?php echo $totalUsers; ?></div><div class="l">Customers</div></div></div>
        <div class="col-6 col-md-3"><div class="stat-box"><div class="n">₹<?php echo number_format($totalRevenue/1000,1); ?>k</div><div class="l">Revenue</div></div></div>
        <div class="col-6 col-md-3"><div class="stat-box"><div class="n"><?php echo $pendingOrders; ?></div><div class="l">Pending</div></div></div>
    </div>
</div>

<!-- Alerts -->
<?php if ($msg): ?>
<div class="alert alert-success alert-dismissible fade show rounded-3"><i class="bi bi-check-circle-fill me-2"></i><?php echo $msg; ?><button class="btn-close" data-bs-dismiss="alert"></button></div>
<?php endif; ?>
<?php if ($err): ?>
<div class="alert alert-danger alert-dismissible fade show rounded-3"><i class="bi bi-exclamation-circle-fill me-2"></i><?php echo $err; ?><button class="btn-close" data-bs-dismiss="alert"></button></div>
<?php endif; ?>

<!-- Tabs -->
<div class="d-flex gap-2 flex-wrap mb-4">
    <button class="tab-pill active" onclick="apTab('info',this)"><i class="bi bi-person me-1"></i>My Info</button>
    <button class="tab-pill" onclick="apTab('password',this)"><i class="bi bi-shield-lock me-1"></i>Password</button>
    <button class="tab-pill" onclick="apTab('notifications',this)"><i class="bi bi-bell me-1"></i>Notifications</button>
    <button class="tab-pill" onclick="apTab('overview',this)"><i class="bi bi-bar-chart me-1"></i>Store Overview</button>
</div>

<!-- TAB: Info -->
<div id="ap-info" class="tab-sec active">
    <div class="card ap-card">
        <div class="card-body">
            <h5 class="fw-bold mb-4"><i class="bi bi-person-fill text-primary me-2"></i>Admin Information</h5>
            <form method="POST">
                <div class="row g-3">
                    <div class="col-md-6">
                        <label class="form-label fw-semibold">Full Name</label>
                        <input type="text" class="form-control" name="full_name" value="<?php echo htmlspecialchars($admin['full_name'] ?? ''); ?>" placeholder="Your full name">
                    </div>
                    <div class="col-md-6">
                        <label class="form-label fw-semibold">Username <span class="text-danger">*</span></label>
                        <div class="input-group">
                            <span class="input-group-text"><i class="bi bi-at"></i></span>
                            <input type="text" class="form-control" name="username" value="<?php echo htmlspecialchars($admin['username']); ?>" required>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label fw-semibold">Email Address <span class="text-danger">*</span></label>
                        <div class="input-group">
                            <span class="input-group-text"><i class="bi bi-envelope"></i></span>
                            <input type="email" class="form-control" name="email" value="<?php echo htmlspecialchars($admin['email']); ?>" required>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label fw-semibold">Phone Number</label>
                        <div class="input-group">
                            <span class="input-group-text"><i class="bi bi-phone"></i></span>
                            <input type="tel" class="form-control" name="phone" value="<?php echo htmlspecialchars($admin['phone'] ?? ''); ?>" placeholder="10-digit number" maxlength="10">
                        </div>
                    </div>
                    <div class="col-12">
                        <div class="alert alert-light border small mb-0">
                            <i class="bi bi-shield-fill text-warning me-1"></i>
                            Admin accounts have full access. Keep your credentials secure and never share them.
                        </div>
                    </div>
                </div>
                <div class="mt-4 d-flex gap-2">
                    <button type="submit" name="update_info" class="btn btn-primary rounded-pill px-4"><i class="bi bi-check-lg me-2"></i>Save Changes</button>
                    <a href="profile.php" class="btn btn-outline-secondary rounded-pill px-4">Cancel</a>
                </div>
            </form>
        </div>
    </div>

    <!-- Account Summary -->
    <div class="card ap-card">
        <div class="card-body">
            <h6 class="fw-bold mb-3"><i class="bi bi-info-circle text-primary me-2"></i>Account Details</h6>
            <table class="table table-borderless mb-0">
                <tr><td class="text-muted fw-semibold" style="width:160px;">Admin ID</td><td>#<?php echo $userId; ?></td></tr>
                <tr><td class="text-muted fw-semibold">Username</td><td><?php echo htmlspecialchars($admin['username']); ?></td></tr>
                <tr><td class="text-muted fw-semibold">Email</td><td><?php echo htmlspecialchars($admin['email']); ?></td></tr>
                <tr><td class="text-muted fw-semibold">Phone</td><td><?php echo htmlspecialchars($admin['phone'] ?? '—'); ?></td></tr>
                <tr><td class="text-muted fw-semibold">Role</td><td><span class="badge bg-warning text-dark">Administrator</span></td></tr>
                <tr><td class="text-muted fw-semibold">Member Since</td><td><?php echo date('F d, Y', strtotime($admin['created_at'])); ?></td></tr>
            </table>
        </div>
    </div>
</div>

<!-- TAB: Password -->
<div id="ap-password" class="tab-sec">
    <div class="card ap-card">
        <div class="card-body">
            <h5 class="fw-bold mb-4"><i class="bi bi-shield-lock-fill text-primary me-2"></i>Change Password</h5>
            <form method="POST" style="max-width:480px;">
                <div class="mb-3">
                    <label class="form-label fw-semibold">Current Password</label>
                    <div class="input-group">
                        <input type="password" class="form-control" name="current_password" id="ap_cur" required placeholder="Enter current password">
                        <button type="button" class="input-group-text bg-light" onclick="apToggle('ap_cur',this)"><i class="bi bi-eye"></i></button>
                    </div>
                </div>
                <div class="mb-3">
                    <label class="form-label fw-semibold">New Password</label>
                    <div class="input-group">
                        <input type="password" class="form-control" name="new_password" id="ap_new" required minlength="6" placeholder="Minimum 6 characters" oninput="apStrength(this.value)">
                        <button type="button" class="input-group-text bg-light" onclick="apToggle('ap_new',this)"><i class="bi bi-eye"></i></button>
                    </div>
                    <div class="pw-bar" id="ap_strength_bar" style="width:0;background:#e5e7eb;"></div>
                    <small id="ap_strength_lbl" class="text-muted">Enter a password</small>
                </div>
                <div class="mb-4">
                    <label class="form-label fw-semibold">Confirm New Password</label>
                    <div class="input-group">
                        <input type="password" class="form-control" name="confirm_password" id="ap_conf" required placeholder="Re-enter new password" oninput="apMatch()">
                        <button type="button" class="input-group-text bg-light" onclick="apToggle('ap_conf',this)"><i class="bi bi-eye"></i></button>
                    </div>
                    <small id="ap_match_lbl"></small>
                </div>
                <div class="alert alert-warning small mb-3">
                    <i class="bi bi-exclamation-triangle-fill me-1"></i>
                    As an admin, use a strong password (8+ chars, uppercase, numbers, symbols).
                </div>
                <button type="submit" name="change_password" class="btn btn-primary rounded-pill px-4 fw-semibold"><i class="bi bi-shield-check me-2"></i>Update Password</button>
            </form>
        </div>
    </div>
</div>

<!-- TAB: Notifications -->
<div id="ap-notifications" class="tab-sec">
    <div class="card ap-card">
        <div class="card-body">
            <h5 class="fw-bold mb-1"><i class="bi bi-bell-fill text-primary me-2"></i>Notification Preferences</h5>
            <p class="text-muted small mb-4">Choose what alerts you want to see in the admin panel.</p>
            <?php
            $notifs = [
                ['icon'=>'bi-cart-check','color'=>'success','title'=>'New Orders','desc'=>'Alert when a new order is placed','key'=>'notif_orders'],
                ['icon'=>'bi-star','color'=>'warning','title'=>'Pending Reviews','desc'=>'Alert when new product reviews need approval','key'=>'notif_reviews'],
                ['icon'=>'bi-chat-dots','color'=>'info','title'=>'New Feedback','desc'=>'Alert when customer feedback is submitted','key'=>'notif_feedback'],
                ['icon'=>'bi-exclamation-triangle','color'=>'danger','title'=>'Low Stock','desc'=>'Alert when product stock falls below threshold','key'=>'notif_stock'],
                ['icon'=>'bi-person-plus','color'=>'primary','title'=>'New Registrations','desc'=>'Alert when a new user registers','key'=>'notif_users'],
                ['icon'=>'bi-cash-stack','color'=>'success','title'=>'Payments & Refunds','desc'=>'Alert on successful payments or refund requests','key'=>'notif_payments'],
            ];
            foreach ($notifs as $n): ?>
            <div class="notif-row">
                <div class="d-flex align-items-center gap-3">
                    <div class="bg-<?php echo $n['color']; ?> bg-opacity-10 p-2 rounded-3" style="font-size:1.3rem;">
                        <i class="bi <?php echo $n['icon']; ?> text-<?php echo $n['color']; ?>"></i>
                    </div>
                    <div>
                        <div class="fw-semibold"><?php echo $n['title']; ?></div>
                        <div class="text-muted small"><?php echo $n['desc']; ?></div>
                    </div>
                </div>
                <div class="form-check form-switch mb-0">
                    <input class="form-check-input" type="checkbox" role="switch" id="<?php echo $n['key']; ?>" checked style="width:2.2em;height:1.2em;cursor:pointer;">
                </div>
            </div>
            <?php endforeach; ?>
            <div class="mt-4">
                <button class="btn btn-primary rounded-pill px-4" onclick="alert('Notification preferences saved!')"><i class="bi bi-check-lg me-2"></i>Save Preferences</button>
            </div>
        </div>
    </div>
</div>

<!-- TAB: Store Overview -->
<div id="ap-overview" class="tab-sec">
    <div class="row g-3 mb-4">
        <?php
        $cards = [
            ['label'=>'Total Orders','val'=>$totalOrders,'icon'=>'bi-bag-check','color'=>'primary'],
            ['label'=>'Total Customers','val'=>$totalUsers,'icon'=>'bi-people','color'=>'success'],
            ['label'=>'Revenue (Delivered)','val'=>'₹'.number_format($totalRevenue,2),'icon'=>'bi-currency-rupee','color'=>'warning'],
            ['label'=>'Pending Orders','val'=>$pendingOrders,'icon'=>'bi-clock','color'=>'danger'],
            ['label'=>'Pending Reviews','val'=>$pendingReviews,'icon'=>'bi-star','color'=>'warning'],
            ['label'=>'Pending Feedback','val'=>$pendingFeedback,'icon'=>'bi-chat-dots','color'=>'info'],
        ];
        foreach ($cards as $c): ?>
        <div class="col-md-4 col-sm-6">
            <div class="card ap-card mb-0">
                <div class="card-body d-flex align-items-center gap-3 py-3">
                    <div class="bg-<?php echo $c['color']; ?> bg-opacity-10 p-3 rounded-3">
                        <i class="bi <?php echo $c['icon']; ?> fs-4 text-<?php echo $c['color']; ?>"></i>
                    </div>
                    <div>
                        <div class="text-muted small"><?php echo $c['label']; ?></div>
                        <div class="fw-bold fs-5"><?php echo $c['val']; ?></div>
                    </div>
                </div>
            </div>
        </div>
        <?php endforeach; ?>
    </div>

    <div class="card ap-card">
        <div class="card-body">
            <h6 class="fw-bold mb-3"><i class="bi bi-lightning-fill text-warning me-2"></i>Quick Admin Actions</h6>
            <div class="row g-2">
                <div class="col-6 col-md-3"><a href="orders.php" class="btn btn-outline-primary w-100 rounded-3 py-3"><i class="bi bi-cart d-block fs-4 mb-1"></i><span class="small">Orders</span></a></div>
                <div class="col-6 col-md-3"><a href="products.php" class="btn btn-outline-success w-100 rounded-3 py-3"><i class="bi bi-box d-block fs-4 mb-1"></i><span class="small">Products</span></a></div>
                <div class="col-6 col-md-3"><a href="reviews.php" class="btn btn-outline-warning w-100 rounded-3 py-3"><i class="bi bi-star d-block fs-4 mb-1"></i><span class="small">Reviews</span></a></div>
                <div class="col-6 col-md-3"><a href="feedback.php" class="btn btn-outline-info w-100 rounded-3 py-3"><i class="bi bi-chat-dots d-block fs-4 mb-1"></i><span class="small">Feedback</span></a></div>
                <div class="col-6 col-md-3"><a href="users.php" class="btn btn-outline-secondary w-100 rounded-3 py-3"><i class="bi bi-people d-block fs-4 mb-1"></i><span class="small">Users</span></a></div>
                <div class="col-6 col-md-3"><a href="stock.php" class="btn btn-outline-danger w-100 rounded-3 py-3"><i class="bi bi-archive d-block fs-4 mb-1"></i><span class="small">Stock</span></a></div>
                <div class="col-6 col-md-3"><a href="coupons.php" class="btn btn-outline-success w-100 rounded-3 py-3"><i class="bi bi-tag d-block fs-4 mb-1"></i><span class="small">Coupons</span></a></div>
                <div class="col-6 col-md-3"><a href="reports.php" class="btn btn-outline-primary w-100 rounded-3 py-3"><i class="bi bi-graph-up d-block fs-4 mb-1"></i><span class="small">Reports</span></a></div>
            </div>
        </div>
    </div>
</div>

<script>
function apTab(name, btn) {
    document.querySelectorAll('.tab-sec').forEach(s => s.classList.remove('active'));
    document.querySelectorAll('.tab-pill').forEach(b => b.classList.remove('active'));
    document.getElementById('ap-' + name).classList.add('active');
    btn.classList.add('active');
}
function apToggle(id, btn) {
    const el = document.getElementById(id);
    const showing = el.type === 'text';
    el.type = showing ? 'password' : 'text';
    btn.innerHTML = showing ? '<i class="bi bi-eye"></i>' : '<i class="bi bi-eye-slash"></i>';
}
function apStrength(pw) {
    const bar = document.getElementById('ap_strength_bar');
    const lbl = document.getElementById('ap_strength_lbl');
    let score = 0;
    if (pw.length >= 6) score++;
    if (pw.length >= 10) score++;
    if (/[A-Z]/.test(pw) && /[a-z]/.test(pw)) score++;
    if (/[0-9]/.test(pw)) score++;
    if (/[^A-Za-z0-9]/.test(pw)) score++;
    const lvl = [
        {w:'0%',c:'#e5e7eb',t:''},
        {w:'20%',c:'#ef4444',t:'Very Weak'},
        {w:'40%',c:'#f97316',t:'Weak'},
        {w:'60%',c:'#eab308',t:'Fair'},
        {w:'80%',c:'#22c55e',t:'Strong'},
        {w:'100%',c:'#16a34a',t:'Very Strong'},
    ][score];
    bar.style.width = lvl.w; bar.style.background = lvl.c;
    lbl.textContent = lvl.t; lbl.style.color = lvl.c;
}
function apMatch() {
    const np = document.getElementById('ap_new').value;
    const cp = document.getElementById('ap_conf').value;
    const lbl = document.getElementById('ap_match_lbl');
    if (!cp) { lbl.textContent=''; return; }
    lbl.innerHTML = np===cp
        ? '<span class="text-success"><i class="bi bi-check-circle-fill me-1"></i>Passwords match</span>'
        : '<span class="text-danger"><i class="bi bi-x-circle-fill me-1"></i>Passwords do not match</span>';
}
</script>

<?php include 'footer.php'; ?>
