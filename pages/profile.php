<?php
session_start();
require_once '../includes/db.php';
require_once 'freelance_crypt.php';
require_once 'company_crypt.php';

if (!isset($_GET['id'])) {
    header("Location: index.php");
    exit;
}

$conn = getDbConnection();


$userId = intval($_GET['id']);
$theme = isset($_COOKIE['theme']) ? $_COOKIE['theme'] : 'light';
// Получаем данные пользователя
$stmt = $conn->prepare("SELECT id, name, email, text, vacancy, image_id, role_id FROM Users WHERE id = ?");
$stmt->bind_param("i", $userId);
$stmt->execute();
$user = $stmt->get_result()->fetch_assoc();

if (!$user) {
    // Если пользователь не найден, перенаправляем на главную
    header("Location: index.php");
    exit;
}

// Инициализируем переменные для статистики фрилансера
$completedCount = 0;
$inProgressCount = 0;
$notCompletedCount = 0;
if (isset($_SESSION['user_id'])) {
    $sessionId = $_SESSION['user_id'];
    $stmt = $conn->prepare("SELECT role_id FROM Users WHERE id = ?");
    $stmt->bind_param("i", $sessionId);
    $stmt->execute();
    $SessionUserRole = $stmt->get_result()->fetch_assoc()['role_id'];
    if ($SessionUserRole === 3 && $user['role_id'] == 2){
        updateUserHistory($sessionId, $user['name'], $userId);
        displayUserHistory($sessionId);
    }
    if ($SessionUserRole == 2){
        displayHistory($SessionUserRole);
    }
}

if ($user['role_id'] == 2) { // Проверяем, если пользователь - фрилансер

    // Получаем статистику по заявкам фрилансера
    $stmt = $conn->prepare("
        SELECT 
            SUM(CASE WHEN s.completed = 1 THEN 1 ELSE 0 END) AS completed,
            SUM(CASE WHEN s.completed IS NULL OR s.completed = '' THEN 1 ELSE 0 END) AS in_progress,
            SUM(CASE WHEN s.completed = 0 THEN 1 ELSE 0 END) AS not_completed
        FROM applications a
        LEFT JOIN application_status s ON a.id = s.application_id
        WHERE a.freelancer_id = ? AND s.status = 'Принято'
    ");
    $stmt->bind_param("i", $userId);
    $stmt->execute();
    $statusCounts = $stmt->get_result()->fetch_assoc();

    // Устанавливаем значения для счетчиков
    $completedCount = $statusCounts['completed'] ?? 0;
    $inProgressCount = $statusCounts['in_progress'] ?? 0;
    $notCompletedCount = $statusCounts['not_completed'] ?? 0;
}

$stmt = $conn->prepare("
    SELECT r.rating, r.comment, r.created_at, u.name AS reviewer_name
    FROM reviews r
    INNER JOIN Users u ON r.reviewer_user_id = u.id
    WHERE r.reviewed_user_id = ?
    ORDER BY r.created_at DESC
");
$stmt->bind_param("i", $userId);
$stmt->execute();
$reviews = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);

$hasReviewed = false;

if (isset($_SESSION['user_id'])) {
    $sessionId = $_SESSION['user_id'];

    $stmt = $conn->prepare("SELECT id FROM reviews WHERE reviewer_user_id = ? AND reviewed_user_id = ?");
    $stmt->bind_param("ii", $sessionId, $userId);
    $stmt->execute();
    $hasReviewed = $stmt->get_result()->num_rows > 0;
}

?>

<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <link id="themeStylesheet" rel="stylesheet" href="../assets/styles/<?php echo $theme; ?>.css">
    <link id="SubthemeStylesheet" rel="stylesheet" href="../assets/styles/profile/profile_<?php echo $theme; ?>.css">
    <title>Профиль пользователя</title>
    <script>
        // Функция для смены темы и сохранения выбора в куки
        function toggleTheme() {
            let currentTheme = document.body.classList.toggle('dark') ? 'dark' : 'light';
            document.cookie = `theme=${currentTheme}; path=/; max-age=31536000`; // Кука на 1 год
            document.getElementById('themeStylesheet').href = `../assets/styles/${currentTheme}.css`;
            document.getElementById('SubthemeStylesheet').href = `../assets/styles/profile/profile_${currentTheme}.css`;
        }

        // Применение темы при загрузке страницы
        document.addEventListener("DOMContentLoaded", function() {
            const theme = "<?php echo $theme; ?>";
            document.body.classList.toggle('dark', theme === 'dark');
        });
    </script>
    <script>
        // Функция для проверки актуальности изображения
        function checkImageUpdate() {
            const imageUrl = "get_image.php?id=<?php echo $user['image_id']; ?>"; // URL изображения
            const lastUpdated = getCookie("image_updated_at"); // Получаем время из куки

            // Если время в куке существует, проверяем его с актуальной датой
            if (lastUpdated) {
                const imageElement = document.getElementById("profile-image"); // Элемент изображения профиля
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

        // Проверка актуальности изображения при загрузке страницы
        document.addEventListener("DOMContentLoaded", function() {
            checkImageUpdate(); // Проверка актуальности изображения при загрузке страницы
        });
    </script>

</head>
<body>
<header>
    <button onclick="toggleTheme()">Сменить тему</button>


    <?php if (isset($_SESSION['user_id'])): ?>
        <?php if ($SessionUserRole == 2): ?> <!-- Проверяем, если пользователь фрилансер -->
            <button onclick="window.location.href='applications.php'">Мои отклики</button> <!-- Кнопка для перехода на страницу откликов -->
        <?php endif; ?>
        <button onclick="window.location.href='profile.php?id=<?php echo $_SESSION['user_id']; ?>'">
            <?php echo $_SESSION['username']; ?>
        </button>

        <button onclick="window.location.href='logout.php'">Выйти</button>
    <?php else: ?>
        <button onclick="window.location.href='login.php'">Войти</button>
    <?php endif; ?>
</header>

<div class="profile-container">
    <?php if ($user['role_id'] == 3): ?>
        <h1>Профиль компании</h1>
    <?php else: ?>
        <h1>Профиль пользователя</h1>
    <?php endif; ?>

    <div class="profile-wrapper">
        <div class="profile-image">

            <img id="profile-image" src="get_image.php?id=<?php echo $user['image_id']; ?>" alt="Фото профиля" style="width:150px; height:150px; border-radius:50%; object-fit: cover;">

        </div>
        <div class="profile-info">
            <?php if ($user['role_id'] == 3 or $user['role_id'] == 1 ): // Если это компания ?>
                <p><strong>Название:</strong> <?php echo htmlspecialchars($user['name']); ?></p>
                <p><strong>Email:</strong> <?php echo htmlspecialchars($user['email']); ?></p>
                <p><strong>О нас:</strong> <?php echo htmlspecialchars($user['text']); ?></p>

            <?php else: ?>
                <p><strong>Имя:</strong> <?php echo htmlspecialchars($user['name']); ?></p>
                <p><strong>Email:</strong> <?php echo htmlspecialchars($user['email']); ?></p>
                <p><strong>О себе:</strong> <?php echo htmlspecialchars($user['text']); ?></p>
                <p><strong>Вакансия:</strong> <?php echo htmlspecialchars($user['vacancy']); ?></p>

                <!-- Выводим статистику фрилансера -->
                <h2>Статистика работ</h2>
                <p><strong>Выполнено:</strong> <?php echo $completedCount; ?></p>
                <p><strong>В процессе:</strong> <?php echo $inProgressCount; ?></p>
                <p><strong>Не выполнено:</strong> <?php echo $notCompletedCount; ?></p>
            <?php endif; ?>
        </div>

    </div>

    <div class="button-container">
        <?php if (isset($_SESSION['user_id']) && $_SESSION['user_id'] == $user['id']): ?>
            <button onclick="window.location.href='edit_profile.php?id=<?php echo $user['id']; ?>'">Редактировать</button>
        <?php endif; ?>
        <?php if (isset($_SESSION['user_id']) && $_SESSION['user_id'] !== $user['id']): ?>
            <!-- На странице профиля пользователя -->
            <button onclick="window.open('chat.php?with=<?php echo $user['id']; ?>', '_blank', 'width=800,height=600');">Чат</button>
        <?php endif; ?>

        <button onclick="window.location.href='index.php';">Назад</button>
    </div>

    <div class="reviews-section">
        <h2>Отзывы</h2>

        <?php if (isset($_SESSION['user_id']) && $_SESSION['user_id'] !== $user['id'] && !$hasReviewed): ?>
            <div class="review-form">
                <form action="add_review.php" method="POST">
                    <input type="hidden" name="reviewed_user_id" value="<?php echo $user['id']; ?>">
                    <label for="rating">Оценка:</label>
                    <div class="rating" id="rating">
                        <span class="star" data-value="1">&#9733;</span>
                        <span class="star" data-value="2">&#9733;</span>
                        <span class="star" data-value="3">&#9733;</span>
                        <span class="star" data-value="4">&#9733;</span>
                        <span class="star" data-value="5">&#9733;</span>
                    </div>
                    <input type="hidden" name="rating" id="rating-input" value="">

                    <script>
                        const stars = document.querySelectorAll('.star');
                        const ratingInput = document.getElementById('rating-input');

                        stars.forEach((star, index) => {
                            star.addEventListener('mouseover', () => {
                                stars.forEach((s, i) => s.classList.toggle('active', i <= index));
                            });

                            star.addEventListener('mouseout', () => {
                                stars.forEach(s => s.classList.remove('active'));
                            });

                            star.addEventListener('click', () => {
                                const selectedRating = index + 1;
                                ratingInput.value = selectedRating; // Устанавливаем значение в скрытое поле
                                stars.forEach((s, i) => {
                                    s.style.color = i < selectedRating ? 'gold' : 'lightgray';
                                });
                            });
                        });

                    </script>

                    <label for="comment">Отзыв:</label>
                    <textarea name="comment" id="comment" rows="4" required></textarea>
                    <button type="submit">Оставить отзыв</button>
                </form>

            </div>
        <?php elseif ($hasReviewed): ?>
            <p>Вы уже оставили отзыв для этого пользователя.</p>
        <?php endif; ?>

        <div class="reviews-list">
            <?php if (empty($reviews)): ?>
                <p>Отзывов пока нет.</p>
            <?php else: ?>
                <?php foreach ($reviews as $review): ?>
                    <div class="review">
                        <p><strong><?php echo htmlspecialchars($review['reviewer_name']); ?></strong> оставил(а) оценку
                            <span><?php echo str_repeat('⭐', $review['rating']); ?></span>:</p>
                        <p><?php echo htmlspecialchars($review['comment']); ?></p>
                        <p class="review-date">Оставлено: <?php echo date("d.m.Y H:i", strtotime($review['created_at'])); ?></p>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>
    </div>

</div>

</body>
</html>
