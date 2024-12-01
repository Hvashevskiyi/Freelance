<?php
session_start();
require_once '../includes/db.php';
$conn = getDbConnection();
$theme = isset($_COOKIE['theme']) ? $_COOKIE['theme'] : 'light';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = trim($_POST['name']);
    $email = trim($_POST['email']);
    $password = trim($_POST['password']);
    $confirm_password = trim($_POST['confirm_password']);
    $text = trim($_POST['text']);  // Информация о себе
    $role = intval($_POST['role']); // Получаем выбранную роль

    // Проверяем, является ли вакансия обязательной для роли "фрилансер"
    if ($role === 2) {
        $vacancy = trim($_POST['vacancy']);  // Вакансия должна быть заполнена для фрилансера
    } else {
        $vacancy = '';  // Очищаем поле вакансии для компании
    }

    // Проверяем, что обязательные поля не пустые после trim
    if (empty($name) || empty($email) || empty($password) || empty($confirm_password) || empty($text)) {
        $error = "Все поля должны быть заполнены!";
    } else {
        // Проверяем совпадение паролей
        if ($password !== $confirm_password) {
            $error = "Пароли не совпадают!";
        } else {
            // Проверка уникальности email
            $stmt = $conn->prepare("SELECT id FROM Users WHERE email = ?");
            $stmt->bind_param("s", $email);
            $stmt->execute();
            $result = $stmt->get_result();

            if ($result->num_rows > 0) {
                $error = "Пользователь с такой почтой уже существует!";
            } else {
                // Хэшируем пароль и сохраняем нового пользователя
                $hashed_password = password_hash($password, PASSWORD_DEFAULT);
                // Сохраняем нового пользователя в базу данных
                $stmt = $conn->prepare("INSERT INTO Users (name, email, password, text, vacancy, role_id, image_id) VALUES (?, ?, ?, ?, ?, ?, ?)");
                $image_id = 1; // Значение по умолчанию для image_id
                $stmt->bind_param("sssssii", $name, $email, $hashed_password, $text, $vacancy, $role, $image_id);
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
    <link id="themeStylesheet" rel="stylesheet" href="../assets/styles/<?php echo $theme; ?>.css">
    <link id="SubthemeStylesheet" rel="stylesheet" href="../assets/styles/register/register_<?php echo $theme; ?>.css">

    <title>Регистрация</title>
    <script>
        // Функция для смены темы и сохранения выбора в куки
        function toggleTheme() {
            let currentTheme = document.body.classList.toggle('dark') ? 'dark' : 'light';
            document.cookie = `theme=${currentTheme}; path=/; max-age=31536000`; // Кука на 1 год
            document.getElementById('themeStylesheet').href = `../assets/styles/${currentTheme}.css`;
            document.getElementById('SubthemeStylesheet').href = `../assets/styles/register/register_${currentTheme}.css`;
        }

        // Применение темы при загрузке страницы
        document.addEventListener("DOMContentLoaded", function() {
            const theme = "<?php echo $theme; ?>";
            document.body.classList.toggle('dark', theme === 'dark');
        });

        function toggleVacancyField() {
            const roleSelect = document.getElementById('role');
            const vacancyInput = document.getElementById('vacancyInput');

            // Если выбрана компания (роль = 3), скрываем поле вакансии
            if (roleSelect.value == 3) {
                vacancyInput.style.display = 'none';
                vacancyInput.removeAttribute('required'); // Убираем обязательное заполнение для компании
                vacancyInput.value = ''; // Очищаем поле, чтобы оно не отправлялось
            } else {
                vacancyInput.style.display = 'block';
                vacancyInput.setAttribute('required', 'required'); // Поле обязательно для фрилансеров
            }
        }

        // При загрузке страницы вызываем проверку для корректной видимости полей
        window.onload = toggleVacancyField;
    </script>
</head>
<body>
<header>
    <button onclick="toggleTheme()">Сменить тему</button>
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

        <!-- Выпадающий список для выбора роли -->
        <select name="role" id="role" onchange="toggleVacancyField()" required>
            <option value="2">Фрилансер</option>
            <option value="3">Компания</option>
        </select>

        <!-- Поле вакансии -->
        <input type="text" name="vacancy" id="vacancyInput" placeholder="Ваша вакансия" required>
        <button type="submit">Зарегистрироваться</button>
    </form>

</div>
</body>
</html>
