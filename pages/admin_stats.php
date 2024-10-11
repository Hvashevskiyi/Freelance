<?php
require_once '../includes/db.php';

$conn = getDbConnection();

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
    <button onclick="window.location.href='admin_stats.php'">Статистика</button>
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
