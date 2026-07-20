<?php

require_once '../config/db.php';
$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {

$full_name = trim($_POST['full_name']);
$username = trim($_POST['username']);
$email = trim($_POST['email']);
$phone = trim($_POST['phone']);
$address = trim($_POST['address']);
$city = trim($_POST['city']);
$pincode = trim($_POST['pincode']);
$password = $_POST['password'];
$confirm = $_POST['confirm_password'];

    // Server-side validation
    if (empty($full_name) || empty($username) || empty($email) || empty($phone) || empty($address) || empty($city) || empty($pincode) || empty($password) || empty($confirm)) {
        $error = "All fields are required.";
    } elseif (!preg_match('/^[0-9]{10}$/',$phone)) {
        $error="Enter a valid 10-digit mobile number.";
    } elseif (!preg_match('/^[0-9]{6}$/',$pincode)) {
        $error="Enter a valid 6-digit pincode.";
    } elseif (!preg_match('/^[a-zA-Z0-9_]{3,20}$/', $username)) {
        $error = "Username must be 3-20 characters and contain only letters, numbers, or underscores.";
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = "Please enter a valid email address.";
    } elseif (strlen($password) < 6) {
        $error = "Password must be at least 6 characters.";
    } elseif (!preg_match('/[A-Z]/', $password)) {
        $error = "Password must contain at least one uppercase letter.";
    } elseif (!preg_match('/[0-9]/', $password)) {
        $error = "Password must contain at least one number.";
    } elseif ($password !== $confirm) {
        $error = "Passwords do not match.";
    } else {

        // Check if username or email exists
        $stmt = $conn->prepare("SELECT id FROM users WHERE username = ? OR email = ?");
        $stmt->bind_param("ss", $username, $email);
        $stmt->execute();
        $stmt->store_result();

        if ($stmt->num_rows > 0) {
            $error = "Username or Email already taken.";
        } else {

            // Insert user
            $hashed = password_hash($password, PASSWORD_DEFAULT);

            $stmt = $conn->prepare("
INSERT INTO users
(full_name,username,email,phone,address,city,pincode,password)
VALUES(?,?,?,?,?,?,?,?)
");

$stmt->bind_param(
"ssssssss",
$full_name,
$username,
$email,
$phone,
$address,
$city,
$pincode,
$hashed
);

            if ($stmt->execute()) {
                $success = "Registration successful! You can now <a href='login.php'>login</a>.";
            } else {
                $error = "Something went wrong. Please try again.";
            }
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

.register-card{

background:white;

border-radius:25px;

box-shadow:0 15px 40px rgba(0,0,0,.2);

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

.strength{

font-size:13px;

font-weight:bold;

margin-top:5px;

}

</style>

<div class="container py-5">

<div class="row justify-content-center">

<div class="col-lg-6">

<div class="card register-card border-0">

<div class="card-body p-5">

<h2 class="text-center mb-4">

Create Account 🛒

</h2>

<?php if($error): ?>

<div class="alert alert-danger">

<?php echo $error; ?>

</div>

<?php endif; ?>

<?php if($success): ?>

<div class="alert alert-success">

<?php echo $success; ?>

</div>

<?php endif; ?>

<form method="post">

<div class="mb-3">
<label>Full Name</label>
<input
type="text"
name="full_name"
class="form-control"
required>
</div>

<div class="mb-3">

<label>Username</label>

<input type="text" name="username" class="form-control" required>

</div>

<div class="mb-3">

<label>Email</label>

<input type="email" name="email" class="form-control" required>

</div>

<div class="mb-3">
<label>Mobile Number</label>
<input
type="text"
name="phone"
class="form-control"
maxlength="10"
required>
</div>

<div class="mb-3">
<label>Address</label>
<textarea
name="address"
class="form-control"
rows="3"
required></textarea>
</div>

<div class="row">

<div class="col-md-6">

<label>City</label>

<input
type="text"
name="city"
class="form-control"
required>

</div>

<div class="col-md-6">

<label>Pincode</label>

<input
type="text"
name="pincode"
class="form-control"
maxlength="6"
required>

</div>

</div>


<div class="mb-3">

<label>Password</label>

<div class="input-group">

<input
type="password"
id="password"
name="password"
class="form-control"
required
onkeyup="strength()">

<button
class="btn btn-outline-secondary"
type="button"
onclick="togglePass()">

👁

</button>

</div>

<div id="strength" class="strength"></div>

</div>

<div class="mb-3">

<label>Confirm Password</label>

<input
type="password"
name="confirm_password"
class="form-control"
required>

</div>

<div class="form-check mb-3"><input class="form-check-input" type="checkbox" required><label class="form-check-label">I agree to the Terms & Conditions</label></div><button class="btn btn-success w-100 py-3 fw-bold fs-5">🛒 Create My Account</button>

</form>

<hr>

<p class="text-center">

Already have an account?

<a href="login.php">

Login

</a>

</p>

</div>

</div>

</div>

</div>

</div>

<script>

function togglePass(){

let x=document.getElementById("password");

x.type=x.type==="password"?"text":"password";

}

function strength(){
let p=document.getElementById("password").value;
let s=document.getElementById("strength");
let strong=/^(?=.*[a-z])(?=.*[A-Z])(?=.*\d).{8,}$/;
if(p.length<6){s.innerHTML="<span style='color:red'>Weak Password</span>";}
else if(strong.test(p)){s.innerHTML="<span style='color:green'>Strong Password ✓</span>";}
else{s.innerHTML="<span style='color:orange'>Medium Password</span>";}
}

document.querySelector("input[name='confirm_password']").addEventListener("keyup",function(){let p=document.getElementById("password").value;this.style.border=(this.value!=p)?"2px solid red":"2px solid green";});
</script>

<?php include '../partials/footer.php'; ?>
