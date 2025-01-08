<?php
use Workerman\Worker;

require_once __DIR__ . '/../../vendor/autoload.php';

// Worker phục vụ file tĩnh
$web_worker = new Worker("http://0.0.0.0:8080");
$web_worker->count = 1;

$web_worker->onMessage = function ($connection, $request) {
    // Đường dẫn file cục bộ
    $path = $request->path();
    if ($path === '/') {
        $path = '/index.html'; // Điều hướng tới index.html
    }
    $file = __DIR__ . '/htdocs' . $path;

    // Kiểm tra nếu file tồn tại
    if (file_exists($file)) {
        $connection->send(file_get_contents($file));
    } else {
        $connection->send("404 Not Found");
    }
};


// Worker điều hướng
$redirect_worker = new Worker("http://0.0.0.0:8081");
$redirect_worker->count = 1;

$redirect_worker->onMessage = function ($connection, $request) {
    // URL đích điều hướng
    $redirect_url = "http://127.0.0.1:8080/index.html";
    $connection->send("HTTP/1.1 302 Found\r\nLocation: $redirect_url\r\n\r\n");
};

// Chạy tất cả Workers
Worker::runAll();
