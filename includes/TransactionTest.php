<?php

use PHPUnit\Framework\TestCase;

require_once 'db.php';
require_once 'transaction_page.php'; // Путь к функции transferFunds

class TransactionTest extends TestCase {
    private $conn;

    protected function setUp(): void {
        $this->conn = getDbConnection();

        // Начало тестовой транзакции
        $this->conn->begin_transaction();

    }

    protected function tearDown(): void {
        // Откат изменений
        $this->conn->rollback();
        $this->conn->close();
    }

    public function testSuccessfulTransaction() {
        $result = transferFunds(1, 2, 200);
        $this->assertEquals("Перевод успешно выполнен.", $result);

        // Проверка балансов
        $stmt = $this->conn->query("SELECT balance FROM accounts WHERE id = 1");
        $fromBalance = $stmt->fetch_assoc()['balance'];
        $this->assertEquals(800, $fromBalance);

        $stmt = $this->conn->query("SELECT balance FROM accounts WHERE id = 2");
        $toBalance = $stmt->fetch_assoc()['balance'];
        $this->assertEquals(700, $toBalance);
    }

    public function testInsufficientFunds() {
        $result = transferFunds(1, 2, 2000);
        $this->assertStringContainsString("Ошибка: Недостаточно средств", $result);

        // Проверка, что баланс не изменился
        $stmt = $this->conn->query("SELECT balance FROM accounts WHERE id = 1");
        $fromBalance = $stmt->fetch_assoc()['balance'];
        $this->assertEquals(1000, $fromBalance);

        $stmt = $this->conn->query("SELECT balance FROM accounts WHERE id = 2");
        $toBalance = $stmt->fetch_assoc()['balance'];
        $this->assertEquals(500, $toBalance);
    }
}
