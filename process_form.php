<?php
// Включаем файл подключения к базе данных
include 'db.php';

// Получаем подключение к базе данных
$conn = getDbConnection();

// Переменные для хранения сообщений об ошибках
$nameError = "";
$emailError = "";

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // Получаем данные из формы и очищаем их
    $name = trim($_POST["name"]);
    $email = trim($_POST["email"]);
    $password = trim($_POST["password"]);

    // Валидация email
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $emailError = "Неверный формат email";
    } else {
        // Проверка на занятость имени
       /* try {
            $sql = "SELECT id FROM users WHERE name = ?";
            $stmt = $conn->prepare($sql);
            $stmt->bind_param("s", $name);
            $stmt->execute();
            $stmt->store_result();
            if ($stmt->num_rows > 0) {
                $nameError = "Имя уже занято";
            }
            $stmt->close();
        } catch (Exception $e) {
            $nameError = "Ошибка при проверке имени: " . $e->getMessage();
        }*/

        // Проверка на занятость email
        try {
            $sql = "SELECT id FROM users WHERE email = ?";
            $stmt = $conn->prepare($sql);
            $stmt->bind_param("s", $email);
            $stmt->execute();
            $stmt->store_result();
            if ($stmt->num_rows > 0) {
                $emailError = "Email уже занят";
            }
            $stmt->close();
        } catch (Exception $e) {
            $emailError = "Ошибка при проверке email: " . $e->getMessage();
        }

        // Если нет ошибок, добавляем пользователя
        if (empty($nameError) && empty($emailError)) {
            try {
                $passwordHash = password_hash($password, PASSWORD_DEFAULT);
                $sql = "INSERT INTO users (name, email, password) VALUES (?, ?, ?)";
                $stmt = $conn->prepare($sql);
                $stmt->bind_param("sss", $name, $email, $passwordHash);
                if ($stmt->execute()) {
                    // Закрываем подготовленный запрос
                    $stmt->close();
                    // Перенаправляем на ту же страницу, но с параметром в URL
                    header("Location: " . $_SERVER['PHP_SELF']);
                    exit;
                } else {
                    echo "Ошибка выполнения запроса: " . $stmt->error;
                }
            } catch (Exception $e) {
                echo "Ошибка при добавлении пользователя: " . $e->getMessage();
            }
        }
    }
}

// Закрываем соединение с базой данных
$conn->close();
?>
