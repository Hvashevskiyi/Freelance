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
// Получаем текущее изображение пользователя
$stmt = $conn->prepare("SELECT text, vacancy, image_id, role_id FROM Users WHERE id = ?");
$stmt->bind_param("i", $userId);
$stmt->execute();
$user = $stmt->get_result()->fetch_assoc();

// Получаем все изображения для выбора
$imagesStmt = $conn->query("SELECT id FROM images");
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

<?php
// Обработка POST-запроса
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $text = $_POST['text'];
    $vacancy = isset($_POST['vacancy']) ? $_POST['vacancy'] : ''; // Проверяем, существует ли поле вакансии
    $imageId = $_POST['image_id']; // Получаем выбранный id изображения

    // Обновление данных пользователя
    $updateStmt = $conn->prepare("UPDATE Users SET text = ?, vacancy = ?, image_id = ? WHERE id = ?");
    $updateStmt->bind_param("ssii", $text, $vacancy, $imageId, $userId);
    $updateStmt->execute();

    // Перенаправление на профиль
    header("Location: profile.php?id=$userId");
    exit;
}
?>

<form method="POST">
    <textarea name="text" placeholder="О себе" required><?php echo htmlspecialchars($user['text']); ?></textarea>

    <?php if ($user['role_id'] == 2): // Если это фрилансер ?>
        <input type="text" name="vacancy" placeholder="Вакансия" value="<?php echo htmlspecialchars($user['vacancy']); ?>" required>
    <?php endif; ?>

    <label for="image_id">Выберите фотографию:</label>
    <select name="image_id" id="image_id">
        <option value="1" <?php echo ($user['image_id'] == 1) ? 'selected' : ''; ?>>Стандартная</option>
        <?php while ($image = $imagesStmt->fetch_assoc()): ?>
            <option value="<?php echo $image['id']; ?>" <?php echo ($user['image_id'] == $image['id']) ? 'selected' : ''; ?>><?php echo 'Изображение ' . $image['id']; ?></option>
        <?php endwhile; ?>
    </select>

    <button type="submit">Сохранить изменения</button>
</form>

<!-- Кнопка для возврата на главную -->
<button onclick="window.location.href='index.php'">Вернуться на главную</button>

</body>
</html>
