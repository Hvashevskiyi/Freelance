<?php
// Подключаем файл подключения к базе данных
include 'db.php';

// Получаем подключение к базе данных
$conn = getDbConnection();

header('Content-Type: application/json');

// Получаем данные из запроса
$name = isset($_POST['name']) ? trim($_POST['name']) : '';
$email = isset($_POST['email']) ? trim($_POST['email']) : '';

// Инициализируем флаги занятости
$nameTaken = false;
$emailTaken = false;

try {
    // Проверка занятости имени
    if ($name) {
        $stmt = $conn->prepare("SELECT COUNT(*) FROM users WHERE name = ?");
        $stmt->bind_param("s", $name);
        $stmt->execute();
        $stmt->bind_result($count);
        $stmt->fetch();
        $nameTaken = $count > 0;
        $stmt->close();
    }

    // Проверка занятости email
    if ($email) {
        $stmt = $conn->prepare("SELECT COUNT(*) FROM users WHERE email = ?");
        $stmt->bind_param("s", $email);
        $stmt->execute();
        $stmt->bind_result($count);
        $stmt->fetch();
        $emailTaken = $count > 0;
        $stmt->close();
    }
} catch (Exception $e) {
    echo json_encode(['nameTaken' => false, 'emailTaken' => false, 'error' => $e->getMessage()]);
    exit;
}

// Закрываем соединение с базой данных
$conn->close();

// Возвращаем результат в формате JSON
echo json_encode([
    'nameTaken' => $nameTaken,
    'emailTaken' => $emailTaken
]);
?>
