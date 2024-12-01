<?php
session_start();
require_once '../includes/db.php';
require_once 'company_crypt.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php"); // Если пользователь не авторизован, перенаправляем на страницу входа
    exit;
}

require_once '../includes/checkUserExists.php';
$userId = $_SESSION['user_id'];
$conn = getDbConnection();
$theme = isset($_COOKIE['theme']) ? $_COOKIE['theme'] : 'light';
$role = $_SESSION['role_id'];

// Проверяем, существует ли пользователь
if (!checkUserExists($conn, $userId) || $role != 3) {
    // Удаляем данные сессии
    session_unset();
    session_destroy();
    header("Location: login.php"); // Перенаправляем на страницу входа
    exit;
}
// Проверяем, что это компания
$stmt = $conn->prepare("SELECT role_id FROM Users WHERE id = ?");
$stmt->bind_param("i", $userId);
$stmt->execute();
$userRole = $stmt->get_result()->fetch_assoc()['role_id'];

if ($userRole !== 3) { // Если это не компания, перенаправляем на главную
    header("Location: index.php");
    exit;
}

// Получаем вакансии компании
$stmt = $conn->prepare("SELECT id, VacancyTag FROM vacancy WHERE id_company = ?");
$stmt->bind_param("i", $userId);
$stmt->execute();
$vacancies = $stmt->get_result();

?>

<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <link id="themeStylesheet" rel="stylesheet" href="../assets/styles/<?php echo $theme; ?>.css">
    <link id="SubthemeStylesheet" rel="stylesheet" href="../assets/styles/company_vacancies/cv_<?php echo $theme; ?>.css">
    <title>Наши вакансии</title>
    <script>
        function filterVacancies() {
            const input = document.getElementById('searchInput');
            const filter = input.value.toLowerCase();
            const table = document.getElementById("vacanciesTable");
            const tr = table.getElementsByTagName("tr");

            for (let i = 1; i < tr.length; i++) {
                const td = tr[i].getElementsByTagName("td")[0]; // Первый столбец с названием вакансии
                if (td) {
                    const vacancyValue = td.textContent || td.innerText;
                    if (vacancyValue.toLowerCase().indexOf(filter) > -1) {
                        tr[i].style.display = "";
                    } else {
                        tr[i].style.display = "none";
                    }
                }
            }
        }
        // Функция для смены темы и сохранения выбора в куки
        function toggleTheme() {
            let currentTheme = document.body.classList.toggle('dark') ? 'dark' : 'light';
            document.cookie = `theme=${currentTheme}; path=/; max-age=31536000`; // Кука на 1 год
            document.getElementById('themeStylesheet').href = `../assets/styles/${currentTheme}.css`;
            document.getElementById('SubthemeStylesheet').href = `../assets/styles/company_vacancies/cv_${currentTheme}.css`;
        }

        // Применение темы при загрузке страницы
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
    <button onclick="window.location.href='index.php'">На главную</button>
    <button onclick="window.location.href='profile.php?id=<?php echo $_SESSION['user_id']; ?>'">
        <?php echo htmlspecialchars($_SESSION['username']); ?>
    </button>
</header>

<div class="vacancies_container">
    <h1>Наши вакансии</h1>
    <input type="text" id="searchInput" placeholder="Поиск по названию вакансии" onkeyup="filterVacancies()">

    <table id="vacanciesTable">
        <tr>
            <th>Вакансия</th>
            <th>Действия</th>
        </tr>
        <?php while ($vacancy = $vacancies->fetch_assoc()): ?>
            <tr>
                <td><?php echo htmlspecialchars($vacancy['VacancyTag']); ?></td>
                <td>
                    <a href="company_edit_vacancy.php?id=<?php echo $vacancy['id']; ?>">Редактировать</a>
                    <a href="company_delete_vacancy.php?id=<?php echo $vacancy['id']; ?>">Удалить</a>
                </td>
            </tr>
        <?php endwhile; ?>
    </table>
</div>

</body>
</html>
