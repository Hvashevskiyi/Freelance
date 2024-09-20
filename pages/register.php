<?php
session_start();
require_once '../includes/db.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = $_POST['name'];
    $email = $_POST['email'];
    $password = password_hash($_POST['password'], PASSWORD_DEFAULT);

    $conn = getDbConnection();
    $stmt = $conn->prepare("INSERT INTO Users (name, email, password) VALUES (?, ?, ?)");
    $stmt->bind_param("sss", $name, $email, $password);
    $stmt->execute();

    header("Location: login.php");
    exit;
}

?>

<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <link rel="stylesheet" href="../assets/style.css">
    <title>Регистрация</title>
</head>
<body>
<h1>Регистрация</h1>
<form method="POST">
    <input type="text" name="name" placeholder="Имя" required>
    <input type="email" name="email" placeholder="Уникальная почта" required>
    <input type="password" name="password" placeholder="Пароль" required>
    <button type="submit">Зарегистрироваться</button>
</form>
</body>
</html>
