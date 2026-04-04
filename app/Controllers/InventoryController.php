<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Controller;
use App\Models\Space;
use App\Models\InventoryItem;
use App\Models\Product;
use App\Models\Location;

class InventoryController extends Controller
{
    private Space $spaceModel;
    private InventoryItem $inventoryModel;
    private Product $productModel;
    private Location $locationModel;

    public function __construct()
    {
        $this->spaceModel = new Space();
        $this->inventoryModel = new InventoryItem();
        $this->productModel = new Product();
        $this->locationModel = new Location();
    }

    private function requireInventoryAccess(int $spaceId): string
    {
        $this->requireAuth();
        $role = $this->spaceModel->getUserRole($spaceId, $this->getCurrentUserId());

        if (!$role) {
            $this->setFlash('danger', 'Accès non autorisé.');
            $this->redirect('/spaces');
            exit;
        }

        return $role;
    }

    private function requireInventoryManagement(int $spaceId): void
    {
        $role = $this->requireInventoryAccess($spaceId);
        $allowed = ['gestionnaire_inventaires', 'gestionnaire_global', 'administrateur'];

        if (!in_array($role, $allowed, true)) {
            $this->setFlash('danger', 'Permissions insuffisantes.');
            $this->redirect('/spaces/' . $spaceId);
            exit;
        }
    }

    /**
     * Vue de l'inventaire d'un espace.
     */
    public function index(string $spaceId): void
    {
        $sid = (int)$spaceId;
        $role = $this->requireInventoryAccess($sid);
        $space = $this->spaceModel->find($sid);
        $items = $this->inventoryModel->findBySpace($sid);
        $locations = $this->locationModel->findBySpace($sid);
        $expiringSoon = $this->inventoryModel->findExpiringSoon($sid);
        $expired = $this->inventoryModel->findExpired($sid);
        $casseItems = $this->inventoryModel->findCasseBySpace($sid);

        $this->render('inventory/index', [
            'title' => 'Inventaire — ' . $space['name'],
            'space' => $space,
            'items' => $items,
            'locations' => $locations,
            'expiringSoon' => $expiringSoon,
            'expired' => $expired,
            'casseItems' => $casseItems,
            'role' => $role,
        ]);
    }

    /**
     * Formulaire d'ajout au stock.
     */
    public function createForm(string $spaceId): void
    {
        $sid = (int)$spaceId;
        $this->requireInventoryManagement($sid);
        $space = $this->spaceModel->find($sid);
        $products = $this->productModel->findBySpace($sid);
        $locations = $this->locationModel->findBySpace($sid);

        $this->render('inventory/create', [
            'title' => 'Ajouter à l\'inventaire',
            'space' => $space,
            'products' => $products,
            'locations' => $locations,
        ]);
    }

    /**
     * Traitement de l'ajout.
     */
    public function store(string $spaceId): void
    {
        $sid = (int)$spaceId;
        $this->requireInventoryManagement($sid);
        $this->validateCSRF();
        $data = $this->getPostData(['product_id', 'location_id', 'quantity']);

        if (empty($data['product_id']) || empty($data['location_id']) || $data['quantity'] === '') {
            $this->setFlash('danger', 'Tous les champs sont requis.');
            $this->redirect('/spaces/' . $sid . '/inventory/create');
            return;
        }

        $quantity = (int)$data['quantity'];
        if ($quantity < 0) {
            $this->setFlash('danger', 'La quantité ne peut pas être négative.');
            $this->redirect('/spaces/' . $sid . '/inventory/create');
            return;
        }

        $this->inventoryModel->upsert($sid, (int)$data['product_id'], (int)$data['location_id'], $quantity);
        $this->setFlash('success', 'Inventaire mis à jour.');
        $this->redirect('/spaces/' . $sid . '/inventory');
    }

    /**
     * Formulaire de modification d'une entrée.
     */
    public function editForm(string $spaceId, string $id): void
    {
        $sid = (int)$spaceId;
        $this->requireInventoryManagement($sid);
        $space = $this->spaceModel->find($sid);
        $item = $this->inventoryModel->find((int)$id);

        if (!$item || (int)$item['space_id'] !== $sid) {
            $this->setFlash('danger', 'Entrée introuvable.');
            $this->redirect('/spaces/' . $sid . '/inventory');
            return;
        }

        $products = $this->productModel->findBySpace($sid);
        $locations = $this->locationModel->findBySpace($sid);

        $this->render('inventory/edit', [
            'title' => 'Modifier l\'entrée',
            'space' => $space,
            'item' => $item,
            'products' => $products,
            'locations' => $locations,
        ]);
    }

    /**
     * Traitement de la modification.
     */
    public function update(string $spaceId, string $id): void
    {
        $sid = (int)$spaceId;
        $this->requireInventoryManagement($sid);
        $this->validateCSRF();
        $data = $this->getPostData(['product_id', 'location_id', 'quantity']);

        if (empty($data['product_id']) || empty($data['location_id']) || $data['quantity'] === '') {
            $this->setFlash('danger', 'Tous les champs sont requis.');
            $this->redirect('/spaces/' . $sid . '/inventory/' . $id . '/edit');
            return;
        }

        $quantity = (int)$data['quantity'];
        if ($quantity < 0) {
            $this->setFlash('danger', 'La quantité ne peut pas être négative.');
            $this->redirect('/spaces/' . $sid . '/inventory/' . $id . '/edit');
            return;
        }

        $this->inventoryModel->update((int)$id, [
            'product_id' => (int)$data['product_id'],
            'location_id' => (int)$data['location_id'],
            'quantity' => $quantity,
        ]);
        $this->setFlash('success', 'Entrée mise à jour.');
        $this->redirect('/spaces/' . $sid . '/inventory');
    }

    /**
     * Suppression d'une entrée.
     */
    public function destroy(string $spaceId, string $id): void
    {
        $sid = (int)$spaceId;
        $this->requireInventoryManagement($sid);
        $this->validateCSRF();

        $this->inventoryModel->delete((int)$id);
        $this->setFlash('success', 'Entrée supprimée.');
        $this->redirect('/spaces/' . $sid . '/inventory');
    }

    /**
     * Mettre un élément en casse.
     */
    public function casse(string $spaceId, string $id): void
    {
        $sid = (int)$spaceId;
        $this->requireInventoryManagement($sid);
        $this->validateCSRF();

        $item = $this->inventoryModel->find((int)$id);
        if (!$item || (int)$item['space_id'] !== $sid) {
            $this->setFlash('danger', 'Entrée introuvable.');
            $this->redirect('/spaces/' . $sid . '/inventory');
            return;
        }

        $this->inventoryModel->markAsCasse((int)$id);
        $this->setFlash('success', 'Produit mis en casse.');
        $this->redirect('/spaces/' . $sid . '/inventory');
    }

    /**
     * Retirer un élément de la casse.
     */
    public function uncasse(string $spaceId, string $id): void
    {
        $sid = (int)$spaceId;
        $this->requireInventoryManagement($sid);
        $this->validateCSRF();

        $item = $this->inventoryModel->find((int)$id);
        if (!$item || (int)$item['space_id'] !== $sid) {
            $this->setFlash('danger', 'Entrée introuvable.');
            $this->redirect('/spaces/' . $sid . '/inventory');
            return;
        }

        $this->inventoryModel->unmarkCasse((int)$id);
        $this->setFlash('success', 'Produit remis en service.');
        $this->redirect('/spaces/' . $sid . '/inventory');
    }
}
