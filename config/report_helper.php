<?php


// Daily PDF Report Helper Functions
// Uses TCPDF library for PDF generation

// Path to TCPDF
define('TCPDF_PATH', __DIR__ . '/../tcpdf/');

// Load TCPDF if available
if (file_exists(TCPDF_PATH . 'tcpdf.php')) {
    require_once(TCPDF_PATH . 'tcpdf.php');
}

/**
 * Generate daily PDF report for given date
 * @param string $date Date in YYYY-MM-DD format (default: today)
 * @return array Report data (sales, orders, etc.)
 */
function getDailyReportData($date = null) {
    global $conn;
    
    if (!$date) {
        $date = date('Y-m-d');
    }
    
    // Validate date format
    if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $date)) {
        return false;
    }
    
    $nextDate = date('Y-m-d', strtotime($date . ' +1 day'));
    
    // Total sales for the day
    $salesResult = $conn->query("
        SELECT 
            SUM(total_amount) as total_sales,
            COUNT(*) as total_orders,
            AVG(total_amount) as avg_order_value
        FROM orders 
        WHERE DATE(order_date) = '$date' AND status != 'Cancelled'
    ");
    $salesData = $salesResult->fetch_assoc();
    
    // Top products sold that day
    $topProducts = [];
    $productResult = $conn->query("
        SELECT 
            p.name,
            SUM(oi.quantity) as qty_sold,
            SUM(oi.quantity * oi.price) as revenue
        FROM order_items oi
        JOIN products p ON oi.product_id = p.id
        JOIN orders o ON oi.order_id = o.id
        WHERE DATE(o.order_date) = '$date' AND o.status != 'Cancelled'
        GROUP BY oi.product_id
        ORDER BY qty_sold DESC
        LIMIT 10
    ");
    while ($row = $productResult->fetch_assoc()) {
        $topProducts[] = $row;
    }
    
    // Category breakdown
    $categories = [];
    $categoryResult = $conn->query("
        SELECT 
            p.category,
            SUM(oi.quantity) as qty_sold,
            SUM(oi.quantity * oi.price) as revenue
        FROM order_items oi
        JOIN products p ON oi.product_id = p.id
        JOIN orders o ON oi.order_id = o.id
        WHERE DATE(o.order_date) = '$date' AND o.status != 'Cancelled'
        GROUP BY p.category
        ORDER BY revenue DESC
    ");
    while ($row = $categoryResult->fetch_assoc()) {
        $categories[] = $row;
    }
    
    // Detailed order list
    $orders = [];
    $orderResult = $conn->query("
        SELECT 
            o.id,
            o.billing_name,
            o.total_amount,
            o.payment_method,
            o.status,
            COUNT(oi.id) as item_count
        FROM orders o
        LEFT JOIN order_items oi ON o.id = oi.order_id
        WHERE DATE(o.order_date) = '$date'
        GROUP BY o.id
        ORDER BY o.order_date DESC
    ");
    while ($row = $orderResult->fetch_assoc()) {
        $orders[] = $row;
    }
    

    // Additional daily KPIs
    $productStats = $conn->query("
        SELECT COALESCE(SUM(quantity),0) as total_products_sold,
               COUNT(DISTINCT product_id) as unique_products_sold
        FROM order_items oi
        JOIN orders o ON oi.order_id=o.id
        WHERE DATE(o.order_date)='$date' AND o.status != 'Cancelled'
    ")->fetch_assoc();

    return [
        'date' => $date,
        'total_sales' => $salesData['total_sales'] ?? 0,
        'total_orders' => $salesData['total_orders'] ?? 0,
        'avg_order_value' => $salesData['avg_order_value'] ?? 0,
        'total_products_sold' => $productStats['total_products_sold'] ?? 0,
        'unique_products_sold' => $productStats['unique_products_sold'] ?? 0,
        'top_products' => $topProducts,
        'categories' => $categories,
        'orders' => $orders
    ];
}

/**
 * Generate PDF report using TCPDF
 * @param array $reportData Report data from getDailyReportData()
 * @return mixed PDF content or false if failed
 */
function generatePDFReport($reportData) {
    if (!class_exists('TCPDF')) {
        return false;
    }
    
    try {
        // Create PDF object
        $pdf = new TCPDF('P', 'mm', 'A4', true, 'UTF-8', false);
        
        // Set document properties
        $pdf->SetCreator('Grocify Admin');
        $pdf->SetAuthor('Grocify');
        $pdf->SetTitle('Daily Sales Report - ' . $reportData['date']);
        $pdf->SetSubject('Daily Sales Report');
        
        // Set margins
        $pdf->SetMargins(10, 15, 10);
        $pdf->SetAutoPageBreak(true, 15);
        
        // Add page
        $pdf->AddPage();
        
        // Set font
        $pdf->SetFont('helvetica', 'B', 16);
        
        // Header
        $pdf->Cell(0, 10, 'Grocify - Daily Sales Report', 0, 1, 'C');
        $pdf->SetFont('helvetica', '', 10);
        $pdf->Cell(0, 5, 'Date: ' . date('F d, Y', strtotime($reportData['date'])), 0, 1, 'C');
        $pdf->Cell(0, 5, 'Generated: ' . date('F d, Y H:i:s'), 0, 1, 'C');
        $pdf->Ln(5);
        
        // Summary Statistics
        $pdf->SetFont('helvetica', 'B', 12);
        $pdf->Cell(0, 8, 'Summary Statistics', 0, 1, 'L');
        $pdf->SetFont('helvetica', '', 10);
        
        // Create summary table
        $pdf->SetFillColor(230, 240, 255);
        $pdf->Cell(70, 7, 'Total Sales', 1, 0, 'L', true);
        $pdf->Cell(60, 7, '₹ ' . number_format($reportData['total_sales'], 2), 1, 1, 'R', true);
        
        $pdf->Cell(70, 7, 'Total Orders', 1, 0, 'L', true);
        $pdf->Cell(60, 7, $reportData['total_orders'], 1, 1, 'R', true);
        
        $pdf->Cell(70, 7, 'Average Order Value', 1, 0, 'L', true);
        $pdf->Cell(60, 7, '₹ ' . number_format($reportData['avg_order_value'], 2), 1, 1, 'R', true);
        
        $pdf->Ln(5);
        
        // Top Products Section
        if (!empty($reportData['top_products'])) {
            $pdf->SetFont('helvetica', 'B', 12);
            $pdf->Cell(0, 8, 'Top 10 Products Sold', 0, 1, 'L');
            $pdf->SetFont('helvetica', '', 9);
            
            // Table header
            $pdf->SetFillColor(50, 100, 200);
            $pdf->SetTextColor(255, 255, 255);
            $pdf->Cell(80, 6, 'Product Name', 1, 0, 'L', true);
            $pdf->Cell(30, 6, 'Quantity', 1, 0, 'C', true);
            $pdf->Cell(40, 6, 'Revenue', 1, 1, 'R', true);
            
            // Table data
            $pdf->SetTextColor(0, 0, 0);
            $pdf->SetFillColor(245, 245, 245);
            $fill = false;
            
            foreach ($reportData['top_products'] as $product) {
                $pdf->SetFillColor($fill ? 245 : 255, $fill ? 245 : 255, $fill ? 245 : 255);
                $pdf->Cell(80, 6, substr($product['name'], 0, 35), 1, 0, 'L', $fill);
                $pdf->Cell(30, 6, $product['qty_sold'], 1, 0, 'C', $fill);
                $pdf->Cell(40, 6, '₹ ' . number_format($product['revenue'], 2), 1, 1, 'R', $fill);
                $fill = !$fill;
            }
            $pdf->Ln(3);
        }
        
        // Category Breakdown Section
        if (!empty($reportData['categories'])) {
            $pdf->SetFont('helvetica', 'B', 12);
            $pdf->Cell(0, 8, 'Sales by Category', 0, 1, 'L');
            $pdf->SetFont('helvetica', '', 9);
            
            // Table header
            $pdf->SetFillColor(50, 100, 200);
            $pdf->SetTextColor(255, 255, 255);
            $pdf->Cell(80, 6, 'Category', 1, 0, 'L', true);
            $pdf->Cell(30, 6, 'Quantity', 1, 0, 'C', true);
            $pdf->Cell(40, 6, 'Revenue', 1, 1, 'R', true);
            
            // Table data
            $pdf->SetTextColor(0, 0, 0);
            $fill = false;
            
            foreach ($reportData['categories'] as $category) {
                $pdf->SetFillColor($fill ? 245 : 255, $fill ? 245 : 255, $fill ? 245 : 255);
                $pdf->Cell(80, 6, $category['category'] ?: 'Uncategorized', 1, 0, 'L', $fill);
                $pdf->Cell(30, 6, $category['qty_sold'], 1, 0, 'C', $fill);
                $pdf->Cell(40, 6, '₹ ' . number_format($category['revenue'], 2), 1, 1, 'R', $fill);
                $fill = !$fill;
            }
            $pdf->Ln(3);
        }
        
        // Check if we need a new page for orders
        if ($pdf->GetY() > 200) {
            $pdf->AddPage();
        }
        
        // Daily Orders Section
        if (!empty($reportData['orders'])) {
            $pdf->SetFont('helvetica', 'B', 12);
            $pdf->Cell(0, 8, 'Orders for ' . date('F d, Y', strtotime($reportData['date'])), 0, 1, 'L');
            $pdf->SetFont('helvetica', '', 8);
            
            // Table header
            $pdf->SetFillColor(50, 100, 200);
            $pdf->SetTextColor(255, 255, 255);
            $pdf->Cell(20, 5, 'Order ID', 1, 0, 'C', true);
            $pdf->Cell(50, 5, 'Customer', 1, 0, 'L', true);
            $pdf->Cell(30, 5, 'Amount', 1, 0, 'R', true);
            $pdf->Cell(30, 5, 'Payment', 1, 0, 'C', true);
            $pdf->Cell(20, 5, 'Items', 1, 0, 'C', true);
            $pdf->Cell(20, 5, 'Status', 1, 1, 'C', true);
            
            // Table data
            $pdf->SetTextColor(0, 0, 0);
            $fill = false;
            
            foreach ($reportData['orders'] as $order) {
                $pdf->SetFillColor($fill ? 245 : 255, $fill ? 245 : 255, $fill ? 245 : 255);
                $pdf->Cell(20, 5, '#' . $order['id'], 1, 0, 'C', $fill);
                $pdf->Cell(50, 5, substr($order['billing_name'], 0, 20), 1, 0, 'L', $fill);
                $pdf->Cell(30, 5, '₹ ' . number_format($order['total_amount'], 2), 1, 0, 'R', $fill);
                $pdf->Cell(30, 5, substr($order['payment_method'] ?: 'N/A', 0, 15), 1, 0, 'C', $fill);
                $pdf->Cell(20, 5, $order['item_count'], 1, 0, 'C', $fill);
                $pdf->Cell(20, 5, substr($order['status'], 0, 10), 1, 1, 'C', $fill);
                $fill = !$fill;
            }
            $pdf->Ln(5);
        }
        
        // Footer
        $pdf->SetFont('helvetica', 'I', 8);
        $pdf->SetY(-15);
        $pdf->Cell(0, 10, 'Page ' . $pdf->getAliasNumPage(), 0, 1, 'C');
        
        // Return PDF content
        return $pdf->Output('', 'S'); // Return as string
        
    } catch (Exception $e) {
        error_log("PDF Generation Error: " . $e->getMessage());
        return false;
    }
}

/**
 * Check if TCPDF is available
 * @return bool
 */
function isTCPDFAvailable() {
    return class_exists('TCPDF');
}

?>
