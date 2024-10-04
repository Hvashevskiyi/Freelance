<?php
session_start();
require_once '../includes/db.php';

$conn = getDbConnection();
$errorMessage = ''; // Переменная для хранения сообщения об ошибке

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = $_POST['email'];
    $password = $_POST['password'];

    $stmt = $conn->prepare("SELECT id, name, password FROM Users WHERE email = ?");
    $stmt->bind_param("s", $email);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows > 0) {
        $user = $result->fetch_assoc();
        if (password_verify($password, $user['password'])) {
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['username'] = $user['name'];
            header("Location: index.php");
            exit;
        } else {
            // Неверный пароль
            $errorMessage = 'Неверный пароль. Пожалуйста, попробуйте еще раз.';
        }
    } else {
        // Неверный email
        $errorMessage = 'Пользователь с таким email не найден.';
    }
}
?>

<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <link rel="stylesheet" href="../assets/styles/base.css">
    <link rel="stylesheet" href="../assets/styles/login.css">
    <title>Вход</title>
</head>
<body>
<header>
    <button onclick="window.location.href='index.php'">На главную</button>
</header>
<div class="login_container">
    <h1>Вход</h1>

    <!-- Вывод сообщения об ошибке -->
    <?php if ($errorMessage): ?>
        <div class="error-message"><?php echo htmlspecialchars($errorMessage); ?></div>
    <?php endif; ?>

    <form method="POST" action="login.php">
        <input type="email" name="email" placeholder="Email" required>
        <input type="password" name="password" placeholder="Пароль" required>
        <button type="submit">Войти</button>
    </form>

    <!-- Сообщение для регистрации -->
    <div class="register-message">
        Нет аккаунта? <a href="register.php">Зарегистрируйтесь</a>
    </div>
</div>
</body>
</html>
