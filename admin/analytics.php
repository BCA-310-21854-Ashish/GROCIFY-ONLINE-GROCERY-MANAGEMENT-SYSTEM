<?php

require_once '../config/db.php';
include 'header.php';

function safeCount($conn,$sql){$r=$conn->query($sql);if(!$r)return 0;$row=$r->fetch_row();return $row?(int)$row[0]:0;}
function safeSum($conn,$sql){$r=$conn->query($sql);if(!$r)return 0;$row=$r->fetch_row();return($row&&$row[0]!==null)?(float)$row[0]:0;}

// Core stats
$totalOrders    = safeCount($conn,"SELECT COUNT(*) FROM orders");
$totalRevenue   = safeSum($conn,"SELECT SUM(total_amount) FROM orders");
$totalUsers     = safeCount($conn,"SELECT COUNT(*) FROM users WHERE is_admin=0");
$totalProducts  = safeCount($conn,"SELECT COUNT(*) FROM products");
$todayOrders    = safeCount($conn,"SELECT COUNT(*) FROM orders WHERE DATE(order_date)=CURDATE()");
$todayRevenue   = safeSum($conn,"SELECT SUM(total_amount) FROM orders WHERE DATE(order_date)=CURDATE()");
$pendingOrders  = safeCount($conn,"SELECT COUNT(*) FROM orders WHERE status='Pending'");
$deliveredOrders= safeCount($conn,"SELECT COUNT(*) FROM orders WHERE status='Delivered'");
$lowStock       = safeCount($conn,"SELECT COUNT(*) FROM products WHERE stock>0 AND stock<=low_stock_alert");
$outOfStock     = safeCount($conn,"SELECT COUNT(*) FROM products WHERE stock=0");
$wishlistTotal  = safeCount($conn,"SELECT COUNT(*) FROM wishlist");
$couponUsed     = safeCount($conn,"SELECT COUNT(*) FROM coupon_usage");
$reviewsPending = safeCount($conn,"SELECT COUNT(*) FROM reviews WHERE status='Pending'");
$avgOrderValue  = $totalOrders > 0 ? round($totalRevenue/$totalOrders,2) : 0;

// Monthly revenue - last 6 months
$monthly = $conn->query("SELECT DATE_FORMAT(order_date,'%b %Y') as m, SUM(total_amount) as rev, COUNT(*) as cnt
    FROM orders GROUP BY DATE_FORMAT(order_date,'%Y-%m') ORDER BY MIN(order_date) DESC LIMIT 6");
$mLabels=$mRevenue=$mOrders=[];
while($r=$monthly->fetch_assoc()){array_unshift($mLabels,$r['m']);array_unshift($mRevenue,round($r['rev'],2));array_unshift($mOrders,$r['cnt']);}

// Category performance
$catPerf = $conn->query("SELECT p.category, COUNT(oi.id) as units, SUM(oi.price*oi.quantity) as revenue
    FROM order_items oi JOIN products p ON oi.product_id=p.id
    GROUP BY p.category ORDER BY revenue DESC LIMIT 6");
$catLabels=$catRevenue=[];
while($r=$catPerf->fetch_assoc()){$catLabels[]=$r['category'];$catRevenue[]=round($r['revenue'],2);}

// Order status breakdown
$statusData = $conn->query("SELECT status, COUNT(*) as cnt FROM orders GROUP BY status");
$statusLabels=$statusCounts=[];
while($r=$statusData->fetch_assoc()){$statusLabels[]=$r['status'];$statusCounts[]=$r['cnt'];}

// Top products
$topProducts = $conn->query("SELECT p.name, SUM(oi.quantity) as qty_sold, SUM(oi.price*oi.quantity) as revenue
    FROM order_items oi JOIN products p ON oi.product_id=p.id
    GROUP BY oi.product_id ORDER BY qty_sold DESC LIMIT 5");

// Recent orders
$recentOrders = $conn->query("SELECT o.id, o.total_amount, o.status, o.order_date, u.username
    FROM orders o JOIN users u ON o.user_id=u.id ORDER BY o.order_date DESC LIMIT 8");
?>

<h2 class="fw-bold mb-4">📊 Dashboard Analytics</h2>

<!-- KPI Cards Row 1 -->
<div class="row g-3 mb-4">
<?php
$kpis = [
    ['label'=>'Total Revenue','value'=>'₹'.number_format($totalRevenue,0),'icon'=>'💰','bg'=>'linear-gradient(135deg,#10b981,#059669)'],
    ['label'=>'Total Orders','value'=>$totalOrders,'icon'=>'📦','bg'=>'linear-gradient(135deg,#3b82f6,#2563eb)'],
    ['label'=>'Total Customers','value'=>$totalUsers,'icon'=>'👥','bg'=>'linear-gradient(135deg,#8b5cf6,#7c3aed)'],
    ['label'=>'Products','value'=>$totalProducts,'icon'=>'🛍️','bg'=>'linear-gradient(135deg,#f59e0b,#d97706)'],
    ['label'=>'Today Revenue','value'=>'₹'.number_format($todayRevenue,0),'icon'=>'📅','bg'=>'linear-gradient(135deg,#ec4899,#be185d)'],
    ['label'=>'Today Orders','value'=>$todayOrders,'icon'=>'🔔','bg'=>'linear-gradient(135deg,#14b8a6,#0d9488)'],
    ['label'=>'Avg Order','value'=>'₹'.number_format($avgOrderValue,0),'icon'=>'📈','bg'=>'linear-gradient(135deg,#6366f1,#4f46e5)'],
    ['label'=>'Pending','value'=>$pendingOrders,'icon'=>'⏳','bg'=>'linear-gradient(135deg,#f97316,#ea580c)'],
];
foreach($kpis as $k): ?>
<div class="col-6 col-md-3">
    <div class="rounded-4 p-3 text-white d-flex align-items-center gap-3 shadow-sm" style="background:<?php echo $k['bg']; ?>;">
        <div style="font-size:2rem;"><?php echo $k['icon']; ?></div>
        <div><div class="small opacity-75"><?php echo $k['label']; ?></div><div class="fw-bold fs-5"><?php echo $k['value']; ?></div></div>
    </div>
</div>
<?php endforeach; ?>
</div>

<!-- KPI Cards Row 2 -->
<div class="row g-3 mb-4">
    <div class="col-6 col-md-3">
        <div class="card border-0 shadow-sm rounded-4 text-center p-3">
            <div class="text-danger fs-3">📦</div>
            <div class="fw-bold text-danger"><?php echo $outOfStock; ?></div>
            <div class="small text-muted">Out of Stock</div>
        </div>
    </div>
    <div class="col-6 col-md-3">
        <div class="card border-0 shadow-sm rounded-4 text-center p-3">
            <div class="text-warning fs-3">⚠️</div>
            <div class="fw-bold text-warning"><?php echo $lowStock; ?></div>
            <div class="small text-muted">Low Stock</div>
        </div>
    </div>
    <div class="col-6 col-md-3">
        <div class="card border-0 shadow-sm rounded-4 text-center p-3">
            <div class="text-danger fs-3">❤️</div>
            <div class="fw-bold text-danger"><?php echo $wishlistTotal; ?></div>
            <div class="small text-muted">Wishlisted</div>
        </div>
    </div>
    <div class="col-6 col-md-3">
        <div class="card border-0 shadow-sm rounded-4 text-center p-3">
            <div class="text-primary fs-3">⭐</div>
            <div class="fw-bold text-primary"><?php echo $reviewsPending; ?></div>
            <div class="small text-muted">Pending Reviews</div>
        </div>
    </div>
</div>

<!-- Charts Row -->
<div class="row g-4 mb-4">
    <div class="col-md-8">
        <div class="card border-0 shadow-sm rounded-4 p-4">
            <h6 class="fw-bold mb-3">📈 Monthly Revenue & Orders (Last 6 Months)</h6>
            <canvas id="revenueChart" height="90"></canvas>
        </div>
    </div>
    <div class="col-md-4">
        <div class="card border-0 shadow-sm rounded-4 p-4">
            <h6 class="fw-bold mb-3">🟢 Order Status</h6>
            <canvas id="statusChart" height="140"></canvas>
        </div>
    </div>
</div>

<div class="row g-4 mb-4">
    <div class="col-md-6">
        <div class="card border-0 shadow-sm rounded-4 p-4">
            <h6 class="fw-bold mb-3">🗂 Revenue by Category</h6>
            <canvas id="catChart" height="120"></canvas>
        </div>
    </div>
    <div class="col-md-6">
        <div class="card border-0 shadow-sm rounded-4 p-4">
            <h6 class="fw-bold mb-3">🏆 Top 5 Products</h6>
            <table class="table table-sm align-middle">
                <thead><tr><th>#</th><th>Product</th><th>Sold</th><th>Revenue</th></tr></thead>
                <tbody>
                <?php $i=1; while($p=$topProducts->fetch_assoc()): ?>
                <tr>
                    <td><span class="badge bg-<?php echo $i==1?'warning text-dark':($i==2?'secondary':'light text-dark'); ?>"><?php echo $i++; ?></span></td>
                    <td><?php echo htmlspecialchars($p['name']); ?></td>
                    <td><span class="badge bg-success"><?php echo $p['qty_sold']; ?></span></td>
                    <td>₹<?php echo number_format($p['revenue'],0); ?></td>
                </tr>
                <?php endwhile; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<!-- Recent Orders -->
<div class="card border-0 shadow-sm rounded-4">
    <div class="card-header bg-transparent fw-bold border-0 pt-4 px-4">🕐 Recent Orders</div>
    <table class="table table-hover align-middle mb-0">
        <thead class="table-light"><tr><th>Order #</th><th>Customer</th><th>Amount</th><th>Status</th><th>Date</th><th>Action</th></tr></thead>
        <tbody>
        <?php while($o=$recentOrders->fetch_assoc()):
            $badge = match($o['status']) {
                'Delivered'=>'success','Pending'=>'warning text-dark','Processing'=>'info text-dark',
                'Shipped'=>'primary','Cancelled'=>'danger', default=>'secondary'
            }; ?>
        <tr>
            <td><strong>#<?php echo $o['id']; ?></strong></td>
            <td><?php echo htmlspecialchars($o['username']); ?></td>
            <td>₹<?php echo number_format($o['total_amount'],2); ?></td>
            <td><span class="badge bg-<?php echo $badge; ?>"><?php echo $o['status']; ?></span></td>
            <td class="text-muted small"><?php echo date('d M Y', strtotime($o['order_date'])); ?></td>
            <td><a href="orders.php" class="btn btn-sm btn-outline-primary">View</a></td>
        </tr>
        <?php endwhile; ?>
        </tbody>
    </table>
</div>

<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.min.js"></script>
<script>
// Revenue + Orders chart
new Chart(document.getElementById('revenueChart'), {
    type:'bar',
    data: {
        labels: <?php echo json_encode($mLabels); ?>,
        datasets: [
            { label:'Revenue (₹)', data: <?php echo json_encode($mRevenue); ?>,
              backgroundColor:'rgba(16,185,129,0.7)', borderRadius:6, yAxisID:'y' },
            { label:'Orders', data: <?php echo json_encode($mOrders); ?>,
              type:'line', borderColor:'#3b82f6', backgroundColor:'rgba(59,130,246,0.1)',
              tension:0.4, pointRadius:5, yAxisID:'y1' }
        ]
    },
    options: {
        responsive:true, interaction:{mode:'index'},
        scales: {
            y:  { position:'left',  ticks:{ callback:v=>'₹'+v.toLocaleString() } },
            y1: { position:'right', grid:{drawOnChartArea:false} }
        }
    }
});

// Status donut
new Chart(document.getElementById('statusChart'), {
    type:'doughnut',
    data: {
        labels: <?php echo json_encode($statusLabels); ?>,
        datasets:[{ data: <?php echo json_encode($statusCounts); ?>,
            backgroundColor:['#10b981','#f59e0b','#3b82f6','#8b5cf6','#ef4444','#6b7280'] }]
    },
    options:{ responsive:true, plugins:{ legend:{position:'bottom'} } }
});

// Category bar
new Chart(document.getElementById('catChart'), {
    type:'bar',
    data: {
        labels: <?php echo json_encode($catLabels); ?>,
        datasets:[{ label:'Revenue (₹)', data: <?php echo json_encode($catRevenue); ?>,
            backgroundColor:['#10b981','#3b82f6','#f59e0b','#8b5cf6','#ec4899','#14b8a6'],
            borderRadius:6 }]
    },
    options:{ indexAxis:'y', responsive:true, plugins:{legend:{display:false}},
              scales:{ x:{ ticks:{ callback:v=>'₹'+v.toLocaleString() } } } }
});
</script>

<?php include 'footer.php'; ?>
