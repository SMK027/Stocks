<?php

declare(strict_types=1);

namespace Tests\Unit;

use PHPUnit\Framework\TestCase;
use App\Models\Space;
use PDO;

class SpaceTest extends TestCase
{
    private PDO $pdo;
    private Space $model;

    protected function setUp(): void
    {
        $this->pdo = new PDO('sqlite::memory:', null, null, [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        ]);

        $this->pdo->exec('CREATE TABLE users (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            username TEXT NOT NULL,
            email TEXT NOT NULL,
            password TEXT NOT NULL,
            global_role TEXT DEFAULT "user"
        )');

        $this->pdo->exec('CREATE TABLE spaces (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            name TEXT NOT NULL,
            description TEXT,
            created_by INTEGER NOT NULL,
            created_at TEXT DEFAULT CURRENT_TIMESTAMP,
            updated_at TEXT DEFAULT CURRENT_TIMESTAMP
        )');

        $this->pdo->exec('CREATE TABLE space_members (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            space_id INTEGER NOT NULL,
            user_id INTEGER NOT NULL,
            role TEXT NOT NULL DEFAULT "membre",
            created_at TEXT DEFAULT CURRENT_TIMESTAMP,
            UNIQUE(space_id, user_id)
        )');

        // Créer des utilisateurs de test
        $this->pdo->exec("INSERT INTO users (username, email, password) VALUES ('alice', 'alice@test.com', 'hash')");
        $this->pdo->exec("INSERT INTO users (username, email, password) VALUES ('bob', 'bob@test.com', 'hash')");

        $this->model = new Space($this->pdo);
    }

    public function testCreateWithOwner(): void
    {
        $spaceId = $this->model->createWithOwner(['name' => 'Mon Espace', 'description' => 'Test'], 1);

        $this->assertGreaterThan(0, $spaceId);
        $space = $this->model->find($spaceId);
        $this->assertSame('Mon Espace', $space['name']);
        $this->assertSame('1', (string)$space['created_by']);
    }

    public function testCreatorIsAdministrator(): void
    {
        $spaceId = $this->model->createWithOwner(['name' => 'Test'], 1);
        $role = $this->model->getUserRole($spaceId, 1);
        $this->assertSame('administrateur', $role);
    }

    public function testFindByUser(): void
    {
        $this->model->createWithOwner(['name' => 'Espace 1'], 1);
        $this->model->createWithOwner(['name' => 'Espace 2'], 1);
        $this->model->createWithOwner(['name' => 'Espace 3'], 2);

        $spaces = $this->model->findByUser(1);
        $this->assertCount(2, $spaces);

        $spaces2 = $this->model->findByUser(2);
        $this->assertCount(1, $spaces2);
    }

    public function testGetUserRoleReturnsNullForNonMember(): void
    {
        $spaceId = $this->model->createWithOwner(['name' => 'Test'], 1);
        $role = $this->model->getUserRole($spaceId, 2);
        $this->assertNull($role);
    }

    public function testAddMember(): void
    {
        $spaceId = $this->model->createWithOwner(['name' => 'Test'], 1);
        $this->model->addMember($spaceId, 2, 'gestionnaire_produits');

        $role = $this->model->getUserRole($spaceId, 2);
        $this->assertSame('gestionnaire_produits', $role);
    }

    public function testGetMembers(): void
    {
        $spaceId = $this->model->createWithOwner(['name' => 'Test'], 1);
        $this->model->addMember($spaceId, 2, 'membre');

        $members = $this->model->getMembers($spaceId);
        $this->assertCount(2, $members);
    }

    public function testUpdateMemberRole(): void
    {
        $spaceId = $this->model->createWithOwner(['name' => 'Test'], 1);
        $this->model->addMember($spaceId, 2, 'membre');

        $this->model->updateMemberRole($spaceId, 2, 'gestionnaire_global');
        $role = $this->model->getUserRole($spaceId, 2);
        $this->assertSame('gestionnaire_global', $role);
    }

    public function testRemoveMember(): void
    {
        $spaceId = $this->model->createWithOwner(['name' => 'Test'], 1);
        $this->model->addMember($spaceId, 2, 'membre');
        $this->model->removeMember($spaceId, 2);

        $role = $this->model->getUserRole($spaceId, 2);
        $this->assertNull($role);
    }
}
