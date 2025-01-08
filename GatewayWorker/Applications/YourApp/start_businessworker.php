<?php
use GatewayWorker\BusinessWorker;
use Workerman\Worker;

require_once __DIR__ . '/../../vendor/autoload.php';

// Khởi tạo BusinessWorker
$worker = new BusinessWorker();
$worker->eventHandler = 'Applications\YourApp\Events';
$worker->name = 'businessWorker' . uniqid();
$worker->count = 4;
$worker->registerAddress = '127.0.0.1:1238';

// Chạy Worker
if (!defined('GLOBAL_START')) {
    Worker::runAll();
}
