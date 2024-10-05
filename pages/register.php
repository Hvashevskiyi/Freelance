<?php
session_start();
require_once '../includes/db.php';
$conn = getDbConnection();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = trim($_POST['name']);
    $email = trim($_POST['email']);
    $password = trim($_POST['password']);
    $confirm_password = trim($_POST['confirm_password']);
    $text = trim($_POST['text']);  // Информация о себе
    $vacancy = trim($_POST['vacancy']);  // Вакансия

    // Проверяем, что обязательные поля не пустые после trim
    if (empty($name) || empty($email) || empty($password) || empty($confirm_password) || empty($text) || empty($vacancy)) {
        $error = "Все поля должны быть заполнены!";
    } else {
        if ($password !== $confirm_password) {
            $error = "Пароли не совпадают!";
        } else {
            // Проверка, что email уникален
            $stmt = $conn->prepare("SELECT id FROM Users WHERE email = ?");
            $stmt->bind_param("s", $email);
            $stmt->execute();
            $result = $stmt->get_result();

            if ($result->num_rows > 0) {
                $error = "Пользователь с такой почтой уже существует!";
            } else {
                // Хэшируем пароль и сохраняем нового пользователя
                $hashed_password = password_hash($password, PASSWORD_DEFAULT);
                $stmt = $conn->prepare("INSERT INTO Users (name, email, password, text, vacancy, image_id) VALUES (?, ?, ?, ?, ?, ?)");
                $stmt->bind_param("ssssi", $name, $email, $hashed_password, $text, $vacancy, 1);
                $stmt->execute();


                // Автоматическая авторизация после регистрации
                $_SESSION['user_id'] = $stmt->insert_id;
                $_SESSION['username'] = $name;

                header("Location: index.php");
                exit;
            }
        }
    }
}
?>

<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <link rel="stylesheet" href="../assets/styles/register.css">
    <script src="../assets/script.js"></script>
    <title>Регистрация</title>
</head>
<body>
<header>
    <button class="button_to_main" onclick="window.location.href='index.php'">На главную</button>
</header>
<div class="register_container">
    <h1>Регистрация</h1>

    <?php if (isset($error)): ?>
        <p style="color:red;"><?php echo $error; ?></p>
    <?php endif; ?>

    <form id="registerForm" method="POST">
        <input type="text" name="name" placeholder="Имя" required>
        <input type="email" name="email" id="email" placeholder="Уникальная почта" required>
        <span id="emailError" style="color:red; display:none;">Этот email уже занят!</span>
        <input type="password" name="password" id="password" placeholder="Пароль" required>
        <input type="password" name="confirm_password" id="confirm_password" placeholder="Подтвердите пароль" required>
        <span id="passwordError" style="color:red; display:none;">Пароли не совпадают!</span>
        <span id="password8" style="color:red; display:none;">Пароль минимум 8 символов</span>
        <textarea name="text" placeholder="Расскажите о себе" required></textarea>
        <input type="text" name="vacancy" placeholder="Ваша вакансия" required>
        <button type="submit">Зарегистрироваться</button>
    </form>

</div>
</body>
</html>
