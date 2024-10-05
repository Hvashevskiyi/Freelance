<?php
session_start();
require_once '../includes/db.php';

$conn = getDbConnection(); // Подключение к базе данных

// Получаем все вакансии вместе с изображениями пользователей
$vacancies = $conn->query("
    SELECT v.id, v.VacancyTag, v.Salary, v.id_company, u.name AS author_name, u.image_id
    FROM vacancy v 
    JOIN users u ON v.id_company = u.id
");
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
            <?php echo htmlspecialchars($_SESSION['username']); ?>
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
                <th>ID вакансии</th>
                <th>Имя вакансии</th>
                <th>Зарплата</th>
                <th>Автор</th>
                <th>Фото</th> <!-- Новый столбец для фото -->
                <th>Действия</th>
            </tr>
            <?php while ($vacancy = $vacancies->fetch_assoc()): ?>
                <tr>
                    <td><?php echo htmlspecialchars($vacancy['id']); ?></td> <!-- Вывод ID вакансии -->
                    <td>
                        <a href="vacancy.php?id=<?php echo $vacancy['id']; ?>" class="vacancy-link">
                            <?php echo htmlspecialchars($vacancy['VacancyTag']); ?>
                        </a>
                    </td>
                    <td><?php echo htmlspecialchars($vacancy['Salary']); ?></td>
                    <td><?php echo htmlspecialchars($vacancy['author_name']); ?></td>
                    <td>
                        <?php
                        // Получаем изображение пользователя по image_id
                        $imageId = $vacancy['image_id'];
                        ?>
                        <img src="get_image.php?id=<?php echo $imageId; ?>" alt="User Image" style="width:50px; height:50px; border-radius:50%;">
                    </td>
                    <td>
                        <?php if (isset($_SESSION['user_id'])): ?>
                            <button class="delete-vacancy" onclick="deleteVacancy(<?php echo $vacancy['id']; ?>)">Удалить</button>
                        <?php endif; ?>
                    </td>
                </tr>
            <?php endwhile; ?>
        </table>
    </div>
    <!-- Поле для удаления вакансии по ID -->
    <h2>Удалить вакансию по ID</h2>
    <input type="number" id="deleteVacancyId" placeholder="ID вакансии" required>
    <button onclick="deleteVacancyById()">Удалить вакансию</button>
    <div id="deleteError" style="color: red;"></div>
</div>
<script>
    function deleteVacancyById() {
        const vacancyId = document.getElementById('deleteVacancyId').value;

        if (!vacancyId) {
            alert('Пожалуйста, введите ID вакансии.');
            return;
        }

        fetch('deleteVacancy.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json'
            },
            body: JSON.stringify({ vacancy_id: vacancyId })
        })
            .then(response => response.json())
            .then(data => {
                const deleteErrorDiv = document.getElementById('deleteError');
                if (data.success) {
                    // Успешное удаление, перезагружаем страницу
                    location.reload();
                } else {
                    // Ошибка удаления
                    deleteErrorDiv.innerText = data.error;
                }
            })
            .catch((error) => {
                console.error('Ошибка:', error);
                document.getElementById('deleteError').innerText = 'Произошла ошибка при удалении вакансии.';
            });
    }
</script>
</body>
</html>
