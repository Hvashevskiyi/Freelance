<?php

session_start();
require_once '../includes/db.php';

$conn = getDbConnection();
$theme = isset($_COOKIE['theme']) ? $_COOKIE['theme'] : 'light';
require_once '../includes/checkUserExists.php';

$userId = $_SESSION['user_id'];
$role = $_SESSION['role_id'];

// Проверяем, что пользователь существует и является администратором
if (!checkUserExists($conn, $userId) || $role != 1) {
    session_unset();
    session_destroy();
    header("Location: login.php");
    exit;
}

$startTime = microtime(true);
$startMemory = memory_get_usage();

//$stmt = $conn->prepare("
//    SELECT a.id AS application_id,
//           a.created_at,
//           a.vacancy_id,
//           a.rating,
//           (SELECT VacancyTag FROM vacancy WHERE vacancy.id = a.vacancy_id) AS VacancyTag,
//           (SELECT u.id FROM Users u WHERE u.id = a.freelancer_id) AS freelancer_id,
//           (SELECT u.name FROM Users u WHERE u.id = a.freelancer_id) AS freelancer_name,
//           (SELECT  u.average_rating  FROM Users u WHERE u.id = a.freelancer_id) AS freelancer_rating,
//           (SELECT u.num_w_s  FROM Users u WHERE u.id = a.freelancer_id) AS Ws,
//           (SELECT u.num_w_c  FROM Users u WHERE u.id = a.freelancer_id) AS Wc,
//           (SELECT u.num_w_u  FROM Users u WHERE u.id = a.freelancer_id) AS Wu,
//           (SELECT u.denom  FROM Users u WHERE u.id = a.freelancer_id) AS denominator,
//           (SELECT s.status FROM application_status s WHERE s.application_id = a.id) AS status,
//           (SELECT s.viewed FROM application_status s WHERE s.application_id = a.id) AS viewed,
//           (SELECT s.completed FROM application_status s WHERE s.application_id = a.id) AS completed,
//           a.cover_letter,
//           a.file_path
//    FROM applications a
//");


$stmt = $conn->prepare("
    SELECT a.id AS application_id,
           a.created_at,
           a.vacancy_id,
           a.rating,
           v.VacancyTag,
           u.id AS freelancer_id,
           u.name AS freelancer_name,
           u.average_rating AS freelancer_rating,
           u.num_w_s AS Ws,
           u.num_w_c AS Wc,
           u.num_w_u AS Wu,
           u.denom AS denominator,
           s.status,
           s.viewed,
           s.completed,
           a.cover_letter,
           a.file_path
    FROM applications a
    JOIN vacancy v ON a.vacancy_id = v.id
    JOIN Users u ON a.freelancer_id = u.id
    LEFT JOIN application_status s ON a.id = s.application_id
");
$stmt->execute();
$applications = $stmt->get_result();

// End measuring time
$endTime = microtime(true);
$endMemory = memory_get_usage();

// Calculate the time difference
$executionTime = $endTime - $startTime;
$memoryUsage = $endMemory - $startMemory;
// Pass the execution time to JavaScript
echo "<script>console.log('Query executed in " . $executionTime . " seconds');</script>";
echo "<script>console.log('Memory usage: " . $memoryUsage . " bytes');</script>";

?>

<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <link id="themeStylesheet" rel="stylesheet" href="../assets/styles/<?php echo $theme; ?>.css">
    <title>Управление откликами</title>
    <script>

        // Функция для смены темы и сохранения выбора в куки
        function toggleTheme() {
            let currentTheme = document.body.classList.toggle('dark') ? 'dark' : 'light';
            document.cookie = `theme=${currentTheme}; path=/; max-age=31536000`; // Кука на 1 год
            document.getElementById('themeStylesheet').href = `../assets/styles/${currentTheme}.css`;
        }

        // Применение темы при загрузке страницы
        document.addEventListener("DOMContentLoaded", function() {
            const theme = "<?php echo $theme; ?>";
            document.body.classList.toggle('dark', theme === 'dark');
        });

        function filterApplications() {
            const input = document.getElementById('searchInput');
            const filter = input.value.trim().toLowerCase();
            const table = document.getElementById("applicationsTable");
            const tr = table.getElementsByTagName("tr");

            for (let i = 1; i < tr.length; i++) {
                const tdFreelancer = tr[i].getElementsByTagName("td")[0];
                const tdVacancy = tr[i].getElementsByTagName("td")[1];
                const tdStatus = tr[i].getElementsByTagName("td")[3];
                const tdViewed = tr[i].getElementsByTagName("td")[4];
                if (tdFreelancer || tdVacancy || tdStatus || tdViewed) {
                    const freelancerValue = tdFreelancer.textContent.trim() || tdFreelancer.innerText.trim();
                    const vacancyValue = tdVacancy.textContent.trim() || tdVacancy.innerText.trim();
                    const statusValue = tdStatus.textContent.trim() || tdStatus.innerText.trim();
                    const viewedValue = tdViewed.textContent.trim() || tdViewed.innerText.trim();
                    if (
                        freelancerValue.toLowerCase().indexOf(filter) > -1 ||
                        vacancyValue.toLowerCase().indexOf(filter) > -1 ||
                        statusValue.toLowerCase().indexOf(filter) > -1 ||
                        viewedValue.toLowerCase().indexOf(filter) > -1
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
    <button onclick="window.location.href='admin_vacancies.php'">Управление вакансиями</button>
    <button onclick="window.location.href='admin_stats.php'">Статистика</button>
    <button onclick="window.location.href='admin_weights.php'">Рейтинг</button>
    <button onclick="window.location.href='admin_freelance.php'">Фрилансеры</button>

    <button onclick="window.location.href='index.php'">На главную</button>
</header>

<div class="container">
    <h1>История откликов</h1>
    <input type="text" id="searchInput" placeholder="Поиск по фрилансеру, вакансии, статусу или просмотру" onkeyup="filterApplications()">

    <table id="applicationsTable">
        <tr>
            <th>Фрилансер</th>
            <th>Вакансия</th>
            <th>Дата отклика</th>
            <th>Оценка</th>
            <th>Статус</th>
            <th>Просмотр</th>
            <th>Завершено</th>
            <th>Рейтинг</th>
            <th>Ws</th>
            <th>Wc</th>
            <th>Wu</th>
            <th>Denom</th>
            <!-- Новый столбец для рейтинга -->
            <th>Сопроводительное письмо</th> <!-- Новый столбец для сопроводительного письма -->

        </tr>
        <?php while ($application = $applications->fetch_assoc()): ?>
            <tr>
                <td><a href="profile.php?id=<?php echo $application['freelancer_id']; ?>"><?php echo htmlspecialchars($application['freelancer_name']); ?></a></td>
                <td><a href="vacancy.php?id=<?php echo $application['vacancy_id']; ?>"><?php echo htmlspecialchars($application['VacancyTag']); ?></a></td>
                <td><?php echo htmlspecialchars($application['created_at']); ?></td>
                <td><?php echo htmlspecialchars($application['rating'] ?? '-' ); ?></td>
                <td><?php echo htmlspecialchars($application['status'] ?? 'Не определен'); ?></td>
                <td><?php echo htmlspecialchars($application['viewed'] ?? 'Не просмотрено'); ?></td>
                <td><?php echo htmlspecialchars($application['completed'] ?? 'В процессе'); ?></td>
                <td><?php echo htmlspecialchars($application['freelancer_rating'] ?? 'Нет рейтинга'); ?></td> <!-- Рейтинг фрилансера -->
                <td><?php echo htmlspecialchars($application['Ws'] ?? 'Нет данных'); ?></td> <!-- Рейтинг фрилансера -->
                <td><?php echo htmlspecialchars($application['Wc'] ?? 'Нет данных'); ?></td> <!-- Рейтинг фрилансера -->
                <td><?php echo htmlspecialchars($application['Wu'] ?? 'Нет данных'); ?></td> <!-- Рейтинг фрилансера -->
                <td><?php echo htmlspecialchars($application['denominator'] ?? 'Нет данных'); ?></td> <!-- Рейтинг фрилансера -->
                <td>
                    <a href="view_cover_letter.php?id=<?php echo $application['application_id']; ?>">Посмотреть</a>
                </td>

            </tr>
        <?php endwhile; ?>
    </table>

</div>

<script>

</script>
</body>
</html>