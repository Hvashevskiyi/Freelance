<?php
session_start();
require_once '../includes/db.php';
$conn = getDbConnection();

if (isset($_POST['application_id'])) {
    $applicationId = $_POST['application_id'];

    // Обновляем статус на "Просмотрено"
    $stmt = $conn->prepare("UPDATE application_status SET viewed = 'Просмотрено' WHERE application_id = ?");
    $stmt->bind_param("i", $applicationId);

    if ($stmt->execute()) {
        echo json_encode(['success' => true]);
    } else {
        echo json_encode(['success' => false]);
    }
}
?>
