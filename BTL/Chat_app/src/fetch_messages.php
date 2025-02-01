<?php
include __DIR__ . '/../config.php';

$user1 = $_GET['user1'] ?? null; // Giả sử user1 là người dùng hiện tại
$user2 = $_GET['user2'] ?? null;

if (!$user1 || !$user2) {
    echo json_encode([]);
    exit;
}

try {
    // Thực hiện join bảng messages với bảng users để lấy username của người gửi
    $stmt = $pdo->prepare("
        SELECT 
            m.sender, 
            m.receiver, 
            m.message, 
            u.username AS sender_username
        FROM messages m
        LEFT JOIN users u ON m.sender = u.id
        WHERE (m.sender = :user1 AND m.receiver = :user2)
           OR (m.sender = :user2 AND m.receiver = :user1)
        ORDER BY m.id ASC
    ");
    $stmt->execute(['user1' => $user1, 'user2' => $user2]);
    $messages = $stmt->fetchAll(PDO::FETCH_ASSOC);
    echo json_encode($messages);
} catch (PDOException $e) {
    echo json_encode([]);
}
?>
