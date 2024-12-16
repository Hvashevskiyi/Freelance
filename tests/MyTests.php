<?php

require_once __DIR__ . '/../includes/crypt.php';
use PHPUnit\Framework\TestCase;

class MyTests extends TestCase
{
    private $conn;

    protected function setUp(): void
    {
        // Устанавливаем подключение к базе данных
        $host = 'localhost';
        $db = 'freelance_platform';
        $user = 'root';
        $pass = '';
        $this->conn = new mysqli($host, $user, $pass, $db);
        if ($this->conn->connect_error) {
            die('Connection failed: ' . $this->conn->connect_error);
        }
    }

    protected function tearDown(): void
    {
        // Закрываем подключение после каждого теста
        $this->conn->close();
    }

    public function testCheckUserExists()
    {

        $userId = 1; // Пример ID пользователя для теста
        $stmt = $this->conn->prepare("SELECT COUNT(*) FROM users WHERE id = ?");
        $stmt->bind_param("i", $userId);
        $stmt->execute();
        $stmt->bind_result($count);
        $stmt->fetch();
        $stmt->close();

        // Проверяем, что пользователь существует
        $this->assertGreaterThan(0, $count, "User should exist.");
    }

    public function testVigenereEncryptionAndDecryption()
    {

        ob_start();
        // Пример шифрования/дешифрования с использованием алгоритма Виженера
        $data = "Test Data";
        $key = 3;

        // Ожидаемый зашифрованный результат
        $expectedEncrypted = "V2h2dyNHZHdk";  // Важен правильный результат шифрования
        $encrypted = vigenereEncryptUser($data, $key);
        $this->assertEquals($expectedEncrypted, $encrypted, "Encryption failed.");

        // Дешифрование
        $decrypted = vigenereDecryptUser($encrypted, $key);
        $this->assertEquals($data, $decrypted, "Decryption failed.");
        ob_end_clean();  // Очищаем буфер без вывода
    }

    public function testPostVacancyErrorHandling()
    {
        // Пример данных для вакансии
        $vacancyData = ['VacancyTag' => 'Test Vacancy', 'Description' => 'Test Description', 'Salary' => 5000, 'id_company' => 28];

        try {
            // Добавляем вакансию в базу данных через подготовленный запрос
            $stmt = $this->conn->prepare("INSERT INTO vacancy (VacancyTag, Description, Salary, id_company) VALUES (?, ?, ?, ?)");
            $stmt->bind_param("ssii", $vacancyData['VacancyTag'], $vacancyData['Description'], $vacancyData['Salary'], $vacancyData['id_company']);
            $result = $stmt->execute();
            $stmt->close();

            // Проверяем, что вакансия была успешно добавлена
            $this->assertTrue($result, "Vacancy posting failed.");
        } catch (mysqli_sql_exception $e) {
            // Если возникла ошибка duplicate entry, вызываем failure
            if (strpos($e->getMessage(), 'Duplicate entry') !== false) {
                $this->fail("Failed to post vacancy: Duplicate entry detected for the unique index.");
            } else {
                // В случае других ошибок выбрасываем исключение
                throw $e; // Повторно выбрасываем исключение, если оно не связано с дубликатом
            }
        }
    }

}
?>