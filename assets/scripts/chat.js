// assets/scripts/chat.js

document.addEventListener("DOMContentLoaded", function() {
    const chatWindow = document.getElementById("chat-window");
    const messageForm = document.getElementById("message-form");
    const messageInput = messageForm.querySelector("textarea");
    const fileInput = messageForm.querySelector("input[type='file']");

    // Функция для обновления сообщений
    function updateMessages() {
        const chatId = new URLSearchParams(window.location.search).get('id');

        fetch(`get_messages.php?chat_id=${chatId}`)
            .then(response => response.json())
            .then(messages => {
                const messagesContainer = chatWindow.querySelector(".messages");
                messagesContainer.innerHTML = ''; // Очищаем сообщения
                messages.forEach(message => {
                    const messageElement = document.createElement('div');
                    messageElement.classList.add('message');

                    const avatarElement = document.createElement('img');
                    avatarElement.classList.add('message-avatar');
                    avatarElement.src = `get_image.php?id=${message.user_id}`; // Получаем изображение отправителя

                    const contentElement = document.createElement('div');
                    contentElement.classList.add('message-content');

                    const messageText = document.createElement('p');
                    messageText.innerHTML = message.message;

                    contentElement.appendChild(messageText);

                    if (message.message_type === 'image') {
                        const imageElement = document.createElement('img');
                        imageElement.src = message.file_path;
                        contentElement.appendChild(imageElement);
                    } else if (message.message_type === 'file') {
                        const fileLink = document.createElement('a');
                        fileLink.href = message.file_path;
                        fileLink.textContent = 'Скачать файл';
                        contentElement.appendChild(fileLink);
                    }

                    messageElement.appendChild(avatarElement);
                    messageElement.appendChild(contentElement);
                    messagesContainer.appendChild(messageElement);
                });
                chatWindow.scrollTop = chatWindow.scrollHeight; // Прокручиваем вниз
            });
    }

    // Отправка сообщения
    messageForm.addEventListener("submit", function(event) {
        event.preventDefault();

        const chatId = new URLSearchParams(window.location.search).get('id');
        const formData = new FormData();
        formData.append('chat_id', chatId);
        formData.append('message', messageInput.value);

        // Добавляем файл, если он выбран
        if (fileInput.files.length > 0) {
            formData.append('file', fileInput.files[0]);
        }

        fetch('chat_handler.php', {
            method: 'POST',
            body: formData
        }).then(response => {
            if (response.ok) {
                messageInput.value = ''; // Очищаем поле ввода
                fileInput.value = ''; // Очищаем файл
                updateMessages(); // Обновляем сообщения
            }
        });
    });

    // Обновление сообщений каждую секунду
    setInterval(updateMessages, 100);

    // Начальная загрузка сообщений
    updateMessages();
});
