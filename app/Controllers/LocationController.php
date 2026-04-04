<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Controller;
use App\Models\Space;
use App\Models\Location;

class LocationController extends Controller
{
    private Space $spaceModel;
    private Location $locationModel;

    public function __construct()
    {
        $this->spaceModel = new Space();
        $this->locationModel = new Location();
    }

    private function requireProductAccess(int $spaceId): string
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

    private function requireProductManagement(int $spaceId): void
    {
        $role = $this->requireProductAccess($spaceId);
        $allowed = ['gestionnaire_produits', 'gestionnaire_global', 'administrateur'];

        if (!in_array($role, $allowed, true)) {
            $this->setFlash('danger', 'Permissions insuffisantes.');
            $this->redirect('/spaces/' . $spaceId);
            exit;
        }
    }

    /**
     * Liste des emplacements d'un espace.
     */
    public function index(string $spaceId): void
    {
        $sid = (int)$spaceId;
        $role = $this->requireProductAccess($sid);
        $space = $this->spaceModel->find($sid);
        $locations = $this->locationModel->findBySpace($sid);

        $this->render('locations/index', [
            'title' => 'Emplacements — ' . $space['name'],
            'space' => $space,
            'locations' => $locations,
            'role' => $role,
        ]);
    }

    /**
     * Formulaire de création.
     */
    public function createForm(string $spaceId): void
    {
        $sid = (int)$spaceId;
        $this->requireProductManagement($sid);
        $space = $this->spaceModel->find($sid);

        $this->render('locations/create', [
            'title' => 'Nouvel emplacement',
            'space' => $space,
        ]);
    }

    /**
     * Traitement de la création.
     */
    public function store(string $spaceId): void
    {
        $sid = (int)$spaceId;
        $this->requireProductManagement($sid);
        $this->validateCSRF();
        $data = $this->getPostData(['name', 'description']);

        if (empty($data['name'])) {
            $this->setFlash('danger', 'Le nom est requis.');
            $this->redirect('/spaces/' . $sid . '/locations/create');
            return;
        }

        $this->locationModel->createInSpace($sid, [
            'name' => $data['name'],
            'description' => $data['description'] ?: null,
        ]);
        $this->setFlash('success', 'Emplacement créé.');
        $this->redirect('/spaces/' . $sid . '/locations');
    }

    /**
     * Formulaire de modification.
     */
    public function editForm(string $spaceId, string $id): void
    {
        $sid = (int)$spaceId;
        $this->requireProductManagement($sid);
        $space = $this->spaceModel->find($sid);
        $location = $this->locationModel->find((int)$id);

        if (!$location || (int)$location['space_id'] !== $sid) {
            $this->setFlash('danger', 'Emplacement introuvable.');
            $this->redirect('/spaces/' . $sid . '/locations');
            return;
        }

        $this->render('locations/edit', [
            'title' => 'Modifier l\'emplacement',
            'space' => $space,
            'location' => $location,
        ]);
    }

    /**
     * Traitement de la modification.
     */
    public function update(string $spaceId, string $id): void
    {
        $sid = (int)$spaceId;
        $this->requireProductManagement($sid);
        $this->validateCSRF();
        $data = $this->getPostData(['name', 'description']);

        if (empty($data['name'])) {
            $this->setFlash('danger', 'Le nom est requis.');
            $this->redirect('/spaces/' . $sid . '/locations/' . $id . '/edit');
            return;
        }

        $this->locationModel->update((int)$id, [
            'name' => $data['name'],
            'description' => $data['description'] ?: null,
        ]);
        $this->setFlash('success', 'Emplacement mis à jour.');
        $this->redirect('/spaces/' . $sid . '/locations');
    }

    /**
     * Suppression.
     */
    public function destroy(string $spaceId, string $id): void
    {
        $sid = (int)$spaceId;
        $this->requireProductManagement($sid);
        $this->validateCSRF();

        $this->locationModel->delete((int)$id);
        $this->setFlash('success', 'Emplacement supprimé.');
        $this->redirect('/spaces/' . $sid . '/locations');
    }
}
