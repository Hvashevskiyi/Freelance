<?php
include 'db.php';
$conn = getDbConnection();

if ($conn->connect_error) {
    die("Ошибка подключения: " . $conn->connect_error);
}

if (isset($_POST['id']) && isset($_POST['name']) && isset($_POST['email'])) {
    $userId = $conn->real_escape_string($_POST['id']);
    $name = $conn->real_escape_string($_POST['name']);
    $email = $conn->real_escape_string($_POST['email']);

    // Выполняем обновление данных пользователя
    $sql = "UPDATE users SET name = '$name', email = '$email' WHERE id = $userId";

    if ($conn->query($sql) === TRUE) {
        echo 'success';
    } else {
        echo 'error';
    }
} else {
    echo 'error';
}

$conn->close();
?>
