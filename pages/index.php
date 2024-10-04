<?php
session_start();
require_once '../includes/db.php';

$conn = getDbConnection();
$vacancies = $conn->query("SELECT v.id, v.VacancyTag, v.Salary, v.id_company, u.name AS author_name FROM vacancy v JOIN users u ON v.id_company = u.id");
?>

<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <link rel="stylesheet" href="../assets/styles/base.css">
    <link rel="stylesheet" href="../assets/styles/index.css">
    <script src="../assets/search.js"></script>
    <script src="../assets/delete.js"></script>
    <title>Фриланс Платформа</title>
</head>
<body>
<header>
    <?php if (isset($_SESSION['user_id'])): ?>
        <button onclick="window.location.href='profile.php?id=<?php echo $_SESSION['user_id']; ?>'">
            <?php echo $_SESSION['username']; ?>
        </button>
        <button onclick="window.location.href='post_vacancy.php'">Разместить вакансию</button>
        <button onclick="window.location.href='logout.php'">Выйти</button>
    <?php else: ?>
        <button onclick="window.location.href='login.php'">Войти</button>
    <?php endif; ?>
</header>

<div class="table_container">
    <h1>Список вакансий</h1>
    <input type="text" id="searchInput" placeholder="Поиск по имени вакансии, зарплате или автору" onkeyup="filterVacancies()">

    <div class="table_wrapper">
        <table id="vacanciesTable">
            <tr>
                <th>Имя вакансии</th>
                <th>Зарплата</th>
                <th>Автор</th>
                <th>Действия</th>
            </tr>
            <?php while ($vacancy = $vacancies->fetch_assoc()): ?>
                <tr>
                    <td><a href="vacancy.php?id=<?php echo $vacancy['id']; ?>" class="vacancy-link"><?php echo htmlspecialchars($vacancy['VacancyTag']); ?></a></td>
                    <td><?php echo htmlspecialchars($vacancy['Salary']); ?></td>
                    <td><?php echo htmlspecialchars($vacancy['author_name']); ?></td>
                    <td>
                        <?php if (isset($_SESSION['user_id']) && $vacancy['id_company'] == $_SESSION['user_id']): ?>
                            <button onclick="deleteVacancy(<?php echo $vacancy['id']; ?>)">Удалить</button>
                        <?php else: ?>
                            <!-- Если пользователь не автор, ничего не отображаем -->
                        <?php endif; ?>
                    </td>
                </tr>
            <?php endwhile; ?>
        </table>
    </div>
</div>

</body>
</html>