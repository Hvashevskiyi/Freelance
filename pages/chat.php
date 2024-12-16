<?php
session_start();
require_once '../includes/db.php';

if (!isset($_SESSION['user_id']) || !isset($_GET['with'])) {
    die("Необходима авторизация.");
}

$currentUserId = $_SESSION['user_id'];
$otherUserId = intval($_GET['with']);

$conn = getDbConnection();

// Проверяем, существует ли уже чат между пользователями
$stmt = $conn->prepare("SELECT id FROM chats WHERE 
    (user_one_id = ? AND user_two_id = ?) OR (user_one_id = ? AND user_two_id = ?)");
$stmt->bind_param("iiii", $currentUserId, $otherUserId, $otherUserId, $currentUserId);
$stmt->execute();
$chat = $stmt->get_result()->fetch_assoc();

if (!$chat) {
    // Если чата нет, создаём новый
    $stmt = $conn->prepare("INSERT INTO chats (user_one_id, user_two_id) VALUES (?, ?)");
    $stmt->bind_param("ii", $currentUserId, $otherUserId);
    $stmt->execute();
    $chatId = $stmt->insert_id;
} else {
    $chatId = $chat['id'];
}

// Перенаправляем на страницу отображения чата
header("Location: chat_window.php?chat_id=$chatId");
exit;
