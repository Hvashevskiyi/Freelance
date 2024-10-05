<?php
session_start();
require_once '../includes/db.php';

$conn = getDbConnection(); // Подключение к базе данных

if (isset($_GET['id'])) {
    $id = intval($_GET['id']);

    // Запрос для получения изображения
    $stmt = $conn->prepare("SELECT image FROM images WHERE id = ?");
    $stmt->bind_param("i", $id);
    $stmt->execute();
    $result = $stmt->get_result();
    $image = $result->fetch_assoc();

    if ($image) {
        header("Content-Type: image/jpeg"); // Укажите правильный тип содержимого
        echo $image['image'];
    } else {
        echo "Изображение не найдено.";
    }
} else {
    echo "ID изображения не передан.";
}
?>
