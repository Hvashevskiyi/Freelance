<?php

require_once 'db.php'; // Подключите базу данных, если это необходимо

/**
 * Проверяет, существует ли пользователь в базе данных
 *
 * @param mysqli $conn - соединение с базой данных
 * @param int $userId - ID пользователя
 * @return bool - true, если пользователь существует, иначе false
 */
function checkUserExists($conn, $userId) {
    $stmt = $conn->prepare("SELECT id FROM Users WHERE id = ?");
    $stmt->bind_param("i", $userId);
    $stmt->execute();
    $result = $stmt->get_result();
    return $result->num_rows > 0; // Возвращает true, если пользователь существует
}
