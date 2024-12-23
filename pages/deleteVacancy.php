<?php
session_start();
require_once '../includes/db.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_SESSION['user_id'])) {
    $conn = getDbConnection();
    $data = json_decode(file_get_contents('php://input'), true);
    $vacancyId = intval($data['vacancy_id']);

    // Проверка, существует ли вакансия
   // $stmt = $conn->prepare("SELECT id FROM vacancy WHERE id = ?");
   // $stmt->bind_param("i", $vacancyId);
  //  $stmt->execute();
   // $result = $stmt->get_result();


        // Удаление вакансии
        $stmt = $conn->prepare("DELETE FROM vacancy WHERE id = ?");
        $stmt->bind_param("i", $vacancyId);
        $stmt->execute();
        echo json_encode(['success' => true]);

} else {
    echo json_encode(['success' => false, 'error' => 'Вы не авторизованы.']);
}
