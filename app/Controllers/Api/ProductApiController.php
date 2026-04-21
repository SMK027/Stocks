<?php

declare(strict_types=1);

namespace App\Controllers\Api;

use App\Models\Product;
use App\Models\Space;

/**
 * API — Produits
 *
 * GET    /api/spaces/{spaceId}/products        → Liste des produits
 * POST   /api/spaces/{spaceId}/products        → Créer un produit
 * GET    /api/spaces/{spaceId}/products/{id}   → Détail d'un produit
 * PUT    /api/spaces/{spaceId}/products/{id}   → Modifier un produit
 * DELETE /api/spaces/{spaceId}/products/{id}   → Supprimer un produit
 */
class ProductApiController extends ApiController
{
    private Product $productModel;
    private Space $spaceModel;

    public function __construct()
    {
        $this->productModel = new Product();
        $this->spaceModel   = new Space();
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
        $allowed = ['gestionnaire_produits', 'gestionnaire_global', 'administrateur'];
        if (!in_array($role, $allowed, true)) {
            $this->error('Permissions insuffisantes.', 403);
        }
    }

    private function formatProduct(array $p): array
    {
        return [
            'id'             => (int) $p['id'],
            'space_id'       => (int) $p['space_id'],
            'name'           => $p['name'],
            'description'    => $p['description'] ?? null,
            'category_ids'   => array_map('intval', $p['category_ids'] ?? []),
            'category_names' => $p['category_names'] ?? null,
        ];
    }

    /** GET /api/spaces/{spaceId}/products */
    public function index(string $spaceId): void
    {
        $this->requireAuth();
        $sid = (int) $spaceId;
        $this->requireAccess($sid);

        $products = $this->productModel->findBySpace($sid);
        $this->json([
            'success' => true,
            'data'    => array_map([$this, 'formatProduct'], $products),
        ]);
    }

    /** POST /api/spaces/{spaceId}/products */
    public function store(string $spaceId): void
    {
        $this->requireAuth();
        $sid = (int) $spaceId;
        $this->requireManage($sid);

        $body = $this->getJsonBody();
        $name = trim($body['name'] ?? '');

        if ($name === '') {
            $this->error('Le nom du produit est requis.', 422);
        }

        $data = ['name' => $name];
        if (isset($body['description'])) {
            $data['description'] = trim($body['description']);
        }

        $categoryIds = array_map('intval', (array) ($body['category_ids'] ?? []));

        $id      = $this->productModel->createInSpace($sid, $data, $categoryIds);
        $product = $this->productModel->findWithCategories($id);

        // Ajouter les noms depuis la liste globale
        $all = $this->productModel->findBySpace($sid);
        foreach ($all as $p) {
            if ((int) $p['id'] === $id) {
                $product['category_names'] = $p['category_names'];
                break;
            }
        }

        $this->json(['success' => true, 'data' => $this->formatProduct($product)], 201);
    }

    /** GET /api/spaces/{spaceId}/products/{id} */
    public function show(string $spaceId, string $id): void
    {
        $this->requireAuth();
        $sid = (int) $spaceId;
        $this->requireAccess($sid);

        $product = $this->productModel->findWithCategories((int) $id);
        if (!$product || (int) $product['space_id'] !== $sid) {
            $this->error('Produit introuvable.', 404);
        }

        $this->json(['success' => true, 'data' => $this->formatProduct($product)]);
    }

    /** PUT /api/spaces/{spaceId}/products/{id} */
    public function update(string $spaceId, string $id): void
    {
        $this->requireAuth();
        $sid = (int) $spaceId;
        $this->requireManage($sid);

        $product = $this->productModel->find((int) $id);
        if (!$product || (int) $product['space_id'] !== $sid) {
            $this->error('Produit introuvable.', 404);
        }

        $body = $this->getJsonBody();
        $data = [];

        if (isset($body['name'])) {
            $name = trim($body['name']);
            if ($name === '') {
                $this->error('Le nom ne peut pas être vide.', 422);
            }
            $data['name'] = $name;
        }

        if (array_key_exists('description', $body)) {
            $data['description'] = trim($body['description'] ?? '');
        }

        $categoryIds = isset($body['category_ids'])
            ? array_map('intval', (array) $body['category_ids'])
            : $this->productModel->getCategoryIds((int) $id);

        if (empty($data) && !isset($body['category_ids'])) {
            $this->error('Aucune donnée valide fournie.', 422);
        }

        $this->productModel->updateWithCategories((int) $id, $data, $categoryIds);
        $updated = $this->productModel->findWithCategories((int) $id);

        $this->json(['success' => true, 'data' => $this->formatProduct($updated)]);
    }

    /** DELETE /api/spaces/{spaceId}/products/{id} */
    public function destroy(string $spaceId, string $id): void
    {
        $this->requireAuth();
        $sid = (int) $spaceId;
        $this->requireManage($sid);

        $product = $this->productModel->find((int) $id);
        if (!$product || (int) $product['space_id'] !== $sid) {
            $this->error('Produit introuvable.', 404);
        }

        $this->productModel->delete((int) $id);
        $this->json(['success' => true, 'message' => 'Produit supprimé.']);
    }
}
