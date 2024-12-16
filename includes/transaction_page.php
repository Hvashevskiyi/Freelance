<?php
require_once 'db.php';

function transferFunds($fromAccountId, $toAccountId, $amount) {
    $conn = getDbConnection();

    try {
        // Начало транзакции
        $conn->begin_transaction();

        // Проверяем баланс отправителя
        $stmt = $conn->prepare("SELECT balance FROM accounts WHERE id = ?");
        $stmt->bind_param("i", $fromAccountId);
        $stmt->execute();
        $stmt->bind_result($fromBalance);
        $stmt->fetch();
        $stmt->close();

        if ($fromBalance < $amount) {
            throw new Exception("Недостаточно средств для перевода.");
        }

        // Вычитаем сумму у отправителя
        $stmt = $conn->prepare("UPDATE accounts SET balance = balance - ? WHERE id = ?");
        $stmt->bind_param("di", $amount, $fromAccountId);
        $stmt->execute();

        // Добавляем сумму получателю
        $stmt = $conn->prepare("UPDATE accounts SET balance = balance + ? WHERE id = ?");
        $stmt->bind_param("di", $amount, $toAccountId);
        $stmt->execute();

        // Завершаем транзакцию
        $conn->commit();
        return "Перевод успешно выполнен.";
    } catch (Exception $e) {
        // Откатываем транзакцию в случае ошибки
        $conn->rollback();
        return "Ошибка: " . $e->getMessage();
    } finally {
        $conn->close();
    }
}

// Обработка запроса
if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    $fromAccountId = $_POST['from_account'];
    $toAccountId = $_POST['to_account'];
    $amount = $_POST['amount'];

    $result = transferFunds($fromAccountId, $toAccountId, $amount);
    echo $result;
}
?>

<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <title>Перевод средств</title>
</head>
<body>
<h1>Перевод средств</h1>
<form method="POST">
    <label for="from_account">ID отправителя:</label>
    <input type="number" id="from_account" name="from_account" required>
    <br>

    <label for="to_account">ID получателя:</label>
    <input type="number" id="to_account" name="to_account" required>
    <br>

    <label for="amount">Сумма:</label>
    <input type="number" id="amount" name="amount" step="0.01" required>
    <br>

    <button type="submit">Выполнить перевод</button>
</form>
</body>
</html>
