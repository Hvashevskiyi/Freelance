<?php
session_start();
require_once '../includes/db.php';


$applicationId = intval($_GET['id']);
$conn = getDbConnection();
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
?>

<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <link rel="stylesheet" href="../assets/styles/base.css">
    <link rel="stylesheet" href="../assets/styles/view_cover_letter.css">
    <title>Сопроводительное письмо</title>
</head>
<body>
<header>
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
            <p><strong>Прикрепленный файл:</strong></p>
            <a href="<?php echo htmlspecialchars($application['file_path']); ?>" download>Скачать файл</a>
        </div>
    <?php endif; ?>
</div>

</body>
</html>
