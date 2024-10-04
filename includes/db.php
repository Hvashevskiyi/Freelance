<?php
function getDbConnection() {
    $host = "localhost";
    $user = "root";
    $pass = "";
    $dbname = "freelance_platform";

    try {
        $conn = new mysqli($host, $user, $pass, $dbname);

        // Установка обработки ошибок с помощью исключений
        mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);

        if ($conn->connect_error) {
            // Перенаправление на страницу ошибки при сбое подключения
            header("Location: db_error.php");
            exit();
        }
        return $conn;
    } catch (mysqli_sql_exception $e) {
        // Если возникает ошибка, перенаправляем на страницу ошибки
        header("Location: db_error.php");
        exit();
    }
}
?>
