<?php

declare(strict_types=1);

namespace Tests\Unit;

use PHPUnit\Framework\TestCase;
use App\Models\PasswordReset;
use PDO;

class PasswordResetTest extends TestCase
{
    private PDO $pdo;
    private PasswordReset $model;

    protected function setUp(): void
    {
        $this->pdo = new PDO('sqlite::memory:', null, null, [
            PDO::ATTR_ERRMODE          => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        ]);

        $this->pdo->exec('CREATE TABLE password_resets (
            id         INTEGER PRIMARY KEY AUTOINCREMENT,
            email      TEXT NOT NULL,
            token      TEXT NOT NULL UNIQUE,
            expires_at TEXT NOT NULL,
            created_at TEXT DEFAULT CURRENT_TIMESTAMP
        )');

        $this->model = new PasswordReset($this->pdo);
    }

    public function testCreateTokenReturnsRawToken(): void
    {
        $token = $this->model->createToken('alice@test.com');
        $this->assertSame(64, strlen($token)); // bin2hex(32 bytes) = 64 hex chars
        $this->assertMatchesRegularExpression('/^[a-f0-9]{64}$/', $token);
    }

    public function testCreateTokenHashedInDatabase(): void
    {
        $token = $this->model->createToken('alice@test.com');
        $expected = hash('sha256', $token);

        $row = $this->pdo->query("SELECT token FROM password_resets LIMIT 1")->fetch();
        $this->assertSame($expected, $row['token']);
    }

    public function testFindValidByToken(): void
    {
        $token = $this->model->createToken('alice@test.com');
        $result = $this->model->findValidByToken($token);

        $this->assertNotNull($result);
        $this->assertSame('alice@test.com', $result['email']);
    }

    public function testFindValidByTokenInvalidReturnsNull(): void
    {
        $result = $this->model->findValidByToken('invalidetoken');
        $this->assertNull($result);
    }

    public function testFindValidByTokenExpiredReturnsNull(): void
    {
        $tokenHash = hash('sha256', 'expiredtoken12345678901234567890123456789012345678901234567890123');
        $this->pdo->exec("INSERT INTO password_resets (email, token, expires_at)
                          VALUES ('bob@test.com', '{$tokenHash}', '2000-01-01 00:00:00')");

        $result = $this->model->findValidByToken('expiredtoken12345678901234567890123456789012345678901234567890123');
        $this->assertNull($result);
    }

    public function testDeleteByToken(): void
    {
        $token = $this->model->createToken('alice@test.com');
        $this->model->deleteByToken($token);

        $result = $this->model->findValidByToken($token);
        $this->assertNull($result);
    }

    public function testCreateTokenReplacesExistingForSameEmail(): void
    {
        $this->model->createToken('alice@test.com');
        $this->model->createToken('alice@test.com');

        $count = $this->pdo->query("SELECT COUNT(*) as n FROM password_resets WHERE email = 'alice@test.com'")->fetch();
        $this->assertSame('1', (string)$count['n']);
    }

    public function testDeleteExpired(): void
    {
        $tokenHash = hash('sha256', 'someexpiredtoken1234567890123456789012345678901234567890123456789');
        $this->pdo->exec("INSERT INTO password_resets (email, token, expires_at)
                          VALUES ('old@test.com', '{$tokenHash}', '2000-01-01 00:00:00')");

        // Ajouter un token valide
        $this->model->createToken('valid@test.com');

        $this->model->deleteExpired();

        $rows = $this->pdo->query("SELECT COUNT(*) as n FROM password_resets")->fetch();
        $this->assertSame('1', (string)$rows['n']);

        $remaining = $this->pdo->query("SELECT email FROM password_resets")->fetch();
        $this->assertSame('valid@test.com', $remaining['email']);
    }
}
