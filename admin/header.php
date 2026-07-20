<?php

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
// Protect admin pages
if (!isset($_SESSION['user_id']) || !isset($_SESSION['is_admin']) || $_SESSION['is_admin'] != 1) {
    header('Location: login.php');
    exit();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Panel - Grocify</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
</head>
<body>
    <nav class="navbar navbar-expand-lg navbar-dark bg-dark">
        <div class="container-fluid">
            <a class="navbar-brand" href="index.php">🍎 Grocify Admin</a>
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#adminNavbar">
                <span class="navbar-toggler-icon"></span>
            </button>
            <div class="collapse navbar-collapse" id="adminNavbar">
                <ul class="navbar-nav">
                    <li class="nav-item dropdown">
                        <a class="nav-link dropdown-toggle" href="#" role="button" data-bs-toggle="dropdown">
                            <i class="bi bi-person-circle"></i> <?php echo htmlspecialchars($_SESSION['username']); ?>
                        </a>
                        <ul class="dropdown-menu dropdown-menu-end">
                            <li><a class="dropdown-item" href="profile.php"><i class="bi bi-person-gear me-2"></i>My Profile</a></li>
                            <li><a class="dropdown-item" href="../index.php" target="_blank"><i class="bi bi-shop me-2"></i>View Store</a></li>
                            <li><hr class="dropdown-divider"></li>
                            <li><a class="dropdown-item text-danger" href="logout.php"><i class="bi bi-box-arrow-right me-2"></i>Logout</a></li>
                        </ul>
                    </li>
                </ul>
            </div>
        </div>
        <!-- Inside the navbar-nav me-auto section -->
<ul class="navbar-nav me-auto">
    <li class="nav-item"><a class="nav-link" href="index.php"><i class="bi bi-speedometer2"></i> Dashboard</a></li>
    <li class="nav-item"><a class="nav-link" href="analytics.php"><i class="bi bi-bar-chart-line"></i> Analytics</a></li>
    <li class="nav-item"><a class="nav-link" href="products.php"><i class="bi bi-box"></i> Products</a></li>
    <li class="nav-item"><a class="nav-link" href="stock.php"><i class="bi bi-archive"></i> Stock</a></li>
    <li class="nav-item"><a class="nav-link" href="orders.php"><i class="bi bi-cart"></i> Orders</a></li>
    <li class="nav-item"><a class="nav-link" href="delivery_boys.php"><i class="bi bi-bicycle"></i> Delivery</a></li>
    <li class="nav-item"><a class="nav-link" href="coupons.php"><i class="bi bi-tag"></i> Coupons</a></li>
    <li class="nav-item"><a class="nav-link" href="reviews.php"><i class="bi bi-star"></i> Reviews</a></li>
    <li class="nav-item"><a class="nav-link" href="users.php"><i class="bi bi-people"></i> Users</a></li>
    <li class="nav-item"><a class="nav-link" href="feedback.php"><i class="bi bi-chat"></i> Feedback</a></li>
    <li class="nav-item"><a class="nav-link" href="reports.php"><i class="bi bi-graph-up"></i> Reports</a></li>
</ul>
    </nav>
    <main class="container-fluid py-4">