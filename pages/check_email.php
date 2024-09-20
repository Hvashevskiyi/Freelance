<?php
require_once '../includes/db.php';

if (isset($_POST['email'])) {
    $email = $_POST['email'];

    $conn = getDbConnection();
    $stmt = $conn->prepare("SELECT id FROM Users WHERE email = ?");
    $stmt->bind_param("s", $email);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows > 0) {
        echo 'taken';  // Почта уже используется
    } else {
        echo 'available';  // Почта доступна
    }
}
