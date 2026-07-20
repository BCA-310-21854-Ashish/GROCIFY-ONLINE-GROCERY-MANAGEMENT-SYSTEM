<?php

session_start();
require_once '../config/db.php';
require_once '../config/report_helper.php';

// Check if user is admin
if (!isset($_SESSION['user_id'])) {
    header('HTTP/1.0 403 Forbidden');
    exit('Unauthorized');
}

$userId = $_SESSION['user_id'];
$userResult = $conn->query("SELECT is_admin FROM users WHERE id = $userId");
$user = $userResult->fetch_assoc();

if (!$user || !$user['is_admin']) {
    header('HTTP/1.0 403 Forbidden');
    exit('Unauthorized');
}

// Get date parameter
$date = isset($_GET['date']) ? trim($_GET['date']) : date('Y-m-d');

// Validate date format
if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $date)) {
    header('HTTP/1.0 400 Bad Request');
    exit('Invalid date format. Use YYYY-MM-DD');
}

// Check if date is in the future
if (strtotime($date) > time()) {
    header('HTTP/1.0 400 Bad Request');
    exit('Cannot generate report for future dates');
}

// Get report data
$reportData = getDailyReportData($date);

if (!$reportData) {
    header('HTTP/1.0 500 Internal Server Error');
    exit('Failed to generate report data');
}

// Check if TCPDF is available
if (!class_exists('TCPDF')) {
    // TCPDF not available - offer fallback
    header('Content-Type: application/json');
    echo json_encode([
        'error' => 'PDF library not available',
        'message' => 'TCPDF library needs to be installed. Visit: https://tcpdf.org/',
        'data' => $reportData,
        'fallback' => true
    ]);
    exit;
}

// Generate PDF
$pdfContent = generatePDFReport($reportData);

if (!$pdfContent) {
    header('HTTP/1.0 500 Internal Server Error');
    exit('Failed to generate PDF');
}

// Send PDF headers
header('Content-Type: application/pdf');
header('Content-Disposition: attachment; filename="Grocify_Report_' . $date . '.pdf"');
header('Content-Length: ' . strlen($pdfContent));
header('Cache-Control: no-cache, no-store, must-revalidate');
header('Pragma: no-cache');
header('Expires: 0');

// Output PDF
echo $pdfContent;
exit;
?>
