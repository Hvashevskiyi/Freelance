<?php
session_start();
require_once '../includes/db.php';
require_once 'company_crypt.php';
$theme = isset($_COOKIE['theme']) ? $_COOKIE['theme'] : 'light';
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

// Получаем ID компании
$stmt = $conn->prepare("SELECT role_id FROM Users WHERE id = ?");
$stmt->bind_param("i", $userId);
$stmt->execute();
$userRole = $stmt->get_result()->fetch_assoc()['role_id'];

if ($userRole !== 3) { // Проверяем, что это компания
    header("Location: index.php"); // Если это не компания, перенаправляем на главную
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['status'])) {
    $applicationId = intval($_POST['application_id']);
    $status = $_POST['status']; // Принято или Отклонено

    // Отладочная информация
    error_log("Updating status for application_id: $applicationId with status: $status");

    // Проверяем, существует ли заявка с данным ID
    $stmt = $conn->prepare("SELECT COUNT(*) as count FROM applications WHERE id = ?");
    $stmt->bind_param("i", $applicationId);
    $stmt->execute();
    $result = $stmt->get_result()->fetch_assoc();

    if ($result['count'] > 0) {
        // Сохраняем статус заявки, если она существует
        $stmt = $conn->prepare("INSERT INTO application_status (application_id, status) 
                                VALUES (?, ?) 
                                ON DUPLICATE KEY UPDATE status = ?");
        $stmt->bind_param("iss", $applicationId, $status, $status);
        $stmt->execute();
    }

    // После обновления, возможно, можно перезагрузить или перенаправить
    header("Location: " . $_SERVER['REQUEST_URI']);
    exit;
}

// Получаем отклики фрилансеров на вакансии данной компании
$stmt = $conn->prepare("
    SELECT a.id, a.cover_letter, v.VacancyTag, v.id AS vacancy_id, f.name AS freelancer_name, v.Salary, s.status, a.order_status
    FROM applications a
    JOIN vacancy v ON a.vacancy_id = v.id
    JOIN Users f ON a.freelancer_id = f.id
    LEFT JOIN application_status s ON a.id = s.application_id
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
    <link id="themeStylesheet" rel="stylesheet" href="../assets/styles/<?php echo $theme; ?>.css">
    <link id="SubthemeStylesheet" rel="stylesheet" href="../assets/styles/company_applications/ca_<?php echo $theme; ?>.css">
    <title>Отклики на вакансии</title>
    <script>
        function toggleTheme() {
            let currentTheme = document.body.classList.toggle('dark') ? 'dark' : 'light';
            document.cookie = `theme=${currentTheme}; path=/; max-age=31536000`; // Кука на 1 год
            document.getElementById('themeStylesheet').href = `../assets/styles/${currentTheme}.css`;
            document.getElementById('SubthemeStylesheet').href = `../assets/styles/company_applications/ca_${currentTheme}.css`;
        }

        // Применение темы при загрузке страницы
        document.addEventListener("DOMContentLoaded", function() {
            const theme = "<?php echo $theme; ?>";
            document.body.classList.toggle('dark', theme === 'dark');
        });
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

        // Функция для открытия модального окна
        function openRatingModal(applicationId) {
            document.getElementById('modalApplicationId').value = applicationId; // Устанавливаем ID заявки
            document.getElementById('ratingModal').style.display = 'block'; // Открываем модальное окно
        }

        function closeModal() {
            document.getElementById('ratingModal').style.display = 'none'; // Закрываем модальное окно
        }

        function submitRating() {
            const ratingInput = document.getElementById('rating');
            const ratingValue = parseFloat(ratingInput.value);

            // Проверяем, что значение рейтинга находится в пределах от 0 до 5
            if (isNaN(ratingValue) || ratingValue < 0 || ratingValue > 5) {
                alert("Пожалуйста, введите значение оценки в пределах от 0 до 5.");
                return false; // Останавливаем отправку формы
            }

            // Если значение корректное, отправляем форму
            document.getElementById('ratingForm').submit(); // Отправляем форму
        }

        // Закрытие модального окна при нажатии вне его области
        window.onclick = function(event) {
            const modal = document.getElementById('ratingModal');
            if (event.target == modal) {
                modal.style.display = "none"; // Закрываем модальное окно
            }
        }
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

<div class="applications_container">
    <h1>Отклики на вакансии</h1>
    <input type="text" id="searchInput" placeholder="Поиск по вакансии или фрилансеру" onkeyup="filterApplications()">

    <table id="applicationsTable">
        <tr>
            <th>Вакансия</th>
            <th>Фрилансер</th>
            <th>Зарплата</th>
            <th>Просмотр</th>
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
                <td>
                    <?php if ($application['order_status'] === 'Закрыт'): ?>
                        <span>Заказ закрыт</span>
                    <?php elseif ($application['status'] === 'Принято'): ?>
                        <button onclick="openRatingModal(<?php echo $application['id']; ?>)">Отчет о заказе</button> <!-- Кнопка для открытия модального окна -->
                    <?php elseif ($application['status'] === 'Отклонено'): ?>
                        <span><?php echo htmlspecialchars($application['status']); ?></span>
                    <?php else: ?>
                        <form method="POST" action="">
                            <input type="hidden" name="application_id" value="<?php echo $application['id']; ?>">
                            <button type="submit" name="status" value="Принято">Принять</button>
                            <button type="submit" name="status" value="Отклонено">Отклонить</button>
                        </form>
                    <?php endif; ?>
                </td>
            </tr>
        <?php endwhile; ?>
    </table>
</div>

<!-- Модальное окно -->
<div id="ratingModal" class="modal" style="display:none;">
    <div class="modal-content">
        <div class="modal-header">
            <h2>Оценка фрилансера</h2>
            <span class="close" onclick="closeModal()">&times;</span> <!-- Кнопка закрытия -->
        </div>
        <div class="modal-body">
            <form id="ratingForm" method="POST" action="process_rating.php"> <!-- Укажите правильный файл для обработки рейтинга -->
                <input type="hidden" name="application_id" id="modalApplicationId" value="">
                <label for="rating">Оценка (0-5):</label>
                <input type="number" name="rating" id="rating" min="0" max="5" step="0.1" required>

                <label for="completed">Статус выполнения:</label>
                <select name="completed" id="completed" required>
                    <option value="Выполнено">Выполнено</option>
                    <option value="Не выполнено">Не выполнено</option>
                </select>
            </form>
        </div>
        <div class="modal-footer">
            <button onclick="submitRating()">Отправить</button> <!-- Кнопка отправки формы -->
            <button onclick="closeModal()">Закрыть</button>
        </div>
    </div>
</div>

</body>
</html>
