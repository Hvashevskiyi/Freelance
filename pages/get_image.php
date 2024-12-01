<?php
require_once '../includes/db.php'; // Подключение к базе данных

$conn = getDbConnection();

if (isset($_GET['id'])) {
    $id = intval($_GET['id']);

    // Функция для проверки валидности изображения
    function isValidImage($imageData) {
        return @imagecreatefromstring($imageData) !== false;
    }

    // Выбор изображения и времени его обновления
    $stmt = $conn->prepare("SELECT image, updated_at FROM images WHERE id = ?");
    $stmt->bind_param('i', $id);
    $stmt->execute();
    $stmt->bind_result($image, $updatedAt);
    $stmt->fetch();
    $stmt->close();

    // Проверка изображения на повреждение
    if (!$image || !isValidImage($image)) {
        // Если изображение отсутствует или повреждено, выбираем изображение с ID=1
        $defaultStmt = $conn->prepare("SELECT image, updated_at FROM images WHERE id = 1");
        $defaultStmt->execute();
        $defaultStmt->bind_result($image, $updatedAt);
        $defaultStmt->fetch();
        $defaultStmt->close();
    }

    // Если даже резервное изображение не найдено или повреждено, выводим ошибку
    if (!$image || !isValidImage($image)) {
        echo "Изображение не найдено.";
        exit;
    }

    // Преобразование `updated_at` для заголовка `Last-Modified`
    $lastModified = strtotime($updatedAt);
    $etag = md5($image . $lastModified); // Уникальный ETag

    // Устанавливаем куку с временем последнего обновления изображения
    setcookie("image_updated_at", $updatedAt, time() + 3600, "/"); // Кука на 1 час

    // Кэширование: проверка заголовков от клиента
    if (isset($_SERVER['HTTP_IF_MODIFIED_SINCE']) && strtotime($_SERVER['HTTP_IF_MODIFIED_SINCE']) >= $lastModified) {
        header("HTTP/1.1 304 Not Modified");
        exit;
    }

    if (isset($_SERVER['HTTP_IF_NONE_MATCH']) && $_SERVER['HTTP_IF_NONE_MATCH'] === $etag) {
        header("HTTP/1.1 304 Not Modified");
        exit;
    }

    // Установка заголовков для кэширования
    header("Cache-Control: public, max-age=3600"); // Кэш на 1 час
    header("Last-Modified: " . gmdate("D, d M Y H:i:s", $lastModified) . " GMT");
    header("ETag: $etag");

    // Установка типа содержимого
    header("Content-Type: image/jpeg");

    // Отправка изображения
    echo $image;
} else {
    echo "ID изображения не передан.";
}
?>
