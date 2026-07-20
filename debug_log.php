<?php

function write_log($message) {
    $logfile = __DIR__ . '/order_debug.log';
    $timestamp = date('Y-m-d H:i:s');
    file_put_contents($logfile, "[$timestamp] $message\n", FILE_APPEND);
}
?>