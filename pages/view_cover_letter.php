<?php
session_start();
require_once '../includes/db.php';

$applicationId = intval($_GET['id']);
$conn = getDbConnection();
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php"); // Если пользователь не авторизован, перенаправляем на страницу входа
    exit;
}
$theme = isset($_COOKIE['theme']) ? $_COOKIE['theme'] : 'light';
require_once '../includes/checkUserExists.php';
$userId = $_SESSION['user_id'];
$role = $_SESSION['role_id'];

if (!checkUserExists($conn, $userId) || $role == 2) {
    session_unset();
    session_destroy();
    header("Location: login.php");
    exit;
}

// Получаем сопроводительное письмо и путь к файлу
$stmt = $conn->prepare("
    SELECT a.cover_letter, v.VacancyTag, f.name AS freelancer_name, a.file_path
    FROM applications a
    JOIN vacancy v ON a.vacancy_id = v.id
    JOIN users f ON a.freelancer_id = f.id
    WHERE a.id = ?
");
$stmt->bind_param("i", $applicationId);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 0) {
    header("Location: company_applications.php");
    exit;
}

$application = $result->fetch_assoc();

// Функция для проверки доступности и целостности файла
function checkFileStatus($filePath) {
    if (!file_exists($filePath)) {
        return 'missing'; // Файл отсутствует
    }

    if (!is_readable($filePath)) {
        return 'unreadable'; // Файл недоступен для чтения
    }

    $fileExtension = strtolower(pathinfo($filePath, PATHINFO_EXTENSION));
    $fileMimeType = mime_content_type($filePath);

    // Проверка для PDF файлов
    if ($fileExtension === 'pdf' && $fileMimeType === 'application/pdf') {
        $fileData = file_get_contents($filePath, false, null, 0, 4);
        return strpos($fileData, '%PDF') === 0 ? 'valid' : 'corrupted';
    }

    // Проверка для TXT файлов
    if ($fileExtension === 'txt' && $fileMimeType === 'text/plain') {
        $fileData = file_get_contents($filePath);
        return !empty(trim($fileData)) ? 'valid' : 'corrupted';
    }

    // Проверка для DOCX (как ZIP-архивов)
    if ($fileExtension === 'docx' && $fileMimeType === 'application/vnd.openxmlformats-officedocument.wordprocessingml.document') {
        $zip = new ZipArchive();
        if ($zip->open($filePath) === true) {
            $zip->close();
            return 'valid';
        } else {
            return 'corrupted';
        }
    }

    // Проверка для DOC (старый формат)
    if ($fileExtension === 'doc' && $fileMimeType === 'application/msword') {
        $fileData = file_get_contents($filePath, false, null, 0, 8);
        return strpos($fileData, "\xD0\xCF\x11\xE0\xA1") === 0 ? 'valid' : 'corrupted';
    }

    // Другие типы файлов считаются валидными без дополнительной проверки
    return 'valid';
}



// Проверяем файл
$fileStatus = checkFileStatus($application['file_path']);
?>

<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <link id="themeStylesheet" rel="stylesheet" href="../assets/styles/<?php echo $theme; ?>.css">
    <link id="SubthemeStylesheet" rel="stylesheet" href="../assets/styles/view_cover_letter/vcl_<?php echo $theme; ?>.css">
    <title>Сопроводительное письмо</title>
    <script>
        function toggleTheme() {
            let currentTheme = document.body.classList.toggle('dark') ? 'dark' : 'light';
            document.cookie = `theme=${currentTheme}; path=/; max-age=31536000`;
            document.getElementById('themeStylesheet').href = `../assets/styles/${currentTheme}.css`;
            document.getElementById('SubthemeStylesheet').href = `../assets/styles/view_cover_letter/vcl_${currentTheme}.css`;
        }

        document.addEventListener("DOMContentLoaded", function() {
            const theme = "<?php echo $theme; ?>";
            document.body.classList.toggle('dark', theme === 'dark');
        });
    </script>
</head>
<body>
<header>
    <button onclick="toggleTheme()">Сменить тему</button>
    <button onclick="window.location.href='index.php'">На главную</button>
    <button onclick="window.location.href='profile.php?id=<?php echo $_SESSION['user_id']; ?>'">
        <?php echo htmlspecialchars($_SESSION['username']); ?>
    </button>
</header>

<div class="cover_letter_container">
    <h1>Сопроводительное письмо</h1>
    <p><strong>Вакансия:</strong> <?php echo htmlspecialchars($application['VacancyTag']); ?></p>
    <p><strong>Фрилансер:</strong> <?php echo htmlspecialchars($application['freelancer_name']); ?></p>
    <div class="cover_letter">
        <p><?php echo nl2br(htmlspecialchars($application['cover_letter'])); ?></p>
    </div>

    <?php if (!empty($application['file_path'])): ?>
        <div class="file_attachment">
            <?php if ($fileStatus === 'missing'): ?>
                <p><strong>Ошибка:</strong> Файл отсутствует на сервере.</p>
            <?php elseif ($fileStatus === 'unreadable'): ?>
                <p><strong>Ошибка:</strong> Файл недоступен для чтения.</p>
            <?php elseif ($fileStatus === 'corrupted'): ?>
                <p><strong>Ошибка:</strong> Файл поврежден.</p>
            <?php else: ?>
                <p><strong>Прикрепленный файл:</strong></p>
                <a href="<?php echo htmlspecialchars($application['file_path']); ?>" download>Скачать файл</a>
            <?php endif; ?>
        </div>
    <?php endif; ?>
</div>

</body>
</html>
