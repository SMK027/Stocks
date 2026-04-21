<?php

declare(strict_types=1);

namespace App\Controllers\Api;

use App\Models\InventoryItem;
use App\Models\Space;

/**
 * API — Inventaire
 *
 * GET    /api/spaces/{spaceId}/inventory              → Liste des entrées actives
 * POST   /api/spaces/{spaceId}/inventory              → Ajouter/mettre à jour une entrée
 * GET    /api/spaces/{spaceId}/inventory/{id}         → Détail d'une entrée
 * PUT    /api/spaces/{spaceId}/inventory/{id}         → Modifier une entrée
 * DELETE /api/spaces/{spaceId}/inventory/{id}         → Supprimer une entrée
 * GET    /api/spaces/{spaceId}/inventory/expired      → Produits périmés
 * GET    /api/spaces/{spaceId}/inventory/expiring     → Produits bientôt périmés
 * GET    /api/spaces/{spaceId}/inventory/casse        → Produits en casse
 * POST   /api/spaces/{spaceId}/inventory/{id}/casse   → Mettre en casse
 * POST   /api/spaces/{spaceId}/inventory/{id}/uncasse → Remettre en service
 * POST   /api/spaces/{spaceId}/inventory/{id}/decrease → Diminuer le stock
 */
class InventoryApiController extends ApiController
{
    private InventoryItem $inventoryModel;
    private Space $spaceModel;

    public function __construct()
    {
        $this->inventoryModel = new InventoryItem();
        $this->spaceModel     = new Space();
    }

    private function requireAccess(int $spaceId): string
    {
        $role = $this->spaceModel->getUserRole($spaceId, $this->userId);
        if (!$role) {
            $this->error('Espace introuvable ou accès refusé.', 403);
        }
        return $role;
    }

    private function requireManage(int $spaceId): void
    {
        $role = $this->requireAccess($spaceId);
        $allowed = ['gestionnaire_inventaires', 'gestionnaire_global', 'administrateur'];
        if (!in_array($role, $allowed, true)) {
            $this->error('Permissions insuffisantes.', 403);
        }
    }

    private function formatItem(array $i): array
    {
        return [
            'id'            => (int) $i['id'],
            'space_id'      => (int) $i['space_id'],
            'product_id'    => (int) $i['product_id'],
            'location_id'   => (int) $i['location_id'],
            'quantity'      => (int) $i['quantity'],
            'stock_date'    => $i['stock_date'],
            'expiry_date'   => $i['expiry_date'] ?? null,
            'is_casse'      => (bool) ($i['is_casse'] ?? false),
            'product_name'  => $i['product_name'] ?? null,
            'location_name' => $i['location_name'] ?? null,
        ];
    }

    /** GET /api/spaces/{spaceId}/inventory */
    public function index(string $spaceId): void
    {
        $this->requireAuth();
        $sid = (int) $spaceId;
        $this->requireAccess($sid);

        $items = $this->inventoryModel->findBySpace($sid);
        $this->json([
            'success' => true,
            'data'    => array_map([$this, 'formatItem'], $items),
        ]);
    }

    /**
     * POST /api/spaces/{spaceId}/inventory
     * Crée ou met à jour une entrée (upsert).
     * Corps JSON : { "product_id", "location_id", "quantity", "stock_date"?, "expiry_date"? }
     */
    public function store(string $spaceId): void
    {
        $this->requireAuth();
        $sid = (int) $spaceId;
        $this->requireManage($sid);

        $body       = $this->getJsonBody();
        $productId  = isset($body['product_id'])  ? (int) $body['product_id']  : 0;
        $locationId = isset($body['location_id']) ? (int) $body['location_id'] : 0;
        $quantity   = isset($body['quantity'])    ? (int) $body['quantity']    : 0;

        if ($productId <= 0 || $locationId <= 0) {
            $this->error('product_id et location_id sont requis.', 422);
        }

        if ($quantity <= 0) {
            $this->error('La quantité doit être un entier positif.', 422);
        }

        $stockDate  = !empty($body['stock_date'])  ? $body['stock_date']  : date('Y-m-d');
        $expiryDate = !empty($body['expiry_date']) ? $body['expiry_date'] : null;

        $id   = $this->inventoryModel->upsert($sid, $productId, $locationId, $quantity, $stockDate, $expiryDate);
        $item = $this->inventoryModel->find($id);

        $this->json(['success' => true, 'data' => $this->formatItem($item)], 201);
    }

    /** GET /api/spaces/{spaceId}/inventory/{id} */
    public function show(string $spaceId, string $id): void
    {
        $this->requireAuth();
        $sid = (int) $spaceId;
        $this->requireAccess($sid);

        $item = $this->inventoryModel->find((int) $id);
        if (!$item || (int) $item['space_id'] !== $sid) {
            $this->error('Entrée introuvable.', 404);
        }

        $this->json(['success' => true, 'data' => $this->formatItem($item)]);
    }

    /**
     * PUT /api/spaces/{spaceId}/inventory/{id}
     * Corps JSON : { "quantity"?, "stock_date"?, "expiry_date"?, "product_id"?, "location_id"? }
     */
    public function update(string $spaceId, string $id): void
    {
        $this->requireAuth();
        $sid = (int) $spaceId;
        $this->requireManage($sid);

        $item = $this->inventoryModel->find((int) $id);
        if (!$item || (int) $item['space_id'] !== $sid) {
            $this->error('Entrée introuvable.', 404);
        }

        $body = $this->getJsonBody();
        $data = [];

        if (isset($body['quantity'])) {
            $qty = (int) $body['quantity'];
            if ($qty <= 0) {
                $this->error('La quantité doit être un entier positif.', 422);
            }
            $data['quantity'] = $qty;
        }

        if (isset($body['stock_date'])) {
            $data['stock_date'] = $body['stock_date'];
        }

        if (array_key_exists('expiry_date', $body)) {
            $data['expiry_date'] = $body['expiry_date'] ?: null;
        }

        if (isset($body['product_id'])) {
            $data['product_id'] = (int) $body['product_id'];
        }

        if (isset($body['location_id'])) {
            $data['location_id'] = (int) $body['location_id'];
        }

        if (empty($data)) {
            $this->error('Aucune donnée valide fournie.', 422);
        }

        $this->inventoryModel->update((int) $id, $data);
        $updated = $this->inventoryModel->find((int) $id);

        $this->json(['success' => true, 'data' => $this->formatItem($updated)]);
    }

    /** DELETE /api/spaces/{spaceId}/inventory/{id} */
    public function destroy(string $spaceId, string $id): void
    {
        $this->requireAuth();
        $sid = (int) $spaceId;
        $this->requireManage($sid);

        $item = $this->inventoryModel->find((int) $id);
        if (!$item || (int) $item['space_id'] !== $sid) {
            $this->error('Entrée introuvable.', 404);
        }

        $this->inventoryModel->delete((int) $id);
        $this->json(['success' => true, 'message' => 'Entrée supprimée.']);
    }

    /** GET /api/spaces/{spaceId}/inventory/expired */
    public function expired(string $spaceId): void
    {
        $this->requireAuth();
        $sid = (int) $spaceId;
        $this->requireAccess($sid);

        $items = $this->inventoryModel->findExpired($sid);
        $this->json([
            'success' => true,
            'data'    => array_map([$this, 'formatItem'], $items),
        ]);
    }

    /** GET /api/spaces/{spaceId}/inventory/expiring?days=7 */
    public function expiring(string $spaceId): void
    {
        $this->requireAuth();
        $sid  = (int) $spaceId;
        $days = isset($_GET['days']) ? max(1, (int) $_GET['days']) : 7;
        $this->requireAccess($sid);

        $items = $this->inventoryModel->findExpiringSoon($sid, $days);
        $this->json([
            'success' => true,
            'data'    => array_map([$this, 'formatItem'], $items),
        ]);
    }

    /** GET /api/spaces/{spaceId}/inventory/casse */
    public function casse(string $spaceId): void
    {
        $this->requireAuth();
        $sid = (int) $spaceId;
        $this->requireAccess($sid);

        $items = $this->inventoryModel->findCasseBySpace($sid);
        $this->json([
            'success' => true,
            'data'    => array_map([$this, 'formatItem'], $items),
        ]);
    }

    /**
     * POST /api/spaces/{spaceId}/inventory/{id}/casse
     * Met un article en casse.
     */
    public function markCasse(string $spaceId, string $id): void
    {
        $this->requireAuth();
        $sid = (int) $spaceId;
        $this->requireManage($sid);

        $item = $this->inventoryModel->find((int) $id);
        if (!$item || (int) $item['space_id'] !== $sid) {
            $this->error('Entrée introuvable.', 404);
        }

        $this->inventoryModel->markAsCasse((int) $id);
        $this->json(['success' => true, 'message' => 'Article mis en casse.']);
    }

    /**
     * POST /api/spaces/{spaceId}/inventory/{id}/uncasse
     * Remet un article en service.
     */
    public function unmarkCasse(string $spaceId, string $id): void
    {
        $this->requireAuth();
        $sid = (int) $spaceId;
        $this->requireManage($sid);

        $item = $this->inventoryModel->find((int) $id);
        if (!$item || (int) $item['space_id'] !== $sid) {
            $this->error('Entrée introuvable.', 404);
        }

        $this->inventoryModel->unmarkCasse((int) $id);
        $this->json(['success' => true, 'message' => 'Article remis en service.']);
    }

    /**
     * POST /api/spaces/{spaceId}/inventory/{id}/decrease
     * Diminue la quantité d'un article.
     * Corps JSON : { "decrease_by": 1 }
     */
    public function decrease(string $spaceId, string $id): void
    {
        $this->requireAuth();
        $sid = (int) $spaceId;
        $this->requireManage($sid);

        $item = $this->inventoryModel->find((int) $id);
        if (!$item || (int) $item['space_id'] !== $sid) {
            $this->error('Entrée introuvable.', 404);
        }

        $body       = $this->getJsonBody();
        $decreaseBy = isset($body['decrease_by']) ? (int) $body['decrease_by'] : 1;

        if ($decreaseBy <= 0) {
            $this->error('La valeur de diminution doit être un entier positif.', 422);
        }

        $newQty = (int) $item['quantity'] - $decreaseBy;

        if ($newQty <= 0) {
            $this->inventoryModel->delete((int) $id);
            $this->json(['success' => true, 'message' => 'Entrée supprimée (quantité nulle).', 'deleted' => true]);
        }

        $this->inventoryModel->update((int) $id, ['quantity' => $newQty]);
        $updated = $this->inventoryModel->find((int) $id);

        $this->json(['success' => true, 'data' => $this->formatItem($updated)]);
    }
}
