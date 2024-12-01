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

$stmt = $conn->prepare("SELECT id, name, role_id FROM Users");
$stmt->execute();


$users = $stmt->get_result();
?>

<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <link id="themeStylesheet" rel="stylesheet" href="../assets/styles/<?php echo $theme; ?>.css">
    <link id="SubthemeStylesheet" rel="stylesheet" href="../assets/styles/admin_users/au_<?php echo $theme; ?>.css">

    <title>Управление пользователями</title>
    <script>
        // Функция для смены темы и сохранения выбора в куки
        function toggleTheme() {
            let currentTheme = document.body.classList.toggle('dark') ? 'dark' : 'light';
            document.cookie = `theme=${currentTheme}; path=/; max-age=31536000`; // Кука на 1 год
            document.getElementById('themeStylesheet').href = `../assets/styles/${currentTheme}.css`;
            document.getElementById('SubthemeStylesheet').href = `../assets/styles/admin_users/au_${currentTheme}.css`;
        }

        // Применение темы при загрузке страницы
        document.addEventListener("DOMContentLoaded", function() {
            const theme = "<?php echo $theme; ?>";
            document.body.classList.toggle('dark', theme === 'dark');
        });

        function filterUsers() {
            const input = document.getElementById('searchInput');
            const filter = input.value.trim().toLowerCase(); // Удаляем пробелы в начале и в конце
            const table = document.getElementById("usersTable");
            const tr = table.getElementsByTagName("tr");

            for (let i = 1; i < tr.length; i++) {
                const tdId = tr[i].getElementsByTagName("td")[0];
                const tdName = tr[i].getElementsByTagName("td")[1];
                const tdRole = tr[i].getElementsByTagName("td")[2];

                if (tdId || tdName || tdRole) {
                    const idValue = tdId.textContent.trim() || tdId.innerText.trim(); // Удаляем пробелы
                    const nameValue = tdName.textContent.trim() || tdName.innerText.trim(); // Удаляем пробелы
                    const roleValue = tdRole.textContent.trim() || tdRole.innerText.trim(); // Удаляем пробелы
                    if (
                        idValue.toLowerCase().indexOf(filter) > -1 ||
                        nameValue.toLowerCase().indexOf(filter) > -1 ||
                        roleValue.toLowerCase().indexOf(filter) > -1
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
    <button onclick="window.location.href='admin_vacancies.php'">Управление вакансиями</button>
    <button onclick="window.location.href='admin_stats.php'">Статистика</button>
    <button onclick="window.location.href='admin_applications.php'">История откликов</button>
    <button onclick="window.location.href='admin_weights.php'">Рейтинг</button>
    <button onclick="window.location.href='admin_freelance.php'">Фрилансеры</button>
    <button onclick="window.location.href='index.php'">На главную</button>
</header>

<div class="container">
    <h1>Управление пользователями</h1>
    <input type="text" id="searchInput" placeholder="Поиск по ID, имени или роли" onkeyup="filterUsers()">
    <table id="usersTable">
        <tr>
            <th>ID</th>
            <th>Имя</th>
            <th>Роль</th>
            <th>Действия</th>
        </tr>
        <?php while ($user = $users->fetch_assoc()): ?>
            <tr>
                <td><?php echo htmlspecialchars($user['id']); ?></td>
                <td><?php echo htmlspecialchars($user['name']); ?></td>
                <td><?php echo htmlspecialchars($user['role_id'] == 2 ? 'Фрилансер' : ($user['role_id'] == 3 ? 'Компания' : 'Администратор')); ?></td>
                <td>
                    <?php if ($user['role_id'] !== 1): // Если это не админ ?>
                        <a href="admin_edit_user.php?id=<?php echo $user['id']; ?>">Редактировать</a>
                        <a href="admin_delete_user.php?id=<?php echo $user['id']; ?>">Удалить</a>
                    <?php endif; ?>
                </td>
            </tr>
        <?php endwhile; ?>
    </table>
</div>
</body>
</html>