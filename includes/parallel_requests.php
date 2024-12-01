<?php
function performSequentialRequests($urls) {
    $responses = [];
    foreach ($urls as $url) {
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_TIMEOUT, 10);
        $responses[] = curl_exec($ch);
        curl_close($ch);
    }
    return $responses;
}

function performParallelRequests($urls) {
    $multiHandle = curl_multi_init();
    $curlHandles = [];

    // Инициализация всех запросов
    foreach ($urls as $i => $url) {
        $curlHandles[$i] = curl_init();
        curl_setopt($curlHandles[$i], CURLOPT_URL, $url);
        curl_setopt($curlHandles[$i], CURLOPT_RETURNTRANSFER, true);
        curl_setopt($curlHandles[$i], CURLOPT_TIMEOUT, 10);
        curl_multi_add_handle($multiHandle, $curlHandles[$i]);
    }

    // Выполнение всех запросов
    $running = null;
    do {
        curl_multi_exec($multiHandle, $running);
    } while ($running);

    // Получение результатов
    $responses = [];
    foreach ($curlHandles as $i => $handle) {
        $responses[$i] = curl_multi_getcontent($handle);
        curl_multi_remove_handle($multiHandle, $handle);
    }

    curl_multi_close($multiHandle);
    return $responses;
}


$urls = array_fill(0, 5, 'http://localhost/freelance/includes/db_query.php');

// Последовательные запросы
$startTimeSeq = microtime(true);
$responsesSeq = performSequentialRequests($urls);
$endTimeSeq = microtime(true);
$executionTimeSeq = $endTimeSeq - $startTimeSeq;

// Параллельные запросы
$startTimePar = microtime(true);
$responsesPar = performParallelRequests($urls);
$endTimePar = microtime(true);
$executionTimePar = $endTimePar - $startTimePar;

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Сравнение производительности запросов</title>
</head>
<body>
<h1>Сравнение производительности: последовательные vs параллельные запросы</h1>

<h2>Последовательные запросы</h2>
<p>Время выполнения: <?php echo number_format($executionTimeSeq, 4); ?> секунд</p>
<pre><?php print_r($responsesSeq); ?></pre>

<h2>Параллельные запросы</h2>
<p>Время выполнения: <?php echo number_format($executionTimePar, 4); ?> секунд</p>
<pre><?php print_r($responsesPar); ?></pre>
</body>
</html>
