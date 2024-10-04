document.addEventListener('DOMContentLoaded', function () {
    const registerForm = document.getElementById('registerForm');
    const emailField = document.getElementById('email');
    const passwordField = document.getElementById('password');
    const confirmPasswordField = document.getElementById('confirm_password');
    const emailError = document.getElementById('emailError'); // Обратите внимание на правильный id
    const passwordError = document.getElementById('passwordError');
    const password8 = document.getElementById('password8');
    // Функция для проверки совпадения паролей
    function checkPasswordMatch() {
        // Проверка длины пароля
        if (passwordField.value.length < 8 || confirmPasswordField.value.length < 8) {
            password8.style.display = 'block';  // Показываем сообщение, если длина меньше 8 символов
        } else {
            password8.style.display = 'none';  // Скрываем сообщение, если длина нормальная

            // Проверка совпадения паролей
            if (passwordField.value !== confirmPasswordField.value) {
                passwordError.style.display = 'block';  // Показываем сообщение о несовпадении паролей
            } else {
                passwordError.style.display = 'none';  // Скрываем сообщение, если пароли совпадают
            }
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
