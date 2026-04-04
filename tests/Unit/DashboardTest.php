<?php

declare(strict_types=1);

namespace Tests\Unit;

use PHPUnit\Framework\TestCase;
use App\Models\InventoryItem;
use PDO;

class DashboardTest extends TestCase
{
    private PDO $pdo;
    private InventoryItem $model;

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
            password TEXT NOT NULL
        )');

        $this->pdo->exec('CREATE TABLE spaces (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            name TEXT NOT NULL,
            created_by INTEGER NOT NULL
        )');

        $this->pdo->exec('CREATE TABLE space_members (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            space_id INTEGER NOT NULL,
            user_id INTEGER NOT NULL,
            role TEXT NOT NULL DEFAULT "membre",
            UNIQUE(space_id, user_id)
        )');

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
            name TEXT NOT NULL
        )');

        $this->pdo->exec('CREATE TABLE inventory_items (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            space_id INTEGER NOT NULL,
            product_id INTEGER NOT NULL,
            location_id INTEGER NOT NULL,
            quantity INTEGER NOT NULL DEFAULT 0,
            is_casse INTEGER NOT NULL DEFAULT 0,
            UNIQUE(product_id, location_id)
        )');

        // Données de test
        $this->pdo->exec("INSERT INTO users (username, email, password) VALUES ('alice', 'a@t.com', 'hash')");
        $this->pdo->exec("INSERT INTO spaces (name, created_by) VALUES ('Cuisine', 1)");
        $this->pdo->exec("INSERT INTO spaces (name, created_by) VALUES ('Bureau', 1)");
        $this->pdo->exec("INSERT INTO space_members (space_id, user_id, role) VALUES (1, 1, 'administrateur')");
        $this->pdo->exec("INSERT INTO space_members (space_id, user_id, role) VALUES (2, 1, 'membre')");
        $this->pdo->exec("INSERT INTO locations (space_id, name) VALUES (1, 'Frigo')");
        $this->pdo->exec("INSERT INTO locations (space_id, name) VALUES (2, 'Placard')");

        // Produit expirant dans 3 jours
        $soon = date('Y-m-d', strtotime('+3 days'));
        $this->pdo->exec("INSERT INTO products (space_id, name, stock_date, expiry_date) VALUES (1, 'Lait', '2024-01-01', '{$soon}')");
        $this->pdo->exec("INSERT INTO inventory_items (space_id, product_id, location_id, quantity) VALUES (1, 1, 1, 2)");

        // Produit périmé
        $past = date('Y-m-d', strtotime('-2 days'));
        $this->pdo->exec("INSERT INTO products (space_id, name, stock_date, expiry_date) VALUES (2, 'Yaourt', '2024-01-01', '{$past}')");
        $this->pdo->exec("INSERT INTO inventory_items (space_id, product_id, location_id, quantity) VALUES (2, 2, 2, 5)");

        // Produit sans date d'expiration
        $this->pdo->exec("INSERT INTO products (space_id, name, stock_date, expiry_date) VALUES (1, 'Eau', '2024-01-01', NULL)");
        $this->pdo->exec("INSERT INTO inventory_items (space_id, product_id, location_id, quantity) VALUES (1, 3, 1, 10)");

        // Produit loin dans le futur
        $future = date('Y-m-d', strtotime('+60 days'));
        $this->pdo->exec("INSERT INTO products (space_id, name, stock_date, expiry_date) VALUES (1, 'Conserves', '2024-01-01', '{$future}')");
        $this->pdo->exec("INSERT INTO inventory_items (space_id, product_id, location_id, quantity) VALUES (1, 4, 1, 3)");

        $this->model = new InventoryItem($this->pdo);
    }

    public function testFindExpiringSoonForUser(): void
    {
        $items = $this->model->findExpiringSoonForUser(1, 14);
        $this->assertCount(1, $items);
        $this->assertSame('Lait', $items[0]['product_name']);
        $this->assertSame('Cuisine', $items[0]['space_name']);
        $this->assertSame('Frigo', $items[0]['location_name']);
    }

    public function testFindExpiredForUser(): void
    {
        $items = $this->model->findExpiredForUser(1);
        $this->assertCount(1, $items);
        $this->assertSame('Yaourt', $items[0]['product_name']);
        $this->assertSame('Bureau', $items[0]['space_name']);
    }

    public function testFindAllWithExpiryForUser(): void
    {
        $items = $this->model->findAllWithExpiryForUser(1);
        // Lait, Yaourt, Conserves (Eau n'a pas de date)
        $this->assertCount(3, $items);
        $this->assertArrayHasKey('product_name', $items[0]);
        $this->assertArrayHasKey('space_name', $items[0]);
        $this->assertArrayHasKey('quantity', $items[0]);
    }

    public function testFindExpiringSoonForUserNoResults(): void
    {
        // Utilisateur 99 n'est membre d'aucun espace
        $items = $this->model->findExpiringSoonForUser(99, 14);
        $this->assertSame([], $items);
    }

    public function testFindExpiredForUserNoResults(): void
    {
        $items = $this->model->findExpiredForUser(99);
        $this->assertSame([], $items);
    }

    public function testExpiringSoonSpansMultipleSpaces(): void
    {
        // Ajouter un produit expirant bientôt dans le 2ème espace
        $soon = date('Y-m-d', strtotime('+5 days'));
        $this->pdo->exec("INSERT INTO products (space_id, name, stock_date, expiry_date) VALUES (2, 'Jus', '2024-01-01', '{$soon}')");
        $this->pdo->exec("INSERT INTO inventory_items (space_id, product_id, location_id, quantity) VALUES (2, 5, 2, 1)");

        $items = $this->model->findExpiringSoonForUser(1, 14);
        $this->assertCount(2, $items);

        $spaces = array_column($items, 'space_name');
        $this->assertContains('Cuisine', $spaces);
        $this->assertContains('Bureau', $spaces);
    }
}
