<?php
session_start();
require_once '../includes/db.php';

$conn = getDbConnection();
$errorMessage = ''; // Переменная для хранения сообщения об ошибке
$theme = isset($_COOKIE['theme']) ? $_COOKIE['theme'] : 'light';
$autoFillEmail = ''; // Переменная для автозаполнения email из токена

// Проверка наличия токена в куки и его актуальности
if (isset($_COOKIE['remember_token'])) {
    $token = $_COOKIE['remember_token'];

    // Получаем пользователя по токену из базы данных
    $stmt = $conn->prepare("SELECT id, name, email, role_id, token_expiry FROM Users WHERE remember_token = ?");
    $stmt->bind_param("s", $token);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows > 0) {
        $user = $result->fetch_assoc();

        // Проверяем, не истек ли срок действия токена
        if (time() < $user['token_expiry']) {
            // Токен действителен, авторизуем пользователя
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['username'] = $user['name'];
            $_SESSION['email'] = $user['email'];  // Сохраняем email в сессии
            $_SESSION['role_id'] = $user['role_id'];

            // Подставляем email в форму для автозаполнения
            $autoFillEmail = $user['email']; // Используем для автозаполнения поля
        } else {
            // Токен истек, удаляем куки
            setcookie('remember_token', '', time() - 3600, '/', '', true, true);
        }
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = $_POST['email'];
    $password = $_POST['password'];
    $rememberMe = isset($_POST['remember_me']); // Проверка на чекбокс

    // Запрос для проверки email и пароля
    $stmt = $conn->prepare("SELECT id, name, password, role_id FROM Users WHERE email = ?");
    $stmt->bind_param("s", $email);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows > 0) {
        $user = $result->fetch_assoc();
        if (password_verify($password, $user['password'])) {
            // Авторизация успешна
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['username'] = $user['name'];
            $_SESSION['role_id'] = $user['role_id'];

            // Если выбран чекбокс "Запомнить меня", сохраняем куки
            if ($rememberMe) {
                // Генерация уникального токена
                $rememberToken = bin2hex(random_bytes(16));
                $tokenExpiry = time() + (86400 * 30); // Токен действует 30 дней

                // Обновление токена в базе данных
                $updateStmt = $conn->prepare("UPDATE Users SET remember_token = ?, token_expiry = ? WHERE email = ?");
                $updateStmt->bind_param("sis", $rememberToken, $tokenExpiry, $email);
                $updateStmt->execute();

                // Устанавливаем токен в куки
                setcookie('remember_token', $rememberToken, $tokenExpiry, "/", "", true, true); // Безопасные куки
            } else {
                // Убираем куки, если чекбокс не выбран
                setcookie('remember_token', '', time() - 3600, '/', '', true, true);
            }

            header("Location: index.php");
            exit;
        } else {
            // Неверный пароль
            $errorMessage = 'Неверный логин или пароль. Пожалуйста, попробуйте еще раз.';
        }
    } else {
        // Неверный email
        $errorMessage = 'Неверный логин или пароль. Пожалуйста, попробуйте еще раз.';
    }
}
?>

<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <link id="themeStylesheet" rel="stylesheet" href="../assets/styles/<?php echo $theme; ?>.css">
    <link id="SubthemeStylesheet" rel="stylesheet" href="../assets/styles/login/login_<?php echo $theme; ?>.css">
    <title>Вход</title>
    <script>
        // Функция для смены темы и сохранения выбора в куки
        function toggleTheme() {
            let currentTheme = document.body.classList.toggle('dark') ? 'dark' : 'light';
            document.cookie = `theme=${currentTheme}; path=/; max-age=31536000`; // Кука на 1 год
            document.getElementById('themeStylesheet').href = `../assets/styles/${currentTheme}.css`;
            document.getElementById('SubthemeStylesheet').href = `../assets/styles/login/login_${currentTheme}.css`;
        }

        // Применение темы при загрузке страницы
        document.addEventListener("DOMContentLoaded", function() {
            const theme = "<?php echo $theme; ?>";
            document.body.classList.toggle('dark', theme === 'dark');

            // Автозаполнение формы, если email сохранен в куки или токен авторизации действителен
            const savedEmail = "<?php echo isset($autoFillEmail) ? $autoFillEmail : ''; ?>";
            if (savedEmail) {
                document.querySelector('input[name="email"]').value = savedEmail;
            }
        });
    </script>
</head>
<body>
<header>
    <button onclick="toggleTheme()">Сменить тему</button>
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

        <!-- Чекбокс "Запомнить меня" -->
        <div class="remember-me">
            <input type="checkbox" name="remember_me" id="remember_me" <?php echo isset($_COOKIE['remember_token']) ? 'checked' : ''; ?>>
            <label for="remember_me">Запомнить меня</label>
        </div>

        <button type="submit">Войти</button>
    </form>

    <!-- Сообщение для регистрации -->
    <div class="register-message">
        Нет аккаунта? <a href="register.php">Зарегистрируйтесь</a>
    </div>
</div>
</body>
</html>
