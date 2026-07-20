<?php
session_start();
require_once 'config/db.php';

if (!isset($_SESSION['user_id'])) {
    header('Location: auth/login.php');
    exit();
}

$userId = $_SESSION['user_id'];

// --- Delete Account ---
if (isset($_GET['action']) && $_GET['action'] === 'delete_account') {
    $conn->query("DELETE FROM wishlist WHERE user_id=$userId");
    $conn->query("DELETE FROM reviews WHERE user_id=$userId");
    $conn->query("DELETE FROM feedback WHERE user_id=$userId");
    $conn->query("DELETE FROM users WHERE id=$userId");
    session_destroy();
    header('Location: index.php');
    exit();
}

// --- Update Personal Info ---
if (isset($_POST['update_info'])) {
    $full_name = trim($_POST['full_name'] ?? '');
    $username  = trim($_POST['username']);
    $email     = trim($_POST['email']);
    $phone     = trim($_POST['phone'] ?? '');

    if (empty($username) || empty($email)) {
        $_SESSION['profile_error'] = "Username and email are required.";
    } elseif ($phone && !preg_match('/^[0-9]{10}$/', $phone)) {
        $_SESSION['profile_error'] = "Phone must be a valid 10-digit number.";
    } else {
        $stmt = $conn->prepare("SELECT id FROM users WHERE (username=? OR email=?) AND id!=?");
        $stmt->bind_param("ssi", $username, $email, $userId);
        $stmt->execute();
        if ($stmt->get_result()->num_rows > 0) {
            $_SESSION['profile_error'] = "Username or email already in use by another account.";
        } else {
            $stmt = $conn->prepare("UPDATE users SET full_name=?, username=?, email=?, phone=? WHERE id=?");
            $stmt->bind_param("ssssi", $full_name, $username, $email, $phone, $userId);
            $stmt->execute() ? $_SESSION['profile_message'] = "Personal information updated successfully." : $_SESSION['profile_error'] = "Update failed. Please try again.";
            $_SESSION['username'] = $username;
        }
        $stmt->close();
    }
    header('Location: profile.php#info');
    exit();
}

// --- Update Address ---
if (isset($_POST['update_address'])) {
    $address = trim($_POST['address'] ?? '');
    $city    = trim($_POST['city'] ?? '');
    $pincode = trim($_POST['pincode'] ?? '');

    if ($pincode && !preg_match('/^[0-9]{6}$/', $pincode)) {
        $_SESSION['profile_error'] = "Pincode must be 6 digits.";
    } else {
        $stmt = $conn->prepare("UPDATE users SET address=?, city=?, pincode=? WHERE id=?");
        $stmt->bind_param("sssi", $address, $city, $pincode, $userId);
        $stmt->execute() ? $_SESSION['profile_message'] = "Address updated successfully." : $_SESSION['profile_error'] = "Update failed.";
        $stmt->close();
    }
    header('Location: profile.php#address');
    exit();
}

// --- Change Password ---
if (isset($_POST['change_password'])) {
    $current = $_POST['current_password'];
    $new     = $_POST['new_password'];
    $confirm = $_POST['confirm_password'];

    if ($new !== $confirm) {
        $_SESSION['profile_error'] = "New passwords do not match.";
    } elseif (strlen($new) < 6) {
        $_SESSION['profile_error'] = "Password must be at least 6 characters.";
    } else {
        $stmt = $conn->prepare("SELECT password FROM users WHERE id=?");
        $stmt->bind_param("i", $userId);
        $stmt->execute();
        $user = $stmt->get_result()->fetch_assoc();
        if (password_verify($current, $user['password'])) {
            $hashed = password_hash($new, PASSWORD_DEFAULT);
            $stmt = $conn->prepare("UPDATE users SET password=? WHERE id=?");
            $stmt->bind_param("si", $hashed, $userId);
            $stmt->execute() ? $_SESSION['profile_message'] = "Password changed successfully." : $_SESSION['profile_error'] = "Failed to update password.";
        } else {
            $_SESSION['profile_error'] = "Current password is incorrect.";
        }
        $stmt->close();
    }
    header('Location: profile.php#password');
    exit();
}

header('Location: profile.php');
exit();
