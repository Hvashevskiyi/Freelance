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
$role = $_SESSION['role_id'];

// Проверяем, существует ли пользователь
if (!checkUserExists($conn, $userId) || $role != 1) {
    // Удаляем данные сессии
    session_unset();
    session_destroy();
    header("Location: login.php"); // Перенаправляем на страницу входа
    exit;
}

// Проверяем, есть ли ID вакансии в GET-запросе
if (!isset($_GET['id'])) {
    header("Location: admin_vacancies.php"); // Если нет ID, перенаправляем на страницу управления вакансиями
    exit;
}

$vacancyId = intval($_GET['id']);

// Получаем данные вакансии
$stmt = $conn->prepare("SELECT VacancyTag, Description, Salary, id_company FROM vacancy WHERE id = ?");
$stmt->bind_param("i", $vacancyId);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 0) {
    header("Location: admin_vacancies.php"); // Если вакансия не найдена, перенаправляем на страницу управления вакансиями
    exit;
}

$vacancy = $result->fetch_assoc();

// Обработка формы редактирования
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $vacancyTag = $_POST['vacancyTag'];
    $description = $_POST['description'];
    $salary = $_POST['salary'];

    // Обновляем данные вакансии
    $stmt = $conn->prepare("UPDATE vacancy SET VacancyTag = ?, Description = ?, Salary = ? WHERE id = ?");
    $stmt->bind_param("ssdi", $vacancyTag, $description, $salary, $vacancyId);
    $stmt->execute();

    // Перенаправляем на страницу управления вакансиями
    header("Location: admin_vacancies.php");
    exit;
}
?>

<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <link rel="stylesheet" href="../assets/styles/base.css">
    <link rel="stylesheet" href="../assets/styles/edit_vacancy.css"> <!-- Стили для редактирования вакансии -->
    <title>Редактировать вакансию</title>
</head>
<body>
<header>
   <button onclick="window.location.href='index.php'">На главную</button>
</header>

<div class="container">
    <h1>Редактировать вакансию</h1>
    <form method="post" action="">
        <label for="vacancyTag">Название вакансии:</label>
        <input type="text" name="vacancyTag" id="vacancyTag" value="<?php echo htmlspecialchars($vacancy['VacancyTag']); ?>" required>

        <label for="description">Описание:</label>
        <textarea name="description" id="description" required><?php echo htmlspecialchars($vacancy['Description']); ?></textarea>

        <label for="salary">Зарплата:</label>
        <input type="number" name="salary" id="salary" value="<?php echo htmlspecialchars($vacancy['Salary']); ?>" required>

        <input type="submit" value="Сохранить изменения">
    </form>
</div>
</body>
</html>