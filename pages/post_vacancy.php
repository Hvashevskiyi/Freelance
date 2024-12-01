<?php
session_start();
require_once '../includes/db.php';
require_once 'company_crypt.php';
$theme = isset($_COOKIE['theme']) ? $_COOKIE['theme'] : 'light';

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}
require_once '../includes/checkUserExists.php';
$userId = $_SESSION['user_id'];
$conn = getDbConnection();
$role = $_SESSION['role_id'];

if (!checkUserExists($conn, $userId) || $role != 3) {
    session_unset();
    session_destroy();
    header("Location: login.php");
    exit();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $vacancyTag = trim(isset($_POST['vacancyTag']) ? $_POST['vacancyTag'] : '');
    $description = trim(isset($_POST['description']) ? $_POST['description'] : '');
    $salary = isset($_POST['salary']) ? $_POST['salary'] : 0;

    if ($salary < 0) {
        $error = "Зарплата не может быть отрицательной.";
    } elseif (empty($vacancyTag) || empty($description)) {
        $error = "Имя вакансии и описание не могут быть пустыми!";
    } else {
        $id_company = $_SESSION['user_id'];

        // Начинаем транзакцию
        $conn->begin_transaction();

        try {
            // Пытаемся вставить новую вакансию с использованием ON DUPLICATE KEY UPDATE
            $stmt = $conn->prepare("
                INSERT INTO vacancy (VacancyTag, Description, Salary, id_company)
                VALUES (?, ?, ?, ?)
                ON DUPLICATE KEY UPDATE VacancyTag = VALUES(VacancyTag)");  // Просто обновляем, чтобы избежать дубликата

            $stmt->bind_param("ssdi", $vacancyTag, $description, $salary, $id_company);
            $stmt->execute();

            // Если запись не была добавлена (affected_rows == 0), то дублирование произошло
            if ($stmt->affected_rows == 0) {
                // Записываем ошибку в таблицу error_log
                $error_message = "ID_".$id_company." Вакансия ".$vacancyTag." с описанием ".$description." и зп равной ".$salary." уже существует.";
                $error_stmt = $conn->prepare("INSERT INTO error_log (error_message) VALUES (?)");
                $error_stmt->bind_param("s", $error_message);
                $error_stmt->execute();
                $error = "Ошибка: Вакансия с такими данными уже существует!";
            } else {
                // Вставка прошла успешно
                $conn->commit();  // Фиксируем изменения
                echo "<script> window.location.href='index.php';</script>";
                exit();
            }
        } catch (Exception $e) {
            $conn->rollback();  // В случае ошибки откатываем транзакцию
            $error = "Ошибка при добавлении вакансии: " . $e->getMessage();
        }
    }
}
?>

<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <link id="themeStylesheet" rel="stylesheet" href="../assets/styles/<?php echo $theme; ?>.css">
    <link id="SubthemeStylesheet" rel="stylesheet" href="../assets/styles/index/index_<?php echo $theme; ?>.css">
    <link id="pvStylesheet" rel="stylesheet" href="../assets/styles/post_vacancy/pv_<?php echo $theme; ?>.css">
    <title>Разместить вакансию</title>
    <script>
        function toggleTheme() {
            let currentTheme = document.body.classList.toggle('dark') ? 'dark' : 'light';
            document.cookie = `theme=${currentTheme}; path=/; max-age=31536000`;
            document.getElementById('themeStylesheet').href = `../assets/styles/${currentTheme}.css`;
            document.getElementById('SubthemeStylesheet').href = `../assets/styles/index/index_${currentTheme}.css`;
            document.getElementById('pvStylesheet').href = `../assets/styles/post_vacancy/pv_${currentTheme}.css`;
        }

        document.addEventListener("DOMContentLoaded", function() {
            const theme = "<?php echo $theme; ?>";
            document.body.classList.toggle('dark', theme === 'dark');
        });
    </script>
</head>
<body>
<header>
    <?php displayuserHistory($userId); ?>
    <button onclick="toggleTheme()">Сменить тему</button>
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
            return false;
        }
        return true;
    }
</script>

</body>
</html>
