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
$role = $_SESSION['role_id'];

// Проверяем, существует ли пользователь
if (!checkUserExists($conn, $userId) || $role != 1) {
    // Удаляем данные сессии
    session_unset();
    session_destroy();
    header("Location: login.php"); // Перенаправляем на страницу входа
    exit;
}


// Если веса были отправлены, обновляем их в базе данных
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $w_completed = floatval($_POST['w_completed']);
    $w_not_completed = floatval($_POST['w_not_completed']);
    $w_avg = floatval($_POST['w_avg']);

    $stmt = $conn->prepare("UPDATE weights SET value = ? WHERE name = 'completed_weight'");
    $stmt->bind_param("d", $w_completed);
    $stmt->execute();

    $stmt = $conn->prepare("UPDATE weights SET value = ? WHERE name = 'not_completed_weight'");
    $stmt->bind_param("d", $w_not_completed);
    $stmt->execute();
    $stmt = $conn->prepare("UPDATE weights SET value = ? WHERE name = 'avg_weight'");
    $stmt->bind_param("d", $w_avg);
    $stmt->execute();

    echo "<script>alert('Весовые коэффициенты обновлены');</script>";
}

// Получаем текущие веса
$weights = $conn->query("SELECT name, value FROM weights");
$weightsData = [];
while ($row = $weights->fetch_assoc()) {
    $weightsData[$row['name']] = $row['value'];
}
?>

<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <link rel="stylesheet" href="../assets/styles/admin_weights.css">

    <title>Настройка весов и перерасчет рейтингов</title>
</head>
<body>
<header>
    <button onclick="window.location.href='admin_users.php'">Управление пользователями</button>
    <button onclick="window.location.href='admin_vacancies.php'">Управление вакансиями</button>
    <button onclick="window.location.href='admin_stats.php'">Статистика</button>
    <button onclick="window.location.href='admin_applications.php'">История откликов</button>
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
        <button type="submit">Сохранить весовые коэффициенты</button>
    </form>

    <h2>Запустить перерасчет рейтингов</h2>
    <form method="post" action="recalc.php" target="hiddenFrame">
        <button type="submit">Перерасчет</button>
    </form>
    <iframe name="hiddenFrame" style="display:none;"></iframe>
</div>

</body>
</html>
