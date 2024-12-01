<?php
// Функция для шифрования с использованием шифра Виженера

ob_start();  // Start output buffering
function vigenereEncrypt($data, $key) {

    $encrypted = '';

    for ($i = 0; $i < strlen($data); $i++) {
        $dataChar = $data[$i];

        $encrypted .= chr(((ord($dataChar) + $key) % 256));
    }

    return base64_encode($encrypted);  // Возвращаем зашифрованные данные в base64
}

// Функция для обновления истории просмотров
// Функция для обновления истории просмотров
function updatePageHistory($userId, $vacancyTag, $vacancyId) {
    if (empty($vacancyTag)) {
        return; // Если vacancyTag пуст, то выходим из функции, чтобы избежать добавления некорректной записи
    }

    // Получаем текущую информацию о странице
    $currentTime = time();
    $pageInfo = [
        'title' => $vacancyTag,
        'url' => "vacancy.php?id=" . intval($vacancyId),
        'time' => $currentTime
    ];

    // Имя куки для истории
    $cookieName = "ID_{$userId}_history";

    $vigenereKey = $userId;


    // Получаем текущую историю из куки
    if (isset($_COOKIE[$cookieName])) {
        $history = json_decode(vigenereDecrypt($_COOKIE[$cookieName], $vigenereKey), true) ?: [];
    } else {
        $history = [];
    }

    // Проверяем, не совпадает ли текущая страница с последней в истории
    if (!empty($history) && $history[0]['url'] === $pageInfo['url']) {
        return; // Если последняя страница та же самая, то ничего не добавляем
    }

    // Добавляем текущую вакансию в историю
    array_unshift($history, $pageInfo);

    // Ограничиваем историю до 10 записей
    if (count($history) > 10) {
        array_pop($history);
    }

    // Шифруем и сохраняем историю в куки
    $encryptedHistory = vigenereEncrypt(json_encode($history), $vigenereKey);
    setcookie($cookieName, $encryptedHistory, time() + 60 * 60 * 24 * 30, "/");
}


// Функция для дешифрования с использованием шифра Виженера
function vigenereDecrypt($data, $key) {
    $data = base64_decode($data);  // Декодируем из base64
    $decrypted = '';

    for ($i = 0; $i < strlen($data); $i++) {
        $dataChar = $data[$i];

        $decrypted .= chr(((ord($dataChar) - $key + 256) % 256));
    }

    return $decrypted;
}

function displayHistory($userId) {
    $cookieName = "ID_{$userId}_history";
    $vigenereKey = $userId;

    // Получаем историю из куки
    if (isset($_COOKIE[$cookieName])) {
        $history = json_decode(vigenereDecrypt($_COOKIE[$cookieName], $vigenereKey), true) ?: [];
    } else {
        $history = [];
    }

    // Если история пуста
    if (empty($history)) {
        echo '<div id="historyBlock" class="history-block">Нет недавних вакансий</div>';
        return;
    }

    echo '<div id="historyBlock" class="history-block">';
    echo '<div class="history-title">Недавние вакансии</div>';
    echo '<div class="history-content">';

    foreach ($history as $index => $entry) {
        if (isset($entry['title'])) {
            $timeAgo = time() - $entry['time'];
            $minutesAgo = floor($timeAgo / 60);
            $hoursAgo = floor($minutesAgo / 60);
            $formattedTime = $hoursAgo > 0 ? "{$hoursAgo} ч. назад" : "{$minutesAgo} мин. назад";

            echo '<div class="history-item">';
            echo '<a href="' . htmlspecialchars($entry['url']) . '">' . htmlspecialchars($entry['title']) . '</a>';
            echo ' <span class="time-ago" data-time="' . $entry['time'] . '" id="time-' . $index . '">(' . $formattedTime . ')</span>';
            echo '</div>';
        }
    }

    echo '</div>';
    echo '</div>';
}
?>

<script>
    function updateTimeAgo() {
        const timeElements = document.querySelectorAll('.time-ago');

        timeElements.forEach(function (element) {
            const timeOpened = parseInt(element.getAttribute('data-time'), 10);
            const timeAgo = Math.floor((Date.now() / 1000) - timeOpened);

            const minutesAgo = Math.floor(timeAgo / 60);
            const hoursAgo = Math.floor(minutesAgo / 60);

            if (hoursAgo > 0) {
                element.textContent = `(${hoursAgo} ч. назад)`;
            } else {
                element.textContent = `(${minutesAgo} мин. назад)`;
            }
        });
    }

    // Обновляем время сразу после загрузки страницы и каждые 60 секунд
    document.addEventListener("DOMContentLoaded", updateTimeAgo);
    setInterval(updateTimeAgo, 60000);
</script>

<!-- CSS для растянутого блока с надписью "Недавние вакансии" -->
<!-- CSS для блока с закругленными краями и скрытым списком -->
<style>
    /* Убедитесь, что ваш стиль относится только к блоку истории с уникальным id */
    #historyBlock {
        position: fixed;
        bottom: 20px;
        left: 20px;
        width: auto;
        background-color: rgba(0, 0, 0, 0.8);
        color: white;
        border-radius: 8px;
        padding: 10px;
        box-shadow: 0px 4px 8px rgba(0, 0, 0, 0.4);
        font-size: 14px;
        cursor: pointer;
        text-align: right;
    }

    /* Заголовок "Недавние вакансии" */
    #historyBlock .history-title {
        font-size: 16px;
        font-weight: bold;
    }

    /* Скрытый список вакансий */
    #historyBlock .history-content {
        display: none;

        overflow-y: auto;
    }

    /* Показываем содержимое списка при наведении на блок */
    #historyBlock:hover .history-title {
        display: none;
    }

    #historyBlock:hover .history-content {
        display: block;
    }

    /* Стиль для каждой вакансии в списке */
    #historyBlock .history-item {
        margin-bottom: 8px;
    }

    #historyBlock .history-item a {
        color: #ffd700;
        text-decoration: none;
        background: none;
        padding: 0;
        margin: 0;
    }

    #historyBlock .history-item a:hover {
        text-decoration: underline;
    }

    /* Время, прошедшее с последнего просмотра */
    #historyBlock .time-ago {
        color: #bbb;
        font-size: 12px;
    }

</style>
