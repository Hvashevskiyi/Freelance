<?php
session_start();
require_once '../includes/db.php';

$conn = getDbConnection();
$users = $conn->query("SELECT id, name, email FROM Users");

if (isset($_POST['delete']) && isset($_SESSION['user_id'])) {
    $idToDelete = intval($_POST['user_id']);
    if ($idToDelete !== $_SESSION['user_id']) {
        $conn->query("DELETE FROM Users WHERE id = $idToDelete");
        header("Location: index.php");
        exit;
    }
}

?>

<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <link rel="stylesheet" href="../assets/style.css">
    <title>Фриланс Платформа</title>
</head>
<body>
<header>
    <?php if (isset($_SESSION['user_id'])): ?>
        <span><?php echo $_SESSION['username']; ?></span>
        <a href="logout.php">Выйти</a>
    <?php else: ?>
        <a href="login.php">Войти</a>
    <?php endif; ?>
</header>

<h1>Список пользователей</h1>
<table>
    <tr>
        <th>Имя</th>
        <th>Email</th>
        <th>Действия</th>
    </tr>
    <?php while ($user = $users->fetch_assoc()): ?>
        <tr>
            <td><?php echo $user['name']; ?></td>
            <td><?php echo $user['email']; ?></td>
            <td>
                <?php if (isset($_SESSION['user_id'])): ?>
                    <form method="POST">
                        <input type="hidden" name="user_id" value="<?php echo $user['id']; ?>">
                        <button type="submit" name="delete">Удалить</button>
                    </form>
                <?php endif; ?>
            </td>
        </tr>
    <?php endwhile; ?>
</table>
<script src="../assets/script.js"></script>

</body>
</html>
