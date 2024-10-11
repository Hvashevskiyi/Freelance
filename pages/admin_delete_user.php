<?php
session_start();
require_once '../includes/db.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php"); // Если пользователь не авторизован, перенаправляем на страницу входа
    exit;
}

require_once '../includes/checkUserExists.php';
$userId = $_SESSION['user_id'];

$conn = getDbConnection();
$role = $_SESSION['role_id'];

// Проверяем, существует ли пользователь
if (!checkUserExists($conn, $userId) || $role != 1) {
    // Удаляем данные сессии
    session_unset();
    session_destroy();
    header("Location: login.php"); // Перенаправляем на страницу входа
    exit;
}

// Проверяем, есть ли ID пользователя в GET-запросе
if (!isset($_GET['id'])) {
    header("Location: admin_users.php"); // Если нет ID, перенаправляем на страницу управления пользователями
    exit;
}

$userId = intval($_GET['id']);

// Обработка подтверждения удаления
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Удаляем пользователя из базы данных
    $stmt = $conn->prepare("DELETE FROM Users WHERE id = ?");
    $stmt->bind_param("i", $userId);
    $stmt->execute();

    header("Location: admin_users.php"); // После удаления перенаправляем на страницу управления пользователями
    exit;
}

// Получаем данные пользователя для подтверждения
$stmt = $conn->prepare("SELECT name FROM Users WHERE id = ?");
$stmt->bind_param("i", $userId);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 0) {
    header("Location: admin_users.php"); // Если пользователь не найден, перенаправляем на страницу управления пользователями
    exit;
}

$user = $result->fetch_assoc();
?>

<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <link rel="stylesheet" href="../assets/styles/base.css">
    <link rel="stylesheet" href="../assets/styles/delete_user.css"> <!-- Стили для удаления пользователя -->
    <title>Удалить пользователя</title>
</head>
<body>
<header>
    <button onclick="window.location.href='admin_users.php'">Управление пользователями</button>
    <button onclick="window.location.href='admin_vacancies.php'">Управление вакансиями</button>
    <button onclick="window.location.href='admin_stats.php'">Статистика</button>
    <button onclick="window.location.href='index.php'">На главную</button>
</header>

<div class="container">
    <h1>Удалить пользователя</h1>
    <p>Вы действительно хотите удалить пользователя "<strong><?php echo htmlspecialchars($user['name']); ?></strong>"? Обратите внимание, что все связанные отклики также будут удалены.</p>

    <form method="post" action="">
        <input type="submit" value="Подтвердить удаление">
        <button type="button" onclick="window.location.href='admin_users.php'">Отмена</button>
    </form>
</div>
</body>
</html>
