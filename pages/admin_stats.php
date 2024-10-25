<?php
session_start();
require_once '../includes/db.php';

require_once '../includes/checkUserExists.php';

$userId = $_SESSION['user_id'];
$conn = getDbConnection();
// Проверяем, существует ли пользователь
$role = $_SESSION['role_id'];

// Проверяем, существует ли пользователь
if (!checkUserExists($conn, $userId) || $role != 1) {
    // Удаляем данные сессии
    session_unset();
    session_destroy();
    header("Location: login.php"); // Перенаправляем на страницу входа
    exit;
}

// Пример получения статистики
$usersCountStmt = $conn->prepare("SELECT COUNT(*) as count FROM Users");
$usersCountStmt->execute();
$usersCount = $usersCountStmt->get_result()->fetch_assoc()['count'];

$vacanciesCountStmt = $conn->prepare("SELECT COUNT(*) as count FROM vacancy");
$vacanciesCountStmt->execute();
$vacanciesCount = $vacanciesCountStmt->get_result()->fetch_assoc()['count'];

$applicationsCountStmt = $conn->prepare("SELECT COUNT(*) as count FROM applications");
$applicationsCountStmt->execute();
$applicationsCount = $applicationsCountStmt->get_result()->fetch_assoc()['count'];
?>

<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <link rel="stylesheet" href="../assets/styles/base.css">
    <link rel="stylesheet" href="../assets/styles/admin_stats.css">
    <title>Статистика</title>
</head>
<body>
<header>
    <button onclick="window.location.href='admin_users.php'">Управление пользователями</button>
    <button onclick="window.location.href='admin_vacancies.php'">Управление вакансиями</button>
    <button onclick="window.location.href='admin_applications.php'">История откликов</button>
    <button onclick="window.location.href='admin_weights.php'">Рейтинг</button>
    <button onclick="window.location.href='index.php'">На главную</button>
</header>

<div class="container">
    <h1>Статистика</h1>
    <p>Количество пользователей: <?php echo htmlspecialchars($usersCount); ?></p>
    <p>Количество вакансий: <?php echo htmlspecialchars($vacanciesCount); ?></p>
    <p>Количество откликов: <?php echo htmlspecialchars($applicationsCount); ?></p>
</div>
</body>
</html>
