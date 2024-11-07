<?php
require_once '../includes/db.php';
$conn = getDbConnection();

try {
    // 1. Получаем все изображения из таблицы `images`
    $stmt = $conn->query("SELECT id FROM images");
    $images = $stmt->fetch_all(MYSQLI_ASSOC);

    // 2. Получаем все изображения, которые привязаны к пользователям
    $stmtUsed = $conn->query("SELECT DISTINCT image_id FROM Users WHERE image_id IS NOT NULL");
    $usedImages = $stmtUsed->fetch_all(MYSQLI_ASSOC);

    // Преобразуем массив использованных изображений в массив id
    $usedImageIds = array_map(function($image) {
        return $image['image_id'];
    }, $usedImages);

    // 3. Проверяем и удаляем изображения, которые не используются
    foreach ($images as $image) {
        if (!in_array($image['id'], $usedImageIds)) {
            // Удаляем изображение, которое не используется
            $deleteStmt = $conn->prepare("DELETE FROM images WHERE id = ?");
            $deleteStmt->bind_param("i", $image['id']);
            $deleteStmt->execute();

        }
    }
} catch (Exception $e) {
    error_log($e->getMessage()); // Записываем ошибку в файл логов
}
?>
