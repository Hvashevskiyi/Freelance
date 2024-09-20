<?php
session_start();
require_once '../includes/db.php';

$conn = getDbConnection();
$users = $conn->query("SELECT id, name, email, vacancy FROM Users");

$deleteError = ''; // Переменная для хранения ошибки

if (isset($_POST['delete']) && isset($_SESSION['user_id'])) {
    $idToDelete = intval($_POST['user_id']);
    if ($idToDelete !== $_SESSION['user_id']) {
        // Если удаляем не себя, удаляем пользователя
        $conn->query("DELETE FROM Users WHERE id = $idToDelete");
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
    <link rel="stylesheet" href="../assets/styles/index.css">
    <script src="../assets/search.js"></script>
    <title>Фриланс Платформа</title>
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


<h1>Список пользователей</h1>
<!-- Поле для поиска -->
<input type="text" id="searchInput" placeholder="Поиск по имени, email или вакансии" onkeyup="filterUsers()">

<table id="usersTable">
    <tr>
        <th>Имя</th>
        <th>Email</th>
        <th>Вакансия</th>
    </tr>
    <?php while ($user = $users->fetch_assoc()): ?>
        <tr>
            <td><a href="profile.php?id=<?php echo $user['id']; ?>"><?php echo $user['name']; ?></a></td>
            <td><?php echo $user['email']; ?></td>
            <td><?php echo $user['vacancy']; ?></td>
        </tr>
    <?php endwhile; ?>
</table>

</body>
</html>

