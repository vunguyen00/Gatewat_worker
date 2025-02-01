<?php
session_start();

// Kiểm tra đăng nhập
if (!isset($_SESSION['user_id'])) {
    die('Bạn chưa đăng nhập. Vui lòng đăng nhập.');
}

include __DIR__ . '/../config.php';

$currentUserId = $_SESSION['user_id'];

try {
    $stmt = $pdo->query("SELECT id, username FROM users");
    $users = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    die("Lỗi kết nối CSDL: " . $e->getMessage());
}
?>

<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Chat</title>
    <link rel="stylesheet" href="../css/chat.css">
    <style>

    </style>
</head>
<body>
    <div class="userlist">
        <ul id="receiver-list">
            <?php foreach ($users as $user): ?>
                <li onclick="onReceiverSelect(<?= $user['id']; ?>)">
                    <?= htmlspecialchars($user['username']); ?>
                </li>
            <?php endforeach; ?>
        </ul>
    </div>
    <div class="chatArea">
        <div id="chat-box"></div>
        <div class="textAndsend">
                <textarea id="message-content" placeholder="Nhập tin nhắn..."></textarea>
                <button onclick="handleSendMessage(event)">Gửi</button>
        </div>
    </div>
    <script>
        const currentUserId = <?= json_encode($currentUserId) ?>;
        let selectedReceiver = null;
        const chatBox = document.getElementById('chat-box');
        const ws = new WebSocket("ws://localhost:8282");

        ws.onopen = function () {
            console.log("WebSocket kết nối thành công.");
        };

        ws.onmessage = function (event) {
            const data = JSON.parse(event.data);
            if (data.type === 'new_message') {
                appendNewMessage(data.message);
            }
        };

        function onReceiverSelect(receiverId) {
            selectedReceiver = receiverId;
            fetchMessages(); // Lấy tin nhắn khi chọn người nhận
        }

        function handleSendMessage(event) {
            event.preventDefault();
            const messageContent = document.getElementById('message-content').value;
            if (!selectedReceiver || !messageContent) {
                alert('Vui lòng chọn người nhận và nhập tin nhắn.');
                return;
            }

            const payload = {
                action: 'send_message',
                sender: currentUserId,
                receiver: selectedReceiver,
                message: messageContent,
            };

            ws.send(JSON.stringify(payload));
            document.getElementById('message-content').value = '';
        }

        function fetchMessages() {
            if (!selectedReceiver) return;

            fetch(`fetch_messages.php?user1=${currentUserId}&user2=${selectedReceiver}`)
                .then(response => response.json())
                .then(data => {
                    chatBox.innerHTML = '';
                    data.forEach(msg => appendNewMessage(msg));
                })
                .catch(error => console.error('Lỗi khi lấy tin nhắn:', error));
        }

        function appendNewMessage(msg) {
    // Xác định nhãn hiển thị: nếu tin nhắn do bạn gửi thì hiển thị "Bạn", còn nếu không thì hiển thị tên người gửi
    const senderLabel = (msg.sender == currentUserId) ? 'Bạn' : msg.sender_username;
    
    // Tạo phần tử div để chứa tin nhắn
    const messageElement = document.createElement('div');
    
    // Thêm CSS cho tin nhắn: căn phải nếu bạn gửi, căn trái nếu người khác gửi
    if (msg.sender == currentUserId) {
        messageElement.style.textAlign = 'right';
        messageElement.style.backgroundColor = '#DCF8C6'; // Ví dụ: màu nền xanh nhạt cho tin nhắn của bạn
        messageElement.style.margin = '5px 0 5px auto';  // Lệch sang bên phải
        messageElement.style.padding = '8px';
        messageElement.style.borderRadius = '8px';
    } else {
        messageElement.style.textAlign = 'left';
        messageElement.style.backgroundColor = '#FFF';
        messageElement.style.margin = '5px auto 5px 0';  // Lệch sang bên trái
        messageElement.style.padding = '8px';
        messageElement.style.borderRadius = '8px';
    }
    
    // Nội dung tin nhắn
    messageElement.innerHTML = `<strong>${senderLabel}:</strong> ${msg.message}`;
    
    // Thêm tin nhắn vào khung chat và cuộn xuống cuối
    chatBox.appendChild(messageElement);
    chatBox.scrollTop = chatBox.scrollHeight;
}


        setInterval(fetchMessages, 500); // Cập nhật tin nhắn mỗi giây
    </script>
</body>
</html>
