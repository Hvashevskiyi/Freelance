<?php
session_start();
require_once '../includes/db.php';
require_once 'freelance_crypt.php';

if (!isset($_GET['id'])) {
    header("Location: index.php");
    exit;
}

$vacancyId = intval($_GET['id']);
$conn = getDbConnection();
$theme = isset($_COOKIE['theme']) ? $_COOKIE['theme'] : 'light';

$stmt = $conn->prepare("SELECT v.VacancyTag, v.Description, v.Salary, v.views, u.name AS author_name FROM vacancy v JOIN users u ON v.id_company = u.id WHERE v.id = ?");
$stmt->bind_param("i", $vacancyId);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 0) {
    header("Location: index.php");
    exit;
}

$vacancy = $result->fetch_assoc();
$userRole = 0;
$isFreelancer = false;
$hasApplied = false;
$views = $vacancy['views']+1;
$stmt = $conn->prepare("UPDATE vacancy SET views = ? WHERE id = ?");
$stmt->bind_param("ii", $views,$vacancyId);
$stmt->execute();
if (isset($_SESSION['user_id']) ) {
    $userId = $_SESSION['user_id'];

    updatePageHistory($userId, $vacancy['VacancyTag'], $vacancyId);


    $stmt = $conn->prepare("SELECT role_id FROM Users WHERE id = ?");
    $stmt->bind_param("i", $userId);
    $stmt->execute();
    $userRole = $stmt->get_result()->fetch_assoc()['role_id'];

    if ($userRole == 2) {
        updatePageHistory($userId, $vacancy['VacancyTag'], $vacancyId);
        $isFreelancer = true;

        $stmt = $conn->prepare("SELECT * FROM applications WHERE freelancer_id = ? AND vacancy_id = ?");
        $stmt->bind_param("ii", $userId, $vacancyId);
        $stmt->execute();
        $applicationResult = $stmt->get_result();
        $hasApplied = $applicationResult->num_rows > 0;
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && $isFreelancer && !$hasApplied) {
    $coverLetter = $_POST['cover_letter'];
    $fileData = $_POST['fileData'] ?? null;

    if ($fileData) {
        // Декодируем файл из Base64
        list($type, $data) = explode(';', $fileData);
        list(, $data) = explode(',', $data);
        $data = base64_decode($data);

        // Проверяем сигнатуру файла
        $isValidFile = false;
        if (substr($data, 0, 4) === '%PDF') {
            $isValidFile = true; // PDF-файл
        } elseif (substr($data, 0, 2) === 'PK') {
            $isValidFile = true; // DOCX/ZIP-файл
        } elseif (mb_detect_encoding($data, 'UTF-8', true)) {
            $isValidFile = true; // TXT-файл
        }

        if (!$isValidFile) {
            echo "<script>alert('Ошибка: файл повреждён.');</script>";
            exit;
        }

        // Сохраняем файл на сервере
        $uploadFileDir = '../uploads/';
        do {
            // Генерируем уникальное имя с исходным расширением
            $originalExtension = pathinfo($_FILES['attachment']['name'], PATHINFO_EXTENSION);
            $newFileName = md5(uniqid(time(), true)) . '.' . $originalExtension;
            $dest_path = $uploadFileDir . $newFileName;
        } while (file_exists($dest_path));
        
           try{
               if (file_put_contents($dest_path, $data)) {
               $stmt = $conn->prepare("INSERT INTO applications (freelancer_id, vacancy_id, cover_letter, file_path) VALUES (?, ?, ?, ?)");
               $stmt->bind_param("iiss", $userId, $vacancyId, $coverLetter, $dest_path);
               if ($stmt->execute()) {
                   echo "<script>
            localStorage.removeItem('uploadedFile');
            window.location.href = 'index.php'; // Перенаправляем на страницу профиля
          </script>";
                   exit;
               }
               }
           } catch(Exception $e){
               echo "<script>alert('Ошибка при сохранении файла.');</script>";
               exit;
           }

    } else {
        echo "<script>alert('Ошибка: файл отсутствует.');
      localStorage.removeItem('uploadedFile');
   window.location.href = 'index.php'; </script>";
        exit;
    }

}
?>

<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <link id="themeStylesheet" rel="stylesheet" href="../assets/styles/<?php echo $theme; ?>.css">
    <link id="SubthemeStylesheet" rel="stylesheet" href="../assets/styles/vacancy/vacancy_<?php echo $theme; ?>.css">
    <title><?php echo htmlspecialchars($vacancy['VacancyTag']); ?></title>
    <script>
        function toggleTheme() {
            let currentTheme = document.body.classList.toggle('dark') ? 'dark' : 'light';
            document.cookie = `theme=${currentTheme}; path=/; max-age=31536000`;
            document.getElementById('themeStylesheet').href = `../assets/styles/${currentTheme}.css`;
            document.getElementById('SubthemeStylesheet').href = `../assets/styles/vacancy/vacancy_${currentTheme}.css`;
        }

        document.addEventListener("DOMContentLoaded", function() {
            const theme = "<?php echo $theme; ?>";
            document.body.classList.toggle('dark', theme === 'dark');
        });

        document.addEventListener('DOMContentLoaded', function() {
            const form = document.querySelector('form');
            const fileInput = document.getElementById('attachment');
            const fileWarning = document.getElementById('fileWarning');
            const submitButton = document.getElementById('submitButton');
            const fileDataInput = document.getElementById('fileData');
            const allowedExtensions = ['pdf', 'txt', 'doc', 'docx'];
            const maxFileSize = 10 * 1024 * 1024; // 10 MB

            fileInput.addEventListener('change', () => {
                const file = fileInput.files[0];
                fileWarning.style.display = 'none';
                submitButton.style.display = 'none';

                if (!file) {
                    fileWarning.textContent = 'Ошибка: файл не выбран.';
                    fileWarning.style.display = 'block';
                    return;
                }

                const fileExtension = file.name.split('.').pop().toLowerCase();
                if (!allowedExtensions.includes(fileExtension)) {
                    fileWarning.textContent = 'Ошибка: допустимые форматы файлов - PDF, TXT, DOC, DOCX.';
                    fileWarning.style.display = 'block';
                    return;
                }

                if (file.size > maxFileSize) {
                    fileWarning.textContent = 'Ошибка: файл должен быть не более 10MB.';
                    fileWarning.style.display = 'block';
                    return;
                }

                const reader = new FileReader();
                reader.onload = function (e) {
                    const fileContent = e.target.result.split(',')[1]; // Получаем только Base64-данные
                    const decodedData = atob(fileContent); // Декодируем Base64 в бинарные данные

                    // Проверяем сигнатуры файла
                    let isValidFile = false;
                    if (fileExtension === 'pdf' && decodedData.startsWith('%PDF')) {
                        isValidFile = true; // PDF-файл
                    } else if ((fileExtension === 'doc' || fileExtension === 'docx') && decodedData.startsWith('PK')) {
                        isValidFile = true; // DOCX/ZIP-файл
                    } else if (fileExtension === 'txt' && decodedData.trim().length > 0) {
                        isValidFile = true; // TXT-файл
                    }

                    if (!isValidFile) {
                        fileWarning.textContent = 'Ошибка: файл повреждён.';
                        fileWarning.style.display = 'block';
                        return;
                    }

                    // Если файл валидный, сохраняем его в скрытое поле
                    fileDataInput.value = e.target.result;
                    submitButton.style.display = 'inline-block';
                };

                reader.readAsDataURL(file);
            });

            form.addEventListener('submit', (event) => {
                if (!fileDataInput.value) {
                    fileWarning.textContent = 'Ошибка: файл отсутствует.';
                    fileWarning.style.display = 'block';
                    event.preventDefault();
                }
            });
        });


        function checkFileSelection() {
            const fileInput = document.getElementById('attachment');
            const submitButton = document.getElementById('submitButton');

            if (fileInput.files.length === 0) {
                submitButton.style.display = 'none'; // Скрыть кнопку
            } else {
                submitButton.style.display = 'inline-block'; // Показать кнопку
            }
        }

    </script>

</head>
<body>
<header>
    <button onclick="toggleTheme()">Сменить тему</button>
    <button onclick="window.location.href='index.php'">На главную</button>
    <?php if ($userRole == 2): ?>
        <?php displayHistory($userId); ?>
        <button onclick="window.location.href='applications.php'">Мои отклики</button>
    <?php endif; ?>
    <?php if (isset($_SESSION['user_id'])): ?>
        <button onclick="window.location.href='profile.php?id=<?php echo $_SESSION['user_id']; ?>'">
            <?php echo htmlspecialchars($_SESSION['username']); ?>
        </button>
    <?php endif; ?>
</header>

<div class="vacancy_container">
    <h1><?php echo htmlspecialchars($vacancy['VacancyTag']); ?></h1>
    <p><?php echo nl2br(htmlspecialchars($vacancy['Description'])); ?></p>
    <p><strong>Зарплата: </strong><?php echo htmlspecialchars($vacancy['Salary']); ?> руб.</p>
    <p><strong>Автор вакансии: </strong><?php echo htmlspecialchars($vacancy['author_name']); ?></p>
    <p><strong>Кол-во просмотров: </strong><?php echo htmlspecialchars($vacancy['views']); ?></p>

    <?php if ($isFreelancer && !$hasApplied): ?>
        <form method="POST" enctype="multipart/form-data">
            <textarea name="cover_letter" placeholder="Мотивационное письмо" required></textarea>
            <label for="attachment" class="file-upload-label">
                <span class="label-text">Выберите файл</span>
                <input type="file" name="attachment" id="attachment" accept=".pdf,.txt,.doc,.docx" onchange="validateFileAndStore()" style="display: none;">
            </label>

            <div id="fileWarning" style="color: red; display: none;"></div>
            <input type="hidden" id="fileData" name="fileData">
            <input type="submit" style="display: none" value="Отправить отклик" id="submitButton">
        </form>


    <?php elseif ($hasApplied): ?>
        <p>Вы уже откликнулись на эту вакансию.</p>
    <?php else: ?>
        <p>Вы должны быть фрилансером, чтобы откликнуться на вакансию.</p>
    <?php endif; ?>
</div>

</body>
</html>

