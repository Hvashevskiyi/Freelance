<?php
session_start();
require_once '../includes/db.php';



$userId = $_SESSION['user_id'];
$conn = getDbConnection();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $applicationId = intval($_POST['application_id']);
    $rating = floatval($_POST['rating']);
    $completed = $_POST['completed'];

    if ($rating < 0 || $rating > 5) {
        die("Ошибка: оценка должна быть в диапазоне от 0 до 5.");
    }

    $stmt = $conn->prepare("UPDATE applications SET rating = ?, order_status = 'Закрыт' WHERE id = ?");
    $stmt->bind_param("di", $rating, $applicationId);

    if ($stmt->execute()) {
        $statusStmt = $conn->prepare("UPDATE application_status SET completed = ? WHERE application_id = ?");
        $statusStmt->bind_param("si", $completed, $applicationId);

        if ($statusStmt->execute()) {
            $applicationStmt = $conn->prepare("SELECT freelancer_id FROM applications WHERE id = ?");
            $applicationStmt->bind_param("i", $applicationId);
            $applicationStmt->execute();
            $freelancerId = $applicationStmt->get_result()->fetch_assoc()['freelancer_id'];

            $ordersStmt = $conn->prepare("
    SELECT 
        SUM(CASE WHEN s.completed = 'Выполнено' THEN 1 ELSE 0 END) as completed_orders,
        SUM(CASE WHEN s.completed = 'Не выполнено' THEN 1 ELSE 0 END) as not_completed_orders,
        AVG(a.rating) as average_rating
    FROM applications a
    JOIN application_status s ON a.id = s.application_id
    WHERE a.freelancer_id = ?
");
            $ordersStmt->bind_param("i", $freelancerId);
            $ordersStmt->execute();
            $orderStats = $ordersStmt->get_result()->fetch_assoc();

            // Определяем веса
            $w_completed = 2; // вес выполненных заказов
            $w_not_completed = -1; // вес не выполненных заказов
            $completedCount = $orderStats['completed_orders'];
            $notCompletedCount = $orderStats['not_completed_orders'];
            $averageRating = $orderStats['average_rating'];

            // Рассчитываем новый рейтинг по обновленной формуле
            if ($completedCount + $notCompletedCount > 0) {
                $numerator = ($averageRating * $w_completed * $completedCount) + ($w_not_completed * $notCompletedCount);
                $denominator = ($w_completed * $completedCount) + $notCompletedCount;
                $newRating = $numerator / $denominator;
                $newRating = max(0, min(5, round($newRating, 2))); // ограничиваем значение от 0 до 5
            } else {
                $newRating = 0;
            }

            $updateRatingStmt = $conn->prepare("UPDATE Users SET average_rating = ? WHERE id = ?");
            $updateRatingStmt->bind_param("di", $newRating, $freelancerId);
            $updateRatingStmt->execute();

            header("Location: company_applications.php?success=1");
        } else {
            echo "Ошибка при обновлении статуса: " . $statusStmt->error;
        }
        $statusStmt->close();
    } else {
        echo "Ошибка при обновлении рейтинга: " . $stmt->error;
    }

    $stmt->close();
}

$conn->close();
?>
