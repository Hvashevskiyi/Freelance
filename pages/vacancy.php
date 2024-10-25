<?php
session_start();
require_once '../includes/db.php';

if (!isset($_GET['id'])) {
    header("Location: index.php"); // Если нет ID вакансии, перенаправляем на главную
    exit;
}

$vacancyId = intval($_GET['id']);
$conn = getDbConnection();

// Получаем данные о вакансии
$stmt = $conn->prepare("SELECT v.VacancyTag, v.Description, v.Salary, u.name AS author_name FROM vacancy v JOIN users u ON v.id_company = u.id WHERE v.id = ?");
$stmt->bind_param("i", $vacancyId);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 0) {
    header("Location: index.php"); // Если вакансия не найдена, перенаправляем на главную
    exit;
}

$vacancy = $result->fetch_assoc();
$userRole = 0;
// Проверяем, авторизован ли пользователь как фрилансер
$isFreelancer = false;
$hasApplied = false; // Инициализация переменной для статуса заявки

if (isset($_SESSION['user_id'])) {
    $userId = $_SESSION['user_id'];
    $stmt = $conn->prepare("SELECT role_id FROM Users WHERE id = ?");
    $stmt->bind_param("i", $userId);
    $stmt->execute();
    $userRole = $stmt->get_result()->fetch_assoc()['role_id'];

    if ($userRole == 2) { // Проверяем, является ли пользователь фрилансером
        $isFreelancer = true;

        // Проверяем, откликнулся ли уже фрилансер на эту вакансию
        $stmt = $conn->prepare("SELECT * FROM applications WHERE freelancer_id = ? AND vacancy_id = ?");
        $stmt->bind_param("ii", $userId, $vacancyId);
        $stmt->execute();
        $applicationResult = $stmt->get_result();
        $hasApplied = $applicationResult->num_rows > 0;
    }
}

// Обработка формы отклика
if ($_SERVER['REQUEST_METHOD'] === 'POST' && $isFreelancer && !$hasApplied) {
    $coverLetter = $_POST['cover_letter'];

    // Проверяем, загружен ли файл
    if (isset($_FILES['attachment'])) {
        if ($_FILES['attachment']['error'] === UPLOAD_ERR_OK) {
            $fileTmpPath = $_FILES['attachment']['tmp_name'];
            $fileName = $_FILES['attachment']['name'];
            $fileSize = $_FILES['attachment']['size'];
            $fileType = $_FILES['attachment']['type'];
            $fileNameCmps = explode(".", $fileName);
            $fileExtension = strtolower(end($fileNameCmps));

            // Разрешенные типы файлов
            $allowedfileExtensions = ['pdf', 'txt', 'doc', 'docx'];
            if (in_array($fileExtension, $allowedfileExtensions)) {
                // Путь для сохранения файла
                $uploadFileDir = '../uploads/';
                $newFileName = md5(time() . $fileName) . '.' . $fileExtension;
                $dest_path = $uploadFileDir . $newFileName;

                // Перемещаем файл в папку
                if (move_uploaded_file($fileTmpPath, $dest_path)) {
                    // Сохранение отклика и пути к файлу в базе данных
                    $stmt = $conn->prepare("INSERT INTO applications (freelancer_id, vacancy_id, cover_letter, file_path) VALUES (?, ?, ?, ?)");
                    $stmt->bind_param("iiss", $userId, $vacancyId, $coverLetter, $dest_path);
                    if ($stmt->execute()) {
                        header("Location: vacancy.php?id=" . $vacancyId); // Перенаправление после успешной отправки
                        exit;
                    }
                } else {
                    echo 'Ошибка при перемещении файла на сервер.';
                }
            } else {
                echo 'Неподдерживаемый тип файла. Пожалуйста, загрузите PDF, TXT, DOC или DOCX файл.';
            }
        } else {
            // Обработка ошибок загрузки
            switch ($_FILES['attachment']['error']) {
                case UPLOAD_ERR_INI_SIZE:
                case UPLOAD_ERR_FORM_SIZE:
                    echo 'Файл слишком большой. Пожалуйста, уменьшите его размер.';
                    break;
                case UPLOAD_ERR_PARTIAL:
                    echo 'Файл был загружен частично.';
                    break;
                case UPLOAD_ERR_NO_FILE:
                    echo 'Файл не был загружен.';
                    break;
                case UPLOAD_ERR_NO_TMP_DIR:
                    echo 'Отсутствует временная директория.';
                    break;
                case UPLOAD_ERR_CANT_WRITE:
                    echo 'Не удалось записать файл на диск.';
                    break;
                case UPLOAD_ERR_EXTENSION:
                    echo 'Загрузка остановлена из-за расширения.';
                    break;
                default:
                    echo 'Неизвестная ошибка загрузки.';
                    break;
            }
        }
    } else {
        echo 'Ошибка при загрузке файла.';
    }
}
?>

<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <link rel="stylesheet" href="../assets/styles/base.css">
    <link rel="stylesheet" href="../assets/styles/vacancy.css"> <!-- Добавим новый стиль для страницы вакансии -->
    <title><?php echo htmlspecialchars($vacancy['VacancyTag']); ?></title>
</head>
<body>
<header>
    <button onclick="window.location.href='index.php'">На главную</button>
    <?php if ($userRole == 2): ?> <!-- Проверяем, если пользователь фрилансер -->
        <button onclick="window.location.href='applications.php'">Мои отклики</button> <!-- Кнопка для перехода на страницу откликов -->
    <?php endif; ?>
    <?php if (isset($_SESSION['user_id'])): ?>
        <button onclick="window.location.href='profile.php?id=<?php echo $_SESSION['user_id']; ?>'">
            <?php echo htmlspecialchars($_SESSION['username']); ?>
        </button>
    <?php endif; ?>
</header>

<div class="vacancy_container">
    <h1><?php echo htmlspecialchars($vacancy['VacancyTag']); ?></h1>
    <p><strong>Описание:</strong></p>
    <p><?php echo htmlspecialchars($vacancy['Description']); ?></p>
    <p><strong>Зарплата:</strong> <?php echo htmlspecialchars($vacancy['Salary']); ?> ₽</p>
    <p><strong>Автор:</strong> <?php echo htmlspecialchars($vacancy['author_name']); ?></p>

    <?php if ($isFreelancer): ?>
        <?php if ($hasApplied): ?>
            <p>Вы уже откликнулись на эту вакансию.</p>
        <?php else: ?>
            <h2>Откликнуться на вакансию</h2>
            <form method="post" action="" enctype="multipart/form-data"> <!-- Не забудьте добавить атрибут enctype -->
                <textarea name="cover_letter" rows="4" cols="50" placeholder="Ваше сопроводительное письмо" required></textarea><br>
                <input type="file" name="attachment" accept=".pdf,.txt,.doc,.docx" required><br>
                <input type="submit" value="Отправить отклик">
            </form>
        <?php endif; ?>
    <?php endif; ?>
</div>

</body>
</html>
