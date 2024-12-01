<?php
session_start();
require_once '../includes/db.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php"); // Если пользователь не авторизован, перенаправляем на страницу входа
    exit;
}

$userId = $_SESSION['user_id'];
$conn = getDbConnection();
require_once '../includes/checkUserExists.php';
$theme = isset($_COOKIE['theme']) ? $_COOKIE['theme'] : 'light';
$role = $_SESSION['role_id'];

// Проверяем, существует ли пользователь
if (!checkUserExists($conn, $userId)|| $role != 2) {
    // Удаляем данные сессии
    session_unset();
    session_destroy();
    header("Location: login.php"); // Перенаправляем на страницу входа
    exit;
}

// Получаем отклики фрилансера с названием компании
$stmt = $conn->prepare("
    SELECT a.id, a.cover_letter, v.VacancyTag, v.id AS vacancy_id, u.name AS company_name
    FROM applications a
    JOIN vacancy v ON a.vacancy_id = v.id
    JOIN users u ON v.id_company = u.id
    WHERE a.freelancer_id = ?
");
$stmt->bind_param("i", $userId);
$stmt->execute();
$applications = $stmt->get_result();
?>

<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <link id="themeStylesheet" rel="stylesheet" href="../assets/styles/<?php echo $theme; ?>.css">
    <link id="SubthemeStylesheet" rel="stylesheet" href="../assets/styles/applications/applications_<?php echo $theme; ?>.css">
    <title>Мои отклики</title>
    <script>
        // Функция для смены темы и сохранения выбора в куки
        function toggleTheme() {
            let currentTheme = document.body.classList.toggle('dark') ? 'dark' : 'light';
            document.cookie = `theme=${currentTheme}; path=/; max-age=31536000`; // Кука на 1 год
            document.getElementById('themeStylesheet').href = `../assets/styles/${currentTheme}.css`;
            document.getElementById('SubthemeStylesheet').href = `../assets/styles/applications/applications_${currentTheme}.css`;
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
    <button onclick="toggleTheme()">Сменить тему</button>
    <button onclick="window.location.href='index.php'">На главную</button>
    <button onclick="window.location.href='profile.php?id=<?php echo $_SESSION['user_id']; ?>'">
        <?php echo htmlspecialchars($_SESSION['username']); ?>
    </button>
</header>

<div class="applications_container">
    <h1>Мои отклики</h1>

    <!-- Поле для динамического поиска -->
    <input type="text" id="searchInput" placeholder="Поиск по вакансии и компании" onkeyup="filterApplications()">

    <table id="applicationsTable">
        <tr>
            <th>Вакансия</th>
            <th>Компания</th> <!-- Добавлено название компании -->
            <th>Сопроводительное письмо</th>
            <th>Действия</th>
        </tr>
        <?php while ($application = $applications->fetch_assoc()): ?>
            <tr>
                <td>
                    <a href="vacancy.php?id=<?php echo $application['vacancy_id']; ?>">
                        <?php echo htmlspecialchars($application['VacancyTag']); ?>
                    </a>
                </td>
                <td><?php echo htmlspecialchars($application['company_name']); ?></td> <!-- Отображаем название компании -->
                <td><?php echo htmlspecialchars($application['cover_letter']); ?></td>
                <td>
                    <a href="edit_application.php?id=<?php echo $application['id']; ?>">Редактировать</a>
                    <a href="delete_application.php?id=<?php echo $application['id']; ?>">Удалить</a>

                </td>
            </tr>
        <?php endwhile; ?>
    </table>
</div>

<script>
    // Функция фильтрации откликов
    function filterApplications() {
        const input = document.getElementById('searchInput');
        const filter = input.value.toLowerCase();
        const table = document.getElementById("applicationsTable");
        const tr = table.getElementsByTagName("tr");

        for (let i = 1; i < tr.length; i++) {
            const tdVacancy = tr[i].getElementsByTagName("td")[0]; // Вакансия
            const tdCompany = tr[i].getElementsByTagName("td")[1]; // Компания
            if (tdVacancy || tdCompany) {
                const vacancyValue = tdVacancy.textContent || tdVacancy.innerText;
                const companyValue = tdCompany.textContent || tdCompany.innerText;
                if (vacancyValue.toLowerCase().indexOf(filter) > -1 || companyValue.toLowerCase().indexOf(filter) > -1) {
                    tr[i].style.display = ""; // Показать строку
                } else {
                    tr[i].style.display = "none"; // Скрыть строку
                }
            }
        }
    }
</script>

</body>
</html>
