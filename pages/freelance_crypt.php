<?php
ob_start();
function vigenereEncrypt($data, $key) {
    $encrypted = '';
    for ($i = 0; $i < strlen($data); $i++) {
        $dataChar = $data[$i];
        $encrypted .= chr(((ord($dataChar) + $key) % 256));
    }
    return base64_encode($encrypted);
}
function updatePageHistory($userId, $vacancyTag, $vacancyId) {
    if (empty($vacancyTag)) {
        return;
    }
    $currentTime = time();
    $pageInfo = [
        'title' => $vacancyTag,
        'url' => "vacancy.php?id=" . intval($vacancyId),
        'time' => $currentTime
    ];
    $cookieName = "ID_{$userId}_history";
    $vigenereKey = $userId;
    if (isset($_COOKIE[$cookieName])) {
        $history = json_decode(vigenereDecrypt($_COOKIE[$cookieName], $vigenereKey), true) ?: [];
    } else {
        $history = [];
    }
    if (!empty($history) && $history[0]['url'] === $pageInfo['url']) {
        return;
    }
    array_unshift($history, $pageInfo);
    if (count($history) > 10) {
        array_pop($history);
    }
    $encryptedHistory = vigenereEncrypt(json_encode($history), $vigenereKey);
    setcookie($cookieName, $encryptedHistory, time() + 60 * 60 * 24 * 30, "/");
}
function vigenereDecrypt($data, $key) {
    $data = base64_decode($data);
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
    if (isset($_COOKIE[$cookieName])) {
        $history = json_decode(vigenereDecrypt($_COOKIE[$cookieName], $vigenereKey), true) ?: [];
    } else {
        $history = [];
    }
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
    document.addEventListener("DOMContentLoaded", updateTimeAgo);
    setInterval(updateTimeAgo, 60000);
</script>
<style>
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
    #historyBlock .history-title {
        font-size: 16px;
        font-weight: bold;
    }
    #historyBlock .history-content {
        display: none;

        overflow-y: auto;
    }
    #historyBlock:hover .history-title {
        display: none;
    }
    #historyBlock:hover .history-content {
        display: block;
    }
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
    #historyBlock .time-ago {
        color: #bbb;
        font-size: 12px;
    }
</style>
