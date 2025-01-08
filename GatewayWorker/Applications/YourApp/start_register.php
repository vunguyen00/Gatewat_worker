<?php
use Workerman\Worker;
use GatewayWorker\Register;

require_once __DIR__ . '/../../vendor/autoload.php'; // Đường dẫn tới autoload.php

// Khởi tạo tiến trình Register
$register = new Register('text://0.0.0.0:1238');
// Chạy Worker
if (!defined('GLOBAL_START')) {
    Worker::runAll();
}
