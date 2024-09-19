<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Список пользователей</title>
    <link rel="stylesheet" href="styles/users.css">
</head>
<body>
<div class="container">
    <h1>Список пользователей</h1>

    <form id="search-form">
        Поиск по имени: <input type="text" id="search-input" name="name">
    </form>

    <!-- Выводим данные в таблице -->
    <div id="table-container">
        <!-- Таблица будет загружена сюда динамически -->
    </div>
</div>

<!-- Всплывающая форма для редактирования пользователя -->
<div id="edit-modal" class="modal" style="display:none;">
    <div class="modal-content">
        <span id="close-modal" class="close">&times;</span>
        <h2>Редактировать пользователя</h2>
        <form id="edit-form">
            <input type="hidden" id="edit-id">
            Имя: <input type="text" id="edit-name"><br>
            Email: <input type="email" id="edit-email"><br>
            <input type="submit" value="Сохранить">
        </form>
    </div>
</div>

<script>
    document.getElementById('search-input').addEventListener('input', function() {
        const query = this.value;
        fetchTable(query);
    });

    function fetchTable(query) {
        fetch('fetch_users.php?name=' + encodeURIComponent(query))
            .then(response => response.text())
            .then(data => {
                document.getElementById('table-container').innerHTML = data;

                // Привязываем обработчик событий для кнопок удаления
                document.querySelectorAll('.delete-btn').forEach(function(button) {
                    button.addEventListener('click', function() {
                        const userId = this.getAttribute('data-id');
                        deleteUser(userId);
                    });
                });

                // Привязываем обработчик событий для кнопок редактирования
                document.querySelectorAll('.edit-btn').forEach(function(button) {
                    button.addEventListener('click', function() {
                        const userId = this.getAttribute('data-id');
                        openEditModal(userId);
                    });
                });
            })
            .catch(error => {
                console.error('Ошибка при загрузке таблицы:', error);
                document.getElementById('table-container').innerHTML = '<p>Ошибка загрузки данных.</p>';
            });
    }

    function deleteUser(userId) {
        if (confirm("Вы уверены, что хотите удалить этого пользователя?")) {
            fetch('delete_user.php?id=' + encodeURIComponent(userId))
                .then(response => response.text())
                .then(data => {
                    if (data === 'success') {
                        alert('Пользователь удален.');
                        fetchTable(''); // Обновляем таблицу после удаления
                    } else {
                        alert('Ошибка при удалении пользователя.');
                    }
                })
                .catch(error => {
                    console.error('Ошибка при удалении пользователя:', error);
                    alert('Ошибка при удалении пользователя.');
                });
        }
    }

    function openEditModal(userId) {
        // Получаем данные пользователя по id для автоподстановки в форму
        fetch('get_user.php?id=' + encodeURIComponent(userId))
            .then(response => response.json())
            .then(data => {
                document.getElementById('edit-id').value = data.id;
                document.getElementById('edit-name').value = data.name;
                document.getElementById('edit-email').value = data.email;

                document.getElementById('edit-modal').style.display = 'block';
            })
            .catch(error => {
                console.error('Ошибка получения данных пользователя:', error);
                alert('Ошибка загрузки данных пользователя.');
            });
    }

    document.getElementById('edit-form').addEventListener('submit', function(event) {
        event.preventDefault();

        const userId = document.getElementById('edit-id').value;
        const name = document.getElementById('edit-name').value;
        const email = document.getElementById('edit-email').value;

        // Отправляем обновленные данные на сервер
        fetch('update_user.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/x-www-form-urlencoded'
            },
            body: `id=${encodeURIComponent(userId)}&name=${encodeURIComponent(name)}&email=${encodeURIComponent(email)}`
        })
            .then(response => response.text())
            .then(data => {
                if (data === 'success') {
                    alert('Данные пользователя обновлены.');
                    document.getElementById('edit-modal').style.display = 'none';
                    fetchTable(''); // Обновляем таблицу после редактирования
                } else {
                    alert('Ошибка при обновлении данных.');
                }
            })
            .catch(error => {
                console.error('Ошибка при обновлении данных пользователя:', error);
                alert('Ошибка при обновлении данных пользователя.');
            });
    });

    document.getElementById('close-modal').addEventListener('click', function() {
        document.getElementById('edit-modal').style.display = 'none';
    });

    // Заполняем таблицу при загрузке страницы
    fetchTable('');
</script>
</body>
</html>
