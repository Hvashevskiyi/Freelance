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

<!-- Модальное окно -->
<div id="edit-modal" class="modal">
    <div class="modal-content">
        <span id="close-modal" class="close">&times;</span>
        <h2>Редактировать пользователя</h2>
        <form id="edit-form">
            <input type="hidden" id="edit-id" name="id">
            <label for="edit-name">Имя:</label>
            <input type="text" id="edit-name" name="name" required>
            <label for="edit-email">Email:</label>
            <input type="email" id="edit-email" name="email" required>
            <button type="submit">Сохранить</button>
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
                attachEventListeners();
            })
            .catch(error => {
                console.error('Ошибка при загрузке таблицы:', error);
                document.getElementById('table-container').innerHTML = '<p>Ошибка загрузки данных.</p>';
            });
    }

    function attachEventListeners() {
        document.querySelectorAll('.edit-btn').forEach(button => {
            button.addEventListener('click', function() {
                const userId = this.getAttribute('data-id');
                openEditModal(userId);
            });
        });

        document.querySelectorAll('.delete-btn').forEach(button => {
            button.addEventListener('click', function() {
                const userId = this.getAttribute('data-id');
                deleteUser(userId);
            });
        });
    }

    function openEditModal(userId) {
        fetch('get_user.php?id=' + encodeURIComponent(userId))
            .then(response => response.json())
            .then(user => {
                document.getElementById('edit-id').value = user.id;
                document.getElementById('edit-name').value = user.name;
                document.getElementById('edit-email').value = user.email;
                document.getElementById('edit-modal').style.display = 'block';
            })
            .catch(error => {
                console.error('Ошибка при загрузке данных пользователя:', error);
            });
    }

    document.getElementById('edit-form').addEventListener('submit', function(event) {
        event.preventDefault();
        const formData = new FormData(this);

        fetch('update_user.php', {
            method: 'POST',
            body: formData
        })
            .then(response => response.text())
            .then(result => {
                if (result === 'success') {
                    fetchTable(document.getElementById('search-input').value);
                    document.getElementById('edit-modal').style.display = 'none';
                } else {
                    alert('Ошибка обновления данных.');
                }
            })
            .catch(error => {
                console.error('Ошибка при обновлении данных:', error);
            });
    });

    document.getElementById('close-modal').addEventListener('click', function() {
        document.getElementById('edit-modal').style.display = 'none';
    });

    window.addEventListener('click', function(event) {
        if (event.target === document.getElementById('edit-modal')) {
            document.getElementById('edit-modal').style.display = 'none';
        }
    });

    // Заполняем таблицу при загрузке страницы
    fetchTable('');
</script>
</body>
</html>
