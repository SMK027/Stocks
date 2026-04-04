<?php

declare(strict_types=1);

namespace Tests\Unit;

use PHPUnit\Framework\TestCase;
use App\Models\Product;
use PDO;

class ProductTest extends TestCase
{
    private PDO $pdo;
    private Product $model;

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
            expiry_date TEXT,
            created_at TEXT DEFAULT CURRENT_TIMESTAMP,
            updated_at TEXT DEFAULT CURRENT_TIMESTAMP
        )');

        $this->pdo->exec('CREATE TABLE categories (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            space_id INTEGER NOT NULL,
            name TEXT NOT NULL,
            description TEXT,
            max_consumption_days INTEGER
        )');

        $this->pdo->exec('CREATE TABLE product_categories (
            product_id INTEGER NOT NULL,
            category_id INTEGER NOT NULL,
            PRIMARY KEY (product_id, category_id)
        )');

        $this->model = new Product($this->pdo);
    }

    public function testCreateInSpace(): void
    {
        $id = $this->model->createInSpace(1, [
            'name' => 'Lait',
            'stock_date' => '2024-01-15',
            'expiry_date' => '2024-01-22',
        ]);

        $this->assertGreaterThan(0, $id);
        $product = $this->model->find($id);
        $this->assertSame('Lait', $product['name']);
        $this->assertSame('2024-01-15', $product['stock_date']);
    }

    public function testCreateWithCategories(): void
    {
        $this->pdo->exec("INSERT INTO categories (space_id, name) VALUES (1, 'Produits laitiers')");
        $this->pdo->exec("INSERT INTO categories (space_id, name) VALUES (1, 'Frais')");

        $id = $this->model->createInSpace(1, [
            'name' => 'Yaourt',
            'stock_date' => '2024-01-15',
        ], [1, 2]);

        $categoryIds = $this->model->getCategoryIds($id);
        $this->assertCount(2, $categoryIds);
        $this->assertContains(1, array_map('intval', $categoryIds));
        $this->assertContains(2, array_map('intval', $categoryIds));
    }

    public function testFindWithCategories(): void
    {
        $this->pdo->exec("INSERT INTO categories (space_id, name) VALUES (1, 'Cat A')");
        $id = $this->model->createInSpace(1, [
            'name' => 'Test',
            'stock_date' => '2024-01-15',
        ], [1]);

        $product = $this->model->findWithCategories($id);
        $this->assertNotNull($product);
        $this->assertArrayHasKey('category_ids', $product);
        $this->assertCount(1, $product['category_ids']);
    }

    public function testUpdateWithCategories(): void
    {
        $this->pdo->exec("INSERT INTO categories (space_id, name) VALUES (1, 'A')");
        $this->pdo->exec("INSERT INTO categories (space_id, name) VALUES (1, 'B')");

        $id = $this->model->createInSpace(1, [
            'name' => 'Test',
            'stock_date' => '2024-01-15',
        ], [1]);

        $this->model->updateWithCategories($id, ['name' => 'Updated'], [2]);

        $product = $this->model->find($id);
        $this->assertSame('Updated', $product['name']);

        $catIds = $this->model->getCategoryIds($id);
        $this->assertCount(1, $catIds);
        $this->assertContains(2, array_map('intval', $catIds));
    }

    public function testSyncCategoriesReplacesAll(): void
    {
        $this->pdo->exec("INSERT INTO categories (space_id, name) VALUES (1, 'A')");
        $this->pdo->exec("INSERT INTO categories (space_id, name) VALUES (1, 'B')");

        $id = $this->model->createInSpace(1, [
            'name' => 'Test',
            'stock_date' => '2024-01-15',
        ], [1, 2]);

        $this->model->syncCategories($id, []);
        $catIds = $this->model->getCategoryIds($id);
        $this->assertEmpty($catIds);
    }

    public function testFindBySpace(): void
    {
        $this->model->createInSpace(1, ['name' => 'A', 'stock_date' => '2024-01-15']);
        $this->model->createInSpace(1, ['name' => 'B', 'stock_date' => '2024-01-15']);
        $this->model->createInSpace(2, ['name' => 'C', 'stock_date' => '2024-01-15']);

        $products = $this->model->findBySpace(1);
        $this->assertCount(2, $products);
    }

    public function testDeleteProduct(): void
    {
        $id = $this->model->createInSpace(1, ['name' => 'Del', 'stock_date' => '2024-01-15']);
        $this->model->delete($id);
        $this->assertNull($this->model->find($id));
    }

    public function testExpiryDateCanBeNull(): void
    {
        $id = $this->model->createInSpace(1, [
            'name' => 'NoExpiry',
            'stock_date' => '2024-01-15',
        ]);
        $product = $this->model->find($id);
        $this->assertNull($product['expiry_date']);
    }
}
