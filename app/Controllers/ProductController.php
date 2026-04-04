<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Controller;
use App\Models\Space;
use App\Models\Product;
use App\Models\Category;

class ProductController extends Controller
{
    private Space $spaceModel;
    private Product $productModel;
    private Category $categoryModel;

    public function __construct()
    {
        $this->spaceModel = new Space();
        $this->productModel = new Product();
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
     * Liste des produits d'un espace.
     */
    public function index(string $spaceId): void
    {
        $sid = (int)$spaceId;
        $role = $this->requireProductAccess($sid);
        $space = $this->spaceModel->find($sid);
        $products = $this->productModel->findBySpace($sid);

        $this->render('products/index', [
            'title' => 'Produits — ' . $space['name'],
            'space' => $space,
            'products' => $products,
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
        $categories = $this->categoryModel->findBySpace($sid);

        $this->render('products/create', [
            'title' => 'Nouveau produit',
            'space' => $space,
            'categories' => $categories,
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
        $data = $this->getPostData(['name']);
        $categoryIds = $_POST['categories'] ?? [];

        if (empty($data['name'])) {
            $this->setFlash('danger', 'Le nom du produit est requis.');
            $this->redirect('/spaces/' . $sid . '/products/create');
            return;
        }

        // Filtrer les IDs de catégories (entiers uniquement)
        $categoryIds = array_map('intval', array_filter($categoryIds, 'is_numeric'));

        $this->productModel->createInSpace($sid, [
            'name' => $data['name'],
        ], $categoryIds);

        $this->setFlash('success', 'Produit créé.');
        $this->redirect('/spaces/' . $sid . '/products');
    }

    /**
     * Formulaire de modification.
     */
    public function editForm(string $spaceId, string $id): void
    {
        $sid = (int)$spaceId;
        $this->requireProductManagement($sid);
        $space = $this->spaceModel->find($sid);
        $product = $this->productModel->findWithCategories((int)$id);
        $categories = $this->categoryModel->findBySpace($sid);

        if (!$product || (int)$product['space_id'] !== $sid) {
            $this->setFlash('danger', 'Produit introuvable.');
            $this->redirect('/spaces/' . $sid . '/products');
            return;
        }

        $this->render('products/edit', [
            'title' => 'Modifier le produit',
            'space' => $space,
            'product' => $product,
            'categories' => $categories,
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
        $data = $this->getPostData(['name']);
        $categoryIds = $_POST['categories'] ?? [];

        if (empty($data['name'])) {
            $this->setFlash('danger', 'Le nom du produit est requis.');
            $this->redirect('/spaces/' . $sid . '/products/' . $id . '/edit');
            return;
        }

        $categoryIds = array_map('intval', array_filter($categoryIds, 'is_numeric'));

        $this->productModel->updateWithCategories((int)$id, [
            'name' => $data['name'],
        ], $categoryIds);

        $this->setFlash('success', 'Produit mis à jour.');
        $this->redirect('/spaces/' . $sid . '/products');
    }

    /**
     * Suppression.
     */
    public function destroy(string $spaceId, string $id): void
    {
        $sid = (int)$spaceId;
        $this->requireProductManagement($sid);
        $this->validateCSRF();

        $this->productModel->delete((int)$id);
        $this->setFlash('success', 'Produit supprimé.');
        $this->redirect('/spaces/' . $sid . '/products');
    }

    /**
     * API : retourne la durée max de consommation pour des catégories.
     */
    public function getMaxConsumptionDays(string $spaceId): void
    {
        $sid = (int)$spaceId;
        $this->requireAuth();
        $role = $this->spaceModel->getUserRole($sid, $this->getCurrentUserId());

        if (!$role) {
            $this->json(['error' => 'Accès non autorisé'], 403);
            return;
        }

        $categoryIds = $_GET['categories'] ?? [];
        if (empty($categoryIds)) {
            $this->json(['max_days' => null]);
            return;
        }

        $categoryIds = array_map('intval', array_filter($categoryIds, 'is_numeric'));
        $maxDays = null;

        foreach ($categoryIds as $catId) {
            $cat = $this->categoryModel->find($catId);
            if ($cat && $cat['max_consumption_days']) {
                $days = (int)$cat['max_consumption_days'];
                if ($maxDays === null || $days < $maxDays) {
                    $maxDays = $days;
                }
            }
        }

        $this->json(['max_days' => $maxDays]);
    }
}
