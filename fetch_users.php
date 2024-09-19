<?php
// Подключаемся к базе данных
include 'db.php';
$conn = getDbConnection();

// Проверяем подключение
if ($conn->connect_error) {
    die("Ошибка подключения: " . $conn->connect_error);
}

// Проверяем, был ли выполнен запрос на поиск
$name = isset($_GET["name"]) ? $conn->real_escape_string($_GET["name"]) : '';
$sql = $name ? "SELECT id, name, email FROM users WHERE (name LIKE '%$name%') or (email LIKE '%$name%')" : "SELECT id, name, email FROM users";

$result = $conn->query($sql);

// Выводим таблицу
echo '<table>';
echo '<thead><tr><th>ID</th><th>Имя</th><th>Email</th><th>Действие</th></tr></thead>';
echo '<tbody>';
if ($result) {
    if ($result->num_rows > 0) {
        while ($row = $result->fetch_assoc()) {
            echo "<tr><td>" . $row["id"] . "</td><td>" . $row["name"] . "</td><td>" . $row["email"] . "</td>";
            echo "<td><button class='edit-btn' data-id='" . $row["id"] . "'>Редактировать</button> ";
            echo "<button class='delete-btn' data-id='" . $row["id"] . "'>Удалить</button></td></tr>";
        }
    } else {
        echo "<tr><td colspan='4'>0 результатов</td></tr>";
    }
    $result->free();
} else {
    echo "<tr><td colspan='4'>Ошибка выполнения запроса: " . $conn->error . "</td></tr>";
}
echo '</tbody></table>';

$conn->close();
?>
