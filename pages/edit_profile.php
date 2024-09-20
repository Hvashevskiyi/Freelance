<?php
session_start();
require_once '../includes/db.php';

if (!isset($_GET['id']) || $_SESSION['user_id'] !== intval($_GET['id'])) {
    header("Location: index.php");
    exit;
}

$userId = intval($_GET['id']);
$conn = getDbConnection();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $text = $_POST['text'];
    $vacancy = $_POST['vacancy'];

    $stmt = $conn->prepare("UPDATE Users SET text = ?, vacancy = ? WHERE id = ?");
    $stmt->bind_param("ssi", $text, $vacancy, $userId);
    $stmt->execute();

    header("Location: profile.php?id=$userId");
    exit;
}

$stmt = $conn->prepare("SELECT text, vacancy FROM Users WHERE id = ?");
$stmt->bind_param("i", $userId);
$stmt->execute();
$user = $stmt->get_result()->fetch_assoc();
?>

<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">

    <link rel="stylesheet" href="../assets/styles/edit_profile.css">
    <title>Редактировать профиль</title>
</head>
<body>
<h1>Редактирование профиля</h1>

<form method="POST">
    <textarea name="text" placeholder="О себе" required><?php echo htmlspecialchars($user['text']); ?></textarea>
    <input type="text" name="vacancy" placeholder="Вакансия" value="<?php echo htmlspecialchars($user['vacancy']); ?>" required>
    <button type="submit">Сохранить изменения</button>
</form>

<!-- Кнопка для возврата на главную -->
<button onclick="window.location.href='index.php'">Вернуться на главную</button>

</body>
</html>
