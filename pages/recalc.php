<?php

require_once '../includes/db.php';

$conn = getDbConnection();

// Получаем текущие весовые коэффициенты из таблицы weights

// Получаем текущие веса (последнюю запись)
$stmt = $conn->query("
    SELECT completed_weight, not_completed_weight, avg_weight 
    FROM weights 
    ORDER BY created_at DESC 
    LIMIT 1
");
$weightsData = $stmt->fetch_assoc();

// Устанавливаем значения весов с учетом значений по умолчанию
$w_completed = $weightsData['completed_weight'];
$w_not_completed = $weightsData['not_completed_weight'];
$w_avg = $weightsData['avg_weight'];

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
    $averageRating = $orderStats['average_rating'] ?? 0;//median

    // Рассчитываем новый рейтинг
    if ($completedCount + $notCompletedCount > 0) {
        $denominator = $w_not_completed * $w_completed + $w_avg + $w_completed + $w_not_completed;
        $norm_s = $averageRating / 5;
        $c_ratio = $completedCount / ($completedCount + $notCompletedCount);
        $n_c_ratio = $notCompletedCount / ($completedCount + $notCompletedCount);
        $num_w_s =$norm_s * $w_avg;
        $num_w_c = $c_ratio * $w_completed;
        $num_w_u = $n_c_ratio * $w_not_completed;
        $numerator = $num_w_s + $num_w_c - $num_w_u;
        $newRating = 5 * $numerator / $denominator;
        $newRating = max(0, min(5, round($newRating, 2)));
    } else {
        $newRating = 0;
    }

    // Обновляем рейтинг фрилансера
    $updateRatingStmt = $conn->prepare("UPDATE Users SET average_rating = ?, num_w_s = ?,num_w_c = ?,num_w_u = ?,denom = ? WHERE id = ?");
    $updateRatingStmt->bind_param("dddddi", $newRating,$num_w_s,$num_w_c,$num_w_u,$denominator, $freelancerId);
    $updateRatingStmt->execute();
}

?>
