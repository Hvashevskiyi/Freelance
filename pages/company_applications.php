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

$role = $_SESSION['role_id'];

// Проверяем, существует ли пользователь
if (!checkUserExists($conn, $userId) || $role != 3) {
    // Удаляем данные сессии
    session_unset();
    session_destroy();
    header("Location: login.php"); // Перенаправляем на страницу входа
    exit;
}
// Проверяем, существует ли пользователь
if (!checkUserExists($conn, $userId)) {
    // Удаляем данные сессии
    session_unset();
    session_destroy();
    header("Location: login.php"); // Перенаправляем на страницу входа
    exit;
}
// Получаем ID компании
$stmt = $conn->prepare("SELECT role_id FROM Users WHERE id = ?");
$stmt->bind_param("i", $userId);
$stmt->execute();
$userRole = $stmt->get_result()->fetch_assoc()['role_id'];

if ($userRole !== 3) { // Проверяем, что это компания
    header("Location: index.php"); // Если это не компания, перенаправляем на главную
    exit;
}

// Получаем отклики фрилансеров на вакансии данной компании
$stmt = $conn->prepare("
    SELECT a.id, a.cover_letter, v.VacancyTag, v.id AS vacancy_id, f.name AS freelancer_name, v.Salary
    FROM applications a
    JOIN vacancy v ON a.vacancy_id = v.id
    JOIN Users f ON a.freelancer_id = f.id
    WHERE v.id_company = ?
");
$stmt->bind_param("i", $userId);
$stmt->execute();
$applications = $stmt->get_result();
?>

<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <link rel="stylesheet" href="../assets/styles/base.css">
    <link rel="stylesheet" href="../assets/styles/company_applications.css"> <!-- Новый стиль для страницы откликов компании -->
    <title>Отклики на вакансии</title>
    <script>
        function filterApplications() {
            const input = document.getElementById('searchInput');
            const filter = input.value.trim().toLowerCase(); // Удаляем пробелы в начале и в конце
            const table = document.getElementById("applicationsTable");
            const tr = table.getElementsByTagName("tr");

            for (let i = 1; i < tr.length; i++) {
                const tdVacancy = tr[i].getElementsByTagName("td")[0];
                const tdFreelancer = tr[i].getElementsByTagName("td")[1];
                if (tdVacancy || tdFreelancer) {
                    const vacancyValue = tdVacancy.textContent.trim() || tdVacancy.innerText.trim(); // Удаляем пробелы
                    const freelancerValue = tdFreelancer.textContent.trim() || tdFreelancer.innerText.trim(); // Удаляем пробелы
                    if (vacancyValue.toLowerCase().indexOf(filter) > -1 || freelancerValue.toLowerCase().indexOf(filter) > -1) {
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
    <button onclick="window.location.href='index.php'">На главную</button>
    <button onclick="window.location.href='profile.php?id=<?php echo $_SESSION['user_id']; ?>'">
        <?php echo htmlspecialchars($_SESSION['username']); ?>
    </button>
</header>

<div class="applications_container">
    <h1>Отклики на вакансии</h1>
    <input type="text" id="searchInput" placeholder="Поиск по вакансии или фрилансеру" onkeyup="filterApplications()">

    <table id="applicationsTable">
        <tr>
            <th>Вакансия</th>
            <th>Фрилансер</th>
            <th>Зарплата</th>
            <th>Действия</th>
        </tr>
        <?php while ($application = $applications->fetch_assoc()): ?>
            <tr>
                <td>
                    <a href="vacancy.php?id=<?php echo $application['vacancy_id']; ?>">
                        <?php echo htmlspecialchars($application['VacancyTag']); ?>
                    </a>
                </td>
                <td><?php echo htmlspecialchars($application['freelancer_name']); ?></td>
                <td><?php echo htmlspecialchars($application['Salary']); ?> ₽</td>
                <td>
                    <a href="view_cover_letter.php?id=<?php echo $application['id']; ?>">Посмотреть</a>
                </td>
            </tr>
        <?php endwhile; ?>

    </table>
</div>

</body>
</html>
