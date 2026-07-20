<?php

$host     = 'localhost';
$user     = 'root';
$password = '';
$dbname   = 'grocify_db';
$port     = 3306; // default MySQL port

$conn = new mysqli($host, $user, $password, $dbname, $port);

if ($conn->connect_error) {
    $errCode = $conn->connect_errno;
    $errMsg  = $conn->connect_error;

    // Show a helpful HTML error page instead of a raw PHP warning
    if (!headers_sent()) header('Content-Type: text/html; charset=utf-8');
    echo '<!DOCTYPE html><html><head>
    <title>Database Error – Grocify</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    </head><body class="bg-light d-flex align-items-center justify-content-center" style="min-height:100vh;">
    <div class="card shadow border-0 rounded-4 p-4" style="max-width:520px;width:100%">
        <h3 class="text-danger fw-bold">⚠️ Database Connection Failed</h3>
        <p class="text-muted mb-3">Grocify could not connect to MySQL. Follow the steps below to fix it.</p>
        <div class="alert alert-warning">
            <strong>Error ' . $errCode . ':</strong> ' . htmlspecialchars($errMsg) . '
        </div>
        <h6 class="fw-bold">✅ How to fix:</h6>
        <ol class="small">
            <li>Open <strong>XAMPP Control Panel</strong></li>
            <li>Click <strong>Start</strong> next to <strong>MySQL</strong> (make sure it turns green)</li>
            <li>Also make sure <strong>Apache</strong> is running</li>
            <li>Refresh this page</li>
        </ol>
        <hr>
        <h6 class="fw-bold">📋 Current settings <small class="text-muted">(config/db.php)</small>:</h6>
        <table class="table table-sm small">
            <tr><td>Host</td><td><code>' . $host . '</code></td></tr>
            <tr><td>Port</td><td><code>' . $port . '</code></td></tr>
            <tr><td>User</td><td><code>' . $user . '</code></td></tr>
            <tr><td>Database</td><td><code>' . $dbname . '</code></td></tr>
        </table>
        <p class="small text-muted mb-0">If MySQL is running but still failing, check that the database <code>' . $dbname . '</code> exists. Import <code>grocify.sql</code> via <a href="http://localhost/phpmyadmin" target="_blank">phpMyAdmin</a>.</p>
    </div>
    </body></html>';
    exit();
}

$conn->set_charset("utf8mb4");
?>
