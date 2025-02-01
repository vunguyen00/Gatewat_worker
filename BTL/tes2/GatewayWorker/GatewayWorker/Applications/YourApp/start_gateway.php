<?php 
/**
 * This file is part of workerman.
 *
 * Licensed under The MIT License
 * For full copyright and license information, please see the MIT-LICENSE.txt
 * Redistributions of files must retain the above copyright notice.
 *
 * @author walkor<walkor@workerman.net>
 * @copyright walkor<walkor@workerman.net>
 * @link http://www.workerman.net/
 * @license http://www.opensource.org/licenses/mit-license.php MIT License
 */
use \Workerman\Worker;
use \Workerman\WebServer;
use \GatewayWorker\Gateway;
use \GatewayWorker\BusinessWorker;
use \Workerman\Autoloader;

// 自动加载类 (Autoload classes)
require_once __DIR__ . '/../../vendor/autoload.php';

// Khởi tạo Gateway, đây là nơi xử lý kết nối WebSocket từ client
$gateway = new Gateway("websocket://0.0.0.0:8282"); // Sử dụng giao thức WebSocket và lắng nghe trên cổng 8282
$gateway->name = 'YourAppGateway';  // Tên Gateway, dễ dàng xác định
$gateway->count = 2;  // Số lượng tiến trình Gateway Worker, có thể đặt 2 cho tính ổn định
$gateway->lanIp = '127.0.0.1';  // Địa chỉ IP nội bộ
$gateway->startPort = 2900;  // Cổng bắt đầu cho các tiến trình worker nội bộ
$gateway->registerAddress = '127.0.0.1:1238';  // Địa chỉ Register server (nơi xử lý đăng ký)

$gateway->protocol = 'ws';  // Sử dụng giao thức WebSocket (ws)

// Nếu không phải là trạng thái khởi tạo toàn bộ (global start), thì chạy tất cả các Worker
if (!defined('GLOBAL_START')) {
    Worker::runAll();
}
