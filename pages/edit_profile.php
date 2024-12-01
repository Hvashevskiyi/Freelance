<?php
session_start();
require_once '../includes/db.php';
$conn = getDbConnection();
$theme = isset($_COOKIE['theme']) ? $_COOKIE['theme'] : 'light';
if (!isset($_GET['id']) || $_SESSION['user_id'] !== intval($_GET['id'])) {
    header("Location: index.php");
    exit;
}

$userId = intval($_GET['id']);
require_once '../includes/checkUserExists.php';

// Проверяем, существует ли пользователь
if (!checkUserExists($conn, $userId)) {
    session_unset();
    session_destroy();
    header("Location: login.php");
    exit;
}

// Получаем текущие данные пользователя
try {
    $stmt = $conn->prepare("SELECT text, vacancy, image_id, role_id FROM Users WHERE id = ?");
    $stmt->bind_param("i", $userId);
    $stmt->execute();
    $user = $stmt->get_result()->fetch_assoc();
} catch (Exception $e) {
    error_log($e->getMessage());
    exit;
}

// Обработка POST-запроса
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        // Обновление текста и вакансии
        $text = $_POST['text'];
        $vacancy = isset($_POST['vacancy']) ? $_POST['vacancy'] : '';

        $updateStmt = $conn->prepare("UPDATE Users SET text = ?, vacancy = ? WHERE id = ?");
        $updateStmt->bind_param("ssi", $text, $vacancy, $userId);
        $updateStmt->execute();

        // Обработка загрузки изображения
        if (isset($_FILES['image']) && $_FILES['image']['error'] === UPLOAD_ERR_OK) {
            $image = $_FILES['image']['tmp_name'];
            $imageMimeType = mime_content_type($image);
            $imageExtension = strtolower(pathinfo($_FILES['image']['name'], PATHINFO_EXTENSION));
            $imageSize = $_FILES['image']['size'];

            // Проверяем, является ли файл изображением по MIME-типу и расширению, и не превышает ли он 10 МБ
            if (in_array($imageMimeType, ['image/jpeg', 'image/png', 'image/gif']) &&
                in_array($imageExtension, ['jpg', 'jpeg', 'png', 'gif']) &&
                $imageSize <= 10 * 1024 * 1024) {  // Ограничение на 10 МБ

                $imageData = file_get_contents($image);

                // Загружаем изображение в базу данных
                $uploadStmt = $conn->prepare("INSERT INTO images (image) VALUES (?)");
                $uploadStmt->bind_param("s", $imageData);
                $uploadStmt->execute();

                // Получаем id загруженного изображения
                $imageId = $conn->insert_id;

                // Обновляем профиль пользователя, присваиваем новое изображение
                $updateImageStmt = $conn->prepare("UPDATE Users SET image_id = ? WHERE id = ?");
                $updateImageStmt->bind_param("ii", $imageId, $userId);
                $updateImageStmt->execute();
            } else {
                echo "<p>Ошибка: можно загружать только файлы изображений (JPEG, PNG, GIF) и размером до 10 МБ.</p>";
            }
        }

        // Вызов скрипта для удаления неиспользуемых изображений
        include_once 'delete_unused_images.php';

        header("Location: profile.php?id=$userId");
        exit;
    } catch (Exception $e) {
        error_log($e->getMessage());
    }
}
?>

<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <link id="themeStylesheet" rel="stylesheet" href="../assets/styles/<?php echo $theme; ?>.css">
    <link id="SubthemeStylesheet" rel="stylesheet" href="../assets/styles/edit_profile/ep_<?php echo $theme; ?>.css">
    <title>Редактировать профиль</title>
    <script>
        function toggleTheme() {
            let currentTheme = document.body.classList.toggle('dark') ? 'dark' : 'light';
            document.cookie = `theme=${currentTheme}; path=/; max-age=31536000`;
            document.getElementById('themeStylesheet').href = `../assets/styles/${currentTheme}.css`;
            document.getElementById('SubthemeStylesheet').href = `../assets/styles/edit_profile/ep_${currentTheme}.css`;
        }

        document.addEventListener("DOMContentLoaded", function() {
            const theme = "<?php echo $theme; ?>";
            document.body.classList.toggle('dark', theme === 'dark');
        });

        // Функция для проверки формата файла и его размера
        // Функция для проверки формата файла, его размера и битости
        function validateFileFormat() {
            const fileInput = document.getElementById('image');
            const submitButton = document.getElementById('submitButton');
            const warningText = document.getElementById('formatWarning');
            const sizeWarningText = document.getElementById('sizeWarning');
            const imageErrorText = document.getElementById('imageError');
            const allowedExtensions = ['jpg', 'jpeg', 'png', 'gif'];
            const maxSize = 10 * 1024 * 1024;  // 10 МБ в байтах

            // Скрываем кнопку и сообщения об ошибке
            submitButton.style.display = 'none'; // Скрываем кнопку
            imageErrorText.style.display = 'none';
            warningText.style.display = 'none';
            sizeWarningText.style.display = 'none';

            if (fileInput.files.length > 0) {
                const file = fileInput.files[0];
                const fileExtension = file.name.split('.').pop().toLowerCase();

                // Проверка формата файла
                if (!allowedExtensions.includes(fileExtension)) {
                    warningText.style.display = 'inline';
                    return;
                }

                // Проверка размера файла
                if (file.size > maxSize) {
                    sizeWarningText.style.display = 'inline';
                    return;
                }

                // Проверка битости изображения
                const reader = new FileReader();
                reader.onload = function(e) {
                    const img = new Image();
                    img.onload = function() {
                        // Если изображение успешно загружено, показываем кнопку
                        submitButton.style.display = 'inline'; // Показываем кнопку
                    };
                    img.onerror = function() {
                        // Если изображение не удалось загрузить (битое), показываем ошибку
                        imageErrorText.style.display = 'inline';
                    };
                    img.src = e.target.result; // Устанавливаем источник изображения
                };
                reader.readAsDataURL(file); // Читаем файл как URL для проверки
            } else {
                submitButton.style.display = 'inline'; // Показываем кнопку, если файл не выбран
            }
        }

    </script>
</head>
<body>
<header>
    <button onclick="toggleTheme()">Сменить тему</button>
</header>
<h1>Редактирование профиля</h1>

<form method="POST" enctype="multipart/form-data">
    <textarea name="text" placeholder="О себе" required><?php echo htmlspecialchars($user['text']); ?></textarea>

    <?php if ($user['role_id'] == 2): ?>
        <input type="text" name="vacancy" placeholder="Вакансия" value="<?php echo htmlspecialchars($user['vacancy']); ?>" required>
    <?php endif; ?>

    <label for="image">Выберите изображение:</label>
    <input type="file" name="image" id="image" accept=".jpg,.jpeg,.png,.gif" onchange="validateFileFormat()">

    <!-- Предупреждения о неверном формате, большом размере или битости файла -->
    <span id="formatWarning" style="display: none; color: red;">Неверный формат файла. Допустимы только JPEG, PNG или GIF.</span>
    <span id="sizeWarning" style="display: none; color: red;">Файл слишком большой. Максимальный размер — 10 МБ.</span>
    <span id="imageError" style="display: none; color: red;">Изображение повреждено или не является изображением.</span>

    <button type="submit" id="submitButton">Сохранить изменения</button>
    <button type="button" onclick="window.location.href='index.php'">Вернуться назад</button>
</form>
</body>
</html>
