<?php
session_start();
require_once '../includes/db.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php"); // Если пользователь не авторизован, перенаправляем на страницу входа
    exit;
}

if (!isset($_GET['id'])) {
    header("Location: company_applications.php"); // Если нет ID отклика, перенаправляем на страницу откликов
    exit;
}


$applicationId = intval($_GET['id']);
$conn = getDbConnection();
require_once '../includes/checkUserExists.php';
$userId = $_SESSION['user_id'];
// Проверяем, существует ли пользователь
if (!checkUserExists($conn, $userId)) {
    // Удаляем данные сессии
    session_unset();
    session_destroy();
    header("Location: login.php"); // Перенаправляем на страницу входа
    exit;
}
// Получаем сопроводительное письмо
$stmt = $conn->prepare("
    SELECT a.cover_letter, v.VacancyTag, f.name AS freelancer_name
    FROM applications a
    JOIN vacancy v ON a.vacancy_id = v.id
    JOIN users f ON a.freelancer_id = f.id
    WHERE a.id = ?
");
$stmt->bind_param("i", $applicationId);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 0) {
    header("Location: company_applications.php"); // Если отклик не найден, перенаправляем на страницу откликов
    exit;
}

$application = $result->fetch_assoc();
?>

<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <link rel="stylesheet" href="../assets/styles/base.css">
    <link rel="stylesheet" href="../assets/styles/view_cover_letter.css"> <!-- Новый стиль для страницы просмотра сопроводительного письма -->
    <title>Сопроводительное письмо</title>
</head>
<body>
<header>
    <button onclick="window.location.href='company_applications.php'">Назад к откликам</button>
    <button onclick="window.location.href='profile.php?id=<?php echo $_SESSION['user_id']; ?>'">
        <?php echo htmlspecialchars($_SESSION['username']); ?>
    </button>
</header>

<div class="cover_letter_container">
    <h1>Сопроводительное письмо</h1>
    <p><strong>Вакансия:</strong> <?php echo htmlspecialchars($application['VacancyTag']); ?></p>
    <p><strong>Фрилансер:</strong> <?php echo htmlspecialchars($application['freelancer_name']); ?></p>
    <div class="cover_letter">
        <p><?php echo nl2br(htmlspecialchars($application['cover_letter'])); ?></p>
    </div>
</div>

</body>
</html>
