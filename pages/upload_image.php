<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <title>Загрузка изображения</title>
</head>
<body>
<form action="upload_image.php" method="post" enctype="multipart/form-data">
    <label for="image">Выберите изображение:</label>
    <input type="file" name="image" id="image" required>
    <button type="submit">Загрузить</button>
</form>

<?php
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $image = $_FILES['image']['tmp_name'];

    // Проверка на ошибки загрузки
    if ($_FILES['image']['error'] === UPLOAD_ERR_OK) {
        $imageData = file_get_contents($image);

        $pdo = new PDO('mysql:host=localhost;dbname=freelance_platform', 'root', '');
        $stmt = $pdo->prepare("INSERT INTO images (image) VALUES (:image)");
        $stmt->bindParam(':image', $imageData);
        $stmt->execute();

        echo "Изображение загружено!";
    } else {
        echo "Ошибка при загрузке изображения.";
    }
}
?>
</body>
</html>