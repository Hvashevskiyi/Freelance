<?php
session_start();
require_once '../includes/db.php';

// Проверяем, авторизован ли пользователь
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $vacancyTag = isset($_POST['vacancyTag']) ? $_POST['vacancyTag'] : '';
    $description = isset($_POST['description']) ? $_POST['description'] : '';
    $salary = isset($_POST['salary']) ? $_POST['salary'] : 0;

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
    <form method="POST" action="">
        <label for="vacancyTag">Имя вакансии:</label>
        <input type="text" name="vacancyTag" id="vacancyTag" required>

        <label for="description">Описание:</label>
        <textarea name="description" id="description" required></textarea>

        <label for="salary">Зарплата:</label>
        <input type="number" name="salary" id="salary" required>

        <button type="submit">Разместить</button>
    </form>
</div>

</body>
</html>
