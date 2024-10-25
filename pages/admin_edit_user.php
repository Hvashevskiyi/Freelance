<?php
session_start();
require_once '../includes/db.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}

$conn = getDbConnection();

if (!isset($_GET['id'])) {
    header("Location: admin_users.php"); // Если нет ID пользователя, перенаправляем
    exit;
}

$userId = intval($_GET['id']);
require_once '../includes/checkUserExists.php';
$role = $_SESSION['role_id'];

// Проверяем, существует ли пользователь
if (!checkUserExists($conn, $userId) || $role != 1) {
    // Удаляем данные сессии
    session_unset();
    session_destroy();
    header("Location: login.php"); // Перенаправляем на страницу входа
    exit;
}

// Получаем данные пользователя
$stmt = $conn->prepare("SELECT name, role_id FROM Users WHERE id = ?");
$stmt->bind_param("i", $userId);
$stmt->execute();
$user = $stmt->get_result()->fetch_assoc();

if (!$user) {
    header("Location: admin_users.php"); // Если пользователь не найден, перенаправляем
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = $_POST['name'];
    $roleId = $_POST['role_id'];

    // Обновляем данные пользователя
    $updateStmt = $conn->prepare("UPDATE Users SET name = ?, role_id = ? WHERE id = ?");
    $updateStmt->bind_param("ssi", $name, $roleId, $userId);
    $updateStmt->execute();

    header("Location: admin_users.php");
    exit;
}
?>

<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <link rel="stylesheet" href="../assets/styles/base.css">
    <link rel="stylesheet" href="../assets/styles/edit_user.css"> <!-- Новый стиль для редактирования пользователя -->
    <title>Редактировать пользователя</title>
</head>
<body>
<header>
    <button onclick="window.location.href='admin_users.php'">Управление пользователями</button>
    <button onclick="window.location.href='admin_vacancies.php'">Управление вакансиями</button>
    <button onclick="window.location.href='admin_stats.php'">Статистика</button>
    <button onclick="window.location.href='admin_applications.php'">История откликов</button>
    <button onclick="window.location.href='admin_weights.php'">Рейтинг</button>
    <button onclick="window.location.href='index.php'">На главную</button>
</header>
<div class="container">
    <h1>Редактировать пользователя</h1>
    <form method="post" action="">
        <label for="name">Имя:</label>
        <input type="text" name="name" id="name" value="<?php echo htmlspecialchars($user['name']); ?>" required>

        <label for="role_id">Роль:</label>
        <select name="role_id" id="role_id">
            <option value="2" <?php echo $user['role_id'] == 2 ? 'selected' : ''; ?>>Фрилансер</option>
            <option value="3" <?php echo $user['role_id'] == 3 ? 'selected' : ''; ?>>Компания</option>
            <option value="1" <?php echo $user['role_id'] == 1 ? 'selected' : ''; ?>>Администратор</option>
        </select>

        <input type="submit" value="Сохранить изменения">
    </form>
</div>
</body>
</html>
