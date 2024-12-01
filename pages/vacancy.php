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

$stmt = $conn->prepare("SELECT v.VacancyTag, v.Description, v.Salary, u.name AS author_name FROM vacancy v JOIN users u ON v.id_company = u.id WHERE v.id = ?");
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

    if (isset($_FILES['attachment'])) {
        $fileTmpPath = $_FILES['attachment']['tmp_name'];
        $fileName = $_FILES['attachment']['name'];
        $fileSize = $_FILES['attachment']['size'];
        $fileNameCmps = explode(".", $fileName);
        $fileExtension = strtolower(end($fileNameCmps));

        $allowedFileExtensions = ['pdf', 'txt', 'doc', 'docx'];
        $maxFileSize = 10 * 1024 * 1024; // 10 MB

        if (in_array($fileExtension, $allowedFileExtensions) && $fileSize <= $maxFileSize) {
            // Читаем первые байты файла для проверки сигнатуры
            $fileHandle = fopen($fileTmpPath, 'rb');
            $fileHeader = fread($fileHandle, 4); // Читаем первые 4 байта
            fclose($fileHandle);

            $isValid = false;

            if ($fileExtension === 'pdf' && $fileHeader === "%PDF") {
                $isValid = true;
            } elseif (($fileExtension === 'doc' || $fileExtension === 'docx') && substr($fileHeader, 0, 2) === "PK") {
                $isValid = true;
            } elseif ($fileExtension === 'txt') {
                // Проверка для TXT — читаемость символов
                $fileContent = file_get_contents($fileTmpPath);
                $isValid = mb_check_encoding($fileContent, 'UTF-8'); // Проверяем, что текст в кодировке UTF-8
            }

            if ($isValid) {
                // Если файл прошел все проверки
                $uploadFileDir = '../uploads/';
                $newFileName = md5(time() . $fileName) . '.' . $fileExtension;
                $dest_path = $uploadFileDir . $newFileName;

                if (move_uploaded_file($fileTmpPath, $dest_path)) {
                    $stmt = $conn->prepare("INSERT INTO applications (freelancer_id, vacancy_id, cover_letter, file_path) VALUES (?, ?, ?, ?)");
                    $stmt->bind_param("iiss", $userId, $vacancyId, $coverLetter, $dest_path);
                    if ($stmt->execute()) {
                        header("Location: vacancy.php?id=" . $vacancyId);
                        exit;
                    }
                } else {
                    echo "<script>alert('Ошибка при перемещении файла на сервер.');</script>";
                }
            } else {
                echo "<script>alert('Ошибка: файл поврежден или имеет неверный формат.');</script>";
            }
        } else {
            echo "<script>alert('Ошибка: файл должен быть PDF, TXT, DOC, DOCX и не более 10MB.');</script>";
        }
    } else {
        echo "<script>alert('Ошибка при загрузке файла.');</script>";
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

        function validateFile() {
            const fileInput = document.querySelector('input[type="file"]');
            const submitButton = document.querySelector('input[type="submit"]');
            const warningText = document.getElementById('fileWarning');
            const maxFileSize = 10 * 1024 * 1024;
            const allowedExtensions = ['pdf', 'txt', 'doc', 'docx'];
            let isValid = true;
            let errorMessage = '';

            if (fileInput.files.length > 0) {
            const file = fileInput.files[0];
            const fileExtension = file.name.split('.').pop().toLowerCase();

            if (!allowedExtensions.includes(fileExtension)) {
            errorMessage = "Ошибка: допустимые форматы файлов - PDF, TXT, DOC, DOCX.";
            isValid = false;
        } else if (file.size > maxFileSize) {
            errorMessage = "Ошибка: файл должен быть не более 10MB.";
            isValid = false;
        } else if (fileExtension === 'pdf' || fileExtension === 'doc' || fileExtension === 'docx' || fileExtension === 'txt') {
            const reader = new FileReader();
            reader.onload = function (e) {
            const fileContent = new Uint8Array(e.target.result.slice(0, 4)); // Читаем первые 4 байта

            if (fileExtension === 'pdf' && fileContent[0] !== 0x25 || fileContent[1] !== 0x50 || fileContent[2] !== 0x44 || fileContent[3] !== 0x46) {
            errorMessage = "Ошибка: файл поврежден или не является PDF.";
            isValid = false;
        } else if ((fileExtension === 'doc' || fileExtension === 'docx') && !(fileContent[0] === 0x50 && fileContent[1] === 0x4B)) {
            errorMessage = "Ошибка: файл поврежден или не является DOC/DOCX.";
            isValid = false;
        } else if (fileExtension === 'txt' && !fileContent.every((byte) => byte >= 0x20 || byte === 0x0A || byte === 0x0D)) {
            errorMessage = "Ошибка: файл поврежден или не является TXT.";
            isValid = false;
        }

            if (!isValid) {
            warningText.textContent = errorMessage;
            warningText.style.display = 'block';
            submitButton.style.display = 'none';
        } else {
            warningText.style.display = 'none';
            submitButton.style.display = 'inline-block';
        }
        };
            reader.readAsArrayBuffer(file.slice(0, 4)); // Читаем первые 4 байта
        } else {
            warningText.style.display = 'none';
            submitButton.style.display = 'inline-block';
        }
        } else {
            errorMessage = "Ошибка: файл не выбран.";
            isValid = false;
        }

            if (!isValid) {
            warningText.textContent = errorMessage;
            warningText.style.display = 'block';
            submitButton.style.display = 'none';
        }
        }


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

    <?php if ($isFreelancer && !$hasApplied): ?>
        <form method="POST" enctype="multipart/form-data" onsubmit="validateFile();">
            <textarea name="cover_letter" placeholder="Мотивационное письмо" required></textarea>
            <label for="attachment">Прикрепить файл (PDF, TXT, DOC, DOCX):</label>
            <input type="file" name="attachment" id="attachment"accept=".pdf,.txt,.doc,.docx" onchange="validateFile(); checkFileSelection(); ">
            <div id="fileWarning" style="color: red; display: none;"></div>
            <input type="submit"  style="display: none" value="Отправить отклик" id="submitButton">
        </form>

    <?php elseif ($hasApplied): ?>
        <p>Вы уже откликнулись на эту вакансию.</p>
    <?php else: ?>
        <p>Вы должны быть фрилансером, чтобы откликнуться на вакансию.</p>
    <?php endif; ?>
</div>

</body>
</html>
