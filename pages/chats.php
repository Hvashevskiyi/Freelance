<?php
session_start();
require_once '../includes/db.php';
$conn = getDbConnection();

// Если пользователь не авторизован, перенаправляем на страницу логина
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}

$userId = $_SESSION['user_id'];

// Получаем все чаты пользователя
$stmt = $conn->prepare("SELECT c.id, u.name, u.email FROM chats c
                        JOIN users u ON (u.id = c.user_one_id OR u.id = c.user_two_id)
                        WHERE u.id != ? AND (c.user_one_id = ? OR c.user_two_id = ?)");
$stmt->bind_param("iii", $userId, $userId, $userId);
$stmt->execute();
$result = $stmt->get_result();

$chats = [];
while ($chat = $result->fetch_assoc()) {
    $chats[] = $chat;
}

?>

<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <title>Чаты</title>
</head>
<body>
<header>
    <button onclick="window.location.href='index.php'">На главную</button>
</header>

<h1>Ваши чаты</h1>
<ul>
    <?php foreach ($chats as $chat): ?>
        <li>
            <a href="chat.php?chat_id=<?php echo $user['id']; ?>" target="_blank">
                Чат с <?php echo htmlspecialchars($chat['name']); ?> (<?php echo htmlspecialchars($chat['email']); ?>)
            </a>
        </li>
    <?php endforeach; ?>
</ul>
</body>
</html>
