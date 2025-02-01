<?php
// File config.php

// Khai báo thông tin kết nối cơ sở dữ liệu
$host = '127.0.0.1';     // Địa chỉ máy chủ
$db   = 'message'; // Tên cơ sở dữ liệu
$user = 'root';     // Tên người dùng
$pass = ''; // Mật khẩu
$charset = 'utf8';       // Bộ ký tự

// Tạo DSN (Data Source Name)
$dsn = "mysql:host=$host;dbname=$db;charset=$charset";

try {
    // Tạo đối tượng PDO
    $pdo = new PDO($dsn, $user, $pass);
    
    // Thiết lập chế độ lỗi
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    // Hiển thị thông báo thành công (chỉ để kiểm tra, có thể bỏ)
    // echo "Kết nối thành công!";
} catch (PDOException $e) {
    // Nếu kết nối thất bại, hiển thị thông báo lỗi
    echo "Lỗi kết nối: " . $e->getMessage();
    die(); // Dừng chương trình
}
