<?php
require_once 'db.php';

$conn = getDbConnection();

$query = "SELECT * FROM sample_data";
$result = $conn->query($query);

$data = [];
while ($row = $result->fetch_assoc()) {
    $data[] = $row;
}

header('Content-Type: application/json');
echo json_encode($data);

$conn->close();
?>
