<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Controller;
use App\Core\Session;
use App\Models\Space;
use App\Models\User;

class SpaceController extends Controller
{
    private Space $spaceModel;
    private User $userModel;

    public function __construct()
    {
        $this->spaceModel = new Space();
        $this->userModel = new User();
    }

    /**
     * Vérifie l'accès à un espace et retourne le rôle de l'utilisateur.
     */
    private function requireSpaceAccess(int $spaceId, array $allowedRoles = []): string
    {
        $this->requireAuth();
        $userId = $this->getCurrentUserId();
        $role = $this->spaceModel->getUserRole($spaceId, $userId);

        if (!$role) {
            $this->setFlash('danger', 'Accès non autorisé à cet espace.');
            $this->redirect('/spaces');
            exit;
        }

        if (!empty($allowedRoles) && !in_array($role, $allowedRoles, true)) {
            $this->setFlash('danger', 'Vous n\'avez pas les permissions nécessaires.');
            $this->redirect('/spaces/' . $spaceId);
            exit;
        }

        return $role;
    }

    /**
     * Liste des espaces de l'utilisateur.
     */
    public function index(): void
    {
        $this->requireAuth();
        $spaces = $this->spaceModel->findByUser($this->getCurrentUserId());

        $this->render('spaces/index', [
            'title' => 'Mes espaces',
            'spaces' => $spaces,
        ]);
    }

    /**
     * Formulaire de création d'un espace.
     */
    public function createForm(): void
    {
        $this->requireAuth();
        $this->render('spaces/create', [
            'title' => 'Créer un espace',
        ]);
    }

    /**
     * Traitement de la création d'un espace.
     */
    public function store(): void
    {
        $this->requireAuth();
        $this->validateCSRF();
        $data = $this->getPostData(['name', 'description']);

        if (empty($data['name'])) {
            $this->setFlash('danger', 'Le nom de l\'espace est requis.');
            $this->redirect('/spaces/create');
            return;
        }

        $spaceId = $this->spaceModel->createWithOwner($data, $this->getCurrentUserId());
        $this->setFlash('success', 'Espace créé avec succès !');
        $this->redirect('/spaces/' . $spaceId);
    }

    /**
     * Vue détaillée d'un espace (tableau de bord).
     */
    public function show(string $id): void
    {
        $spaceId = (int)$id;
        $role = $this->requireSpaceAccess($spaceId);
        $space = $this->spaceModel->find($spaceId);

        if (!$space) {
            $this->setFlash('danger', 'Espace introuvable.');
            $this->redirect('/spaces');
            return;
        }

        $this->render('spaces/show', [
            'title' => $space['name'],
            'space' => $space,
            'role' => $role,
        ]);
    }

    /**
     * Formulaire de modification d'un espace.
     */
    public function editForm(string $id): void
    {
        $spaceId = (int)$id;
        $this->requireSpaceAccess($spaceId, ['administrateur']);
        $space = $this->spaceModel->find($spaceId);

        $this->render('spaces/edit', [
            'title' => 'Modifier l\'espace',
            'space' => $space,
        ]);
    }

    /**
     * Traitement de la modification d'un espace.
     */
    public function update(string $id): void
    {
        $spaceId = (int)$id;
        $this->requireSpaceAccess($spaceId, ['administrateur']);
        $this->validateCSRF();
        $data = $this->getPostData(['name', 'description']);

        if (empty($data['name'])) {
            $this->setFlash('danger', 'Le nom de l\'espace est requis.');
            $this->redirect('/spaces/' . $spaceId . '/edit');
            return;
        }

        $this->spaceModel->update($spaceId, $data);
        $this->setFlash('success', 'Espace mis à jour.');
        $this->redirect('/spaces/' . $spaceId);
    }

    /**
     * Suppression d'un espace.
     */
    public function destroy(string $id): void
    {
        $spaceId = (int)$id;
        $this->requireSpaceAccess($spaceId, ['administrateur']);
        $this->validateCSRF();

        $this->spaceModel->delete($spaceId);
        $this->setFlash('success', 'Espace supprimé.');
        $this->redirect('/spaces');
    }

    /**
     * Gestion des membres d'un espace.
     */
    public function members(string $id): void
    {
        $spaceId = (int)$id;
        $role = $this->requireSpaceAccess($spaceId, ['administrateur']);
        $space = $this->spaceModel->find($spaceId);
        $members = $this->spaceModel->getMembers($spaceId);

        $this->render('spaces/members', [
            'title' => 'Membres — ' . $space['name'],
            'space' => $space,
            'members' => $members,
            'role' => $role,
        ]);
    }

    /**
     * Ajouter un membre à un espace.
     */
    public function addMember(string $id): void
    {
        $spaceId = (int)$id;
        $this->requireSpaceAccess($spaceId, ['administrateur']);
        $this->validateCSRF();
        $data = $this->getPostData(['email', 'role']);

        if (empty($data['email'])) {
            $this->setFlash('danger', 'L\'email est requis.');
            $this->redirect('/spaces/' . $spaceId . '/members');
            return;
        }

        $user = $this->userModel->findByEmail($data['email']);
        if (!$user) {
            $this->setFlash('danger', 'Aucun utilisateur trouvé avec cette adresse email.');
            $this->redirect('/spaces/' . $spaceId . '/members');
            return;
        }

        $validRoles = ['membre', 'gestionnaire_produits', 'gestionnaire_inventaires', 'gestionnaire_global', 'administrateur'];
        $role = in_array($data['role'], $validRoles, true) ? $data['role'] : 'membre';

        $this->spaceModel->addMember($spaceId, $user['id'], $role);
        $this->setFlash('success', 'Membre ajouté avec succès.');
        $this->redirect('/spaces/' . $spaceId . '/members');
    }

    /**
     * Modifier le rôle d'un membre.
     */
    public function updateMember(string $id, string $userId): void
    {
        $spaceId = (int)$id;
        $this->requireSpaceAccess($spaceId, ['administrateur']);
        $this->validateCSRF();
        $data = $this->getPostData(['role']);

        $validRoles = ['membre', 'gestionnaire_produits', 'gestionnaire_inventaires', 'gestionnaire_global', 'administrateur'];
        if (!in_array($data['role'], $validRoles, true)) {
            $this->setFlash('danger', 'Rôle invalide.');
            $this->redirect('/spaces/' . $spaceId . '/members');
            return;
        }

        $this->spaceModel->updateMemberRole($spaceId, (int)$userId, $data['role']);
        $this->setFlash('success', 'Rôle mis à jour.');
        $this->redirect('/spaces/' . $spaceId . '/members');
    }

    /**
     * Retirer un membre d'un espace.
     */
    public function removeMember(string $id, string $userId): void
    {
        $spaceId = (int)$id;
        $this->requireSpaceAccess($spaceId, ['administrateur']);
        $this->validateCSRF();

        if ((int)$userId === $this->getCurrentUserId()) {
            $this->setFlash('danger', 'Vous ne pouvez pas vous retirer vous-même.');
            $this->redirect('/spaces/' . $spaceId . '/members');
            return;
        }

        $this->spaceModel->removeMember($spaceId, (int)$userId);
        $this->setFlash('success', 'Membre retiré.');
        $this->redirect('/spaces/' . $spaceId . '/members');
    }
}
