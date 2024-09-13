document.addEventListener('DOMContentLoaded', function() {
    const nameInput = document.getElementById('name');
    const emailInput = document.getElementById('email');
    const submitButton = document.getElementById('submitBtn');

    function checkFieldAvailability() {
        const name = nameInput.value.trim();
        const email = emailInput.value.trim();

        if (name || email) {
            // Здесь делаем запрос на сервер для проверки, существуют ли уже такие имя и email
            fetch('check_availability.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded'
                },
                body: new URLSearchParams({
                    'name': name,
                    'email': email
                })
            })
                .then(response => response.json())
                .then(data => {
                    // Обновляем сообщение и класс полей
                    if (data.nameTaken) {
                        document.getElementById('nameError').textContent = 'Имя уже занято';
                        nameInput.classList.add('error-input');
                    } else {
                        document.getElementById('nameError').textContent = '';
                        nameInput.classList.remove('error-input');
                    }

                    if (data.emailTaken) {
                        document.getElementById('emailError').textContent = 'Email уже занят';
                        emailInput.classList.add('error-input');
                    } else {
                        document.getElementById('emailError').textContent = '';
                        emailInput.classList.remove('error-input');
                    }

                    // Проверка активности кнопки отправки
                    submitButton.disabled = data.nameTaken || data.emailTaken;
                })
                .catch(error => console.error('Error:', error));
        } else {
            // Если поля пустые, очистим ошибки и активируем кнопку
            document.getElementById('nameError').textContent = '';
            document.getElementById('emailError').textContent = '';
            nameInput.classList.remove('error-input');
            emailInput.classList.remove('error-input');
            submitButton.disabled = false;
        }
    }

    nameInput.addEventListener('input', checkFieldAvailability);
    emailInput.addEventListener('input', checkFieldAvailability);

    // Проверка при загрузке страницы
    checkFieldAvailability();
});
