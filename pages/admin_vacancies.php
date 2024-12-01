<?php
session_start();
require_once '../includes/db.php';

$conn = getDbConnection();
$theme = isset($_COOKIE['theme']) ? $_COOKIE['theme'] : 'light';
require_once '../includes/checkUserExists.php';
$userId = $_SESSION['user_id'];
$role = $_SESSION['role_id'];

// Проверяем, существует ли пользователь
if (!checkUserExists($conn, $userId) || $role != 1) {
    // Удаляем данные сессии
    session_unset();
    session_destroy();
    header("Location: login.php"); // Перенаправляем на страницу входа
    exit;
}
$stmt = $conn->prepare("SELECT v.id, v.VacancyTag,v.Salary, u.name AS company_name FROM vacancy v JOIN Users u ON v.id_company = u.id");
$stmt->execute();


$vacancies = $stmt->get_result();
?>

<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <link id="themeStylesheet" rel="stylesheet" href="../assets/styles/<?php echo $theme; ?>.css">
    <link id="SubthemeStylesheet" rel="stylesheet" href="../assets/styles/admin_vacancies/av_<?php echo $theme; ?>.css">

    <title>Управление вакансиями</title>
    <script>
        // Функция для смены темы и сохранения выбора в куки
        function toggleTheme() {
            let currentTheme = document.body.classList.toggle('dark') ? 'dark' : 'light';
            document.cookie = `theme=${currentTheme}; path=/; max-age=31536000`; // Кука на 1 год
            document.getElementById('themeStylesheet').href = `../assets/styles/${currentTheme}.css`;
            document.getElementById('SubthemeStylesheet').href = `../assets/styles/admin_vacancies/av_${currentTheme}.css`;
        }

        // Применение темы при загрузке страницы
        document.addEventListener("DOMContentLoaded", function() {
            const theme = "<?php echo $theme; ?>";
            document.body.classList.toggle('dark', theme === 'dark');
        });


        function filterVacancies() {
            const input = document.getElementById('searchInput');
            const filter = input.value.trim().toLowerCase(); // Удаляем пробелы в начале и в конце
            const table = document.getElementById("vacanciesTable");
            const tr = table.getElementsByTagName("tr");

            for (let i = 1; i < tr.length; i++) { // Начинаем с 1, чтобы пропустить заголовок
                const tdId = tr[i].getElementsByTagName("td")[0];
                const tdName = tr[i].getElementsByTagName("td")[1];
                const tdCompany = tr[i].getElementsByTagName("td")[2];

                if (tdId || tdName || tdCompany) {
                    const idValue = tdId.textContent.trim() || tdId.innerText.trim(); // Удаляем пробелы
                    const nameValue = tdName.textContent.trim() || tdName.innerText.trim(); // Удаляем пробелы
                    const companyValue = tdCompany.textContent.trim() || tdCompany.innerText.trim(); // Удаляем пробелы
                    if (
                        idValue.toLowerCase().indexOf(filter) > -1 ||
                        nameValue.toLowerCase().indexOf(filter) > -1 ||
                        companyValue.toLowerCase().indexOf(filter) > -1
                    ) {
                        tr[i].style.display = "";
                    } else {
                        tr[i].style.display = "none";
                    }
                }
            }
        }
    </script>
</head>
<body>
<header>
    <button onclick="toggleTheme()">Сменить тему</button>
    <button onclick="window.location.href='admin_users.php'">Управление пользователями</button>
    <button onclick="window.location.href='admin_stats.php'">Статистика</button>
    <button onclick="window.location.href='admin_applications.php'">История откликов</button>
    <button onclick="window.location.href='admin_weights.php'">Рейтинг</button>
    <button onclick="window.location.href='admin_freelance.php'">Фрилансеры</button>

    <button onclick="window.location.href='index.php'">На главную</button>
</header>


<div class="container">
    <h1>Управление вакансиями</h1>
    <input type="text" id="searchInput" placeholder="Поиск по ID, названию вакансии или компании" onkeyup="filterVacancies()">

    <table id="vacanciesTable">
        <tr>
            <th>ID</th>
            <th>Название вакансии</th>
            <th>Компания</th>
            <th>Зарплата</th>
            <th>Действия</th>
        </tr>
        <?php while ($vacancy = $vacancies->fetch_assoc()): ?>
            <tr>
                <td><?php echo htmlspecialchars($vacancy['id']); ?></td>
                <td><?php echo htmlspecialchars($vacancy['VacancyTag']); ?></td>
                <td><?php echo htmlspecialchars($vacancy['company_name']); ?></td>
                <td><?php echo htmlspecialchars($vacancy['Salary']); ?></td>

                <td>
                    <a href="admin_edit_vacancy.php?id=<?php echo $vacancy['id']; ?>">Редактировать</a>
                    <a href="admin_delete_vacancy.php?id=<?php echo $vacancy['id']; ?>">Удалить</a>
                </td>
            </tr>
        <?php endwhile; ?>
    </table>
</div>
</body>
</html>
