<?php
session_start();
require_once '../includes/db.php';

if (!isset($_GET['id'])) {
    header("Location: index.php");
    exit;
}

$userId = intval($_GET['id']);
$conn = getDbConnection();

// Получаем данные пользователя
$stmt = $conn->prepare("SELECT id, name, email, text, vacancy, image_id, role_id FROM Users WHERE id = ?");
$stmt->bind_param("i", $userId);
$stmt->execute();
$user = $stmt->get_result()->fetch_assoc();

if (!$user) {
    // Если пользователь не найден, перенаправляем на главную
    header("Location: index.php");
    exit;
}

// Инициализируем переменные для статистики фрилансера
$completedCount = 0;
$inProgressCount = 0;
$notCompletedCount = 0;

if ($user['role_id'] == 2) { // Проверяем, если пользователь - фрилансер
    // Получаем статистику по заявкам фрилансера
    $stmt = $conn->prepare("
        SELECT 
            SUM(CASE WHEN s.completed = 1 THEN 1 ELSE 0 END) AS completed,
            SUM(CASE WHEN s.completed IS NULL OR s.completed = '' THEN 1 ELSE 0 END) AS in_progress,
            SUM(CASE WHEN s.completed = 0 THEN 1 ELSE 0 END) AS not_completed
        FROM applications a
        LEFT JOIN application_status s ON a.id = s.application_id
        WHERE a.freelancer_id = ? AND s.status = 'Принято'
    ");
    $stmt->bind_param("i", $userId);
    $stmt->execute();
    $statusCounts = $stmt->get_result()->fetch_assoc();

    // Устанавливаем значения для счетчиков
    $completedCount = $statusCounts['completed'] ?? 0;
    $inProgressCount = $statusCounts['in_progress'] ?? 0;
    $notCompletedCount = $statusCounts['not_completed'] ?? 0;
}

?>

<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <link rel="stylesheet" href="../assets/styles/profile.css">
    <title>Профиль пользователя</title>
</head>
<body>
<header>
    <?php if ($user['role_id'] == 2): ?> <!-- Проверяем, если пользователь фрилансер -->
        <button onclick="window.location.href='applications.php'">Мои отклики</button> <!-- Кнопка для перехода на страницу откликов -->
    <?php endif; ?>

    <?php if (isset($_SESSION['user_id'])): ?>
        <button onclick="window.location.href='profile.php?id=<?php echo $_SESSION['user_id']; ?>'">
            <?php echo $_SESSION['username']; ?>
        </button>

        <button onclick="window.location.href='logout.php'">Выйти</button>
    <?php else: ?>
        <button onclick="window.location.href='login.php'">Войти</button>
    <?php endif; ?>
</header>

<div class="profile-container">
    <?php if ($user['role_id'] == 3): ?>
        <h1>Профиль компании</h1>
    <?php else: ?>
        <h1>Профиль пользователя</h1>
    <?php endif; ?>

    <div class="profile-wrapper">
        <div class="profile-image">
            <img src="get_image.php?id=<?php echo $user['image_id']; ?>" alt="Фото профиля" style="width:150px; height:150px; border-radius:50%; object-fit: cover; ">
        </div>
        <div class="profile-info">
            <?php if ($user['role_id'] == 3): // Если это компания ?>
                <p><strong>Название:</strong> <?php echo htmlspecialchars($user['name']); ?></p>
                <p><strong>Email:</strong> <?php echo htmlspecialchars($user['email']); ?></p>
                <p><strong>О нас:</strong> <?php echo htmlspecialchars($user['text']); ?></p>

            <?php else: ?>
                <p><strong>Имя:</strong> <?php echo htmlspecialchars($user['name']); ?></p>
                <p><strong>Email:</strong> <?php echo htmlspecialchars($user['email']); ?></p>
                <p><strong>О себе:</strong> <?php echo htmlspecialchars($user['text']); ?></p>
                <p><strong>Вакансия:</strong> <?php echo htmlspecialchars($user['vacancy']); ?></p>

                <!-- Выводим статистику фрилансера -->
                <h2>Статистика работ</h2>
                <p><strong>Выполнено:</strong> <?php echo $completedCount; ?></p>
                <p><strong>В процессе:</strong> <?php echo $inProgressCount; ?></p>
                <p><strong>Не выполнено:</strong> <?php echo $notCompletedCount; ?></p>
            <?php endif; ?>
        </div>
    </div>

    <div class="button-container">
        <?php if (isset($_SESSION['user_id']) && $_SESSION['user_id'] == $user['id']): ?>
            <button onclick="window.location.href='edit_profile.php?id=<?php echo $user['id']; ?>'">Редактировать</button>
        <?php endif; ?>
        <button onclick="window.location.href='index.php'">Вернуться на главную</button>
    </div>
</div>

</body>
</html>
