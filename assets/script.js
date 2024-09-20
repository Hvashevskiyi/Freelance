document.addEventListener('DOMContentLoaded', function () {
    const registerForm = document.getElementById('registerForm');
    const emailField = document.getElementById('email');
    const passwordField = document.getElementById('password');
    const confirmPasswordField = document.getElementById('confirm_password');
    const emailError = document.getElementById('emailError'); // Обратите внимание на правильный id
    const passwordError = document.getElementById('passwordError');

    // Функция для проверки совпадения паролей
    function checkPasswordMatch() {
        if (passwordField.value !== confirmPasswordField.value) {
            passwordError.style.display = 'block';
        } else {
            passwordError.style.display = 'none';
        }
    }

    // Обработчики событий для паролей
    passwordField.addEventListener('input', checkPasswordMatch);
    confirmPasswordField.addEventListener('input', checkPasswordMatch);

    // Проверка занятости почты через AJAX
    emailField.addEventListener('input', function () {
        const email = emailField.value;
        if (email) {
            const xhr = new XMLHttpRequest();
            xhr.open('POST', 'check_email.php', true);
            xhr.setRequestHeader('Content-Type', 'application/x-www-form-urlencoded');
            xhr.onload = function () {
                if (xhr.responseText === 'taken') {
                    emailError.style.display = 'block';
                } else {
                    emailError.style.display = 'none';
                }
            };
            xhr.send('email=' + encodeURIComponent(email));
        } else {
            emailError.style.display = 'none'; // Скрываем, если поле пустое
        }
    });

    // Проверка перед отправкой формы
    registerForm.addEventListener('submit', function (event) {
        if (passwordField.value !== confirmPasswordField.value || emailError.style.display === 'block') {
            event.preventDefault(); // Блокировать отправку формы
        }
    });
});
