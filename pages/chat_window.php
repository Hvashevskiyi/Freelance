<?php
session_start();
require_once '../includes/db.php';

if (!isset($_SESSION['user_id']) || !isset($_GET['chat_id'])) {
    die("Необходима авторизация.");
}
$currentUserId = $_SESSION['user_id'];
$chatId = intval($_GET['chat_id']);
$conn = getDbConnection();
$stmt = $conn->prepare("SELECT * FROM chats WHERE id = ? AND (user_one_id = ? OR user_two_id = ?)");
$stmt->bind_param("iii", $chatId, $currentUserId, $currentUserId);
$stmt->execute();
$chat = $stmt->get_result()->fetch_assoc();
if (!$chat) {
    die("Чат не найден.");
}
$stmt = $conn->prepare("
    SELECT m.*, u.name AS sender_name
    FROM messages m
    JOIN Users u ON m.sender_id = u.id
    WHERE m.chat_id = ?
    ORDER BY m.created_at ASC
");
$stmt->bind_param("i", $chatId);
$stmt->execute();
$messages = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
?>
<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <title>Чат</title>
    <style>
        body{ background-color: #eae0d5 ;}
        .chat-container {
            display: flex;
            height: 100vh;
            background-color: #eae0d5 ;
        }
        .chat-list {
            width: 30%;
            margin-bottom: 10px;
            background-color: #c6ac8f;
            border-radius: 8px;
            overflow-y: auto;
        }
        .chat-window {
            width: 70%;
            display: flex;
            flex-direction: column;
        }
        .messages {
            flex: 1;
            overflow-y: auto;

        }
        .message {
            margin-bottom: 10px;
            background-color: #c6ac8f;
            border-radius: 8px;
            padding: 12px;
            margin: 10px;
        }
        .message img {
            max-width: 100px;
            max-height: 100px;
        }
        .input-area {
            display: flex;
            flex-direction: column;
            gap: 10px;
            padding: 10px;
            background-color: #f9f9f9;
            border-top: 1px solid #ccc;
        }
        .input-area textarea {
            width: 100%;
            padding: 10px;
            font-size: 14px;
            border: 1px solid #ccc;
            border-radius: 5px;
            resize: none;
            min-height: 60px;
            box-sizing: border-box;
        }
        .input-area input[type="file"] {
            padding: 5px;
            font-size: 14px;
            border: 1px solid #ccc;
            border-radius: 5px;
            box-sizing: border-box;
            cursor: pointer;
        }
        .input-area button {
            padding: 10px 20px;
            background-color: #4CAF50;
            color: white;
            font-size: 14px;
            border: none;
            border-radius: 5px;
            cursor: pointer;
            transition: background-color 0.3s;
        }
        .input-area button:hover {
            background-color: #45a049;
        }
        .input-area .error-message {
            color: red;
            font-size: 12px;
            display: none;
        }
    </style>
</head>
<body>
<div class="chat-container">
    <div class="chat-list">
        <h2>Ваши чаты</h2>
        <ul>
            <?php
            $stmt = $conn->prepare("SELECT * FROM chats WHERE user_one_id = ? OR user_two_id = ?");
            $stmt->bind_param("ii", $currentUserId, $currentUserId);
            $stmt->execute();
            $chats = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
            foreach ($chats as $c) {
                $otherUserId = ($c['user_one_id'] === $currentUserId) ? $c['user_two_id'] : $c['user_one_id'];
                $stmt = $conn->prepare("SELECT name FROM Users WHERE id = ?");
                $stmt->bind_param("i", $otherUserId);
                $stmt->execute();
                $otherUser = $stmt->get_result()->fetch_assoc();
                echo "<li><a href='chat_window.php?chat_id={$c['id']}'>" . htmlspecialchars($otherUser['name']) . "</a></li>";
            }
            ?>
        </ul>
    </div>
    <script>
        document.addEventListener("DOMContentLoaded", function () {
            const chatContainer = document.getElementById("chat-messages");
            const userId = <?php echo $_SESSION['user_id']; ?>;
            const chatId = <?php echo $chatId; ?>;
            let lastMessageTime = null;
            function loadMessages() {
                console.log('Запрос на сервер отправлен...');
                fetch(`fetch_messages.php?chat_id=${chatId}&last_message_time=${lastMessageTime}`)
                    .then(response => response.json())
                    .then(data => {
                        console.log('Ответ от сервера:', data);
                        if (data.messages.length > 0) {
                            lastMessageTime = data.messages[data.messages.length - 1].created_at;
                            data.messages.forEach(message => {
                                const messageElement = document.createElement("div");
                                messageElement.className = message.sender_id === userId ? "message sent" : "message received";
                                if (message.is_file) {
                                    messageElement.innerHTML = `
                                <a href="uploads/${message.file_path}" target="_blank">${message.file_name}</a>
                            `;
                                } else {
                                    messageElement.textContent = message.message;
                                }
                                chatContainer.appendChild(messageElement);
                            });
                            chatContainer.scrollTop = chatContainer.scrollHeight;
                        }
                    })
                    .catch(error => console.error("Ошибка загрузки сообщений:", error));
            }
            setInterval(loadMessages, 2000);
            loadMessages();
        });
    </script>
    <div class="chat-window">
        <div class="messages">
            <?php foreach ($messages as $msg): ?>
                <div class="message">
                    <strong><?php echo htmlspecialchars($msg['sender_name']); ?>:</strong>
                    <p><?php echo htmlspecialchars($msg['message']); ?></p>
                    <?php if ($msg['file_path']): ?>
                        <?php if (preg_match('/\.(jpg|jpeg|png|gif)$/i', $msg['file_path'])): ?>
                            <img src="<?php echo htmlspecialchars($msg['file_path']); ?>" alt="Image">
                        <?php else: ?>
                            <a href="<?php echo htmlspecialchars($msg['file_path']); ?>" download>Скачать файл</a>
                        <?php endif; ?>
                    <?php endif; ?>
                    <span><?php echo date("d.m.Y H:i", strtotime($msg['created_at'])); ?></span>
                </div>
            <?php endforeach; ?>
        </div>
        <form class="input-area" action="send_message.php" method="POST" enctype="multipart/form-data">
            <input type="hidden" name="chat_id" value="<?php echo $chatId; ?>">
            <textarea name="message" rows="3" placeholder="Введите сообщение"></textarea>
            <input type="file" name="file">
            <button type="submit">Отправить</button>
        </form>
    </div>
</div>
</body>
</html>
