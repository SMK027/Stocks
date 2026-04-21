<?php

declare(strict_types=1);

namespace App\Controllers\Api;

use App\Core\JWT;
use App\Models\User;

/**
 * API — Authentification
 *
 * POST /api/auth/login   → Connexion, retourne un JWT (public)
 * POST /api/auth/refresh → Renouvelle le token (authentifié)
 * GET  /api/auth/me      → Profil de l'utilisateur connecté (authentifié)
 */
class AuthApiController extends ApiController
{
    private User $userModel;

    public function __construct()
    {
        $this->userModel = new User();
    }

    /**
     * POST /api/auth/login
     * Corps JSON : { "email": "...", "password": "..." }
     */
    public function login(): void
    {
        $body = $this->getJsonBody();
        $email    = trim($body['email'] ?? '');
        $password = $body['password'] ?? '';

        if ($email === '' || $password === '') {
            $this->error('Email et mot de passe requis.', 422);
        }

        $user = $this->userModel->authenticate($email, $password);
        if (!$user) {
            $this->error('Identifiants incorrects.', 401);
        }

        $token = JWT::encode([
            'user_id'     => $user['id'],
            'username'    => $user['username'],
            'email'       => $user['email'],
            'global_role' => $user['global_role'] ?? 'user',
        ]);

        $this->json([
            'success' => true,
            'token'   => $token,
            'user'    => [
                'id'          => (int) $user['id'],
                'username'    => $user['username'],
                'email'       => $user['email'],
                'firstname'   => $user['firstname'] ?? null,
                'lastname'    => $user['lastname'] ?? null,
                'global_role' => $user['global_role'] ?? 'user',
            ],
        ]);
    }

    /**
     * POST /api/auth/refresh
     * Renouvelle le JWT courant pour 30 jours supplémentaires.
     */
    public function refresh(): void
    {
        $this->requireAuth();

        $user = $this->userModel->find($this->userId);
        if (!$user) {
            $this->error('Utilisateur introuvable.', 404);
        }

        $token = JWT::encode([
            'user_id'     => $user['id'],
            'username'    => $user['username'],
            'email'       => $user['email'],
            'global_role' => $user['global_role'] ?? 'user',
        ]);

        $this->json(['success' => true, 'token' => $token]);
    }

    /**
     * GET /api/auth/me
     * Retourne le profil de l'utilisateur authentifié.
     */
    public function me(): void
    {
        $this->requireAuth();

        $user = $this->userModel->find($this->userId);
        if (!$user) {
            $this->error('Utilisateur introuvable.', 404);
        }

        $this->json([
            'success' => true,
            'user'    => [
                'id'           => (int) $user['id'],
                'username'     => $user['username'],
                'email'        => $user['email'],
                'firstname'    => $user['firstname'] ?? null,
                'lastname'     => $user['lastname'] ?? null,
                'global_role'  => $user['global_role'] ?? 'user',
                'daily_digest' => (bool) ($user['daily_digest'] ?? false),
            ],
        ]);
    }

    /**
     * PUT /api/auth/me
     * Met à jour le profil de l'utilisateur connecté.
     * Corps JSON : { "username": "...", "email": "...", "firstname": "...", "lastname": "...", "daily_digest": true }
     */
    public function updateMe(): void
    {
        $this->requireAuth();

        $body = $this->getJsonBody();
        $allowed = ['username', 'email', 'firstname', 'lastname', 'daily_digest'];
        $data = array_intersect_key($body, array_flip($allowed));

        if (empty($data)) {
            $this->error('Aucune donnée valide fournie.', 422);
        }

        $ok = $this->userModel->updateProfile($this->userId, $data);
        if (!$ok) {
            $this->error('Mise à jour échouée.', 500);
        }

        $user = $this->userModel->find($this->userId);
        $this->json([
            'success' => true,
            'message' => 'Profil mis à jour.',
            'user'    => [
                'id'           => (int) $user['id'],
                'username'     => $user['username'],
                'email'        => $user['email'],
                'firstname'    => $user['firstname'] ?? null,
                'lastname'     => $user['lastname'] ?? null,
                'global_role'  => $user['global_role'] ?? 'user',
                'daily_digest' => (bool) ($user['daily_digest'] ?? false),
            ],
        ]);
    }

    /**
     * POST /api/auth/password
     * Change le mot de passe de l'utilisateur connecté.
     * Corps JSON : { "current_password": "...", "new_password": "..." }
     */
    public function changePassword(): void
    {
        $this->requireAuth();

        $body            = $this->getJsonBody();
        $currentPassword = $body['current_password'] ?? '';
        $newPassword     = $body['new_password'] ?? '';

        if ($currentPassword === '' || $newPassword === '') {
            $this->error('Les deux mots de passe sont requis.', 422);
        }

        if (strlen($newPassword) < 8) {
            $this->error('Le nouveau mot de passe doit comporter au moins 8 caractères.', 422);
        }

        $user = $this->userModel->find($this->userId);
        if (!$user || !password_verify($currentPassword, $user['password'])) {
            $this->error('Mot de passe actuel incorrect.', 401);
        }

        $this->userModel->updatePassword($this->userId, $newPassword);
        $this->json(['success' => true, 'message' => 'Mot de passe mis à jour.']);
    }
}
