<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Добавление пользователя</title>
    <link rel="stylesheet" href="styles/index.css">
    <script src="scripts/check_availability.js" defer></script>
</head>
<body>
<div class="container">
    <h1>Добавление пользователя</h1>
    <?php
    // Включаем файл обработки формы
    include 'process_form.php';
    ?>

    <!-- Форма для добавления пользователя -->
    <form method="post" action="">
        Имя: <input type="text" id="name" name="name" required>
        <span id="nameError" class="error"></span><br>
        Email: <input type="email" id="email" name="email" required>
        <span id="emailError" class="error"></span><br>
        Пароль: <input type="password" name="password" required><br>
        <div class="form-actions">
            <input type="submit" id="submitBtn" class="button" value="Добавить пользователя">
            <a href="users.php" class="button">Перейти к списку пользователей</a>
        </div>
    </form>
</div>
</body>
</html>
