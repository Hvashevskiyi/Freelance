<?php
function getDbConnection() {
    $host = "localhost";
    $user = "root";
    $pass = "";
    $dbname = "freelance_platform";

    try {
        $conn = new mysqli($host, $user, $pass, $dbname);
        if ($conn->connect_error) {
            throw new Exception("Ошибка подключения: " . $conn->connect_error);
        }
        return $conn;
    } catch (Exception $e) {
        die($e->getMessage());
    }
}