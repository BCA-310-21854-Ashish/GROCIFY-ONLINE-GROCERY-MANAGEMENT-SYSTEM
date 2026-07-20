<?php

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Base URL for absolute paths
$baseUrl = '/grocify/'; // Adjust if your folder name differs

// Default location (can be set from session or cookie later)
$currentLocation = $_SESSION['location'] ?? 'Select Location';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Grocify - Daily Grocery</title>
    <!-- Bootstrap 5 CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Bootstrap Icons -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <!-- Custom CSS -->
    <link rel="stylesheet" href="<?php echo $baseUrl; ?>assets/css/style.css">
</head>
<body>
    <nav class="navbar navbar-expand-lg navbar-light bg-light shadow-sm py-2">
        <div class="container">
            <!-- Logo -->
            <a class="navbar-brand fw-bold text-success me-3" href="<?php echo $baseUrl; ?>index.php">
                Grocify
            </a>

            <!-- Mobile Toggler -->
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarContent">
                <span class="navbar-toggler-icon"></span>
            </button>

            <!-- Collapsible Content -->
            <div class="collapse navbar-collapse" id="navbarContent">
                <!-- Location Selector -->
                <div class="dropdown me-3">
                    <button class="btn btn-outline-secondary dropdown-toggle" type="button" id="locationDropdown" data-bs-toggle="dropdown" aria-expanded="false">
                        <i class="bi bi-geo-alt-fill text-danger"></i> 
                        <span id="selected-location-text"><?php echo htmlspecialchars($currentLocation); ?></span>
                    </button>
                    <ul class="dropdown-menu" aria-labelledby="locationDropdown">
                        <li><h6 class="dropdown-header">Select Delivery Location</h6></li>
                        <li><a class="dropdown-item location-option" href="#" data-location="Mumbai">Mumbai</a></li>
                        <li><a class="dropdown-item location-option" href="#" data-location="Delhi">Delhi</a></li>
                        <li><a class="dropdown-item location-option" href="#" data-location="Bangalore">Bangalore</a></li>
                        <li><a class="dropdown-item location-option" href="#" data-location="Chennai">Chennai</a></li>
                        <li><hr class="dropdown-divider"></li>
                        <li><a class="dropdown-item" href="#" data-bs-toggle="modal" data-bs-target="#locationModal">Enter New Address</a></li>
                    </ul>
                </div>

                <!-- Search Bar -->
               <form action="search.php" method="GET" class="d-flex mx-auto" style="width:350px;">
    <input class="form-control rounded-start-pill"
           type="search"
           name="q"
           placeholder="Search fruits, vegetables, milk...">

    <button class="btn btn-success rounded-end-pill">
        🔍
    </button>
</form>

                <!-- Navigation Links -->
                <!-- Navigation Links -->
<ul class="navbar-nav ms-auto align-items-center">
    <li class="nav-item">
        <a class="nav-link" href="<?php echo $baseUrl; ?>index.php">Home</a>
    </li>
    <li class="nav-item">
        <a class="nav-link position-relative" href="<?php echo $baseUrl; ?>cart.php">
            <i class="bi bi-cart3"></i> Cart
            <span id="cart-count" class="position-absolute top-0 start-100 translate-middle badge rounded-pill bg-success">
                0
            </span>
        </a>
    </li>
    <?php if(isset($_SESSION['user_id'])): ?>
    <li class="nav-item">
        <a class="nav-link" href="<?php echo $baseUrl; ?>wishlist.php" title="Wishlist">
            <i class="bi bi-heart-fill text-danger"></i> Wishlist
        </a>
    </li>
    <?php endif; ?>
    
   <?php if(isset($_SESSION['user_id'])): ?>
    <li class="nav-item dropdown">
        <a class="nav-link dropdown-toggle" href="#" role="button" data-bs-toggle="dropdown">
            <i class="bi bi-person-circle"></i> <?php echo htmlspecialchars($_SESSION['username']); ?>
        </a>
        <ul class="dropdown-menu dropdown-menu-end">
            <li><a class="dropdown-item" href="<?php echo $baseUrl; ?>dashboard.php"><i class="bi bi-speedometer2 me-2"></i>Dashboard</a></li>
            <li><a class="dropdown-item" href="<?php echo $baseUrl; ?>profile.php"><i class="bi bi-person me-2"></i>My Profile</a></li>
            <li><a class="dropdown-item" href="<?php echo $baseUrl; ?>orders.php"><i class="bi bi-list-ul me-2"></i>My Orders</a></li>
            <li><a class="dropdown-item" href="<?php echo $baseUrl; ?>wishlist.php"><i class="bi bi-heart-fill text-danger me-2"></i>Wishlist</a></li>
                <li><a class="dropdown-item" href="<?php echo $baseUrl; ?>my_reviews.php"><i class="bi bi-star-fill text-warning me-2"></i>My Reviews</a></li>
                <li><a class="dropdown-item" href="<?php echo $baseUrl; ?>feedback.php"><i class="bi bi-chat-dots-fill text-info me-2"></i>Feedback</a></li>
            
            <?php if(isset($_SESSION['is_admin']) && $_SESSION['is_admin'] == 1): ?>
                <li><hr class="dropdown-divider"></li>
                <li><a class="dropdown-item" href="<?php echo $baseUrl; ?>admin/index.php"><i class="bi bi-shield-lock me-2"></i>Admin Panel</a></li>
            <?php endif; ?>
            
            <li><hr class="dropdown-divider"></li>
            <li><a class="dropdown-item" href="<?php echo $baseUrl; ?>auth/logout.php"><i class="bi bi-box-arrow-right me-2"></i>Logout</a></li>
        </ul>
    </li>
<?php else: ?>
    <!-- Login/Register/Admin links unchanged -->
        <!-- Not Logged In - Show Login, Register, and Admin -->
        <li class="nav-item">
            <a class="nav-link" href="<?php echo $baseUrl; ?>auth/login.php">Login</a>
        </li>
        <li class="nav-item">
            <a class="nav-link btn btn-outline-success btn-sm ms-2" href="<?php echo $baseUrl; ?>auth/register.php">Register</a>
        </li>
        <li class="nav-item">
            <a class="nav-link text-secondary ms-2" href="<?php echo $baseUrl; ?>admin/login.php" title="Admin Login">
                <i class="bi bi-shield-lock"></i> Admin
            </a>
        </li>
    <?php endif; ?>
</ul>
            </div>
        </div>
    </nav>

    <!-- Location Modal (for manual address entry) -->
    <div class="modal fade" id="locationModal" tabindex="-1" aria-labelledby="locationModalLabel" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Enter Delivery Address</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <form id="location-form">
                        <div class="mb-3">
                            <label for="address" class="form-label">Full Address</label>
                            <textarea class="form-control" id="address" rows="2" placeholder="Street, Area, City, PIN"></textarea>
                        </div>
                        <button type="submit" class="btn btn-success w-100">Save Location</button>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <main class="container my-4">