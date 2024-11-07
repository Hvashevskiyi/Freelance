<?php
session_start();
require_once '../includes/db.php';
$conn = getDbConnection();

if (!isset($_GET['id']) || $_SESSION['user_id'] !== intval($_GET['id'])) {
    header("Location: index.php");
    exit;
}

$userId = intval($_GET['id']);
require_once '../includes/checkUserExists.php';

// Проверяем, существует ли пользователь
if (!checkUserExists($conn, $userId)) {
    // Удаляем данные сессии
    session_unset();
    session_destroy();
    header("Location: login.php"); // Перенаправляем на страницу входа
    exit;
}

// Получаем текущие данные пользователя
try {
    $stmt = $conn->prepare("SELECT text, vacancy, image_id, role_id FROM Users WHERE id = ?");
    $stmt->bind_param("i", $userId);
    $stmt->execute();
    $user = $stmt->get_result()->fetch_assoc();
} catch (Exception $e) {
    error_log($e->getMessage()); // Записываем ошибку в файл логов
    exit;
}

// Обработка POST-запроса
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        // Обновление текста и вакансии
        $text = $_POST['text'];
        $vacancy = isset($_POST['vacancy']) ? $_POST['vacancy'] : ''; // Проверяем, существует ли поле вакансии

        // Обновляем текст и вакансию пользователя
        $updateStmt = $conn->prepare("UPDATE Users SET text = ?, vacancy = ? WHERE id = ?");
        $updateStmt->bind_param("ssi", $text, $vacancy, $userId);
        $updateStmt->execute();

        // Обработка загрузки изображения
        if (isset($_FILES['image']) && $_FILES['image']['error'] === UPLOAD_ERR_OK) {
            $image = $_FILES['image']['tmp_name'];
            $imageData = file_get_contents($image);

            // Загружаем изображение в базу данных
            $uploadStmt = $conn->prepare("INSERT INTO images (image) VALUES (?)");
            $uploadStmt->bind_param("s", $imageData);
            $uploadStmt->execute();

            // Получаем id загруженного изображения
            $imageId = $conn->insert_id;

            // Обновляем профиль пользователя, присваиваем новое изображение
            $updateImageStmt = $conn->prepare("UPDATE Users SET image_id = ? WHERE id = ?");
            $updateImageStmt->bind_param("ii", $imageId, $userId);
            $updateImageStmt->execute();


        }
// Вызов скрипта для удаления неиспользуемых изображений
        include_once 'delete_unused_images.php';
        // Перенаправляем на профиль после обновлений
        header("Location: profile.php?id=$userId");
        exit;
    } catch (Exception $e) {
        error_log($e->getMessage()); // Записываем ошибку в файл логов
    }
}

// Путь к скрипту для удаления неиспользуемых изображений
?>

<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <link rel="stylesheet" href="../assets/styles/edit_profile.css">
    <title>Редактировать профиль</title>
</head>
<body>
<h1>Редактирование профиля</h1>

<form method="POST" enctype="multipart/form-data">
    <textarea name="text" placeholder="О себе" required><?php echo htmlspecialchars($user['text']); ?></textarea>

    <?php if ($user['role_id'] == 2): // Если это фрилансер ?>
        <input type="text" name="vacancy" placeholder="Вакансия" value="<?php echo htmlspecialchars($user['vacancy']); ?>" required>
    <?php endif; ?>

    <!-- Загрузка изображения -->
    <label for="image">Выберите изображение:</label>
    <input type="file" name="image" id="image">

    <button type="submit">Сохранить изменения</button>
</form>

<!-- Кнопка для возврата на главную -->
<button onclick="window.location.href='index.php'">Вернуться на главную</button>

</body>
</html>
