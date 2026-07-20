<?php

require_once '../config/db.php';
$error = '';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $username = trim($_POST['username']);
    $password = $_POST['password'];

    // Server-side validation
    if (empty($username) || empty($password)) {
        $error = "Please enter username/email and password.";
    } elseif (strlen($username) < 3) {
        $error = "Username or email must be at least 3 characters.";
    } elseif (strlen($password) < 6) {
        $error = "Password must be at least 6 characters.";
    } else {
        $stmt = $conn->prepare("SELECT id, username, password, is_admin FROM users WHERE username = ? OR email = ?");
        if(!$stmt){
            die("SQL Prepare Error: ".$conn->error);
        }
        $stmt->bind_param("ss", $username, $username);
        $stmt->execute();
        $result = $stmt->get_result();

        if ($user = $result->fetch_assoc()) {
            if (password_verify($password, $user['password'])) {
                session_start();
                $_SESSION['user_id'] = $user['id'];
                $_SESSION['username'] = $user['username'];
                $_SESSION['is_admin'] = (int)$user['is_admin'];

                header('Location: ../index.php');
                exit();
            } else {
                $error = "Invalid username/email or password.";
            }
        } else {
            $error = "Invalid username/email or password.";
        }

        $stmt->close();
    }
}

include '../partials/header.php';
?>

<style>
body{
background:linear-gradient(135deg,#11998e,#38ef7d);
min-height:100vh;
}
.login-card{
background:rgba(255,255,255,.95);
backdrop-filter:blur(10px);
border-radius:25px;
box-shadow:0 15px 40px rgba(0,0,0,.2);
}
.hero{
color:white;
padding:50px;
}
.form-control{
border-radius:12px;
padding:12px;
}
.btn-success{
border-radius:12px;
padding:12px;
font-weight:bold;
}
</style>

<div class="container py-5">

<div class="row align-items-center">

<div class="col-lg-6 d-none d-lg-block hero">

<h1 class="display-4 fw-bold">
🍎 Grocify
</h1>

<h3>Fresh groceries delivered instantly.</h3>

<p class="mt-4 fs-5">
Order fruits, vegetables, dairy, bakery and household items from one place.
</p>

</div>

<div class="col-lg-6">

<div class="card login-card border-0">

<div class="card-body p-5">

<h2 class="text-center mb-4">
Welcome Back 👋
</h2>

<?php if($error): ?>

<div class="alert alert-danger">

<?php echo $error; ?>

</div>

<?php endif; ?>

<form method="post">

<div class="mb-3">

<label class="form-label">

Username or Email

</label>

<input
type="text"
name="username"
class="form-control"
required>

</div>

<div class="mb-3">

<label>Password</label>

<div class="input-group">

<input
type="password"
name="password"
id="password"
class="form-control"
required>

<button
class="btn btn-outline-secondary"
type="button"
onclick="togglePassword()">

👁

</button>

</div>

</div>

<div class="d-flex justify-content-between mb-3">

<div>

<input type="checkbox">

Remember Me

</div>

<a href="forgot_password.php">

Forgot Password?

</a>

</div>

<button class="btn btn-success w-100">

Login

</button>

</form>

<hr>

<p class="text-center">

Don't have an account?

<a href="register.php">

Register

</a>

</p>

</div>

</div>

</div>

</div>

</div>

<script>

function togglePassword(){

let x=document.getElementById("password");

if(x.type==="password")

x.type="text";

else

x.type="password";

}

</script>

<?php include '../partials/footer.php'; ?>
