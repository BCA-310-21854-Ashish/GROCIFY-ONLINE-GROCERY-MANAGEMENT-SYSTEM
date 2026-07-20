<?php

session_start();
if (isset($_POST['location'])) {
    $_SESSION['location'] = $_POST['location'];
    echo 'success';
} else {
    echo 'error';
}
?>