<?php

declare(strict_types=1);

namespace App\Controllers\Api;

use App\Models\Category;
use App\Models\Space;

/**
 * API — Catégories
 *
 * GET    /api/spaces/{spaceId}/categories        → Liste des catégories
 * POST   /api/spaces/{spaceId}/categories        → Créer une catégorie
 * GET    /api/spaces/{spaceId}/categories/{id}   → Détail d'une catégorie
 * PUT    /api/spaces/{spaceId}/categories/{id}   → Modifier une catégorie
 * DELETE /api/spaces/{spaceId}/categories/{id}   → Supprimer une catégorie
 */
class CategoryApiController extends ApiController
{
    private Category $categoryModel;
    private Space $spaceModel;

    public function __construct()
    {
        $this->categoryModel = new Category();
        $this->spaceModel    = new Space();
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

    private function formatCategory(array $c): array
    {
        return [
            'id'                   => (int) $c['id'],
            'space_id'             => (int) $c['space_id'],
            'name'                 => $c['name'],
            'description'          => $c['description'] ?? null,
            'max_consumption_days' => isset($c['max_consumption_days']) ? (int) $c['max_consumption_days'] : null,
        ];
    }

    /** GET /api/spaces/{spaceId}/categories */
    public function index(string $spaceId): void
    {
        $this->requireAuth();
        $sid = (int) $spaceId;
        $this->requireAccess($sid);

        $cats = $this->categoryModel->findBySpace($sid);
        $this->json([
            'success' => true,
            'data'    => array_map([$this, 'formatCategory'], $cats),
        ]);
    }

    /** POST /api/spaces/{spaceId}/categories */
    public function store(string $spaceId): void
    {
        $this->requireAuth();
        $sid = (int) $spaceId;
        $this->requireManage($sid);

        $body = $this->getJsonBody();
        $name = trim($body['name'] ?? '');

        if ($name === '') {
            $this->error('Le nom de la catégorie est requis.', 422);
        }

        $data = ['name' => $name];
        if (isset($body['description'])) {
            $data['description'] = trim($body['description']);
        }
        if (isset($body['max_consumption_days']) && $body['max_consumption_days'] !== null) {
            $days = (int) $body['max_consumption_days'];
            if ($days < 1) {
                $this->error('La durée maximale doit être un entier positif.', 422);
            }
            $data['max_consumption_days'] = $days;
        }

        $id = $this->categoryModel->createInSpace($sid, $data);
        $cat = $this->categoryModel->find($id);

        $this->json(['success' => true, 'data' => $this->formatCategory($cat)], 201);
    }

    /** GET /api/spaces/{spaceId}/categories/{id} */
    public function show(string $spaceId, string $id): void
    {
        $this->requireAuth();
        $sid = (int) $spaceId;
        $this->requireAccess($sid);

        $cat = $this->categoryModel->find((int) $id);
        if (!$cat || (int) $cat['space_id'] !== $sid) {
            $this->error('Catégorie introuvable.', 404);
        }

        $this->json(['success' => true, 'data' => $this->formatCategory($cat)]);
    }

    /** PUT /api/spaces/{spaceId}/categories/{id} */
    public function update(string $spaceId, string $id): void
    {
        $this->requireAuth();
        $sid = (int) $spaceId;
        $this->requireManage($sid);

        $cat = $this->categoryModel->find((int) $id);
        if (!$cat || (int) $cat['space_id'] !== $sid) {
            $this->error('Catégorie introuvable.', 404);
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

        if (array_key_exists('max_consumption_days', $body)) {
            if ($body['max_consumption_days'] === null || $body['max_consumption_days'] === '') {
                $data['max_consumption_days'] = null;
            } else {
                $days = (int) $body['max_consumption_days'];
                if ($days < 1) {
                    $this->error('La durée maximale doit être un entier positif.', 422);
                }
                $data['max_consumption_days'] = $days;
            }
        }

        if (empty($data)) {
            $this->error('Aucune donnée valide fournie.', 422);
        }

        $this->categoryModel->update((int) $id, $data);
        $updated = $this->categoryModel->find((int) $id);

        $this->json(['success' => true, 'data' => $this->formatCategory($updated)]);
    }

    /** DELETE /api/spaces/{spaceId}/categories/{id} */
    public function destroy(string $spaceId, string $id): void
    {
        $this->requireAuth();
        $sid = (int) $spaceId;
        $this->requireManage($sid);

        $cat = $this->categoryModel->find((int) $id);
        if (!$cat || (int) $cat['space_id'] !== $sid) {
            $this->error('Catégorie introuvable.', 404);
        }

        $this->categoryModel->delete((int) $id);
        $this->json(['success' => true, 'message' => 'Catégorie supprimée.']);
    }
}
