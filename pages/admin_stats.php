<?php
session_start();
require_once '../includes/db.php';

require_once '../includes/checkUserExists.php';

$userId = $_SESSION['user_id'];
$conn = getDbConnection();
$theme = isset($_COOKIE['theme']) ? $_COOKIE['theme'] : 'light';
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
    <link id="themeStylesheet" rel="stylesheet" href="../assets/styles/<?php echo $theme; ?>.css">
    <link id="SubthemeStylesheet" rel="stylesheet" href="../assets/styles/admin_stats/as_<?php echo $theme; ?>.css">
    <title>Статистика</title>
    <script>
        // Функция для смены темы и сохранения выбора в куки
        function toggleTheme() {
            let currentTheme = document.body.classList.toggle('dark') ? 'dark' : 'light';
            document.cookie = `theme=${currentTheme}; path=/; max-age=31536000`; // Кука на 1 год
            document.getElementById('themeStylesheet').href = `../assets/styles/${currentTheme}.css`;
            document.getElementById('SubthemeStylesheet').href = `../assets/styles/admin_stats/as_${currentTheme}.css`;
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
    <button onclick="window.location.href='admin_users.php'">Управление пользователями</button>
    <button onclick="window.location.href='admin_vacancies.php'">Управление вакансиями</button>
    <button onclick="window.location.href='admin_applications.php'">История откликов</button>
    <button onclick="window.location.href='admin_weights.php'">Рейтинг</button>
    <button onclick="window.location.href='admin_freelance.php'">Фрилансеры</button>

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
