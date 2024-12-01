<?php
session_start();
require_once '../includes/db.php';
$conn = getDbConnection();

$theme = isset($_COOKIE['theme']) ? $_COOKIE['theme'] : 'light';

// Проверяем, авторизован ли пользователь
if (isset($_SESSION['user_id'])) {
    // Получаем роль пользователя
    $userId = $_SESSION['user_id'];
    $stmt = $conn->prepare("SELECT role_id FROM Users WHERE id = ?");
    $stmt->bind_param("i", $userId);
    $stmt->execute();
    $userRole = $stmt->get_result()->fetch_assoc()['role_id'];

    if ($userRole == 1) { // Если пользователь - компания
        // Получаем список фрилансеров с их рейтингами
        $freelancers = $conn->query("
            SELECT u.id, u.name, u.vacancy, u.image_id, u.average_rating
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
    <link id="themeStylesheet" rel="stylesheet" href="../assets/styles/<?php echo $theme; ?>.css">
    <link id="SubthemeStylesheet" rel="stylesheet" href="../assets/styles/index/index_<?php echo $theme; ?>.css">
    <script src="../assets/js/search.js"></script>
    <title>Фриланс Платформа</title>
    <script>
        // Функция для смены темы и сохранения выбора в куки
        function toggleTheme() {
            let currentTheme = document.body.classList.toggle('dark') ? 'dark' : 'light';
            document.cookie = `theme=${currentTheme}; path=/; max-age=31536000`; // Кука на 1 год
            document.getElementById('themeStylesheet').href = `../assets/styles/${currentTheme}.css`;
            document.getElementById('SubthemeStylesheet').href = `../assets/styles/index/index_${currentTheme}.css`;
        }

        // Применение темы при загрузке страницы
        document.addEventListener("DOMContentLoaded", function() {
            const theme = "<?php echo $theme; ?>";
            document.body.classList.toggle('dark', theme === 'dark');
        });
    </script>
    <script>
        // Функция для проверки актуальности изображения
        function checkImageUpdate(imageId, elementId) {
            const imageUrl = `get_image.php?id=${imageId}`; // URL изображения
            const lastUpdated = getCookie("image_updated_at"); // Получаем время из куки

            // Если время в куке существует, проверяем его с актуальной датой
            if (lastUpdated) {
                const imageElement = document.getElementById(elementId); // Элемент изображения
                imageElement.src = `${imageUrl}&cacheBust=${Date.now()}`; // Добавляем временную метку, чтобы избежать использования кэша
            }
        }

        // Вспомогательная функция для получения значения куки по имени
        function getCookie(name) {
            const value = `; ${document.cookie}`;
            const parts = value.split(`; ${name}=`);
            if (parts.length === 2) return parts.pop().split(';').shift();
            return null;
        }

        // Проверка актуальности изображений при загрузке страницы
        document.addEventListener("DOMContentLoaded", function() {
            // Для изображений фрилансеров
            const freelancerImages = document.querySelectorAll('.freelancer-image');
            freelancerImages.forEach(image => {
                const imageId = image.getAttribute('data-id'); // Получаем id изображения
                checkImageUpdate(imageId, image.id); // Проверяем актуальность
            });

        });
    </script>

</head>
<body>
<header>
    <button onclick="toggleTheme()">Сменить тему</button>
    <?php if (isset($_SESSION['user_id'])): ?>
        <button onclick="window.location.href='profile.php?id=<?php echo $_SESSION['user_id']; ?>'">
            <?php echo htmlspecialchars($_SESSION['username']); ?>
        </button>
        <?php if ($userRole == 1): ?> <!-- Проверяем, если пользователь фрилансер -->
            <button onclick="window.location.href='admin_users.php'">Управление пользователями</button>
            <button onclick="window.location.href='admin_vacancies.php'">Управление вакансиями</button>
            <button onclick="window.location.href='admin_stats.php'">Статистика</button>
            <button onclick="window.location.href='admin_applications.php'">История откликов</button>
            <button onclick="window.location.href='admin_weights.php'">Рейтинг</button>
            <button onclick="window.location.href='index.php'">Компании</button>

        <?php endif; ?>

        <button onclick="window.location.href='logout.php'">Выйти</button>
    <?php else: ?>
        <button onclick="window.location.href='login.php'">Войти</button>
    <?php endif; ?>
</header>

<div class="table_container">

        <h1>Список фрилансеров</h1>


        <div class="sorting-container">
            <input type="text" id="searchFreelancers" placeholder="Поиск по имени или вакансии" onkeyup="filterFreelancers()">

            <div>
                <select id="sortFreelancersField" onchange="sortFreelancers()">
                    <option value="default">Сортировать по</option>
                    <option value="name">Имя</option>
                    <option value="vacancy">Вакансии</option>
                    <option value="rating">Рейтинг</option>
                </select>
                <select id="sortFreelancersOrder" onchange="sortFreelancers()">
                    <option value="asc">По возрастанию</option>
                    <option value="desc">По убыванию</option>
                </select>
            </div>
        </div>


        <div class="table_wrapper">
            <table id="freelancersTable">
                <tr>
                    <th>Фото</th>
                    <th>Имя</th>
                    <th>Вакансия</th>
                    <th>Рейтинг</th>
                </tr>
                <?php while ($freelancer = $freelancers->fetch_assoc()): ?>
                    <tr>
                        <td>
                            <img
                                id="freelancer-image-<?php echo $freelancer['id']; ?>"
                                class="freelancer-image"
                                data-id="<?php echo $freelancer['image_id']; ?>"
                                src="get_image.php?id=<?php echo $freelancer['image_id']; ?>"
                                alt="User Image"

                                style="width:50px;
                                height:50px;
                                border-radius:50%;
                                object-fit: cover;">
                        </td>
                        <td>
                            <a href="profile.php?id=<?php echo $freelancer['id']; ?>" class="freelancer-link">
                                <?php echo htmlspecialchars($freelancer['name']); ?>
                            </a>
                        </td>
                        <td><?php echo htmlspecialchars($freelancer['vacancy']); ?></td>
                        <td><?php echo number_format((float)$freelancer['average_rating'], 2, '.', ''); ?></td>
                    </tr>
                <?php endwhile; ?>
            </table>
        </div>

</div>

<!-- Модальное окно -->
<div id="modal">
    <div class="modal-content">
        <div class="modal-header">
            <h2>Информация об отклике</h2>
        </div>
        <p id="modalContent"></p>
        <div class="modal-footer">
            <button id="okButton">ОК</button>
        </div>
    </div>
</div>

<script>
    // Функция фильтрации фрилансеров
    function filterFreelancers() {
        const input = document.getElementById('searchFreelancers');
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

    // Функция сортировки фрилансеров
    function sortFreelancers() {
        const table = document.getElementById('freelancersTable');
        const rows = Array.from(table.rows).slice(1); // Игнорируем заголовок
        const sortField = document.getElementById('sortFreelancersField').value;
        const sortOrder = document.getElementById('sortFreelancersOrder').value;

        if (sortField === 'name') {
            rows.sort((a, b) => sortOrder === 'asc'
                ? a.cells[1].innerText.localeCompare(b.cells[1].innerText)
                : b.cells[1].innerText.localeCompare(a.cells[1].innerText));
        } else if (sortField === 'vacancy') {
            rows.sort((a, b) => sortOrder === 'asc'
                ? a.cells[2].innerText.localeCompare(b.cells[2].innerText)
                : b.cells[2].innerText.localeCompare(a.cells[2].innerText));
        } else if (sortField === 'rating') {
            rows.sort((a, b) => sortOrder === 'asc'
                ? parseFloat(a.cells[3].innerText) - parseFloat(b.cells[3].innerText)
                : parseFloat(b.cells[3].innerText) - parseFloat(a.cells[3].innerText));
        }

        // Перемещаем отсортированные строки обратно в таблицу
        rows.forEach(row => table.appendChild(row));
    }

    // Функция фильтрации вакансий
    function filterVacancies() {
        const input = document.getElementById('searchVacancies');
        const filter = input.value.toLowerCase();
        const table = document.getElementById("vacanciesTable");
        const tr = table.getElementsByTagName("tr");

        for (let i = 1; i < tr.length; i++) {
            const tdVacancy = tr[i].getElementsByTagName("td")[0];
            const tdAuthor = tr[i].getElementsByTagName("td")[1];
            const tdSalary = tr[i].getElementsByTagName("td")[3];
            if (tdVacancy || tdAuthor || tdSalary) {
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

    // Функция сортировки вакансий
    function sortVacancies() {
        const table = document.getElementById('vacanciesTable');
        const rows = Array.from(table.rows).slice(1); // Игнорируем заголовок
        const sortField = document.getElementById('sortVacanciesField').value;
        const sortOrder = document.getElementById('sortVacanciesOrder').value;

        if (sortField === 'position') {
            rows.sort((a, b) => sortOrder === 'asc'
                ? a.cells[0].innerText.localeCompare(b.cells[0].innerText)
                : b.cells[0].innerText.localeCompare(a.cells[0].innerText));
        } else if (sortField === 'author') {
            rows.sort((a, b) => sortOrder === 'asc'
                ? a.cells[1].innerText.localeCompare(b.cells[1].innerText)
                : b.cells[1].innerText.localeCompare(a.cells[1].innerText));
        } else if (sortField === 'salary') {
            rows.sort((a, b) => sortOrder === 'asc'
                ? parseFloat(a.cells[3].innerText) - parseFloat(b.cells[3].innerText)
                : parseFloat(b.cells[3].innerText) - parseFloat(a.cells[3].innerText));
        }

        // Перемещаем отсортированные строки обратно в таблицу
        rows.forEach(row => table.appendChild(row));
    }

    // Отображение модального окна для откликов
    function showModal(applicationId, vacancyTag, status) {
        const modal = document.getElementById('modal');
        const modalContent = document.getElementById('modalContent');

        // Обновление текста модального окна с использованием статуса
        modalContent.textContent = `Ваша отклик на вакансию "${vacancyTag}" имеет статус: "${status}". Нажмите ОК, чтобы закрыть это сообщение.`;

        modal.style.display = 'block';

        document.getElementById('okButton').onclick = function() {
            // Отправка AJAX-запроса для обновления статуса
            fetch('update_application_status.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                },
                body: `application_id=${applicationId}`
            })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        modal.style.display = 'none';
                        // Дополнительные действия, если нужно
                    } else {
                        alert("Ошибка при обновлении статуса");
                    }
                })
                .catch(error => {
                    console.error('Ошибка:', error);
                    alert("Ошибка при обновлении статуса");
                });
        };
    }

    // Проверка и отображение модального окна для откликов
    <?php if (isset($applications) && $applications->num_rows > 0): ?>
    const applications = <?php echo json_encode($applications->fetch_all(MYSQLI_ASSOC)); ?>;
    applications.forEach(application => {
        showModal(application.application_id, application.VacancyTag, application.status);
    });
    <?php endif; ?>
</script>
</body>
</html>
