<?php

session_start();
require_once 'config/db.php';
require_once 'config/mail_helper.php';

// Test email sending functionality
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $testEmail = trim($_POST['test_email']);
    $testName = trim($_POST['test_name']);

    if (empty($testEmail) || empty($testName)) {
        $error = "Please fill all fields.";
    } else {
        // Prepare sample order data
        $sampleOrderDetails = array(
            'items' => [
                ['name' => 'Fresh Apples (1kg)', 'quantity' => 2, 'price' => 150.00],
                ['name' => 'Organic Tomatoes (500g)', 'quantity' => 1, 'price' => 40.00],
                ['name' => 'Whole Wheat Bread', 'quantity' => 3, 'price' => 35.00],
            ],
            'total' => 425.00,
            'address' => '123 Main Street, New Delhi, Delhi - 110001',
            'phone' => '+91 98765 43210',
            'payment_method' => 'Credit Card',
            'estimated_delivery' => '2-3 business days',
            'order_link' => 'http://' . $_SERVER['HTTP_HOST'] . '/order_details.php?id=TEST123'
        );

        if (sendOrderConfirmationEmail($testEmail, $testName, 'TEST123', $sampleOrderDetails)) {
            $success = "Test email sent successfully to $testEmail!";
        } else {
            $error = "Failed to send test email. Please check your email address and try again.";
        }
    }
}

include 'partials/header.php';
?>

<div class="container py-5">
    <div class="row justify-content-center">
        <div class="col-md-6">
            <div class="card shadow-sm">
                <div class="card-header bg-success text-white">
                    <h5 class="mb-0">📧 Test Order Confirmation Email</h5>
                </div>
                <div class="card-body p-4">
                    <?php if (isset($success)): ?>
                        <div class="alert alert-success alert-dismissible fade show" role="alert">
                            ✅ <?php echo htmlspecialchars($success); ?>
                            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                        </div>
                    <?php endif; ?>

                    <?php if (isset($error)): ?>
                        <div class="alert alert-danger alert-dismissible fade show" role="alert">
                            ❌ <?php echo htmlspecialchars($error); ?>
                            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                        </div>
                    <?php endif; ?>

                    <form method="POST">
                        <div class="mb-3">
                            <label for="test_name" class="form-label">Your Name</label>
                            <input type="text" class="form-control" id="test_name" name="test_name" required>
                        </div>

                        <div class="mb-4">
                            <label for="test_email" class="form-label">Your Email Address</label>
                            <input type="email" class="form-control" id="test_email" name="test_email" required>
                        </div>

                        <button type="submit" class="btn btn-success w-100">
                            📨 Send Test Email
                        </button>
                    </form>

                    <hr class="my-4">

                    <div class="alert alert-info">
                        <strong>ℹ️ Note:</strong> This will send a sample order confirmation email with test data. 
                        Use this to verify that the email functionality is working correctly before placing real orders.
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<?php include 'partials/footer.php'; ?>
