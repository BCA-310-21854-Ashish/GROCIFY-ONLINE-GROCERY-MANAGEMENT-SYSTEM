<?php

session_start();
$payment_id = htmlspecialchars($_GET['payment_id'] ?? 'N/A');
$order_id   = intval($_GET['order_id'] ?? 0);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Payment Successful - Grocify</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <style>
        body { background: linear-gradient(135deg,#f0fdf4,#ecfdf5); min-height:100vh; }
        .success-card { max-width:480px; margin:60px auto; border-radius:24px; }
        .tick-circle { width:100px; height:100px; border-radius:50%; background:linear-gradient(135deg,#10b981,#059669);
            display:flex; align-items:center; justify-content:center; margin:0 auto 1.5rem; }
        .confetti { position:fixed; top:0; left:0; width:100%; height:100%; pointer-events:none; z-index:9999; }
    </style>
</head>
<body class="py-4">
<canvas class="confetti" id="confettiCanvas"></canvas>
<div class="container">
    <div class="success-card card border-0 shadow-lg p-5 text-center">
        <div class="tick-circle">
            <i class="bi bi-check-lg text-white" style="font-size:3rem;"></i>
        </div>
        <h2 class="fw-bold text-success mb-2">Payment Successful! 🎉</h2>
        <p class="text-muted mb-4">Your grocery order has been placed successfully and is being processed.</p>

        <div class="bg-light rounded-3 p-3 mb-4 text-start">
            <?php if($order_id): ?>
            <div class="d-flex justify-content-between mb-1">
                <span class="text-muted">Order #</span>
                <strong>#<?php echo $order_id; ?></strong>
            </div>
            <?php endif; ?>
            <div class="d-flex justify-content-between mb-1">
                <span class="text-muted">Payment ID</span>
                <code class="small"><?php echo $payment_id; ?></code>
            </div>
            <div class="d-flex justify-content-between">
                <span class="text-muted">Estimated Delivery</span>
                <strong class="text-success">2–3 Business Days</strong>
            </div>
        </div>

        <div class="d-grid gap-2">
            <?php if($order_id): ?>
            <a href="../gst_invoice.php?order_id=<?php echo $order_id; ?>" class="btn btn-outline-success">
                📄 Download GST Invoice
            </a>
            <a href="../order_details.php?id=<?php echo $order_id; ?>" class="btn btn-outline-primary">
                📦 View Order Details
            </a>
            <?php endif; ?>
            <a href="../index.php" class="btn btn-success">🛒 Continue Shopping</a>
        </div>
    </div>
</div>

<script>
// Simple confetti
const canvas = document.getElementById('confettiCanvas');
const ctx = canvas.getContext('2d');
canvas.width = window.innerWidth;
canvas.height = window.innerHeight;
const pieces = [];
const colors = ['#10b981','#3b82f6','#f59e0b','#ec4899','#8b5cf6','#ef4444'];
for (let i=0;i<120;i++) {
    pieces.push({
        x: Math.random()*canvas.width, y: Math.random()*canvas.height-canvas.height,
        r: Math.random()*8+4, d: Math.random()*pieces.length,
        color: colors[Math.floor(Math.random()*colors.length)],
        tilt: Math.floor(Math.random()*10)-10, tiltAngle:0, tiltAngleInc:Math.random()*0.07+0.05
    });
}
let angle=0, ticks=0;
function draw() {
    ctx.clearRect(0,0,canvas.width,canvas.height);
    angle+=0.01;
    ticks++;
    pieces.forEach((p,i)=>{
        p.tiltAngle+=p.tiltAngleInc;
        p.y+=(Math.cos(angle+p.d)+3+p.r/2)*0.7;
        p.tilt=Math.sin(p.tiltAngle-i/3)*15;
        ctx.beginPath();
        ctx.lineWidth=p.r/2;
        ctx.strokeStyle=p.color;
        ctx.moveTo(p.x+p.tilt+p.r/4,p.y);
        ctx.lineTo(p.x+p.tilt,p.y+p.tilt+p.r/4);
        ctx.stroke();
        if(p.y>canvas.height){ p.x=Math.random()*canvas.width; p.y=-20; }
    });
    if(ticks<300) requestAnimationFrame(draw);
    else ctx.clearRect(0,0,canvas.width,canvas.height);
}
draw();
</script>
</body>
</html>
