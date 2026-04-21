<?php

declare(strict_types=1);

namespace App\Controllers\Api;

use App\Models\Space;
use App\Models\User;

/**
 * API — Espaces & Membres
 *
 * GET    /api/spaces                              → Liste des espaces de l'utilisateur
 * POST   /api/spaces                              → Créer un espace
 * GET    /api/spaces/{id}                         → Détail d'un espace
 * PUT    /api/spaces/{id}                         → Modifier un espace (admin)
 * DELETE /api/spaces/{id}                         → Supprimer un espace (admin)
 * GET    /api/spaces/{id}/members                 → Membres de l'espace
 * POST   /api/spaces/{id}/members                 → Ajouter un membre
 * PUT    /api/spaces/{id}/members/{userId}        → Modifier le rôle d'un membre
 * DELETE /api/spaces/{id}/members/{userId}        → Retirer un membre
 */
class SpaceApiController extends ApiController
{
    private Space $spaceModel;
    private User $userModel;

    private const ROLES = [
        'membre',
        'gestionnaire_produits',
        'gestionnaire_inventaires',
        'gestionnaire_global',
        'administrateur',
    ];

    public function __construct()
    {
        $this->spaceModel = new Space();
        $this->userModel  = new User();
    }

    // ----------------------------------------------------------------
    //  Helpers
    // ----------------------------------------------------------------

    /**
     * Vérifie que l'utilisateur est membre de l'espace et retourne son rôle.
     */
    private function requireSpaceAccess(int $spaceId): string
    {
        $role = $this->spaceModel->getUserRole($spaceId, $this->userId);
        if (!$role) {
            $this->error('Espace introuvable ou accès refusé.', 403);
        }
        return $role;
    }

    /**
     * Vérifie que l'utilisateur est administrateur de l'espace.
     */
    private function requireSpaceAdmin(int $spaceId): void
    {
        $role = $this->requireSpaceAccess($spaceId);
        if ($role !== 'administrateur') {
            $this->error('Droits administrateur requis.', 403);
        }
    }

    private function formatSpace(array $s): array
    {
        return [
            'id'          => (int) $s['id'],
            'name'        => $s['name'],
            'description' => $s['description'] ?? null,
            'created_by'  => (int) ($s['created_by'] ?? 0),
            'role'        => $s['role'] ?? null,
        ];
    }

    // ----------------------------------------------------------------
    //  Endpoints
    // ----------------------------------------------------------------

    /** GET /api/spaces */
    public function index(): void
    {
        $this->requireAuth();

        $spaces = $this->spaceModel->findByUser($this->userId);
        $this->json([
            'success' => true,
            'data'    => array_map([$this, 'formatSpace'], $spaces),
        ]);
    }

    /** POST /api/spaces */
    public function store(): void
    {
        $this->requireAuth();

        $body = $this->getJsonBody();
        $name = trim($body['name'] ?? '');

        if ($name === '') {
            $this->error('Le nom de l\'espace est requis.', 422);
        }

        $id = $this->spaceModel->createWithOwner([
            'name'        => $name,
            'description' => trim($body['description'] ?? ''),
        ], $this->userId);

        $space = $this->spaceModel->find($id);
        $space['role'] = 'administrateur';

        $this->json(['success' => true, 'data' => $this->formatSpace($space)], 201);
    }

    /** GET /api/spaces/{id} */
    public function show(string $id): void
    {
        $this->requireAuth();
        $spaceId = (int) $id;

        $role  = $this->requireSpaceAccess($spaceId);
        $space = $this->spaceModel->find($spaceId);
        if (!$space) {
            $this->error('Espace introuvable.', 404);
        }
        $space['role'] = $role;

        $this->json(['success' => true, 'data' => $this->formatSpace($space)]);
    }

    /** PUT /api/spaces/{id} */
    public function update(string $id): void
    {
        $this->requireAuth();
        $spaceId = (int) $id;
        $this->requireSpaceAdmin($spaceId);

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

        $this->spaceModel->update($spaceId, $data);
        $space = $this->spaceModel->find($spaceId);
        $space['role'] = 'administrateur';

        $this->json(['success' => true, 'data' => $this->formatSpace($space)]);
    }

    /** DELETE /api/spaces/{id} */
    public function destroy(string $id): void
    {
        $this->requireAuth();
        $spaceId = (int) $id;
        $this->requireSpaceAdmin($spaceId);

        $this->spaceModel->delete($spaceId);
        $this->json(['success' => true, 'message' => 'Espace supprimé.']);
    }

    // ----------------------------------------------------------------
    //  Membres
    // ----------------------------------------------------------------

    /** GET /api/spaces/{id}/members */
    public function members(string $id): void
    {
        $this->requireAuth();
        $spaceId = (int) $id;
        $this->requireSpaceAccess($spaceId);

        $members = $this->spaceModel->getMembers($spaceId);
        $this->json([
            'success' => true,
            'data'    => array_map(static fn($m) => [
                'user_id'   => (int) $m['id'],
                'username'  => $m['username'],
                'email'     => $m['email'],
                'role'      => $m['role'],
                'joined_at' => $m['joined_at'],
            ], $members),
        ]);
    }

    /** POST /api/spaces/{id}/members */
    public function addMember(string $id): void
    {
        $this->requireAuth();
        $spaceId = (int) $id;
        $this->requireSpaceAdmin($spaceId);

        $body  = $this->getJsonBody();
        $email = trim($body['email'] ?? '');
        $role  = $body['role'] ?? 'membre';

        if ($email === '') {
            $this->error('L\'email de l\'utilisateur est requis.', 422);
        }

        if (!in_array($role, self::ROLES, true)) {
            $this->error('Rôle invalide.', 422);
        }

        $user = $this->userModel->findByEmail($email);
        if (!$user) {
            $this->error('Aucun utilisateur trouvé avec cet email.', 404);
        }

        $added = $this->spaceModel->addMember($spaceId, (int) $user['id'], $role);
        if (!$added) {
            $this->error('Cet utilisateur est déjà membre de cet espace.', 409);
        }

        $this->json(['success' => true, 'message' => 'Membre ajouté.'], 201);
    }

    /** PUT /api/spaces/{id}/members/{userId} */
    public function updateMember(string $id, string $userId): void
    {
        $this->requireAuth();
        $spaceId       = (int) $id;
        $targetUserId  = (int) $userId;
        $this->requireSpaceAdmin($spaceId);

        $body = $this->getJsonBody();
        $role = $body['role'] ?? '';

        if (!in_array($role, self::ROLES, true)) {
            $this->error('Rôle invalide.', 422);
        }

        $this->spaceModel->updateMemberRole($spaceId, $targetUserId, $role);
        $this->json(['success' => true, 'message' => 'Rôle mis à jour.']);
    }

    /** DELETE /api/spaces/{id}/members/{userId} */
    public function removeMember(string $id, string $userId): void
    {
        $this->requireAuth();
        $spaceId      = (int) $id;
        $targetUserId = (int) $userId;
        $this->requireSpaceAdmin($spaceId);

        if ($targetUserId === $this->userId) {
            $this->error('Vous ne pouvez pas vous retirer vous-même.', 422);
        }

        $this->spaceModel->removeMember($spaceId, $targetUserId);
        $this->json(['success' => true, 'message' => 'Membre retiré.']);
    }
}
