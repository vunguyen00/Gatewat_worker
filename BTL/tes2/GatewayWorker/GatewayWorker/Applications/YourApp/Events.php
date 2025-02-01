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

use \GatewayWorker\Lib\Gateway;

class Events
{
    public static function onConnect($client_id)
    {
        // Khi client kết nối, gửi thông báo chào mừng
        Gateway::sendToClient($client_id, json_encode(['type' => 'welcome', 'message' => "Hello $client_id"]));
    }

    public static function onMessage($client_id, $message)
    {
        // Parse message từ client
        $data = json_decode($message, true);
        if (!$data || !isset($data['action'])) {
            Gateway::sendToClient($client_id, json_encode(['type' => 'error', 'message' => 'Invalid message format']));
            return;
        }

        // Xử lý action
        switch ($data['action']) {
            case 'register':
                // Gọi hàm xử lý đăng ký
                self::handleRegister($client_id, $data);
                break;
            case 'login':
                // Gọi hàm xử lý đăng nhập
                self::handleLogin($client_id, $data);
                break;
            case 'send_message':
                self::handleSendMessage($client_id, $data);
                break;
            case 'get_messages':
                self::handleGetMessages($client_id, $data);
                break;                
            default:
                Gateway::sendToClient($client_id, json_encode(['type' => 'error', 'message' => 'Unknown action']));
        }
    }

    private static function handleSendMessage($client_id, $data) {
        if (empty($data['sender']) || empty($data['receiver']) || empty($data['message'])) {
            Gateway::sendToClient($client_id, json_encode(['type' => 'error', 'message' => 'Missing fields']));
            return;
        }
    
        include __DIR__ . '/../config.php';
    
        try {
            // Lưu tin nhắn vào cơ sở dữ liệu
            $stmt = $pdo->prepare("
                INSERT INTO messages (sender, receiver, message, timestamp)
                VALUES (:sender, :receiver, :message, NOW())
            ");
            $stmt->execute([
                'sender' => $data['sender'],
                'receiver' => $data['receiver'],
                'message' => $data['message'],
            ]);
    
            // Tạo payload tin nhắn
            $newMessage = [
                'type' => 'new_message',
                'message' => [
                    'sender' => $data['sender'],
                    'receiver' => $data['receiver'],
                    'message' => $data['message'],
                ],
            ];
    
            // Gửi tin nhắn tới cả người gửi và người nhận
            Gateway::sendToUid($data['sender'], json_encode($newMessage));
            Gateway::sendToUid($data['receiver'], json_encode($newMessage));
        } catch (PDOException $e) {
            Gateway::sendToClient($client_id, json_encode(['type' => 'error', 'message' => 'Database error: ' . $e->getMessage()]));
        }
    }
    

    private static function handleRegister($client_id, $data)
    {
        // Kiểm tra dữ liệu đầu vào
        if (empty($data['username']) || empty($data['email']) || empty($data['password'])) {
            Gateway::sendToClient($client_id, json_encode(['type' => 'error', 'message' => 'Missing fields']));
            return;
        }

        // Kết nối database (cấu hình PDO)
        include __DIR__ . '/../config.php';

        try {
            // Kiểm tra trùng lặp
            $stmt = $pdo->prepare("SELECT * FROM users WHERE username = :username OR email = :email");
            $stmt->execute(['username' => $data['username'], 'email' => $data['email']]);
            if ($stmt->rowCount() > 0) {
                Gateway::sendToClient($client_id, json_encode(['type' => 'error', 'message' => 'Username or email already exists']));
                return;
            }

            // Thêm người dùng mới
            $hashedPassword = password_hash($data['password'], PASSWORD_BCRYPT);
            $stmt = $pdo->prepare("INSERT INTO users (username, email, password) VALUES (:username, :email, :password)");
            $stmt->execute([
                'username' => $data['username'],
                'email' => $data['email'],
                'password' => $hashedPassword
            ]);

            // Gửi thông báo đăng ký thành công
            Gateway::sendToClient($client_id, json_encode(['type' => 'success', 'message' => 'Registration successful']));
        } catch (PDOException $e) {
            Gateway::sendToClient($client_id, json_encode(['type' => 'error', 'message' => 'Database error: ' . $e->getMessage()]));
        }
    }

    private static function handleGetMessages($client_id, $data)
    {
        if (empty($data['user1']) || empty($data['user2'])) {
            Gateway::sendToClient($client_id, json_encode(['type' => 'error', 'message' => 'Missing fields']));
            return;
        }

        include __DIR__ . '/../config.php';

        try {
            // Lấy tin nhắn giữa hai người dùng
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

            // Gửi tin nhắn lịch sử đến client
            Gateway::sendToClient($client_id, json_encode(['type' => 'message_history', 'messages' => $messages]));
        } catch (PDOException $e) {
            Gateway::sendToClient($client_id, json_encode(['type' => 'error', 'message' => 'Database error: ' . $e->getMessage()]));
        }
    }


    private static function handleLogin($client_id, $data)
    {
        if (empty($data['username']) || empty($data['password'])) {
            Gateway::sendToClient($client_id, json_encode([
                'type' => 'error',
                'message' => 'Vui lòng nhập đầy đủ thông tin đăng nhập.'
            ]));
            return;
        }
    
        // Kết nối database
        include __DIR__ . '/../config.php';
    
        if (!$pdo) {
            Gateway::sendToClient($client_id, json_encode([
                'type' => 'error',
                'message' => 'Lỗi kết nối cơ sở dữ liệu.'
            ]));
            return;
        }
    
        try {
            // Lấy thông tin user
            $stmt = $pdo->prepare("SELECT id, password FROM users WHERE username = :username");
            $stmt->execute(['username' => $data['username']]);
            $user = $stmt->fetch(PDO::FETCH_ASSOC);
    
            if (!$user || !password_verify($data['password'], $user['password'])) {
                Gateway::sendToClient($client_id, json_encode([
                    'type' => 'error',
                    'message' => 'Sai tên đăng nhập hoặc mật khẩu.'
                ]));
                return;
            }
    
            // Gán user_id vào WebSocket bằng GatewayWorker
            Gateway::bindUid($client_id, $user['id']);
    
            // Trả về kết quả đăng nhập thành công
            Gateway::sendToClient($client_id, json_encode([
                'type' => 'success',
                'message' =>  'Đăng nhập thành công!',
                'user_id' => $user['id']
            ]));
        } catch (PDOException $e) {
            Gateway::sendToClient($client_id, json_encode([
                'type' => 'error',
                'message' => 'Lỗi cơ sở dữ liệu: ' . $e->getMessage()
            ]));
        }
    }
    


    public static function onClose($client_id)
    {
        // Thông báo khi người dùng ngắt kết nối
        Gateway::sendToAll(json_encode(['type' => 'disconnect', 'message' => "$client_id disconnected"]));
    }
}
