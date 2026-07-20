<?php

session_start();
require_once '../config/db.php';
if (!isset($_SESSION['is_admin']) || $_SESSION['is_admin'] != 1) {
    header('Location: login.php');
    exit();
}
$id = intval($_GET['id']);
$conn->query("UPDATE users SET is_admin = NOT is_admin WHERE id = $id");
header('Location: users.php');
exit();
?>
