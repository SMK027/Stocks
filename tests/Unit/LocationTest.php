<?php

declare(strict_types=1);

namespace Tests\Unit;

use PHPUnit\Framework\TestCase;
use App\Models\Location;
use PDO;

class LocationTest extends TestCase
{
    private PDO $pdo;
    private Location $model;

    protected function setUp(): void
    {
        $this->pdo = new PDO('sqlite::memory:', null, null, [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        ]);

        $this->pdo->exec('CREATE TABLE locations (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            space_id INTEGER NOT NULL,
            name TEXT NOT NULL,
            description TEXT,
            created_at TEXT DEFAULT CURRENT_TIMESTAMP,
            updated_at TEXT DEFAULT CURRENT_TIMESTAMP
        )');

        $this->model = new Location($this->pdo);
    }

    public function testCreateInSpace(): void
    {
        $id = $this->model->createInSpace(1, [
            'name' => 'Étagère A',
            'description' => 'Première étagère',
        ]);

        $this->assertGreaterThan(0, $id);
        $loc = $this->model->find($id);
        $this->assertSame('Étagère A', $loc['name']);
        $this->assertSame('1', (string)$loc['space_id']);
    }

    public function testFindBySpace(): void
    {
        $this->model->createInSpace(1, ['name' => 'A']);
        $this->model->createInSpace(1, ['name' => 'B']);
        $this->model->createInSpace(2, ['name' => 'C']);

        $locs = $this->model->findBySpace(1);
        $this->assertCount(2, $locs);
    }

    public function testFindBySpaceEmpty(): void
    {
        $this->assertSame([], $this->model->findBySpace(999));
    }

    public function testUpdateLocation(): void
    {
        $id = $this->model->createInSpace(1, ['name' => 'Old']);
        $this->model->update($id, ['name' => 'New']);
        $loc = $this->model->find($id);
        $this->assertSame('New', $loc['name']);
    }

    public function testDeleteLocation(): void
    {
        $id = $this->model->createInSpace(1, ['name' => 'ToDelete']);
        $this->model->delete($id);
        $this->assertNull($this->model->find($id));
    }
}
