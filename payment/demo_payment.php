<?php

session_start();
require_once '../config/db.php';

if (!isset($_SESSION['user_id'])) { header('Location: ../auth/login.php'); exit(); }
if (empty($_SESSION['cart']))     { header('Location: ../cart.php'); exit(); }

$total    = floatval($_GET['total'] ?? 0);
$coupon   = $_SESSION['coupon'] ?? null;
$discount = $coupon ? $coupon['discount'] : 0;
$payable  = max(0, $total);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Demo Payment Gateway - Grocify</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <style>
        body { background: linear-gradient(135deg,#f0fdf4,#ecfdf5); min-height:100vh; font-family:'Segoe UI',sans-serif; }
        .pay-card { max-width:500px; margin:auto; }
        .method-btn { cursor:pointer; border:2px solid #e5e7eb; transition:all .2s; border-radius:14px; padding:14px; }
        .method-btn.active { border-color:#16a34a; background:#f0fdf4; }
        .method-btn:hover { border-color:#16a34a; }
        #cardForm, #netbankForm, #upiForm, #walletForm { display:none; }
        .processing-overlay { display:none; position:fixed; inset:0; background:rgba(0,0,0,.55);
            z-index:9999; align-items:center; justify-content:center; flex-direction:column; color:#fff; }
        .spinner-grow { width:3rem; height:3rem; }
        .card-input { letter-spacing:3px; font-family:monospace; }
        .upi-app { width:52px; height:52px; border-radius:12px; cursor:pointer; transition:.2s; object-fit:contain; }
        .upi-app:hover { transform:scale(1.12); }
    </style>
</head>
<body class="py-4">

<!-- Processing Overlay -->
<div class="processing-overlay" id="processingOverlay">
    <div class="spinner-grow text-light mb-3"></div>
    <div class="fs-5 fw-bold" id="processingText">Processing Payment...</div>
    <div class="small opacity-75 mt-1">Please do not close this window</div>
</div>

<div class="pay-card mx-auto px-3">
    <!-- Header -->
    <div class="text-center mb-4">
        <div class="fw-bold fs-3 text-success">🛒 Grocify Pay</div>
        <div class="text-muted small">Secure Demo Payment Gateway</div>
        <span class="badge bg-warning text-dark mt-1">⚡ DEMO MODE — No real charges</span>
    </div>

    <!-- Amount Card -->
    <div class="card border-0 shadow rounded-4 mb-4 p-4" style="background:linear-gradient(135deg,#16a34a,#15803d);color:#fff;">
        <div class="d-flex justify-content-between align-items-center">
            <div>
                <div class="opacity-75 small">Amount Payable</div>
                <div class="fw-bold" style="font-size:2.2rem;">₹<?php echo number_format($payable,2); ?></div>
                <?php if($discount > 0): ?>
                <div class="opacity-75 small">You saved ₹<?php echo number_format($discount,2); ?> 🎉</div>
                <?php endif; ?>
            </div>
            <div class="text-end opacity-75 small">
                <div style="font-size:2rem;">🔒</div>
                <div>Encrypted</div>
                <div><?php echo date('d M Y'); ?></div>
            </div>
        </div>
    </div>

    <!-- Payment Method Selection -->
    <div class="fw-bold mb-3">Choose Payment Method</div>
    <div class="row g-2 mb-3">
        <div class="col-6">
            <div class="method-btn text-center active" onclick="selectMethod('upi',this)">
                <div style="font-size:1.5rem;">📱</div>
                <div class="fw-semibold small">UPI</div>
            </div>
        </div>
        <div class="col-6">
            <div class="method-btn text-center" onclick="selectMethod('card',this)">
                <div style="font-size:1.5rem;">💳</div>
                <div class="fw-semibold small">Card</div>
            </div>
        </div>
        <div class="col-6">
            <div class="method-btn text-center" onclick="selectMethod('netbank',this)">
                <div style="font-size:1.5rem;">🏦</div>
                <div class="fw-semibold small">Net Banking</div>
            </div>
        </div>
        <div class="col-6">
            <div class="method-btn text-center" onclick="selectMethod('wallet',this)">
                <div style="font-size:1.5rem;">👜</div>
                <div class="fw-semibold small">Wallet</div>
            </div>
        </div>
    </div>

    <!-- UPI Form -->
    <div id="upiForm" class="card border-0 shadow-sm rounded-4 p-4 mb-3" style="display:block;">
        <h6 class="fw-bold mb-3">Pay via UPI</h6>
        <div class="d-flex gap-3 mb-3 flex-wrap">
            <?php
            $upiApps = [
                ['name'=>'GPay','emoji'=>'🟢','color'=>'#4CAF50'],
                ['name'=>'PhonePe','emoji'=>'🟣','color'=>'#5F259F'],
                ['name'=>'Paytm','emoji'=>'🔵','color'=>'#00BAF2'],
                ['name'=>'BHIM','emoji'=>'🟠','color'=>'#FF6600'],
            ];
            foreach($upiApps as $app): ?>
            <div class="text-center" onclick="selectUpiApp('<?php echo $app['name']; ?>')">
                <div style="width:52px;height:52px;border-radius:12px;background:<?php echo $app['color']; ?>;
                    display:flex;align-items:center;justify-content:center;font-size:1.5rem;cursor:pointer;
                    transition:.2s;margin:auto;" class="upi-btn">
                    <?php echo $app['emoji']; ?>
                </div>
                <div class="small mt-1"><?php echo $app['name']; ?></div>
            </div>
            <?php endforeach; ?>
        </div>
        <div class="mb-3">
            <label class="form-label small fw-semibold">Or enter UPI ID</label>
            <div class="input-group">
                <input type="text" id="upiId" class="form-control" placeholder="yourname@upi">
                <button class="btn btn-outline-success" onclick="verifyUpi()">Verify</button>
            </div>
            <div id="upiVerifyMsg" class="small mt-1"></div>
        </div>
    </div>

    <!-- Card Form -->
    <div id="cardForm" class="card border-0 shadow-sm rounded-4 p-4 mb-3">
        <h6 class="fw-bold mb-3">💳 Debit / Credit Card</h6>
        <div class="mb-3">
            <label class="form-label small fw-semibold">Card Number</label>
            <input type="text" id="cardNumber" class="form-control card-input" maxlength="19"
                placeholder="1234 5678 9012 3456" oninput="formatCard(this)">
        </div>
        <div class="mb-3">
            <label class="form-label small fw-semibold">Cardholder Name</label>
            <input type="text" id="cardName" class="form-control" placeholder="As on card">
        </div>
        <div class="row g-2 mb-3">
            <div class="col-6">
                <label class="form-label small fw-semibold">Expiry</label>
                <input type="text" id="cardExpiry" class="form-control" maxlength="5" placeholder="MM/YY" oninput="formatExpiry(this)">
            </div>
            <div class="col-6">
                <label class="form-label small fw-semibold">CVV</label>
                <input type="password" id="cardCvv" class="form-control" maxlength="3" placeholder="•••">
            </div>
        </div>
    </div>

    <!-- Net Banking Form -->
    <div id="netbankForm" class="card border-0 shadow-sm rounded-4 p-4 mb-3">
        <h6 class="fw-bold mb-3">🏦 Net Banking</h6>
        <select id="bankSelect" class="form-select mb-3">
            <option value="">-- Select Your Bank --</option>
            <option>State Bank of India</option>
            <option>HDFC Bank</option>
            <option>ICICI Bank</option>
            <option>Axis Bank</option>
            <option>Kotak Mahindra Bank</option>
            <option>Punjab National Bank</option>
            <option>Bank of Baroda</option>
            <option>Canara Bank</option>
        </select>
    </div>

    <!-- Wallet Form -->
    <div id="walletForm" class="card border-0 shadow-sm rounded-4 p-4 mb-3">
        <h6 class="fw-bold mb-3">👜 Wallet</h6>
        <div class="row g-2">
            <?php
            $wallets = ['Paytm Wallet'=>'🔵','Amazon Pay'=>'🟡','Mobikwik'=>'🟣','Ola Money'=>'⚫'];
            foreach($wallets as $w=>$e): ?>
            <div class="col-6">
                <div class="method-btn text-center" onclick="selectWallet('<?php echo $w; ?>', this)">
                    <span class="me-1"><?php echo $e; ?></span><?php echo $w; ?>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
    </div>

    <!-- Pay Button -->
    <form id="payForm" action="../new_checkout.php" method="POST">
        <input type="hidden" name="billing_name"    value="<?php echo htmlspecialchars($_SESSION['checkout_billing']['name']    ?? 'Customer'); ?>">
        <input type="hidden" name="billing_email"   value="<?php echo htmlspecialchars($_SESSION['checkout_billing']['email']   ?? ''); ?>">
        <input type="hidden" name="billing_phone"   value="<?php echo htmlspecialchars($_SESSION['checkout_billing']['phone']   ?? ''); ?>">
        <input type="hidden" name="billing_address" value="<?php echo htmlspecialchars($_SESSION['checkout_billing']['address'] ?? ''); ?>">
        <input type="hidden" name="payment_method"  id="payMethodInput" value="UPI">
        <input type="hidden" name="payment_id"      id="payIdInput"     value="">
        <input type="hidden" name="coupon_code"     value="<?php echo htmlspecialchars($coupon['code']    ?? ''); ?>">
        <input type="hidden" name="discount_amount" value="<?php echo htmlspecialchars($coupon['discount'] ?? 0); ?>">

        <button type="button" class="btn btn-success btn-lg w-100 fw-bold rounded-4 py-3" onclick="payNow()">
            🔒 Pay ₹<?php echo number_format($payable,2); ?> Securely
        </button>
    </form>

    <div class="text-center text-muted small mt-3">
        <i class="bi bi-shield-check text-success"></i> 256-bit SSL Encrypted &nbsp;|&nbsp;
        <i class="bi bi-patch-check text-success"></i> PCI DSS Compliant
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script>
let currentMethod = 'upi';
let selectedWalletName = '';

function selectMethod(method, el) {
    currentMethod = method;
    document.querySelectorAll('.method-btn').forEach(b => b.classList.remove('active'));
    el.classList.add('active');
    ['cardForm','netbankForm','upiForm','walletForm'].forEach(id => {
        document.getElementById(id).style.display = 'none';
    });
    document.getElementById(method + 'Form').style.display = 'block';
    document.getElementById('payMethodInput').value =
        method === 'upi' ? 'UPI' : method === 'card' ? 'Card' :
        method === 'netbank' ? 'Net Banking' : 'Wallet';
}

function selectUpiApp(name) {
    document.getElementById('upiId').value = name.toLowerCase() + '@upi';
    document.getElementById('upiVerifyMsg').innerHTML = '<span class="text-success">✓ ' + name + ' selected</span>';
}

function verifyUpi() {
    const val = document.getElementById('upiId').value;
    const msg = document.getElementById('upiVerifyMsg');
    if (!val || !val.includes('@')) {
        msg.innerHTML = '<span class="text-danger">Invalid UPI ID format</span>'; return;
    }
    msg.innerHTML = '<span class="text-muted">Verifying...</span>';
    setTimeout(() => {
        msg.innerHTML = '<span class="text-success">✓ UPI ID verified</span>';
    }, 1000);
}

function selectWallet(name, el) {
    selectedWalletName = name;
    document.querySelectorAll('#walletForm .method-btn').forEach(b => b.classList.remove('active'));
    el.classList.add('active');
}

function formatCard(el) {
    let v = el.value.replace(/\D/g,'').substring(0,16);
    el.value = v.replace(/(.{4})/g,'$1 ').trim();
}
function formatExpiry(el) {
    let v = el.value.replace(/\D/g,'');
    if (v.length >= 2) v = v.substring(0,2) + '/' + v.substring(2,4);
    el.value = v;
}

function payNow() {
    // Basic validation
    if (currentMethod === 'card') {
        const num = document.getElementById('cardNumber').value.replace(/\s/g,'');
        const name = document.getElementById('cardName').value.trim();
        const cvv  = document.getElementById('cardCvv').value.trim();
        const exp  = document.getElementById('cardExpiry').value.trim();
        if (num.length < 16 || !name || cvv.length < 3 || !exp) {
            alert('Please fill all card details correctly.'); return;
        }
    } else if (currentMethod === 'netbank') {
        if (!document.getElementById('bankSelect').value) {
            alert('Please select your bank.'); return;
        }
    }

    // Generate demo payment ID
    const ts = Date.now();
    const rand = Math.random().toString(36).substring(2,8).toUpperCase();
    const methods = { upi:'UPI', card:'CARD', netbank:'NB', wallet:'WLLT' };
    const payId = `GROC-${methods[currentMethod]||'PAY'}-${rand}-${ts.toString().slice(-6)}`;
    document.getElementById('payIdInput').value = payId;

    // Show processing
    const overlay = document.getElementById('processingOverlay');
    overlay.style.display = 'flex';
    const steps = ['Connecting to payment gateway...','Verifying payment details...','Processing transaction...','Confirming payment...'];
    let i = 0;
    const interval = setInterval(() => {
        if (i < steps.length) {
            document.getElementById('processingText').textContent = steps[i++];
        } else {
            clearInterval(interval);
            document.getElementById('payForm').submit();
        }
    }, 800);
}
</script>
</body>
</html>
