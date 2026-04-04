<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Controller;
use App\Models\Space;
use App\Models\Category;

class CategoryController extends Controller
{
    private Space $spaceModel;
    private Category $categoryModel;

    public function __construct()
    {
        $this->spaceModel = new Space();
        $this->categoryModel = new Category();
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
     * Liste des catégories d'un espace.
     */
    public function index(string $spaceId): void
    {
        $sid = (int)$spaceId;
        $role = $this->requireProductAccess($sid);
        $space = $this->spaceModel->find($sid);
        $categories = $this->categoryModel->findBySpace($sid);

        $this->render('categories/index', [
            'title' => 'Catégories — ' . $space['name'],
            'space' => $space,
            'categories' => $categories,
            'role' => $role,
        ]);
    }

    /**
     * Formulaire de création d'une catégorie.
     */
    public function createForm(string $spaceId): void
    {
        $sid = (int)$spaceId;
        $this->requireProductManagement($sid);
        $space = $this->spaceModel->find($sid);

        $this->render('categories/create', [
            'title' => 'Nouvelle catégorie',
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
        $data = $this->getPostData(['name', 'description', 'max_consumption_days']);

        if (empty($data['name'])) {
            $this->setFlash('danger', 'Le nom est requis.');
            $this->redirect('/spaces/' . $sid . '/categories/create');
            return;
        }

        $insertData = [
            'name' => $data['name'],
            'description' => $data['description'] ?: null,
        ];
        if (!empty($data['max_consumption_days'])) {
            $insertData['max_consumption_days'] = (int)$data['max_consumption_days'];
        }

        $this->categoryModel->createInSpace($sid, $insertData);
        $this->setFlash('success', 'Catégorie créée.');
        $this->redirect('/spaces/' . $sid . '/categories');
    }

    /**
     * Formulaire de modification.
     */
    public function editForm(string $spaceId, string $id): void
    {
        $sid = (int)$spaceId;
        $this->requireProductManagement($sid);
        $space = $this->spaceModel->find($sid);
        $category = $this->categoryModel->find((int)$id);

        if (!$category || (int)$category['space_id'] !== $sid) {
            $this->setFlash('danger', 'Catégorie introuvable.');
            $this->redirect('/spaces/' . $sid . '/categories');
            return;
        }

        $this->render('categories/edit', [
            'title' => 'Modifier la catégorie',
            'space' => $space,
            'category' => $category,
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
        $data = $this->getPostData(['name', 'description', 'max_consumption_days']);

        if (empty($data['name'])) {
            $this->setFlash('danger', 'Le nom est requis.');
            $this->redirect('/spaces/' . $sid . '/categories/' . $id . '/edit');
            return;
        }

        $updateData = [
            'name' => $data['name'],
            'description' => $data['description'] ?: null,
            'max_consumption_days' => !empty($data['max_consumption_days']) ? (int)$data['max_consumption_days'] : null,
        ];

        $this->categoryModel->update((int)$id, $updateData);
        $this->setFlash('success', 'Catégorie mise à jour.');
        $this->redirect('/spaces/' . $sid . '/categories');
    }

    /**
     * Suppression d'une catégorie.
     */
    public function destroy(string $spaceId, string $id): void
    {
        $sid = (int)$spaceId;
        $this->requireProductManagement($sid);
        $this->validateCSRF();

        $this->categoryModel->delete((int)$id);
        $this->setFlash('success', 'Catégorie supprimée.');
        $this->redirect('/spaces/' . $sid . '/categories');
    }
}
