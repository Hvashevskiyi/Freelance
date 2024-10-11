<?php
session_start();
require_once '../includes/db.php';

// Проверяем, авторизован ли пользователь
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}
require_once '../includes/checkUserExists.php';
$userId = $_SESSION['user_id'];
$conn = getDbConnection();

$role = $_SESSION['role_id'];

// Проверяем, существует ли пользователь
if (!checkUserExists($conn, $userId) || $role != 3) {
    // Удаляем данные сессии
    session_unset();
    session_destroy();
    header("Location: login.php"); // Перенаправляем на страницу входа
    exit;
}
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $vacancyTag = trim(isset($_POST['vacancyTag']) ? $_POST['vacancyTag'] : '');
    $description = trim(isset($_POST['description']) ? $_POST['description'] : '');
    $salary = isset($_POST['salary']) ? $_POST['salary'] : 0;

    // Проверяем, чтобы зарплата была положительным числом
    if ($salary < 0) {
        $error = "Зарплата не может быть отрицательной.";
    } elseif (empty($vacancyTag) || empty($description)) {
        $error = "Имя вакансии и описание не могут быть пустыми!";
    } else {
        // Подключение к базе данных
        $conn = getDbConnection();
        $id_company = $_SESSION['user_id'];

        // Подготовка и выполнение запроса на добавление вакансии
        $stmt = $conn->prepare("INSERT INTO vacancy (VacancyTag, Description, Salary, id_company) VALUES (?, ?, ?, ?)");
        $stmt->bind_param("ssdi", $vacancyTag, $description, $salary, $id_company);

        if ($stmt->execute()) {
            echo "<script> window.location.href='index.php';</script>";
            exit();
        } else {
            $error = "Ошибка: " . $stmt->error;
        }
    }
}
?>

<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <link rel="stylesheet" href="../assets/styles/base.css">
    <link rel="stylesheet" href="../assets/styles/index.css">
    <link rel="stylesheet" href="../assets/styles/post_vacancy.css"> <!-- Подключаем новый стиль -->
    <title>Разместить вакансию</title>
</head>
<body>
<header>
    <button class="button_to_main" onclick="window.location.href='index.php'">На главную</button>

    <?php if (isset($_SESSION['user_id'])): ?>
        <button onclick="window.location.href='profile.php?id=<?php echo $_SESSION['user_id']; ?>'">
            <?php echo $_SESSION['username']; ?>
        </button>
        <button onclick="window.location.href='logout.php'">Выйти</button>
    <?php else: ?>
        <button onclick="window.location.href='login.php'">Войти</button>
    <?php endif; ?>
</header>

<div class="form_container">
    <h1>Разместить вакансию</h1>
    <?php if (isset($error)): ?>
        <div style="color: red;"><?php echo htmlspecialchars($error); ?></div>
    <?php endif; ?>
    <form method="POST" action="" onsubmit="return validateForm()">
        <label for="vacancyTag">Имя вакансии:</label>
        <input type="text" name="vacancyTag" id="vacancyTag" required>

        <label for="description">Описание:</label>
        <textarea name="description" id="description" required></textarea>

        <label for="salary">Зарплата:</label>
        <input type="number" name="salary" id="salary" min="0" required>

        <button type="submit">Разместить</button>
    </form>
</div>

<script>
    function validateForm() {
        const salary = document.getElementById('salary').value;
        if (salary < 0) {
            alert('Зарплата не может быть отрицательной.');
            return false; // Остановить отправку формы
        }
        return true; // Если все ок, форма отправляется
    }
</script>

</body>
</html>
