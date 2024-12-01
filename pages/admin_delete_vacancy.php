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
$theme = isset($_COOKIE['theme']) ? $_COOKIE['theme'] : 'light';
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

// Обработка подтверждения удаления
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $stmt = $conn->prepare("DELETE FROM vacancy WHERE id = ?");
    $stmt->bind_param("i", $vacancyId);
    $stmt->execute();

    header("Location: admin_vacancies.php"); // Перенаправляем на страницу управления вакансиями
    exit;
}

// Получаем данные вакансии для подтверждения
$stmt = $conn->prepare("SELECT VacancyTag FROM vacancy WHERE id = ?");
$stmt->bind_param("i", $vacancyId);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 0) {
    header("Location: admin_vacancies.php"); // Если вакансия не найдена, перенаправляем на страницу управления вакансиями
    exit;
}

$vacancy = $result->fetch_assoc();
?>

<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <link id="themeStylesheet" rel="stylesheet" href="../assets/styles/<?php echo $theme; ?>.css">
    <link id="SubthemeStylesheet" rel="stylesheet" href="../assets/styles/delete_vacancy/dv_<?php echo $theme; ?>.css">
    <title>Удалить вакансию</title>
    <script>
        // Функция для смены темы и сохранения выбора в куки
        function toggleTheme() {
            let currentTheme = document.body.classList.toggle('dark') ? 'dark' : 'light';
            document.cookie = `theme=${currentTheme}; path=/; max-age=31536000`; // Кука на 1 год
            document.getElementById('themeStylesheet').href = `../assets/styles/${currentTheme}.css`;
            document.getElementById('SubthemeStylesheet').href = `../assets/styles/delete_vacancy/dv_${currentTheme}.css`;
        }

        // Применение темы при загрузке страницы
        document.addEventListener("DOMContentLoaded", function() {
            const theme = "<?php echo $theme; ?>";
            document.body.classList.toggle('dark', theme === 'dark');
        });
    </script>
</head>
<body>
<header>
    <button onclick="toggleTheme()">Сменить тему</button>
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
