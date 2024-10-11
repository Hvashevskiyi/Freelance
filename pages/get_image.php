<?php
require_once '../includes/db.php'; // Подключение к базе данных

$conn = getDbConnection();

if (isset($_GET['id'])) {
    $id = intval($_GET['id']);
    // Выбираем изображение из базы данных
    $stmt = $conn->prepare("SELECT image FROM images WHERE id = ?");
    $stmt->bind_param('i', $id);
    $stmt->execute();
    $stmt->bind_result($image);
    $stmt->fetch();

    if ($image) {
        // Устанавливаем тип содержимого
        header("Content-Type: image/jpeg");
        echo $image; // Отправляем изображение
    } else {
        echo "Изображение не найдено.";
    }
} else {
    echo "ID изображения не передан.";
}
?>
