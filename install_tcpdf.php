<?php

/**
 * TCPDF Auto-Installer
 * 
 * This script attempts to download and install TCPDF automatically.
 * Usage: Access this file from a browser, or run: php install_tcpdf.php
 */

// Configuration
$TCPDF_DOWNLOAD_URL = 'https://tcpdf.org/download/tcpdf_6_4_5.zip';
$TCPDF_INSTALL_PATH = __DIR__ . '/lib/tcpdf';
$ZIP_TEMP_PATH = sys_get_temp_dir() . '/tcpdf_' . uniqid() . '.zip';

function showStatus($message, $type = 'info') {
    $colors = [
        'success' => "\033[92m",
        'error' => "\033[91m",
        'warning' => "\033[93m",
        'info' => "\033[94m",
        'reset' => "\033[0m"
    ];
    
    $color = $colors[$type] ?? $colors['info'];
    if (php_sapi_name() === 'cli') {
        echo $color . "[" . strtoupper($type) . "] " . $message . $colors['reset'] . "\n";
    } else {
        echo "<div style='color: " . ($type === 'error' ? 'red' : ($type === 'success' ? 'green' : 'orange')) . "'>" . htmlspecialchars($message) . "</div>\n";
    }
}

function isCLI() {
    return php_sapi_name() === 'cli';
}

function checkPrerequisites() {
    $checks = [
        'PHP Version >= 5.6' => version_compare(PHP_VERSION, '5.6', '>='),
        'cURL Extension' => extension_loaded('curl'),
        'ZIP Extension' => extension_loaded('zip'),
        'Write Permission to ./lib' => is_writable(__DIR__ . '/lib') || @mkdir(__DIR__ . '/lib', 0755, true)
    ];
    
    $allPassed = true;
    foreach ($checks as $check => $passed) {
        showStatus($check . ': ' . ($passed ? 'OK' : 'FAILED'), $passed ? 'success' : 'error');
        if (!$passed) $allPassed = false;
    }
    
    return $allPassed;
}

function downloadFile($url, $destination) {
    showStatus("Downloading from: $url", 'info');
    
    $ch = curl_init();
    curl_setopt_array($ch, [
        CURLOPT_URL => $url,
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_FOLLOWLOCATION => true,
        CURLOPT_TIMEOUT => 300,
        CURLOPT_SSL_VERIFYPEER => true,
        CURLOPT_USERAGENT => 'TCPDF-Installer/1.0'
    ]);
    
    $content = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $error = curl_error($ch);
    curl_close($ch);
    
    if ($error) {
        showStatus("Download error: $error", 'error');
        return false;
    }
    
    if ($httpCode !== 200) {
        showStatus("HTTP Error: $httpCode", 'error');
        return false;
    }
    
    if (file_put_contents($destination, $content) === false) {
        showStatus("Failed to save downloaded file", 'error');
        return false;
    }
    
    showStatus("Downloaded successfully (" . round(strlen($content)/1024/1024, 2) . " MB)", 'success');
    return true;
}

function extractZip($zipPath, $extractPath) {
    showStatus("Extracting ZIP file...", 'info');
    
    $zip = new ZipArchive();
    $res = $zip->open($zipPath);
    
    if ($res !== true) {
        showStatus("Failed to open ZIP: Error code $res", 'error');
        return false;
    }
    
    if (!$zip->extractTo($extractPath)) {
        showStatus("Failed to extract ZIP", 'error');
        $zip->close();
        return false;
    }
    
    $zip->close();
    showStatus("Extracted successfully", 'success');
    return true;
}

function verifyInstallation() {
    $tcpdfFile = TCPDF_INSTALL_PATH . '/tcpdf.php';
    
    showStatus("Verifying installation...", 'info');
    
    if (!file_exists($tcpdfFile)) {
        showStatus("TCPDF main file not found: $tcpdfFile", 'error');
        return false;
    }
    
    // Try to load TCPDF
    require_once($tcpdfFile);
    
    if (!class_exists('TCPDF')) {
        showStatus("TCPDF class not found after loading", 'error');
        return false;
    }
    
    showStatus("TCPDF installed successfully!", 'success');
    return true;
}

// Main execution
if (!isCLI()) {
    echo "<!DOCTYPE html><html><head><title>TCPDF Installer</title><style>";
    echo "body { font-family: Arial, sans-serif; margin: 20px; background: #f5f5f5; }";
    echo ".container { max-width: 600px; margin: 0 auto; background: white; padding: 20px; border-radius: 5px; box-shadow: 0 2px 5px rgba(0,0,0,0.1); }";
    echo "h1 { color: #333; border-bottom: 2px solid #007bff; padding-bottom: 10px; }";
    echo "div { margin: 10px 0; padding: 10px; border-radius: 3px; }";
    echo "</style></head><body>";
    echo "<div class='container'>";
    echo "<h1>TCPDF Auto Installer for Grocify</h1>";
}

showStatus("Starting TCPDF installation...", 'info');
showStatus("Installation path: " . TCPDF_INSTALL_PATH, 'info');

// Check prerequisites
if (!checkPrerequisites()) {
    showStatus("Prerequisites check failed. Cannot proceed.", 'error');
    if (!isCLI()) {
        echo "<p><strong>Please ensure all prerequisites are met and try again.</strong></p>";
        echo "</div></body></html>";
    }
    exit(1);
}

// Create lib directory if needed
if (!is_dir(__DIR__ . '/lib')) {
    mkdir(__DIR__ . '/lib', 0755, true);
}

// Create TCPDF installation directory
if (!is_dir(TCPDF_INSTALL_PATH)) {
    mkdir(TCPDF_INSTALL_PATH, 0755, true);
}

// Download TCPDF
if (!downloadFile($TCPDF_DOWNLOAD_URL, $ZIP_TEMP_PATH)) {
    showStatus("Installation failed", 'error');
    if (!isCLI()) {
        echo "</div></body></html>";
    }
    exit(1);
}

// Extract ZIP
if (!extractZip($ZIP_TEMP_PATH, TCPDF_INSTALL_PATH)) {
    showStatus("Installation failed", 'error');
    @unlink($ZIP_TEMP_PATH);
    if (!isCLI()) {
        echo "</div></body></html>";
    }
    exit(1);
}

// Clean up temp file
@unlink($ZIP_TEMP_PATH);

// Verify installation
if (verifyInstallation()) {
    showStatus("TCPDF installation completed successfully!", 'success');
    showStatus("You can now generate PDF reports from the admin panel.", 'info');
    if (!isCLI()) {
        echo "<p><a href='admin/reports.php' style='padding: 10px 20px; background: #007bff; color: white; text-decoration: none; border-radius: 3px;'>Go to Reports</a></p>";
        echo "</div></body></html>";
    }
    exit(0);
} else {
    showStatus("Installation verification failed", 'error');
    if (!isCLI()) {
        echo "</div></body></html>";
    }
    exit(1);
}
?>
