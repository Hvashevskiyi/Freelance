<?php

// Подключаемся к базе данных
include 'db.php';
$conn = getDbConnection();

if ($conn->connect_error) {
    die("Ошибка подключения: " . $conn->connect_error);
}

if (isset($_GET['id'])) {
    $userId = $conn->real_escape_string($_GET['id']);

    $sql = "DELETE FROM users WHERE id = $userId";

    if ($conn->query($sql) === TRUE) {
        echo 'success';
    } else {
        echo 'Ошибка удаления: ' . $conn->error;
    }
} else {
    echo 'Нет id пользователя для удаления';
}

$conn->close();

