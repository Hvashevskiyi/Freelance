<?php
// Подключение к базе данных
$host = "localhost";
$user = "root";
$pass = "";
$dbname = "freelance_platform";

$conn = new mysqli($host, $user, $pass, $dbname);

// Проверяем подключение на ошибки
if ($conn->connect_error) {
    die("Ошибка подключения: " . $conn->connect_error);
}

// Генерация случайных данных
function generateRandomString($length = 10) {
    $characters = 'abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ';
    $charactersLength = strlen($characters);
    $randomString = '';
    for ($i = 0; $i < $length; $i++) {
        $randomString .= $characters[rand(0, $charactersLength - 1)];
    }
    return $randomString;
}

function generateRandomEmail() {
    return generateRandomString(5) . '@example.com';
}

function generateRandomPassword() {
    return generateRandomString(8);
}

// Вставка пользователей
for ($i = 1; $i <= 15; $i++) {
    $name = generateRandomString(8);
    $email = generateRandomEmail();
    $password = generateRandomPassword();
    $passwordHash = password_hash($password, PASSWORD_DEFAULT);

    $sql = "INSERT INTO users (name, email, password) VALUES (?, ?, ?)";

    $stmt = $conn->prepare($sql);
    if ($stmt) {
        $stmt->bind_param("sss", $name, $email, $passwordHash);
        $stmt->execute();
        $stmt->close();
    } else {
        echo "Ошибка подготовки запроса: " . $conn->error;
    }
}

echo "15 пользователей успешно добавлены в базу данных!";

// Закрываем соединение с базой данных
$conn->close();
?>
