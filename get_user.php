<?php
include 'db.php';
$conn = getDbConnection();

if ($conn->connect_error) {
    die("Ошибка подключения: " . $conn->connect_error);
}

if (isset($_GET['id'])) {
    $userId = $conn->real_escape_string($_GET['id']);

    $sql = "SELECT id, name, email FROM users WHERE id = $userId";
    $result = $conn->query($sql);

    if ($result && $result->num_rows > 0) {
        $user = $result->fetch_assoc();
        echo json_encode($user);
    } else {
        echo json_encode(['error' => 'Пользователь не найден']);
    }

    $result->free();
} else {
    echo json_encode(['error' => 'ID пользователя не указан']);
}

$conn->close();
?>
