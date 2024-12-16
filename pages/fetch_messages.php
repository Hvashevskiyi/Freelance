<?php
session_start();
require_once '../includes/db.php';

if (!isset($_SESSION['user_id']) || !isset($_GET['chat_id'])) {
    die("Необходима авторизация.");
}

$currentUserId = $_SESSION['user_id'];
$chatId = intval($_GET['chat_id']);
$lastMessageTime = isset($_GET['last_message_time']) ? $_GET['last_message_time'] : null;

$conn = getDbConnection();

// Создаем запрос для получения новых сообщений
$query = "
    SELECT m.*, u.name AS sender_name
    FROM messages m
    JOIN Users u ON m.sender_id = u.id
    WHERE m.chat_id = ? " . ($lastMessageTime ? "AND m.created_at > ?" : "") . "
    ORDER BY m.created_at ASC
";

$stmt = $conn->prepare($query);
if ($lastMessageTime) {
    // Если lastMessageTime передан, используем его в запросе
    $stmt->bind_param("is", $chatId, $lastMessageTime);
} else {
    $stmt->bind_param("i", $chatId);
}

$stmt->execute();
$messages = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);

// Формируем ответ
$response = [];
foreach ($messages as $msg) {
    $response[] = [
        'sender_id' => $msg['sender_id'],
        'sender_name' => $msg['sender_name'],
        'message' => $msg['message'],
        'file_path' => $msg['file_path'],
        'file_name' => basename($msg['file_path']),
        'is_file' => !empty($msg['file_path']),
        'created_at' => $msg['created_at']
    ];
}

echo json_encode(['messages' => $response]);
?>
