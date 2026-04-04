<?php

declare(strict_types=1);

namespace App\Models;

use App\Core\Model;

class Product extends Model
{
    protected string $table = 'products';

    /**
     * Retourne tous les produits d'un espace avec leurs catégories.
     */
    public function findBySpace(int $spaceId): array
    {
        $stmt = $this->db->prepare(
            "SELECT p.*, GROUP_CONCAT(c.name SEPARATOR ', ') as category_names
             FROM products p
             LEFT JOIN product_categories pc ON pc.product_id = p.id
             LEFT JOIN categories c ON c.id = pc.category_id
             WHERE p.space_id = :space_id
             GROUP BY p.id
             ORDER BY p.name ASC"
        );
        $stmt->execute(['space_id' => $spaceId]);
        return $stmt->fetchAll(\PDO::FETCH_ASSOC);
    }

    /**
     * Crée un produit et associe ses catégories.
     */
    public function createInSpace(int $spaceId, array $data, array $categoryIds = []): int
    {
        $data['space_id'] = $spaceId;
        $productId = $this->create($data);

        $this->syncCategories($productId, $categoryIds);

        return $productId;
    }

    /**
     * Met à jour un produit et ses catégories.
     */
    public function updateWithCategories(int $id, array $data, array $categoryIds = []): bool
    {
        $result = $this->update($id, $data);
        $this->syncCategories($id, $categoryIds);
        return $result;
    }

    /**
     * Synchronise les catégories d'un produit.
     */
    public function syncCategories(int $productId, array $categoryIds): void
    {
        $this->db->prepare("DELETE FROM product_categories WHERE product_id = :id")
            ->execute(['id' => $productId]);

        if (!empty($categoryIds)) {
            $stmt = $this->db->prepare(
                "INSERT INTO product_categories (product_id, category_id) VALUES (:product_id, :category_id)"
            );
            foreach ($categoryIds as $catId) {
                $stmt->execute(['product_id' => $productId, 'category_id' => (int)$catId]);
            }
        }
    }

    /**
     * Retourne les IDs des catégories d'un produit.
     */
    public function getCategoryIds(int $productId): array
    {
        $stmt = $this->db->prepare(
            "SELECT category_id FROM product_categories WHERE product_id = :id"
        );
        $stmt->execute(['id' => $productId]);
        return $stmt->fetchAll(\PDO::FETCH_COLUMN);
    }

    /**
     * Retourne un produit avec ses catégories.
     */
    public function findWithCategories(int $id): ?array
    {
        $product = $this->find($id);
        if ($product) {
            $product['category_ids'] = $this->getCategoryIds($id);
        }
        return $product;
    }
}
