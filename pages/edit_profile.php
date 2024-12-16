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
if (!checkUserExists($conn, $userId)) {
    session_unset();
    session_destroy();
    header("Location: login.php");
    exit;
}
try {
    $stmt = $conn->prepare("SELECT text, vacancy, image_id, role_id FROM Users WHERE id = ?");
    $stmt->bind_param("i", $userId);
    $stmt->execute();
    $user = $stmt->get_result()->fetch_assoc();
} catch (Exception $e) {
    error_log($e->getMessage());
    exit;
}
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        $text = $_POST['text'];
        $vacancy = isset($_POST['vacancy']) ? $_POST['vacancy'] : '';
        $updateStmt = $conn->prepare("UPDATE Users SET text = ?, vacancy = ? WHERE id = ?");
        $updateStmt->bind_param("ssi", $text, $vacancy, $userId);
        $updateStmt->execute();
        if (isset($_POST['imageData'])) {
            $imageData = $_POST['imageData'];
            if (preg_match('/^data:image\/(jpeg|png|gif);base64,/', $imageData, $matches)) {
                $imageData = substr($imageData, strpos($imageData, ',') + 1);
                $imageData = base64_decode($imageData);
                $uploadStmt = $conn->prepare("INSERT INTO images (image) VALUES (?)");
                $uploadStmt->bind_param("s", $imageData);
                $uploadStmt->execute();
                $imageId = $conn->insert_id;
                $updateImageStmt = $conn->prepare("UPDATE Users SET image_id = ? WHERE id = ?");
                $updateImageStmt->bind_param("ii", $imageId, $userId);
                $updateImageStmt->execute();
            } else {
                echo "<p>Ошибка: Неверный формат изображения.</p>";
            }
        }
        echo "<script>
                localStorage.removeItem('profileImage');
                window.location.href = 'profile.php?id=$userId'; // Перенаправляем на страницу профиля
              </script>";
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
        document.addEventListener('DOMContentLoaded', function () {
            const form = document.querySelector('form');
            if (form) {
                form.addEventListener('submit', function (event) {
                    const imageData = localStorage.getItem('profileImage');
                    if (imageData) {
                        document.getElementById('imageData').value = imageData;
                    }
                });
            } else {
                console.error('Форма не найдена.');
            }
        });
        function validateFileFormat() {
            const fileInput = document.getElementById('image');
            const submitButton = document.getElementById('submitButton');
            const warningText = document.getElementById('formatWarning');
            const sizeWarningText = document.getElementById('sizeWarning');
            const imageErrorText = document.getElementById('imageError');
            const allowedExtensions = ['jpg', 'jpeg', 'png', 'gif'];
            const maxSize = 10 * 1024 * 1024;
            submitButton.style.display = 'none';
            imageErrorText.style.display = 'none';
            warningText.style.display = 'none';
            sizeWarningText.style.display = 'none';
            if (fileInput.files.length > 0) {
                const file = fileInput.files[0];
                const fileExtension = file.name.split('.').pop().toLowerCase();
                if (!allowedExtensions.includes(fileExtension)) {
                    warningText.style.display = 'inline';
                    return;
                }
                if (file.size > maxSize) {
                    sizeWarningText.style.display = 'inline';
                    return;
                }
                const reader = new FileReader();
                reader.onload = function(e) {
                    const img = new Image();
                    img.onload = function() {
                        localStorage.setItem('profileImage', e.target.result);
                        fileInput.value = '';
                        submitButton.style.display = 'inline';
                    };
                    img.onerror = function() {
                        imageErrorText.style.display = 'inline';
                    };
                    img.src = e.target.result;
                };
                reader.readAsDataURL(file);
            } else {
                submitButton.style.display = 'inline';
            }
        }
        document.querySelector('form').addEventListener('submit', function(event) {
            const imageData = localStorage.getItem('profileImage');
            if (imageData) {
                document.getElementById('imageData').value = imageData;
            }
        });
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
    <label for="image" class="file-upload-label">
        <span class="label-text">Выберите файл</span>
        <input type="file" name="image" id="image" accept=".jpg,.jpeg,.png,.gif" onchange="validateFileFormat()" style="display: none;">
    </label>
    <input type="hidden" name="imageData" id="imageData" />
    <span id="formatWarning" style="display: none; color: red;">Неверный формат файла. Допустимы только JPEG, PNG или GIF.</span>
    <span id="sizeWarning" style="display: none; color: red;">Файл слишком большой. Максимальный размер — 10 МБ.</span>
    <span id="imageError" style="display: none; color: red;">Изображение повреждено или не является изображением.</span>
    <button type="submit" id="submitButton">Сохранить изменения</button>
    <button type="button" onclick="window.location.href='index.php'">Вернуться назад</button>
</form>
</body>
</html>
