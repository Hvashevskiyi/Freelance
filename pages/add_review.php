<?php
session_start();
require_once '../includes/db.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}

$conn = getDbConnection();

// Проверяем входные данные
$reviewedUserId = intval($_POST['reviewed_user_id']);
$rating = intval($_POST['rating']);
$comment = trim($_POST['comment']);
$reviewerUserId = $_SESSION['user_id'];

if ($rating < 1 || $rating > 5 || empty($comment)) {
    die("Некорректные данные отзыва.");
}

// Получаем роли обоих пользователей
$stmt = $conn->prepare("SELECT role_id FROM Users WHERE id = ?");
$stmt->bind_param("i", $reviewerUserId);
$stmt->execute();
$reviewerRole = $stmt->get_result()->fetch_assoc()['role_id'];

$stmt->bind_param("i", $reviewedUserId);
$stmt->execute();
$reviewedRole = $stmt->get_result()->fetch_assoc()['role_id'];

// Проверяем роли
if (!(
    ($reviewerRole == 2 && $reviewedRole == 3) ||
    ($reviewerRole == 3 && $reviewedRole == 2)
)) {
    die("Вы не можете оставить отзыв этому пользователю.");
}

// Проверяем, оставлял ли пользователь уже отзыв
$stmt = $conn->prepare("SELECT id FROM reviews WHERE reviewer_user_id = ? AND reviewed_user_id = ?");
$stmt->bind_param("ii", $reviewerUserId, $reviewedUserId);
$stmt->execute();
if ($stmt->get_result()->num_rows > 0) {
    die("Вы уже оставили отзыв этому пользователю.");
}

// Добавляем отзыв
$stmt = $conn->prepare("
    INSERT INTO reviews (reviewer_user_id, reviewed_user_id, rating, comment, created_at)
    VALUES (?, ?, ?, ?, NOW())
");
$stmt->bind_param("iiis", $reviewerUserId, $reviewedUserId, $rating, $comment);

if ($stmt->execute()) {
    header("Location: profile.php?id=$reviewedUserId");
    exit;
} else {
    die("Ошибка при добавлении отзыва.");
}
?>
