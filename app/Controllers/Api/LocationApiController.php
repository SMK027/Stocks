<?php

declare(strict_types=1);

namespace App\Controllers\Api;

use App\Models\Location;
use App\Models\Space;

/**
 * API — Emplacements
 *
 * GET    /api/spaces/{spaceId}/locations        → Liste des emplacements
 * POST   /api/spaces/{spaceId}/locations        → Créer un emplacement
 * GET    /api/spaces/{spaceId}/locations/{id}   → Détail d'un emplacement
 * PUT    /api/spaces/{spaceId}/locations/{id}   → Modifier un emplacement
 * DELETE /api/spaces/{spaceId}/locations/{id}   → Supprimer un emplacement
 */
class LocationApiController extends ApiController
{
    private Location $locationModel;
    private Space $spaceModel;

    public function __construct()
    {
        $this->locationModel = new Location();
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

    private function formatLocation(array $l): array
    {
        return [
            'id'          => (int) $l['id'],
            'space_id'    => (int) $l['space_id'],
            'name'        => $l['name'],
            'description' => $l['description'] ?? null,
        ];
    }

    /** GET /api/spaces/{spaceId}/locations */
    public function index(string $spaceId): void
    {
        $this->requireAuth();
        $sid = (int) $spaceId;
        $this->requireAccess($sid);

        $locs = $this->locationModel->findBySpace($sid);
        $this->json([
            'success' => true,
            'data'    => array_map([$this, 'formatLocation'], $locs),
        ]);
    }

    /** POST /api/spaces/{spaceId}/locations */
    public function store(string $spaceId): void
    {
        $this->requireAuth();
        $sid = (int) $spaceId;
        $this->requireManage($sid);

        $body = $this->getJsonBody();
        $name = trim($body['name'] ?? '');

        if ($name === '') {
            $this->error('Le nom de l\'emplacement est requis.', 422);
        }

        $data = ['name' => $name];
        if (isset($body['description'])) {
            $data['description'] = trim($body['description']);
        }

        $id  = $this->locationModel->createInSpace($sid, $data);
        $loc = $this->locationModel->find($id);

        $this->json(['success' => true, 'data' => $this->formatLocation($loc)], 201);
    }

    /** GET /api/spaces/{spaceId}/locations/{id} */
    public function show(string $spaceId, string $id): void
    {
        $this->requireAuth();
        $sid = (int) $spaceId;
        $this->requireAccess($sid);

        $loc = $this->locationModel->find((int) $id);
        if (!$loc || (int) $loc['space_id'] !== $sid) {
            $this->error('Emplacement introuvable.', 404);
        }

        $this->json(['success' => true, 'data' => $this->formatLocation($loc)]);
    }

    /** PUT /api/spaces/{spaceId}/locations/{id} */
    public function update(string $spaceId, string $id): void
    {
        $this->requireAuth();
        $sid = (int) $spaceId;
        $this->requireManage($sid);

        $loc = $this->locationModel->find((int) $id);
        if (!$loc || (int) $loc['space_id'] !== $sid) {
            $this->error('Emplacement introuvable.', 404);
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

        if (empty($data)) {
            $this->error('Aucune donnée valide fournie.', 422);
        }

        $this->locationModel->update((int) $id, $data);
        $updated = $this->locationModel->find((int) $id);

        $this->json(['success' => true, 'data' => $this->formatLocation($updated)]);
    }

    /** DELETE /api/spaces/{spaceId}/locations/{id} */
    public function destroy(string $spaceId, string $id): void
    {
        $this->requireAuth();
        $sid = (int) $spaceId;
        $this->requireManage($sid);

        $loc = $this->locationModel->find((int) $id);
        if (!$loc || (int) $loc['space_id'] !== $sid) {
            $this->error('Emplacement introuvable.', 404);
        }

        $this->locationModel->delete((int) $id);
        $this->json(['success' => true, 'message' => 'Emplacement supprimé.']);
    }
}
