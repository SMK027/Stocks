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
        $groupConcat = $this->isSQLite()
            ? "GROUP_CONCAT(c.name, ', ')"
            : "GROUP_CONCAT(c.name SEPARATOR ', ')";

        $stmt = $this->db->prepare(
            "SELECT ii.*, p.name as product_name,
                    l.name as location_name,
                    {$groupConcat} as category_names
             FROM inventory_items ii
             INNER JOIN products p ON p.id = ii.product_id
             INNER JOIN locations l ON l.id = ii.location_id
             LEFT JOIN product_categories pc ON pc.product_id = p.id
             LEFT JOIN categories c ON c.id = pc.category_id
             WHERE ii.space_id = :space_id AND ii.is_casse = 0
             GROUP BY ii.id
             ORDER BY p.name ASC, l.name ASC"
        );
        $stmt->execute(['space_id' => $spaceId]);
        return $stmt->fetchAll(\PDO::FETCH_ASSOC);
    }

    /**
     * Crée ou met à jour un élément d'inventaire.
     * Deux entrées pour le même produit/emplacement sont considérées distinctes
     * si stock_date ou expiry_date diffèrent.
     */
    public function upsert(int $spaceId, int $productId, int $locationId, int $quantity, ?string $stockDate = null, ?string $expiryDate = null): int
    {
        $existing = $this->findByProductLocationDates($productId, $locationId, $stockDate, $expiryDate);

        if ($existing) {
            $this->update($existing['id'], ['quantity' => $quantity]);
            return (int)$existing['id'];
        }

        return $this->create([
            'space_id'    => $spaceId,
            'product_id'  => $productId,
            'location_id' => $locationId,
            'quantity'    => $quantity,
            'stock_date'  => $stockDate,
            'expiry_date' => $expiryDate,
        ]);
    }

    /**
     * Recherche une entrée d'inventaire par produit, emplacement et dates.
     * Gère correctement les valeurs NULL via IS NULL.
     */
    private function findByProductLocationDates(int $productId, int $locationId, ?string $stockDate, ?string $expiryDate): ?array
    {
        $stockCondition  = $stockDate  !== null ? 'stock_date = :stock_date'   : 'stock_date IS NULL';
        $expiryCondition = $expiryDate !== null ? 'expiry_date = :expiry_date' : 'expiry_date IS NULL';

        $params = ['product_id' => $productId, 'location_id' => $locationId];
        if ($stockDate  !== null) { $params['stock_date']  = $stockDate; }
        if ($expiryDate !== null) { $params['expiry_date'] = $expiryDate; }

        $stmt = $this->db->prepare(
            "SELECT * FROM {$this->table}
             WHERE product_id = :product_id
               AND location_id = :location_id
               AND {$stockCondition}
               AND {$expiryCondition}
             LIMIT 1"
        );
        $stmt->execute($params);
        $result = $stmt->fetch(\PDO::FETCH_ASSOC);
        return $result ?: null;
    }

    /**
     * Retourne les éléments d'inventaire filtrés par emplacement.
     */
    public function findByLocation(int $spaceId, int $locationId): array
    {
        $stmt = $this->db->prepare(
            "SELECT ii.*, p.name as product_name,
                    l.name as location_name
             FROM inventory_items ii
             INNER JOIN products p ON p.id = ii.product_id
             INNER JOIN locations l ON l.id = ii.location_id
             WHERE ii.space_id = :space_id AND ii.location_id = :location_id AND ii.is_casse = 0
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
            "SELECT ii.*, p.name as product_name,
                    l.name as location_name
             FROM inventory_items ii
             INNER JOIN products p ON p.id = ii.product_id
             INNER JOIN locations l ON l.id = ii.location_id
             WHERE ii.space_id = :space_id AND ii.is_casse = 0
               AND ii.expiry_date IS NOT NULL
               AND ii.expiry_date <= DATE_ADD(CURDATE(), INTERVAL :days DAY)
               AND ii.expiry_date >= CURDATE()
             ORDER BY ii.expiry_date ASC"
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
            "SELECT ii.*, p.name as product_name,
                    l.name as location_name
             FROM inventory_items ii
             INNER JOIN products p ON p.id = ii.product_id
             INNER JOIN locations l ON l.id = ii.location_id
             WHERE ii.space_id = :space_id AND ii.is_casse = 0
               AND ii.expiry_date IS NOT NULL
               AND ii.expiry_date < CURDATE()
             ORDER BY ii.expiry_date ASC"
        );
        $stmt->execute(['space_id' => $spaceId]);
        return $stmt->fetchAll(\PDO::FETCH_ASSOC);
    }

    /**
     * Retourne les produits à consommer prochainement pour tous les espaces d'un utilisateur.
     */
    public function findExpiringSoonForUser(int $userId, int $days = 14): array
    {
        $curdate = $this->isSQLite() ? "date('now')" : 'CURDATE()';
        $dateAdd = $this->isSQLite()
            ? "date('now', '+' || :days || ' days')"
            : 'DATE_ADD(CURDATE(), INTERVAL :days DAY)';

        $stmt = $this->db->prepare(
            "SELECT ii.*, p.name as product_name,
                    l.name as location_name, s.name as space_name, s.id as sid
             FROM inventory_items ii
             INNER JOIN products p ON p.id = ii.product_id
             INNER JOIN locations l ON l.id = ii.location_id
             INNER JOIN spaces s ON s.id = ii.space_id
             INNER JOIN space_members sm ON sm.space_id = s.id AND sm.user_id = :user_id
             WHERE ii.is_casse = 0
               AND ii.expiry_date IS NOT NULL
               AND ii.expiry_date <= {$dateAdd}
               AND ii.expiry_date >= {$curdate}
             ORDER BY ii.expiry_date ASC"
        );
        $stmt->execute(['user_id' => $userId, 'days' => $days]);
        return $stmt->fetchAll(\PDO::FETCH_ASSOC);
    }

    /**
     * Retourne les produits périmés pour tous les espaces d'un utilisateur.
     */
    public function findExpiredForUser(int $userId): array
    {
        $curdate = $this->isSQLite() ? "date('now')" : 'CURDATE()';

        $stmt = $this->db->prepare(
            "SELECT ii.*, p.name as product_name,
                    l.name as location_name, s.name as space_name, s.id as sid
             FROM inventory_items ii
             INNER JOIN products p ON p.id = ii.product_id
             INNER JOIN locations l ON l.id = ii.location_id
             INNER JOIN spaces s ON s.id = ii.space_id
             INNER JOIN space_members sm ON sm.space_id = s.id AND sm.user_id = :user_id
             WHERE ii.is_casse = 0
               AND ii.expiry_date IS NOT NULL
               AND ii.expiry_date < {$curdate}
             ORDER BY ii.expiry_date ASC"
        );
        $stmt->execute(['user_id' => $userId]);
        return $stmt->fetchAll(\PDO::FETCH_ASSOC);
    }

    /**
     * Retourne tous les produits avec date de péremption pour le calendrier d'un utilisateur.
     */
    public function findAllWithExpiryForUser(int $userId): array
    {
        $stmt = $this->db->prepare(
            "SELECT ii.id, p.name as product_name, ii.expiry_date, ii.stock_date,
                    l.name as location_name, s.name as space_name, s.id as sid,
                    ii.quantity
             FROM inventory_items ii
             INNER JOIN products p ON p.id = ii.product_id
             INNER JOIN locations l ON l.id = ii.location_id
             INNER JOIN spaces s ON s.id = ii.space_id
             INNER JOIN space_members sm ON sm.space_id = s.id AND sm.user_id = :user_id
             WHERE ii.is_casse = 0
               AND ii.expiry_date IS NOT NULL
             ORDER BY ii.expiry_date ASC"
        );
        $stmt->execute(['user_id' => $userId]);
        return $stmt->fetchAll(\PDO::FETCH_ASSOC);
    }

    /**
     * Retourne les éléments marqués en casse pour un espace.
     */
    public function findCasseBySpace(int $spaceId): array
    {
        $groupConcat = $this->isSQLite()
            ? "GROUP_CONCAT(c.name, ', ')"
            : "GROUP_CONCAT(c.name SEPARATOR ', ')";

        $stmt = $this->db->prepare(
            "SELECT ii.*, p.name as product_name,
                    l.name as location_name,
                    {$groupConcat} as category_names
             FROM inventory_items ii
             INNER JOIN products p ON p.id = ii.product_id
             INNER JOIN locations l ON l.id = ii.location_id
             LEFT JOIN product_categories pc ON pc.product_id = p.id
             LEFT JOIN categories c ON c.id = pc.category_id
             WHERE ii.space_id = :space_id AND ii.is_casse = 1
             GROUP BY ii.id
             ORDER BY p.name ASC, l.name ASC"
        );
        $stmt->execute(['space_id' => $spaceId]);
        return $stmt->fetchAll(\PDO::FETCH_ASSOC);
    }

    /**
     * Marque un élément comme étant en casse.
     */
    public function markAsCasse(int $id): void
    {
        $this->update($id, ['is_casse' => 1]);
    }

    /**
     * Retire un élément de la casse (le remet en service).
     */
    public function unmarkCasse(int $id): void
    {
        $this->update($id, ['is_casse' => 0]);
    }
}
