<?php

declare(strict_types=1);

namespace Tests\Unit;

use PHPUnit\Framework\TestCase;
use App\Models\Category;
use PDO;

class CategoryTest extends TestCase
{
    private PDO $pdo;
    private Category $model;

    protected function setUp(): void
    {
        $this->pdo = new PDO('sqlite::memory:', null, null, [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        ]);

        $this->pdo->exec('CREATE TABLE categories (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            space_id INTEGER NOT NULL,
            name TEXT NOT NULL,
            description TEXT,
            max_consumption_days INTEGER,
            created_at TEXT DEFAULT CURRENT_TIMESTAMP,
            updated_at TEXT DEFAULT CURRENT_TIMESTAMP
        )');

        $this->model = new Category($this->pdo);
    }

    public function testCreateInSpace(): void
    {
        $id = $this->model->createInSpace(1, [
            'name' => 'Fruits',
            'description' => 'Fruits frais',
            'max_consumption_days' => 7,
        ]);

        $this->assertGreaterThan(0, $id);
        $cat = $this->model->find($id);
        $this->assertSame('Fruits', $cat['name']);
        $this->assertSame('1', (string)$cat['space_id']);
        $this->assertSame('7', (string)$cat['max_consumption_days']);
    }

    public function testFindBySpace(): void
    {
        $this->model->createInSpace(1, ['name' => 'Fruits']);
        $this->model->createInSpace(1, ['name' => 'Légumes']);
        $this->model->createInSpace(2, ['name' => 'Viandes']);

        $cats1 = $this->model->findBySpace(1);
        $this->assertCount(2, $cats1);

        $cats2 = $this->model->findBySpace(2);
        $this->assertCount(1, $cats2);
    }

    public function testFindBySpaceReturnsEmptyForUnknown(): void
    {
        $result = $this->model->findBySpace(999);
        $this->assertSame([], $result);
    }

    public function testUpdateCategory(): void
    {
        $id = $this->model->createInSpace(1, ['name' => 'Old']);
        $this->model->update($id, ['name' => 'New', 'max_consumption_days' => 30]);

        $cat = $this->model->find($id);
        $this->assertSame('New', $cat['name']);
        $this->assertSame('30', (string)$cat['max_consumption_days']);
    }

    public function testDeleteCategory(): void
    {
        $id = $this->model->createInSpace(1, ['name' => 'ToDelete']);
        $this->model->delete($id);
        $this->assertNull($this->model->find($id));
    }

    public function testMaxConsumptionDaysCanBeNull(): void
    {
        $id = $this->model->createInSpace(1, ['name' => 'Sans durée']);
        $cat = $this->model->find($id);
        $this->assertNull($cat['max_consumption_days']);
    }
}
