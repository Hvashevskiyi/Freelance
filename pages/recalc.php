<?php
session_start();
require_once '../includes/db.php';

$conn = getDbConnection();

// Получаем текущие весовые коэффициенты из таблицы weights
$weights = $conn->query("SELECT name, value FROM weights");
$weightsData = [];
while ($row = $weights->fetch_assoc()) {
    $weightsData[$row['name']] = $row['value'];
}

// Устанавливаем значения весов с учетом значений по умолчанию
$w_completed = $weightsData['completed_weight'] ?? 2;
$w_not_completed = $weightsData['not_completed_weight'] ?? -1;


// Получаем всех фрилансеров
$freelancers = $conn->query("SELECT id FROM Users WHERE role_id = 2");

while ($freelancer = $freelancers->fetch_assoc()) {
    $freelancerId = $freelancer['id'];

    // Получаем данные о заказах для текущего фрилансера
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

    $completedCount = $orderStats['completed_orders'];
    $notCompletedCount = $orderStats['not_completed_orders'];
    $averageRating = $orderStats['average_rating'] ?? 0;

    // Выводим информацию о заказах и среднем рейтинге

    // Рассчитываем новый рейтинг
    if ($completedCount + $notCompletedCount > 0) {
        $numerator = ($averageRating * $w_completed * $completedCount) + ($w_not_completed * $notCompletedCount);
        $denominator = ($w_completed * $completedCount) + $notCompletedCount;

        $newRating = $numerator / $denominator;
        $newRating = max(0, min(5, round($newRating, 2)));
    } else {
        $newRating = 0;
    }

    // Обновляем рейтинг фрилансера
    $updateRatingStmt = $conn->prepare("UPDATE Users SET average_rating = ? WHERE id = ?");
    $updateRatingStmt->bind_param("di", $newRating, $freelancerId);
    $updateRatingStmt->execute();


}

$conn->close();
echo "<script>alert('Рейтинги перерассчитаны для всех фрилансеров');</script>";
?>
