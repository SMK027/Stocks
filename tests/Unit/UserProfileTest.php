<?php

declare(strict_types=1);

namespace Tests\Unit;

use PHPUnit\Framework\TestCase;
use App\Models\User;
use PDO;

class UserProfileTest extends TestCase
{
    private PDO $pdo;
    private User $model;

    protected function setUp(): void
    {
        $this->pdo = new PDO('sqlite::memory:', null, null, [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        ]);

        $this->pdo->exec('CREATE TABLE users (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            username TEXT NOT NULL UNIQUE,
            firstname TEXT DEFAULT NULL,
            lastname TEXT DEFAULT NULL,
            email TEXT NOT NULL UNIQUE,
            password TEXT NOT NULL,
            global_role TEXT DEFAULT "user",
            daily_digest INTEGER NOT NULL DEFAULT 0,
            avatar TEXT DEFAULT NULL,
            bio TEXT DEFAULT NULL,
            created_at TEXT DEFAULT CURRENT_TIMESTAMP,
            updated_at TEXT DEFAULT CURRENT_TIMESTAMP
        )');

        $this->model = new User($this->pdo);
    }

    public function testUpdateProfile(): void
    {
        $id = $this->model->register('alice', 'alice@test.com', 'password123');

        $this->model->updateProfile($id, [
            'firstname' => 'Alice',
            'lastname' => 'Dupont',
            'email' => 'alice.new@test.com',
            'username' => 'alice_updated',
        ]);

        $user = $this->model->find($id);
        $this->assertSame('Alice', $user['firstname']);
        $this->assertSame('Dupont', $user['lastname']);
        $this->assertSame('alice.new@test.com', $user['email']);
        $this->assertSame('alice_updated', $user['username']);
    }

    public function testUpdateProfileIgnoresDisallowedFields(): void
    {
        $id = $this->model->register('bob', 'bob@test.com', 'password123');

        $this->model->updateProfile($id, [
            'firstname' => 'Bob',
            'global_role' => 'superadmin',
            'password' => 'hacked',
        ]);

        $user = $this->model->find($id);
        $this->assertSame('Bob', $user['firstname']);
        $this->assertSame('user', $user['global_role']);
        $this->assertTrue(password_verify('password123', $user['password']));
    }

    public function testUpdateProfilePartial(): void
    {
        $id = $this->model->register('charlie', 'charlie@test.com', 'password123');

        $this->model->updateProfile($id, ['firstname' => 'Charlie']);

        $user = $this->model->find($id);
        $this->assertSame('Charlie', $user['firstname']);
        $this->assertNull($user['lastname']);
        $this->assertSame('charlie@test.com', $user['email']);
    }

    public function testUpdateProfileEmptyDataReturnsFalse(): void
    {
        $id = $this->model->register('dave', 'dave@test.com', 'password123');

        $result = $this->model->updateProfile($id, ['global_role' => 'admin']);
        $this->assertFalse($result);
    }

    public function testUpdatePassword(): void
    {
        $id = $this->model->register('eve', 'eve@test.com', 'oldpassword');

        $this->model->updatePassword($id, 'newpassword');

        $user = $this->model->find($id);
        $this->assertTrue(password_verify('newpassword', $user['password']));
        $this->assertFalse(password_verify('oldpassword', $user['password']));
    }

    public function testEnableDailyDigest(): void
    {
        $id = $this->model->register('frank', 'frank@test.com', 'password123');

        $this->model->updateProfile($id, ['daily_digest' => 1]);

        $user = $this->model->find($id);
        $this->assertSame('1', (string)$user['daily_digest']);
    }

    public function testDisableDailyDigest(): void
    {
        $id = $this->model->register('grace', 'grace@test.com', 'password123');

        $this->model->updateProfile($id, ['daily_digest' => 1]);
        $this->model->updateProfile($id, ['daily_digest' => 0]);

        $user = $this->model->find($id);
        $this->assertSame('0', (string)$user['daily_digest']);
    }

    public function testDailyDigestDefaultIsZero(): void
    {
        $id = $this->model->register('henry', 'henry@test.com', 'password123');

        $user = $this->model->find($id);
        $this->assertSame('0', (string)$user['daily_digest']);
    }
}
