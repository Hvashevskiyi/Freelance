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
// Проверяем, существует ли пользователь
if (!checkUserExists($conn, $userId)) {
    // Удаляем данные сессии
    session_unset();
    session_destroy();
    header("Location: login.php"); // Перенаправляем на страницу входа
    exit;
}

// Проверяем, есть ли ID вакансии в GET-запросе
if (!isset($_GET['id'])) {
    header("Location: company_vacancies.php"); // Если нет ID, перенаправляем на страницу управления вакансиями
    exit;
}

$vacancyId = intval($_GET['id']);

// Обработка подтверждения удаления
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $stmt = $conn->prepare("DELETE FROM vacancy WHERE id = ?");
    $stmt->bind_param("i", $vacancyId);
    $stmt->execute();

    header("Location: company_vacancies.php"); // Перенаправляем на страницу управления вакансиями
    exit;
}

// Получаем данные вакансии для подтверждения
$stmt = $conn->prepare("SELECT VacancyTag FROM vacancy WHERE id = ?");
$stmt->bind_param("i", $vacancyId);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 0) {
    header("Location: company_vacancies.php"); // Если вакансия не найдена, перенаправляем на страницу управления вакансиями
    exit;
}

$vacancy = $result->fetch_assoc();
?>

<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <link rel="stylesheet" href="../assets/styles/base.css">
    <link rel="stylesheet" href="../assets/styles/delete_vacancy.css"> <!-- Стили для удаления вакансии -->
    <title>Удалить вакансию</title>
</head>
<body>
<header>
    <button onclick="window.location.href='index.php'">На главную</button>
</header>

<div class="container">
    <h1>Удалить вакансию</h1>
    <p>Вы действительно хотите удалить вакансию "<strong><?php echo htmlspecialchars($vacancy['VacancyTag']); ?></strong>"?</p>

    <form method="post" action="">
        <input type="submit" value="Подтвердить удаление">
        <button type="button" onclick="window.location.href='admin_vacancies.php'">Отмена</button>
    </form>
</div>
</body>
</html>
