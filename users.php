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
            })
            .catch(error => {
                console.error('Ошибка при загрузке таблицы:', error);
                document.getElementById('table-container').innerHTML = '<p>Ошибка загрузки данных.</p>';
            });
    }

    // Заполняем таблицу при загрузке страницы
    fetchTable('');
</script>
</body>
</html>
