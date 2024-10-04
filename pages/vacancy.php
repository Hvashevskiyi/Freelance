<?php
session_start();
require_once '../includes/db.php';

if (!isset($_GET['id'])) {
    header("Location: index.php"); // Если нет ID вакансии, перенаправляем на главную
    exit;
}

$vacancyId = intval($_GET['id']);
$conn = getDbConnection();

// Получаем данные о вакансии
$stmt = $conn->prepare("SELECT v.VacancyTag, v.Description, v.Salary, u.name AS author_name FROM vacancy v JOIN users u ON v.id_company = u.id WHERE v.id = ?");
$stmt->bind_param("i", $vacancyId);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 0) {
    header("Location: index.php"); // Если вакансия не найдена, перенаправляем на главную
    exit;
}

$vacancy = $result->fetch_assoc();
?>

<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <link rel="stylesheet" href="../assets/styles/base.css">
    <link rel="stylesheet" href="../assets/styles/vacancy.css"> <!-- Добавим новый стиль для страницы вакансии -->
    <title><?php echo htmlspecialchars($vacancy['VacancyTag']); ?></title>
</head>
<body>
<header>
    <button onclick="window.location.href='index.php'">На главную</button>
    <?php if (isset($_SESSION['user_id'])): ?>
        <button onclick="window.location.href='profile.php?id=<?php echo $_SESSION['user_id']; ?>'">
            <?php echo $_SESSION['username']; ?>
        </button>
    <?php endif; ?>
</header>

<div class="vacancy_container">
    <h1><?php echo htmlspecialchars($vacancy['VacancyTag']); ?></h1>
    <p><strong>Описание:</strong></p>
    <p><?php echo htmlspecialchars($vacancy['Description']); ?></p>
    <p><strong>Зарплата:</strong> <?php echo htmlspecialchars($vacancy['Salary']); ?> ₽</p>
    <p><strong>Автор:</strong> <?php echo htmlspecialchars($vacancy['author_name']); ?></p>
</div>

</body>
</html>
