<?php
include_once '../includes/crypt.php';
ob_start();  // Start output buffering
// Функция для шифрования с использованием шифра Виженера

// Функция для обновления истории пользователей
function updateUserHistory($userId, $username, $userIdVisited) {
    if (empty($username)) {
        return; // Если username пуст, то выходим из функции, чтобы избежать добавления некорректной записи
    }

    // Получаем текущую информацию о странице
    $currentTime = time();
    $pageInfo = [
        'username' => $username,
        'url' => "profile.php?id=" . intval($userIdVisited),
        'time' => $currentTime
    ];

    // Имя куки для истории
    $cookieName = "ID_{$userId}_user_history";
    $vigenereKey = $userId;

    // Получаем текущую историю из куки
    if (isset($_COOKIE[$cookieName])) {
        $history = json_decode(vigenereDecryptUser($_COOKIE[$cookieName], $vigenereKey), true) ?: [];
    } else {
        $history = [];
    }

    // Проверяем, не совпадает ли текущая страница с последней в истории
    if (!empty($history) && $history[0]['url'] === $pageInfo['url']) {
        return; // Если последняя страница та же самая, то ничего не добавляем
    }

    // Добавляем текущего пользователя в историю
    array_unshift($history, $pageInfo);

    // Ограничиваем историю до 10 записей
    if (count($history) > 10) {
        array_pop($history);
    }

    // Шифруем и сохраняем историю в куки
    $encryptedHistory = vigenereEncryptUser(json_encode($history), $vigenereKey);
    setcookie($cookieName, $encryptedHistory, time() + 60 * 60 * 24 * 30, "/");

}
function displayUserHistory($userId) {
    $cookieName = "ID_{$userId}_user_history";
    $vigenereKey = $userId;

    // Получаем историю из куки
    if (isset($_COOKIE[$cookieName])) {
        $history = json_decode(vigenereDecryptUser($_COOKIE[$cookieName], $vigenereKey), true) ?: [];
    } else {
        $history = [];
    }

    // Если история пуста
    if (empty($history)) {
        echo '<div id="UserhistoryBlock" class="Userhistory-block">Нет недавних пользователей</div>';
        return;
    }

    echo '<div id="UserhistoryBlock" class="Userhistory-block">';
    echo '<div class="Userhistory-title">Недавние пользователи</div>';
    echo '<div class="Userhistory-content">';

    foreach ($history as $index => $entry) {
        if (isset($entry['username'])) {
            $timeAgo = time() - $entry['time'];
            $minutesAgo = floor($timeAgo / 60);
            $hoursAgo = floor($minutesAgo / 60);
            $formattedTime = $hoursAgo > 0 ? "{$hoursAgo} ч. назад" : "{$minutesAgo} мин. назад";

            echo '<div class="Userhistory-item">';
            echo '<a href="' . htmlspecialchars($entry['url']) . '">' . htmlspecialchars($entry['username']) . '</a>';
            echo ' <span class="Usertime-ago" data-time="' . $entry['time'] . '" id="time-' . $index . '">(' . $formattedTime . ')</span>';
            echo '</div>';
        }
    }

    echo '</div>';
    echo '</div>';
}


?>
<script>
    function updateTimeAgoUser() {
        const timeElements = document.querySelectorAll('.Usertime-ago');

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
  // Очищаем буфер без вывода

</script>
<style>
    /* Стиль для блока истории с уникальным id */
    #UserhistoryBlock {
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

    /* Заголовок "Недавние пользователи" */
    #UserhistoryBlock .Userhistory-title {
        font-size: 16px;
        font-weight: bold;
    }

    /* Скрытый список пользователей */
    #UserhistoryBlock .Userhistory-content {
        display: none;
        overflow-y: auto;
    }

    /* Показываем содержимое списка при наведении на блок */
    #UserhistoryBlock:hover .Userhistory-title {
        display: none;
    }

    #UserhistoryBlock:hover .Userhistory-content {
        display: block;
    }

    /* Стиль для каждого пользователя в списке */
    #UserhistoryBlock .Userhistory-item {
        margin-bottom: 8px;
    }

    #UserhistoryBlock .Userhistory-item a {
        color: #ffd700;
        text-decoration: none;
        background: none;
        padding: 0;
        margin: 0;
    }

    #UserhistoryBlock .Userhistory-item a:hover {
        text-decoration: underline;
    }

    /* Время, прошедшее с последнего посещения */
    #UserhistoryBlock .Usertime-ago {
        color: #bbb;
        font-size: 12px;
    }

</style>