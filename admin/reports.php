<?php

require_once '../config/db.php';
require_once '../config/report_helper.php';
include 'header.php';

// Get report for selected date
$selectedDate = isset($_GET['report_date']) ? trim($_GET['report_date']) : date('Y-m-d');

// Validate date format
if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $selectedDate)) {
    $selectedDate = date('Y-m-d');
}

// Get report data for today (existing reports)
$totalSales = $conn->query("SELECT SUM(total_amount) FROM orders WHERE status != 'Cancelled'")->fetch_row()[0] ?? 0;
$avgOrderValue = $conn->query("SELECT AVG(total_amount) FROM orders WHERE status != 'Cancelled'")->fetch_row()[0] ?? 0;
$totalOrders = $conn->query("SELECT COUNT(*) FROM orders")->fetch_row()[0];

// Top selling products
$topProducts = $conn->query("SELECT p.name, SUM(oi.quantity) as total_qty, SUM(oi.quantity * oi.price) as revenue 
                             FROM order_items oi JOIN products p ON oi.product_id = p.id 
                             GROUP BY oi.product_id ORDER BY total_qty DESC LIMIT 5");

// Sales by category
$categorySales = $conn->query("SELECT p.category, SUM(oi.quantity * oi.price) as revenue 
                               FROM order_items oi JOIN products p ON oi.product_id = p.id 
                               GROUP BY p.category ORDER BY revenue DESC");

// Daily sales last 7 days
$dailySales = $conn->query("SELECT DATE(order_date) as day, SUM(total_amount) as total 
                            FROM orders WHERE order_date >= DATE_SUB(CURDATE(), INTERVAL 7 DAY) AND status != 'Cancelled'
                            GROUP BY day ORDER BY day");
$dailyLabels = [];
$dailyData = [];
while($row = $dailySales->fetch_assoc()) {
    $dailyLabels[] = date('M d', strtotime($row['day']));
    $dailyData[] = round($row['total'], 2);
}

// Check if TCPDF is available
$tcpdfAvailable = class_exists('TCPDF');
?>

<h1 class="mb-4">Reports & Analytics</h1>
<div class="mb-3">
<a class="btn btn-success" href="export_csv_report.php?date=<?php echo $selectedDate; ?>">Export CSV</a>
<a class="btn btn-primary" href="export_excel_report.php?date=<?php echo $selectedDate; ?>">Export Excel</a>
<a class="btn btn-danger" href="generate_pdf_report.php?date=<?php echo $selectedDate; ?>">Export PDF</a>
</div>

<!-- PDF Report Generation Section -->
<div class="row mb-4">
    <div class="col-md-12">
        <div class="card">
            <div class="card-header bg-primary text-white">
                <h5 class="mb-0">📄 Generate Daily PDF Report</h5>
            </div>
            <div class="card-body">
                <div class="row align-items-end">
                    <div class="col-md-4">
                        <label for="report_date" class="form-label">Select Date</label>
                        <input type="date" class="form-control" id="report_date" value="<?php echo $selectedDate; ?>" max="<?php echo date('Y-m-d'); ?>">
                    </div>
                    <div class="col-md-4">
                        <button class="btn btn-primary" onclick="generateReport()">
                            📥 Generate Report
                        </button>
                        <button class="btn btn-success" onclick="generateReport('<?php echo date('Y-m-d'); ?>')">
                            📄 Today's Report
                        </button>
                    </div>
                    <div class="col-md-4">
                        <?php if (!$tcpdfAvailable): ?>
                            <div class="alert alert-warning mb-0">
                                <small>⚠️ TCPDF library not found. <a href="#install-tcpdf">Install guide</a></small>
                            </div>
                        <?php else: ?>
                            <div class="alert alert-success mb-0">
                                <small>✅ PDF generation ready</small>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<div class="row mb-4">
    <div class="col-md-4"><div class="card"><div class="card-body"><h5>Total Sales</h5><h2>₹<?php echo number_format($totalSales,2); ?></h2></div></div></div>
    <div class="col-md-4"><div class="card"><div class="card-body"><h5>Average Order Value</h5><h2>₹<?php echo number_format($avgOrderValue,2); ?></h2></div></div></div>
    <div class="col-md-4"><div class="card"><div class="card-body"><h5>Total Orders</h5><h2><?php echo $totalOrders; ?></h2></div></div></div>
</div>

<div class="row">
    <div class="col-md-6">
        <div class="card mb-4">
            <div class="card-header">Daily Sales (Last 7 Days)</div>
            <div class="card-body"><canvas id="dailyChart"></canvas></div>
        </div>
    </div>
    <div class="col-md-6">
        <div class="card mb-4">
            <div class="card-header">Top Selling Products</div>
            <div class="card-body p-0">
                <table class="table table-sm mb-0">
                    <thead><tr><th>Product</th><th>Qty Sold</th><th>Revenue</th></tr></thead>
                    <tbody>
                        <?php while($p = $topProducts->fetch_assoc()): ?>
                        <tr><td><?php echo htmlspecialchars($p['name']); ?></td><td><?php echo $p['total_qty']; ?></td><td>₹<?php echo number_format($p['revenue'],2); ?></td></tr>
                        <?php endwhile; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<div class="row">
    <div class="col-md-6">
        <div class="card">
            <div class="card-header">Sales by Category</div>
            <div class="card-body p-0">
                <table class="table table-sm mb-0">
                    <thead><tr><th>Category</th><th>Revenue</th></tr></thead>
                    <tbody>
                        <?php while($c = $categorySales->fetch_assoc()): ?>
                        <tr><td><?php echo $c['category'] ?: 'Uncategorized'; ?></td><td>₹<?php echo number_format($c['revenue'],2); ?></td></tr>
                        <?php endwhile; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<!-- TCPDF Installation Guide -->
<div class="row mt-4" id="install-tcpdf">
    <div class="col-md-12">
        <div class="card border-warning">
            <div class="card-header bg-warning text-dark">
                <h5 class="mb-0">ℹ️ TCPDF Installation Guide</h5>
            </div>
            <div class="card-body">
                <?php if ($tcpdfAvailable): ?>
                    <div class="alert alert-success">✅ TCPDF is already installed and working!</div>
                <?php else: ?>
                    <p><strong>To enable PDF report generation, install TCPDF:</strong></p>
                    <ol>
                        <li><a href="https://tcpdf.org/download.php" target="_blank">Download TCPDF from tcpdf.org</a></li>
                        <li>Extract the ZIP file</li>
                        <li>Create folder: <code>lib/tcpdf/</code> in your Grocify root directory</li>
                        <li>Copy contents of TCPDF to <code>lib/tcpdf/</code></li>
                        <li>Ensure <code>lib/tcpdf/tcpdf.php</code> exists</li>
                        <li>Refresh this page - PDF generation will be enabled</li>
                    </ol>
                    <p><strong>Or using Composer (if available):</strong></p>
                    <pre><code>composer require tecnickcom/tcpdf</code></pre>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
new Chart(document.getElementById('dailyChart'), {
    type: 'line',
    data: {
        labels: <?php echo json_encode($dailyLabels); ?>,
        datasets: [{
            label: 'Sales (₹)',
            data: <?php echo json_encode($dailyData); ?>,
            borderColor: 'rgb(75, 192, 192)',
            tension: 0.1
        }]
    }
});

function generateReport(date = null) {
    if (!date) {
        const dateInput = document.getElementById('report_date').value;
        if (!dateInput) {
            alert('Please select a date');
            return;
        }
        date = dateInput;
    }
    
    // Validate date
    if (!/^\d{4}-\d{2}-\d{2}$/.test(date)) {
        alert('Invalid date format');
        return;
    }
    
    // Check if future date
    if (new Date(date) > new Date()) {
        alert('Cannot generate report for future dates');
        return;
    }
    
    // Show loading indicator
    const btn = event.target;
    const originalText = btn.innerText;
    btn.innerText = '⏳ Generating...';
    btn.disabled = true;
    
    // Download PDF
    window.location.href = 'generate_pdf_report.php?date=' + date;
    
    // Reset button after 2 seconds
    setTimeout(() => {
        btn.innerText = originalText;
        btn.disabled = false;
    }, 2000);
}
</script>

<?php include 'footer.php'; ?>
