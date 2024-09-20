<?php
session_start();
require_once '../includes/db.php';

if (!isset($_GET['id'])) {
    header("Location: index.php");
    exit;
}

$userId = intval($_GET['id']);
$conn = getDbConnection();

$stmt = $conn->prepare("SELECT id, name, email, text, vacancy FROM Users WHERE id = ?");
$stmt->bind_param("i", $userId);
$stmt->execute();
$user = $stmt->get_result()->fetch_assoc();

if (!$user) {
    // Если пользователь не найден, перенаправляем на главную
    header("Location: index.php");
    exit;
}

// Удаление пользователя
if (isset($_POST['delete'])) {
    if (isset($_SESSION['user_id']) && $_SESSION['user_id'] !== $userId) {
        $stmt = $conn->prepare("DELETE FROM Users WHERE id = ?");
        $stmt->bind_param("i", $userId);
        $stmt->execute();
        header("Location: index.php");
        exit;
    } else {
        // Сообщение об ошибке, если пытаемся удалить себя
        $deleteError = 'Вы не можете удалить себя!';
    }
}
?>

<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <link rel="stylesheet" href="../assets/styles/base.css">
    <link rel="stylesheet" href="../assets/styles/profile.css">
    <title>Профиль пользователя</title>
</head>
<body>
<header>
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
    <h1>Профиль пользователя</h1>
    <div class="profile-info">
        <p><strong>Имя:</strong> <?php echo htmlspecialchars($user['name']); ?></p>
        <p><strong>Email:</strong> <?php echo htmlspecialchars($user['email']); ?></p>
        <p><strong>О себе:</strong> <?php echo htmlspecialchars($user['text']); ?></p>
        <p><strong>Вакансия:</strong> <?php echo htmlspecialchars($user['vacancy']); ?></p>
    </div>

    <div class="button-container">
        <?php if (isset($_SESSION['user_id']) && $_SESSION['user_id'] == $user['id']): ?>

            <button onclick="window.location.href='edit_profile.php?id=<?php echo $user['id']; ?>'">Редактировать</button>

        <?php elseif (isset($_SESSION['user_id'])): ?>
            <form method="POST">
                <button type="submit" name="delete">Удалить пользователя</button>
            </form>
        <?php endif; ?>
        <button onclick="window.location.href='index.php'">Вернуться на главную</button>
    </div>
</div>

</body>
</html>
