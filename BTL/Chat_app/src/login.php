<?php
include __DIR__ . '/../config.php';
session_start(); // Bắt buộc phải có để dùng session


if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = $_POST['username'] ?? '';
    $password = $_POST['password'] ?? '';

    // Kiểm tra dữ liệu đầu vào
    if (empty($username) || empty($password)) {
        echo json_encode(['status' => 'error', 'message' => 'Vui lòng nhập đầy đủ thông tin!']);
        exit;
    }

    // Kiểm tra tài khoản trong DB
    $stmt = $pdo->prepare("SELECT id, password FROM users WHERE username = :username");
    $stmt->execute(['username' => $username]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($user && password_verify($password, $user['password'])) {
        $_SESSION['user_id'] = $user['id']; // Lưu vào session

        echo json_encode([
            'status' => 'success',
            'message' => 'Đăng nhập thành công!',
            'user_id' => $user['id']
        ]);
    } else {
        echo json_encode(['status' => 'error', 'message' => 'Sai tên đăng nhập hoặc mật khẩu.']);
    }

    exit;
}
?>

<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Form Đăng nhập Huyền Ảo</title>
    <link rel="stylesheet" href="../css/loginstyle.css">
</head>
<body>
    <form onsubmit="handleLogin(event)">
        <label for="username">Your Name</label>
        <input type="text" id="username" name="username" placeholder="Enter your username" required><br>

        <label for="password">Your Password</label>
        <input type="password" id="password" name="password" placeholder="Enter your password" required><br>

        <button type="submit">Log In</button>

        <p class="register-link">Don't have an account? <a href="register.php">Sign up now</a></p>
        <p class="register-link">Forgot password? <a href="forgot_pass.php">Click here</a></p>
    </form>
</body>
</html>

<script>
    const ws = new WebSocket('ws://127.0.0.1:8282');

    ws.onopen = () => {
        console.log('WebSocket connected');
    };

    ws.onmessage = (event) => {
        const response = JSON.parse(event.data);
        if (response.type === 'success') {
            alert(response.message);
            localStorage.setItem('user_id', response.user_id); // Lưu user_id
            window.location.href = 'message.php'; // Chuyển hướng nếu thành công
        } else if (response.type === 'error') {
            alert(response.message);
        }
    };

    ws.onerror = (error) => {
        console.error('WebSocket error:', error);
    };

    function handleLogin(event) {
        event.preventDefault();

        const username = document.getElementById('username').value;
        const password = document.getElementById('password').value;

        fetch('login.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
            body: new URLSearchParams({ username, password })
        })
        .then(response => response.json())
        .then(data => {
            if (data.status === 'success') {
                localStorage.setItem('user_id', data.user_id); // Lưu user_id vào localStorage
                window.location.href = 'message.php'; // Chuyển hướng đến trang chat
            } else {
                alert(data.message);
            }
        })
        .catch(error => console.error('Lỗi:', error));
    }
</script>