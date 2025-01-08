<?php
use GatewayWorker\Gateway;
use Workerman\Worker;

require_once __DIR__ . '/../../vendor/autoload.php';

// Khởi tạo Gateway
$gateway = new Gateway("websocket://0.0.0.0:8282");
$gateway->name = 'YourAppGateway';
$gateway->count = 4;
$gateway->lanIp = '127.0.0.1';
$gateway->startPort = 2300;
$gateway->registerAddress = '0.0.0.0:1238';

// Chạy Worker
if (!defined('GLOBAL_START')) {
    Worker::runAll();
}
