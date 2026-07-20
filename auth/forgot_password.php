<!--# Forgot Password Interface Matching Your Grocify Project

Replace your complete `auth/forgot_password.php` file with this code so the forgot password page matches your existing Bootstrap Grocify login design.

```php*-->
<?php
session_start();
include '../config/db.php';

$message = "";
$message_type = "success";

if (isset($_POST['send_otp'])) {

    $email = mysqli_real_escape_string($conn, $_POST['email']);

    $check = mysqli_query($conn, "SELECT * FROM users WHERE email='$email'");

    if (mysqli_num_rows($check) > 0) {

        $otp = rand(100000, 999999);

        $_SESSION['reset_otp'] = $otp;
        $_SESSION['reset_email'] = $email;

        require_once '../config/smtp.php';

        if (sendOTP($email, $otp)) {
            $message = "OTP sent to your email";
            $message_type = "success";
        } else {
            $message = "Failed to send OTP";
            $message_type = "danger";
        }

    } else {

        $message = "Email not found";
        $message_type = "danger";
    }
}

if (isset($_POST['verify_otp'])) {

    $entered_otp = $_POST['otp'];

    if ($entered_otp == $_SESSION['reset_otp']) {

        $new_password = password_hash($_POST['new_password'], PASSWORD_DEFAULT);

        $email = $_SESSION['reset_email'];

        mysqli_query($conn, "UPDATE users SET password='$new_password' WHERE email='$email'");

        unset($_SESSION['reset_otp']);
        unset($_SESSION['reset_email']);

        $message = "Password changed successfully";
        $message_type = "success";

    } else {

        $message = "Invalid OTP";
        $message_type = "danger";
    }
}

include '../partials/header.php';
?>

<div class="row justify-content-center">
    <div class="col-md-6 col-lg-5">

        <div class="card shadow border-0 rounded-4">
            <div class="card-body p-4">

                <h2 class="card-title text-center mb-4 text-success">
                    Forgot Password
                </h2>

                <p class="text-center text-muted mb-4">
                    Reset your password securely using OTP verification
                </p>

                <?php if (!empty($message)): ?>
                    <div class="alert alert-<?php echo $message_type; ?>">
                        <?php echo $message; ?>
                    </div>
                <?php endif; ?>

                <form method="POST">

                    <div class="mb-3">
                        <label class="form-label">Email Address</label>
                        <input type="email"
                               name="email"
                               class="form-control"
                               placeholder="Enter your email"
                               required>
                    </div>

                    <button type="submit"
                            name="send_otp"
                            class="btn btn-success w-100 mb-3">
                        Send OTP
                    </button>

                </form>

                <hr>

                <form method="POST">

                    <div class="mb-3">
                        <label class="form-label">OTP</label>
                        <input type="text"
                               name="otp"
                               class="form-control"
                               placeholder="Enter OTP"
                               required>
                    </div>

                    <div class="mb-3">
                        <label class="form-label">New Password</label>
                        <input type="password"
                               name="new_password"
                               class="form-control"
                               placeholder="Enter new password"
                               required>
                    </div>

                    <button type="submit"
                            name="verify_otp"
                            class="btn btn-dark w-100">
                        Verify OTP & Reset Password
                    </button>

                </form>

                <p class="text-center mt-4 mb-0">
                    Back to <a href="login.php">Login</a>
                </p>

            </div>
        </div>

    </div>
</div>

<?php include '../partials/footer.php'; ?>
```

<!--This version now matches your project design because it:

* Uses your existing Bootstrap layout
* Uses `header.php` and `footer.php`
* Matches the login card UI
* Uses Bootstrap buttons/forms
* Has success and error alerts
* Keeps Grocify styling consistent-->
