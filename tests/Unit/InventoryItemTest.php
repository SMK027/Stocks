<?php

declare(strict_types=1);

namespace Tests\Unit;

use PHPUnit\Framework\TestCase;
use App\Models\InventoryItem;
use PDO;

class InventoryItemTest extends TestCase
{
    private PDO $pdo;
    private InventoryItem $model;

    protected function setUp(): void
    {
        $this->pdo = new PDO('sqlite::memory:', null, null, [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        ]);

        $this->pdo->exec('CREATE TABLE products (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            space_id INTEGER NOT NULL,
            name TEXT NOT NULL,
            stock_date TEXT NOT NULL,
            expiry_date TEXT
        )');

        $this->pdo->exec('CREATE TABLE locations (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            space_id INTEGER NOT NULL,
            name TEXT NOT NULL,
            description TEXT
        )');

        $this->pdo->exec('CREATE TABLE product_categories (
            product_id INTEGER NOT NULL,
            category_id INTEGER NOT NULL,
            PRIMARY KEY (product_id, category_id)
        )');

        $this->pdo->exec('CREATE TABLE categories (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            space_id INTEGER NOT NULL,
            name TEXT NOT NULL
        )');

        $this->pdo->exec('CREATE TABLE inventory_items (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            space_id INTEGER NOT NULL,
            product_id INTEGER NOT NULL,
            location_id INTEGER NOT NULL,
            quantity INTEGER NOT NULL DEFAULT 0,
            is_casse INTEGER NOT NULL DEFAULT 0,
            created_at TEXT DEFAULT CURRENT_TIMESTAMP,
            updated_at TEXT DEFAULT CURRENT_TIMESTAMP,
            UNIQUE(product_id, location_id)
        )');

        // Données de test
        $this->pdo->exec("INSERT INTO products (space_id, name, stock_date, expiry_date) VALUES (1, 'Lait', '2024-01-15', '2024-01-22')");
        $this->pdo->exec("INSERT INTO products (space_id, name, stock_date, expiry_date) VALUES (1, 'Eau', '2024-01-15', NULL)");
        $this->pdo->exec("INSERT INTO locations (space_id, name) VALUES (1, 'Frigo')");
        $this->pdo->exec("INSERT INTO locations (space_id, name) VALUES (1, 'Placard')");

        $this->model = new InventoryItem($this->pdo);
    }

    public function testCreateInventoryItem(): void
    {
        $id = $this->model->create([
            'space_id' => 1,
            'product_id' => 1,
            'location_id' => 1,
            'quantity' => 5,
        ]);

        $this->assertGreaterThan(0, $id);
        $item = $this->model->find($id);
        $this->assertSame('5', (string)$item['quantity']);
    }

    public function testUpsertCreatesNew(): void
    {
        $id = $this->model->upsert(1, 1, 1, 10);
        $this->assertGreaterThan(0, $id);

        $item = $this->model->find($id);
        $this->assertSame('10', (string)$item['quantity']);
    }

    public function testUpsertUpdatesExisting(): void
    {
        $id1 = $this->model->upsert(1, 1, 1, 5);
        $id2 = $this->model->upsert(1, 1, 1, 15);

        $this->assertSame($id1, $id2);
        $item = $this->model->find($id1);
        $this->assertSame('15', (string)$item['quantity']);
    }

    public function testFindBySpace(): void
    {
        $this->model->create(['space_id' => 1, 'product_id' => 1, 'location_id' => 1, 'quantity' => 5]);
        $this->model->create(['space_id' => 1, 'product_id' => 2, 'location_id' => 2, 'quantity' => 3]);

        $items = $this->model->findBySpace(1);
        $this->assertCount(2, $items);
        $this->assertArrayHasKey('product_name', $items[0]);
        $this->assertArrayHasKey('location_name', $items[0]);
    }

    public function testFindBySpaceEmpty(): void
    {
        $items = $this->model->findBySpace(999);
        $this->assertSame([], $items);
    }

    public function testFindByLocation(): void
    {
        $this->model->create(['space_id' => 1, 'product_id' => 1, 'location_id' => 1, 'quantity' => 5]);
        $this->model->create(['space_id' => 1, 'product_id' => 2, 'location_id' => 2, 'quantity' => 3]);

        $items = $this->model->findByLocation(1, 1);
        $this->assertCount(1, $items);
        $this->assertSame('Lait', $items[0]['product_name']);
    }

    public function testDeleteItem(): void
    {
        $id = $this->model->create(['space_id' => 1, 'product_id' => 1, 'location_id' => 1, 'quantity' => 5]);
        $this->model->delete($id);
        $this->assertNull($this->model->find($id));
    }

    public function testUpdateQuantity(): void
    {
        $id = $this->model->create(['space_id' => 1, 'product_id' => 1, 'location_id' => 1, 'quantity' => 5]);
        $this->model->update($id, ['quantity' => 20]);

        $item = $this->model->find($id);
        $this->assertSame('20', (string)$item['quantity']);
    }

    public function testMarkAsCasse(): void
    {
        $id = $this->model->create(['space_id' => 1, 'product_id' => 1, 'location_id' => 1, 'quantity' => 5]);
        $this->model->markAsCasse($id);

        $item = $this->model->find($id);
        $this->assertSame('1', (string)$item['is_casse']);
    }

    public function testUnmarkCasse(): void
    {
        $id = $this->model->create(['space_id' => 1, 'product_id' => 1, 'location_id' => 1, 'quantity' => 5]);
        $this->model->markAsCasse($id);
        $this->model->unmarkCasse($id);

        $item = $this->model->find($id);
        $this->assertSame('0', (string)$item['is_casse']);
    }

    public function testFindBySpaceExcludesCasse(): void
    {
        $this->model->create(['space_id' => 1, 'product_id' => 1, 'location_id' => 1, 'quantity' => 5]);
        $id2 = $this->model->create(['space_id' => 1, 'product_id' => 2, 'location_id' => 2, 'quantity' => 3]);
        $this->model->markAsCasse($id2);

        $items = $this->model->findBySpace(1);
        $this->assertCount(1, $items);
        $this->assertSame('Lait', $items[0]['product_name']);
    }

    public function testFindCasseBySpace(): void
    {
        $this->model->create(['space_id' => 1, 'product_id' => 1, 'location_id' => 1, 'quantity' => 5]);
        $id2 = $this->model->create(['space_id' => 1, 'product_id' => 2, 'location_id' => 2, 'quantity' => 3]);
        $this->model->markAsCasse($id2);

        $casseItems = $this->model->findCasseBySpace(1);
        $this->assertCount(1, $casseItems);
        $this->assertSame('Eau', $casseItems[0]['product_name']);
    }

    public function testFindByLocationExcludesCasse(): void
    {
        $id = $this->model->create(['space_id' => 1, 'product_id' => 1, 'location_id' => 1, 'quantity' => 5]);
        $this->model->markAsCasse($id);

        $items = $this->model->findByLocation(1, 1);
        $this->assertCount(0, $items);
    }
}
