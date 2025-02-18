<?php
use \GatewayWorker\Lib\Gateway;

class Events
{
    public static function onConnect($client_id)
    {
        Gateway::sendToClient($client_id, json_encode(['type' => 'welcome', 'message' => "Hello $client_id"]));
        echo "[KẾT NỐI] Client $client_id đã kết nối.\n";
    }

    public static function onMessage($client_id, $message)
    {
        $data = json_decode($message, true);
        if (!$data || !isset($data['action'])) {
            Gateway::sendToClient($client_id, json_encode(['type' => 'error', 'message' => 'Invalid message format']));
            return;
        }

        switch ($data['action']) {
            case 'register':
                self::handleRegister($client_id, $data);
                break;
            case 'login':
                self::handleLogin($client_id, $data);
                break;
            case 'send_message':
                self::handleSendMessage($client_id, $data);
                break;
            case 'get_messages':
                self::handleGetMessages($client_id, $data);
                break;
            case 'logout':
                self::handleLogout($client_id, $data);
                break;
            default:
                Gateway::sendToClient($client_id, json_encode(['type' => 'error', 'message' => 'Unknown action']));
        }
    }

    private static function handleSendMessage($client_id, $data)
    {
        if (empty($data['sender']) || empty($data['receiver']) || empty($data['message'])) {
            Gateway::sendToClient($client_id, json_encode(['type' => 'error', 'message' => 'Missing fields']));
            return;
        }

        include __DIR__ . '/../config.php';

        try {
            $stmt = $pdo->prepare("
                INSERT INTO messages (sender, receiver, message, timestamp)
                VALUES (:sender, :receiver, :message, NOW())
            ");
            $stmt->execute([
                'sender' => $data['sender'],
                'receiver' => $data['receiver'],
                'message' => $data['message'],
            ]);

            $newMessage = [
                'type' => 'new_message',
                'message' => [
                    'sender' => $data['sender'],
                    'receiver' => $data['receiver'],
                    'message' => $data['message'],
                ],
            ];

            Gateway::sendToUid($data['sender'], json_encode($newMessage));
            Gateway::sendToUid($data['receiver'], json_encode($newMessage));

            // Gửi thông tin lên server WebSocket
            echo "[TIN NHẮN] {$data['sender']} gửi tin nhắn tới {$data['receiver']}: {$data['message']}\n";
        } catch (PDOException $e) {
            Gateway::sendToClient($client_id, json_encode(['type' => 'error', 'message' => 'Database error: ' . $e->getMessage()]));
        }
    }

    private static function handleRegister($client_id, $data)
    {
        if (empty($data['username']) || empty($data['email']) || empty($data['password'])) {
            Gateway::sendToClient($client_id, json_encode(['type' => 'error', 'message' => 'Missing fields']));
            return;
        }

        include __DIR__ . '/../config.php';

        try {
            $stmt = $pdo->prepare("SELECT * FROM users WHERE username = :username OR email = :email");
            $stmt->execute(['username' => $data['username'], 'email' => $data['email']]);
            if ($stmt->rowCount() > 0) {
                Gateway::sendToClient($client_id, json_encode(['type' => 'error', 'message' => 'Username or email already exists']));
                return;
            }

            $hashedPassword = password_hash($data['password'], PASSWORD_BCRYPT);
            $stmt = $pdo->prepare("INSERT INTO users (username, email, password) VALUES (:username, :email, :password)");
            $stmt->execute([
                'username' => $data['username'],
                'email' => $data['email'],
                'password' => $hashedPassword
            ]);

            Gateway::sendToClient($client_id, json_encode(['type' => 'success', 'message' => 'Registration successful']));
        } catch (PDOException $e) {
            Gateway::sendToClient($client_id, json_encode(['type' => 'error', 'message' => 'Database error: ' . $e->getMessage()]));
        }
    }

    private static function handleLogin($client_id, $data)
    {
        if (empty($data['username']) || empty($data['password'])) {
            Gateway::sendToClient($client_id, json_encode(['type' => 'error', 'message' => 'Missing fields']));
            return;
        }

        include __DIR__ . '/../config.php';

        try {
            $stmt = $pdo->prepare("SELECT id FROM users WHERE username = :username");
            $stmt->execute(['username' => $data['username']]);
            $user = $stmt->fetch(PDO::FETCH_ASSOC);

            if (!$user) {
                Gateway::sendToClient($client_id, json_encode(['type' => 'error', 'message' => 'Invalid credentials']));
                return;
            }

            Gateway::bindUid($client_id, $user['id']);
            Gateway::sendToClient($client_id, json_encode(['type' => 'success', 'message' => 'Login successful', 'user_id' => $user['id']]));

            echo "[ĐĂNG NHẬP] Người dùng {$data['username']} (ID: {$user['id']}) đã đăng nhập.\n";
        } catch (PDOException $e) {
            Gateway::sendToClient($client_id, json_encode(['type' => 'error', 'message' => 'Database error: ' . $e->getMessage()]));
        }
    }

    private static function handleLogout($client_id, $data)
    {
        if (empty($data['user_id'])) {
            Gateway::sendToClient($client_id, json_encode(['type' => 'error', 'message' => 'Missing user ID']));
            return;
        }

        Gateway::unbindUid($client_id, $data['user_id']);
        Gateway::sendToClient($client_id, json_encode(['type' => 'success', 'message' => 'Logout successful']));

        echo "[ĐĂNG XUẤT] Người dùng ID: {$data['user_id']} đã đăng xuất.\n";
    }

    private static function handleGetMessages($client_id, $data)
    {
        if (empty($data['user1']) || empty($data['user2'])) {
            Gateway::sendToClient($client_id, json_encode(['type' => 'error', 'message' => 'Missing fields']));
            return;
        }

        include __DIR__ . '/../config.php';

        try {
            $stmt = $pdo->prepare("
                SELECT * FROM messages 
                WHERE (sender = :user1 AND receiver = :user2) 
                OR (sender = :user2 AND receiver = :user1)
                ORDER BY timestamp ASC
            ");
            $stmt->execute([
                'user1' => $data['user1'],
                'user2' => $data['user2']
            ]);
            $messages = $stmt->fetchAll(PDO::FETCH_ASSOC);

            Gateway::sendToClient($client_id, json_encode(['type' => 'message_history', 'messages' => $messages]));
        } catch (PDOException $e) {
            Gateway::sendToClient($client_id, json_encode(['type' => 'error', 'message' => 'Database error: ' . $e->getMessage()]));
        }
    }

    public static function onClose($client_id)
    {
        echo "[NGẮT KẾT NỐI] Client $client_id đã ngắt kết nối.\n";
    }
}
