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

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $w_completed = floatval($_POST['w_completed']);
    $w_not_completed = floatval($_POST['w_not_completed']);
    $w_avg = floatval($_POST['w_avg']);

    // Вставка новой записи
    $stmt = $conn->prepare("
        INSERT INTO weights (completed_weight, not_completed_weight, avg_weight, created_at) 
        VALUES (?, ?, ?, NOW())
    ");
    $stmt->bind_param("ddd", $w_completed, $w_not_completed, $w_avg);
    $stmt->execute();

    // Запуск перерасчета рейтингов
    include 'recalc.php';
}


// Получаем текущие веса (последнюю запись)
$stmt = $conn->query("
    SELECT completed_weight, not_completed_weight, avg_weight 
    FROM weights 
    ORDER BY created_at DESC 
    LIMIT 1
");
$weightsData = $stmt->fetch_assoc();

?>

<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <link id="themeStylesheet" rel="stylesheet" href="../assets/styles/<?php echo $theme; ?>.css">
    <link id="SubthemeStylesheet" rel="stylesheet" href="../assets/styles/admin_weights/aw_<?php echo $theme; ?>.css">

    <title>Настройка весов и перерасчет рейтингов</title>
    <script>
        // Функция для смены темы и сохранения выбора в куки
        function toggleTheme() {
            let currentTheme = document.body.classList.toggle('dark') ? 'dark' : 'light';
            document.cookie = `theme=${currentTheme}; path=/; max-age=31536000`; // Кука на 1 год
            document.getElementById('themeStylesheet').href = `../assets/styles/${currentTheme}.css`;
            document.getElementById('SubthemeStylesheet').href = `../assets/styles/admin_weights/aw_${currentTheme}.css`;
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
    <button onclick="window.location.href='admin_stats.php'">Статистика</button>
    <button onclick="window.location.href='admin_applications.php'">История откликов</button>
    <button onclick="window.location.href='admin_freelance.php'">Фрилансеры</button>
    <button onclick="window.location.href='index.php'">На главную</button>
</header>

<div class="container">
    <h1>Настройка весов для расчета рейтинга</h1>
    <form method="post" action="admin_weights.php">
        <label>Вес за выполненные заказы:</label>
        <input type="number" step="0.1" name="w_completed" value="<?php echo htmlspecialchars($weightsData['completed_weight'] ?? 0.5); ?>"><br>

        <label>Вес за не выполненные заказы:</label>
        <input type="number" step="0.1" name="w_not_completed" value="<?php echo htmlspecialchars($weightsData['not_completed_weight'] ?? 0.1); ?>"><br>

        <label>Вес за среднее значение оценки:</label>
        <input type="number" step="0.1" name="w_avg" value="<?php echo htmlspecialchars($weightsData['avg_weight'] ?? 1.4); ?>"><br>

        <button type="submit">Сохранить весовые коэффициенты и запустить перерасчет</button>
    </form>

</div>

</body>
</html>
