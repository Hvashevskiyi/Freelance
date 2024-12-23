<?php
session_start();
require_once '../includes/db.php';

if (!isset($_GET['id']) || !isset($_SESSION['user_id'])) {
    header("Location: index.php"); // Если нет ID отклика или пользователь не авторизован, перенаправляем
    exit;
}

$applicationId = intval($_GET['id']);
$userId = $_SESSION['user_id'];
$conn = getDbConnection();
$theme = isset($_COOKIE['theme']) ? $_COOKIE['theme'] : 'light';
require_once '../includes/checkUserExists.php';

$role = $_SESSION['role_id'];

// Проверяем, существует ли пользователь
if (!checkUserExists($conn, $userId) || $role != 2) {
    // Удаляем данные сессии
    session_unset();
    session_destroy();
    header("Location: login.php"); // Перенаправляем на страницу входа
    exit;
}

// Получаем отклик для редактирования
$stmt = $conn->prepare("SELECT cover_letter FROM applications WHERE id = ? AND freelancer_id = ?");
$stmt->bind_param("ii", $applicationId, $userId);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 0) {
    header("Location: applications.php"); // Если отклик не найден, перенаправляем
    exit;
}

$application = $result->fetch_assoc();

// Обработка формы редактирования
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $newCoverLetter = $_POST['cover_letter'];

    // Обновление сопроводительного письма в базе данных
    $stmt = $conn->prepare("UPDATE applications SET cover_letter = ? WHERE id = ?");
    $stmt->bind_param("si", $newCoverLetter, $applicationId);
    if ($stmt->execute()) {
        header("Location: applications.php"); // Перенаправление после успешного редактирования
        exit;
    }
}
?>

<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <link id="themeStylesheet" rel="stylesheet" href="../assets/styles/<?php echo $theme; ?>.css">
    <link id="SubthemeStylesheet" rel="stylesheet" href="../assets/styles/edit_application/ea_<?php echo $theme; ?>.css">
    <title>Редактирование отклика</title>
    <script>
        // Функция для смены темы и сохранения выбора в куки
        function toggleTheme() {
            let currentTheme = document.body.classList.toggle('dark') ? 'dark' : 'light';
            document.cookie = `theme=${currentTheme}; path=/; max-age=31536000`; // Кука на 1 год
            document.getElementById('themeStylesheet').href = `../assets/styles/${currentTheme}.css`;
            document.getElementById('SubthemeStylesheet').href = `../assets/styles/edit_application/ea_${currentTheme}.css`;
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
    <button onclick="window.location.href='applications.php'">Назад к откликам</button>
    <button onclick="window.location.href='profile.php?id=<?php echo $_SESSION['user_id']; ?>'">
        <?php echo htmlspecialchars($_SESSION['username']); ?>
    </button>
</header>

<div class="edit_application_container">
    <h1>Редактировать отклик</h1>
    <form method="post" action="">
        <textarea name="cover_letter" rows="4" cols="50" required><?php echo htmlspecialchars($application['cover_letter']); ?></textarea><br>
        <input type="submit" value="Сохранить изменения">
    </form>
</div>

</body>
</html>
