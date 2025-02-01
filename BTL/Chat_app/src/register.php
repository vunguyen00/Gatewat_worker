<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Đăng ký tài khoản</title>
    <link rel="stylesheet" href="../css/registercss.css">
</head> 
<body>
    <!-- Form đăng ký -->
    <form onsubmit="handleRegister(event)">
        <label for="username">Tên người dùng</label>
        <input type="text" id="username" name="username" placeholder="Nhập tên người dùng" required><br>

        <label for="email">Email</label>
        <input type="email" id="email" name="email" placeholder="Nhập email của bạn" required><br>

        <label for="password">Mật khẩu</label>
        <input type="password" id="password" name="password" placeholder="Nhập mật khẩu" required><br>

        <button type="submit">Đăng ký</button>
        <p class="register-link">Đã có tài khoản? <a href="login.php">Đăng nhập</a></p>
    </form>
</body>
<script>
    const ws = new WebSocket('ws://127.0.0.1:8282');

    ws.onopen = () => {
        console.log('WebSocket connected');
    };

    ws.onmessage = (event) => {
        const response = JSON.parse(event.data);
        if (response.type === 'success') {
            alert(response.message);
            window.location.href = 'login.php'; // Chuyển hướng nếu thành công
        } else if (response.type === 'error') {
            alert(response.message);
        }
    };

    ws.onerror = (error) => {
        console.error('WebSocket error:', error);
    };

    // Hàm xử lý đăng ký
    function handleRegister(event) {
        event.preventDefault(); // Ngăn form gửi yêu cầu HTTP

        const username = document.getElementById('username').value;
        const email = document.getElementById('email').value;
        const password = document.getElementById('password').value;

        // Tạo payload gửi qua WebSocket
        const payload = {
            action: 'register',
            username,
            email,
            password
        };

        // Gửi dữ liệu qua WebSocket
        ws.send(JSON.stringify(payload));
    }
</script>
</html>
