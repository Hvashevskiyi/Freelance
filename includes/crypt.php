<?php
function vigenereEncryptUser($data, $key) {
    $encrypted = '';

    for ($i = 0; $i < strlen($data); $i++) {
        $dataChar = $data[$i];
        $encrypted .= chr(((ord($dataChar) + $key) % 256));
    }

    return base64_encode($encrypted);  // Возвращаем зашифрованные данные в base64
}
function vigenereDecryptUser($data, $key) {
    $data = base64_decode($data);  // Декодируем из base64
    $decrypted = '';

    for ($i = 0; $i < strlen($data); $i++) {
        $dataChar = $data[$i];

        $decrypted .= chr(((ord($dataChar) - $key + 256) % 256));
    }

    return $decrypted;
}
?>