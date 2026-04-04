<?php

declare(strict_types=1);

namespace App\Models;

use App\Core\Model;

class InventoryItem extends Model
{
    protected string $table = 'inventory_items';

    /**
     * Retourne tous les éléments d'inventaire d'un espace.
     */
    public function findBySpace(int $spaceId): array
    {
        $stmt = $this->db->prepare(
            "SELECT ii.*, p.name as product_name, p.expiry_date, p.stock_date,
                    l.name as location_name,
                    GROUP_CONCAT(c.name SEPARATOR ', ') as category_names
             FROM inventory_items ii
             INNER JOIN products p ON p.id = ii.product_id
             INNER JOIN locations l ON l.id = ii.location_id
             LEFT JOIN product_categories pc ON pc.product_id = p.id
             LEFT JOIN categories c ON c.id = pc.category_id
             WHERE ii.space_id = :space_id
             GROUP BY ii.id
             ORDER BY p.name ASC, l.name ASC"
        );
        $stmt->execute(['space_id' => $spaceId]);
        return $stmt->fetchAll(\PDO::FETCH_ASSOC);
    }

    /**
     * Crée ou met à jour un élément d'inventaire.
     */
    public function upsert(int $spaceId, int $productId, int $locationId, int $quantity): int
    {
        $existing = $this->findOneBy([
            'product_id' => $productId,
            'location_id' => $locationId,
        ]);

        if ($existing) {
            $this->update($existing['id'], ['quantity' => $quantity]);
            return (int)$existing['id'];
        }

        return $this->create([
            'space_id' => $spaceId,
            'product_id' => $productId,
            'location_id' => $locationId,
            'quantity' => $quantity,
        ]);
    }

    /**
     * Retourne les éléments d'inventaire filtrés par emplacement.
     */
    public function findByLocation(int $spaceId, int $locationId): array
    {
        $stmt = $this->db->prepare(
            "SELECT ii.*, p.name as product_name, p.expiry_date, p.stock_date,
                    l.name as location_name
             FROM inventory_items ii
             INNER JOIN products p ON p.id = ii.product_id
             INNER JOIN locations l ON l.id = ii.location_id
             WHERE ii.space_id = :space_id AND ii.location_id = :location_id
             ORDER BY p.name ASC"
        );
        $stmt->execute(['space_id' => $spaceId, 'location_id' => $locationId]);
        return $stmt->fetchAll(\PDO::FETCH_ASSOC);
    }

    /**
     * Retourne les produits dont la date de péremption approche.
     */
    public function findExpiringSoon(int $spaceId, int $days = 7): array
    {
        $stmt = $this->db->prepare(
            "SELECT ii.*, p.name as product_name, p.expiry_date, p.stock_date,
                    l.name as location_name
             FROM inventory_items ii
             INNER JOIN products p ON p.id = ii.product_id
             INNER JOIN locations l ON l.id = ii.location_id
             WHERE ii.space_id = :space_id
               AND p.expiry_date IS NOT NULL
               AND p.expiry_date <= DATE_ADD(CURDATE(), INTERVAL :days DAY)
               AND p.expiry_date >= CURDATE()
             ORDER BY p.expiry_date ASC"
        );
        $stmt->execute(['space_id' => $spaceId, 'days' => $days]);
        return $stmt->fetchAll(\PDO::FETCH_ASSOC);
    }

    /**
     * Retourne les produits dont la date de péremption est dépassée.
     */
    public function findExpired(int $spaceId): array
    {
        $stmt = $this->db->prepare(
            "SELECT ii.*, p.name as product_name, p.expiry_date, p.stock_date,
                    l.name as location_name
             FROM inventory_items ii
             INNER JOIN products p ON p.id = ii.product_id
             INNER JOIN locations l ON l.id = ii.location_id
             WHERE ii.space_id = :space_id
               AND p.expiry_date IS NOT NULL
               AND p.expiry_date < CURDATE()
             ORDER BY p.expiry_date ASC"
        );
        $stmt->execute(['space_id' => $spaceId]);
        return $stmt->fetchAll(\PDO::FETCH_ASSOC);
    }
}
