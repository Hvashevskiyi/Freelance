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

// Проверяем, существует ли пользователь и его роль (фрилансер)
if (!checkUserExists($conn, $userId) || $role != 2) {
    // Удаляем данные сессии
    session_unset();
    session_destroy();
    header("Location: login.php"); // Перенаправляем на страницу входа
    exit;
}

// Проверяем, есть ли ID отклика в GET-запросе
if (!isset($_GET['id'])) {
    header("Location: applications.php"); // Если нет ID отклика, перенаправляем на страницу откликов
    exit;
}

$applicationId = intval($_GET['id']);

// Обработка подтверждения удаления
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $stmt = $conn->prepare("DELETE FROM applications WHERE id = ? AND freelancer_id = ?");
    $stmt->bind_param("ii", $applicationId, $userId);
    $stmt->execute();

    header("Location: applications.php"); // Перенаправляем на страницу откликов
    exit;
}

// Получаем данные отклика для подтверждения
$stmt = $conn->prepare("
    SELECT v.VacancyTag, u.name AS company_name 
    FROM applications a
    JOIN vacancy v ON a.vacancy_id = v.id
    JOIN users u ON v.id_company = u.id
    WHERE a.id = ? AND a.freelancer_id = ?
");
$stmt->bind_param("ii", $applicationId, $userId);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 0) {
    header("Location: applications.php"); // Если отклик не найден, перенаправляем на страницу откликов
    exit;
}

$application = $result->fetch_assoc();
?>

<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <link rel="stylesheet" href="../assets/styles/base.css">
    <link rel="stylesheet" href="../assets/styles/delete_application.css"> <!-- Стили для удаления отклика -->
    <title>Удалить отклик</title>
</head>
<body>
<header>
    <button onclick="window.location.href='index.php'">На главную</button>
</header>

<div class="container">
    <h1>Удалить отклик</h1>
    <p>Вы действительно хотите удалить отклик на вакансию "<strong><?php echo htmlspecialchars($application['VacancyTag']); ?></strong>" компании "<strong><?php echo htmlspecialchars($application['company_name']); ?></strong>"?</p>

    <form method="post" action="">
        <input type="submit" value="Подтвердить удаление">
        <button type="button" onclick="window.location.href='applications.php'">Отмена</button>
    </form>
</div>
</body>
</html>
