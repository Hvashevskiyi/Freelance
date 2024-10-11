<?php
session_start();
require_once '../includes/db.php';

require_once '../includes/checkUserExists.php';
//$userId = $_SESSION['user_id'];
$conn = getDbConnection();
// Проверяем, существует ли пользователь
//if (!checkUserExists($conn, $userId)) {
//    // Удаляем данные сессии
//    session_unset();
//    session_destroy();
//    header("Location: login.php"); // Перенаправляем на страницу входа
//    exit;
//}

// Получаем все вакансии, чтобы отображать их, если пользователь не авторизован
$vacancies = $conn->query("
    SELECT v.id, v.VacancyTag, v.Salary, v.id_company, u.name AS author_name, u.image_id
    FROM vacancy v 
    JOIN users u ON v.id_company = u.id
");

// Проверяем, авторизован ли пользователь
if (isset($_SESSION['user_id'])) {
    // Получаем роль пользователя
    $userId = $_SESSION['user_id'];
    $stmt = $conn->prepare("SELECT role_id FROM Users WHERE id = ?");
    $stmt->bind_param("i", $userId);
    $stmt->execute();
    $userRole = $stmt->get_result()->fetch_assoc()['role_id'];

    if ($userRole == 3) { // Если пользователь - компания
        // Получаем список фрилансеров
        $freelancers = $conn->query("
            SELECT u.id, u.name, u.vacancy, u.image_id
            FROM Users u
            WHERE u.role_id = 2
        ");
    }
}
?>

<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <link rel="stylesheet" href="../assets/styles/base.css">
    <link rel="stylesheet" href="../assets/styles/index.css">
    <script src="../assets/search.js"></script>
    <title>Фриланс Платформа</title>
</head>
<body>
<header>
    <?php if (isset($_SESSION['user_id'])): ?>
        <button onclick="window.location.href='profile.php?id=<?php echo $_SESSION['user_id']; ?>'">
            <?php echo htmlspecialchars($_SESSION['username']); ?>
        </button>
        <?php if ($userRole == 1): ?> <!-- Проверяем, если пользователь фрилансер -->
            <button onclick="window.location.href='admin_users.php'">Управление пользователями</button>
            <button onclick="window.location.href='admin_vacancies.php'">Управление вакансиями</button>

            <button onclick="window.location.href='admin_stats.php'">Статистика</button>
        <?php endif; ?>
        <?php if ($userRole == 2): ?> <!-- Проверяем, если пользователь фрилансер -->
            <button onclick="window.location.href='applications.php'">Мои отклики</button> <!-- Кнопка для перехода на страницу откликов -->
        <?php endif; ?>
        <?php if ($userRole == 3): // Если компания ?>
            <button onclick="window.location.href='company_vacancies.php'">Наши вакансии</button>
            <button onclick="window.location.href='company_applications.php'">Отклики</button>
            <button onclick="window.location.href='post_vacancy.php'">Разместить вакансию</button>
        <?php endif; ?>
        <button onclick="window.location.href='logout.php'">Выйти</button>
    <?php else: ?>
        <button onclick="window.location.href='login.php'">Войти</button>
    <?php endif; ?>
</header>

<div class="table_container">
    <?php if (isset($freelancers)): ?>
        <h1>Список фрилансеров</h1>
        <input type="text" id="searchInput" placeholder="Поиск по имени или вакансии" onkeyup="filterFreelancers()">

        <div class="table_wrapper">
            <table id="freelancersTable">
                <tr>
                    <th>Фото</th>
                    <th>Имя</th>
                    <th>Вакансия</th>
                </tr>
                <?php while ($freelancer = $freelancers->fetch_assoc()): ?>
                    <tr>
                        <td>
                            <img src="get_image.php?id=<?php echo $freelancer['image_id']; ?>" alt="User Image" style="width:50px; height:50px; border-radius:50%;">
                        </td>
                        <td>
                            <a href="profile.php?id=<?php echo $freelancer['id']; ?>" class="freelancer-link">
                                <?php echo htmlspecialchars($freelancer['name']); ?>
                            </a>
                        </td>
                        <td><?php echo htmlspecialchars($freelancer['vacancy']); ?></td>
                    </tr>
                <?php endwhile; ?>
            </table>
        </div>
    <?php else: ?>
        <h1>Список вакансий</h1>
        <input type="text" id="searchInput" placeholder="Поиск по имени вакансии, зарплате или автору" onkeyup="filterVacancies()">

        <div class="table_wrapper">
            <table id="vacanciesTable">
                <tr>
                    <th>Должность</th>
                    <th>Автор</th>
                    <th>Фото</th>
                    <th>Зарплата</th>


                </tr>
                <?php while ($vacancy = $vacancies->fetch_assoc()): ?>
                    <tr>
                        <td>
                            <a href="vacancy.php?id=<?php echo $vacancy['id']; ?>" class="vacancy-link">
                                <?php echo htmlspecialchars($vacancy['VacancyTag']); ?>
                            </a>
                        </td>

                        <td>
                            <a href="profile.php?id=<?php echo $vacancy['id_company']; ?>" class="vacancy-link">
                            <?php echo htmlspecialchars($vacancy['author_name']); ?>
                            </a>
                        </td>

                        <td>
                            <img src="get_image.php?id=<?php echo $vacancy['image_id']; ?>" alt="User Image" style="width:50px; height:50px; border-radius:50%;">
                        </td>
                        <td><?php echo htmlspecialchars($vacancy['Salary']); ?></td>
                    </tr>
                <?php endwhile; ?>
            </table>
        </div>
    <?php endif; ?>
</div>

<script>
    // Функция фильтрации фрилансеров
    function filterFreelancers() {
        const input = document.getElementById('searchInput');
        const filter = input.value.toLowerCase();
        const table = document.getElementById("freelancersTable");
        const tr = table.getElementsByTagName("tr");

        for (let i = 1; i < tr.length; i++) {
            const tdName = tr[i].getElementsByTagName("td")[1];
            const tdVacancy = tr[i].getElementsByTagName("td")[2];
            if (tdName || tdVacancy) {
                const nameValue = tdName.textContent || tdName.innerText;
                const vacancyValue = tdVacancy.textContent || tdVacancy.innerText;
                if (nameValue.toLowerCase().indexOf(filter) > -1 || vacancyValue.toLowerCase().indexOf(filter) > -1) {
                    tr[i].style.display = "";
                } else {
                    tr[i].style.display = "none";
                }
            }
        }
    }

    // Функция фильтрации вакансий
    function filterVacancies() {
        const input = document.getElementById('searchInput');
        const filter = input.value.toLowerCase();
        const table = document.getElementById("vacanciesTable");
        const tr = table.getElementsByTagName("tr");

        for (let i = 1; i < tr.length; i++) {
            const tdVacancy = tr[i].getElementsByTagName("td")[0];
            const tdSalary = tr[i].getElementsByTagName("td")[1];
            const tdAuthor = tr[i].getElementsByTagName("td")[2];
            if (tdVacancy || tdSalary || tdAuthor) {
                const vacancyValue = tdVacancy.textContent || tdVacancy.innerText;
                const salaryValue = tdSalary.textContent || tdSalary.innerText;
                const authorValue = tdAuthor.textContent || tdAuthor.innerText;
                if (vacancyValue.toLowerCase().indexOf(filter) > -1 || salaryValue.toLowerCase().indexOf(filter) > -1 || authorValue.toLowerCase().indexOf(filter) > -1) {
                    tr[i].style.display = "";
                } else {
                    tr[i].style.display = "none";
                }
            }
        }
    }
</script>
</body>
</html>
