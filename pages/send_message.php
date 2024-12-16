<?php
session_start();
require_once '../includes/db.php';

if (!isset($_SESSION['user_id'])) {
    die("Необходима авторизация.");
}

$currentUserId = $_SESSION['user_id'];
$chatId = intval($_POST['chat_id']);
$message = trim($_POST['message']);
$filePath = null;

// Обработка файла
if (!empty($_FILES['file']['name'])) {
    if ($_FILES['file']['size'] > 5 * 1024 * 1024) {
        die("Файл слишком большой. Максимум 5 МБ.");
    }
    $fileName = uniqid() . "_" . basename($_FILES['file']['name']);
    $uploadDir = '../uploads/';
    $filePath = $uploadDir . $fileName;

    if (!move_uploaded_file($_FILES['file']['tmp_name'], $filePath)) {
        die("Ошибка загрузки файла.");
    }
}

// Добавление сообщения
$conn = getDbConnection();
$stmt = $conn->prepare("INSERT INTO messages (chat_id, sender_id, message, file_path) VALUES (?, ?, ?, ?)");
$stmt->bind_param("iiss", $chatId, $currentUserId, $message, $filePath);
$stmt->execute();

// Для AJAX-ответа
if (isset($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) == 'xmlhttprequest') {
    $response = ['status' => 'success', 'chat_id' => $chatId];
    echo json_encode($response);
    exit;
}

// Возврат к чату (если не AJAX)
header("Location: chat_window.php?chat_id=$chatId");
exit;
