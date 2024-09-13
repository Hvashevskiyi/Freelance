<?php
function getDbConnection() {
    $host = "localhost";
    $user = "root";
    $pass = "";
    $dbname = "freelance_platform";

    $conn = new mysqli($host, $user, $pass, $dbname);

    if ($conn->connect_error) {
        die("Ошибка подключения: " . $conn->connect_error);
    }

    return $conn;
}
