<?php

declare(strict_types=1);

/**
 * Point d'entrée de l'application (Front Controller).
 * Toutes les requêtes HTTP passent par ce fichier.
 */

// Charger l'autoloader Composer
require_once __DIR__ . '/../vendor/autoload.php';

// Fuseau horaire par défaut
date_default_timezone_set('Europe/Paris');

use App\Core\Router;
use App\Core\Session;
use App\Controllers\HomeController;
use App\Controllers\AuthController;
use App\Controllers\SpaceController;
use App\Controllers\CategoryController;
use App\Controllers\LocationController;
use App\Controllers\ProductController;
use App\Controllers\InventoryController;
use App\Controllers\ProfileController;
use App\Controllers\DashboardController;
use App\Controllers\PasswordResetController;
use App\Controllers\Api\AuthApiController;
use App\Controllers\Api\SpaceApiController;
use App\Controllers\Api\CategoryApiController;
use App\Controllers\Api\LocationApiController;
use App\Controllers\Api\ProductApiController;
use App\Controllers\Api\InventoryApiController;

// Démarrer la session
Session::start();

// ============================================================
// En-têtes CORS (à adapter selon les besoins)
// ============================================================
$allowedOrigin = getenv('APP_URL') ?: 'http://localhost:8080';
header("Access-Control-Allow-Origin: {$allowedOrigin}");
header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization');
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(204);
    exit;
}

// ============================================================
// Initialiser le routeur
// ============================================================
$router = new Router();

// --- Routes publiques ---
$router->get('/', HomeController::class, 'index');

// --- Routes d'authentification ---
$router->get('/login', AuthController::class, 'loginForm');
$router->post('/login', AuthController::class, 'login');
$router->get('/register', AuthController::class, 'registerForm');
$router->post('/register', AuthController::class, 'register');
$router->get('/logout', AuthController::class, 'logout');

// --- Réinitialisation de mot de passe ---
$router->get('/password-reset', PasswordResetController::class, 'requestForm');
$router->post('/password-reset', PasswordResetController::class, 'request');
$router->get('/password-reset/{token}', PasswordResetController::class, 'resetForm');
$router->post('/password-reset/{token}', PasswordResetController::class, 'reset');

// --- Tableau de bord ---
$router->get('/dashboard', DashboardController::class, 'index');

// --- Profil utilisateur ---
$router->get('/profile', ProfileController::class, 'edit');
$router->post('/profile', ProfileController::class, 'update');
$router->post('/profile/password', ProfileController::class, 'updatePassword');
$router->post('/profile/send-digest-test', ProfileController::class, 'sendDigestTest');

// --- Espaces ---
$router->get('/spaces', SpaceController::class, 'index');
$router->get('/spaces/create', SpaceController::class, 'createForm');
$router->post('/spaces/create', SpaceController::class, 'store');
$router->get('/spaces/{id}', SpaceController::class, 'show');
$router->get('/spaces/{id}/edit', SpaceController::class, 'editForm');
$router->post('/spaces/{id}/edit', SpaceController::class, 'update');
$router->post('/spaces/{id}/delete', SpaceController::class, 'destroy');

// --- Membres d'un espace ---
$router->get('/spaces/{id}/members', SpaceController::class, 'members');
$router->post('/spaces/{id}/members/add', SpaceController::class, 'addMember');
$router->post('/spaces/{id}/members/{userId}/update', SpaceController::class, 'updateMember');
$router->post('/spaces/{id}/members/{userId}/remove', SpaceController::class, 'removeMember');

// --- Catégories ---
$router->get('/spaces/{spaceId}/categories', CategoryController::class, 'index');
$router->get('/spaces/{spaceId}/categories/create', CategoryController::class, 'createForm');
$router->post('/spaces/{spaceId}/categories/create', CategoryController::class, 'store');
$router->get('/spaces/{spaceId}/categories/{id}/edit', CategoryController::class, 'editForm');
$router->post('/spaces/{spaceId}/categories/{id}/edit', CategoryController::class, 'update');
$router->post('/spaces/{spaceId}/categories/{id}/delete', CategoryController::class, 'destroy');

// --- Emplacements ---
$router->get('/spaces/{spaceId}/locations', LocationController::class, 'index');
$router->get('/spaces/{spaceId}/locations/create', LocationController::class, 'createForm');
$router->post('/spaces/{spaceId}/locations/create', LocationController::class, 'store');
$router->get('/spaces/{spaceId}/locations/{id}/edit', LocationController::class, 'editForm');
$router->post('/spaces/{spaceId}/locations/{id}/edit', LocationController::class, 'update');
$router->post('/spaces/{spaceId}/locations/{id}/delete', LocationController::class, 'destroy');

// --- Produits ---
$router->get('/spaces/{spaceId}/products', ProductController::class, 'index');
$router->get('/spaces/{spaceId}/products/create', ProductController::class, 'createForm');
$router->post('/spaces/{spaceId}/products/create', ProductController::class, 'store');
$router->get('/spaces/{spaceId}/products/{id}/edit', ProductController::class, 'editForm');
$router->post('/spaces/{spaceId}/products/{id}/edit', ProductController::class, 'update');
$router->post('/spaces/{spaceId}/products/{id}/delete', ProductController::class, 'destroy');
$router->get('/spaces/{spaceId}/products/max-consumption', ProductController::class, 'getMaxConsumptionDays');

// --- Inventaire ---
$router->get('/spaces/{spaceId}/inventory', InventoryController::class, 'index');
$router->get('/spaces/{spaceId}/inventory/create', InventoryController::class, 'createForm');
$router->post('/spaces/{spaceId}/inventory/create', InventoryController::class, 'store');
$router->get('/spaces/{spaceId}/inventory/{id}/edit', InventoryController::class, 'editForm');
$router->post('/spaces/{spaceId}/inventory/{id}/edit', InventoryController::class, 'update');
$router->post('/spaces/{spaceId}/inventory/{id}/delete', InventoryController::class, 'destroy');
$router->post('/spaces/{spaceId}/inventory/{id}/casse', InventoryController::class, 'casse');
$router->post('/spaces/{spaceId}/inventory/{id}/uncasse', InventoryController::class, 'uncasse');
$router->post('/spaces/{spaceId}/inventory/{id}/decrease', InventoryController::class, 'decrease');

// ============================================================
// Routes API REST — JWT uniquement (sauf /api/auth/login)
// ============================================================

// Authentification (POST /api/auth/login = publique)
$router->post('/api/auth/login', AuthApiController::class, 'login');
$router->post('/api/auth/refresh', AuthApiController::class, 'refresh');
$router->get('/api/auth/me', AuthApiController::class, 'me');
$router->put('/api/auth/me', AuthApiController::class, 'updateMe');
$router->post('/api/auth/password', AuthApiController::class, 'changePassword');

// Espaces
$router->get('/api/spaces', SpaceApiController::class, 'index');
$router->post('/api/spaces', SpaceApiController::class, 'store');
$router->get('/api/spaces/{id}', SpaceApiController::class, 'show');
$router->put('/api/spaces/{id}', SpaceApiController::class, 'update');
$router->delete('/api/spaces/{id}', SpaceApiController::class, 'destroy');

// Membres d'un espace
$router->get('/api/spaces/{id}/members', SpaceApiController::class, 'members');
$router->post('/api/spaces/{id}/members', SpaceApiController::class, 'addMember');
$router->put('/api/spaces/{id}/members/{userId}', SpaceApiController::class, 'updateMember');
$router->delete('/api/spaces/{id}/members/{userId}', SpaceApiController::class, 'removeMember');

// Catégories
$router->get('/api/spaces/{spaceId}/categories', CategoryApiController::class, 'index');
$router->post('/api/spaces/{spaceId}/categories', CategoryApiController::class, 'store');
$router->get('/api/spaces/{spaceId}/categories/{id}', CategoryApiController::class, 'show');
$router->put('/api/spaces/{spaceId}/categories/{id}', CategoryApiController::class, 'update');
$router->delete('/api/spaces/{spaceId}/categories/{id}', CategoryApiController::class, 'destroy');

// Emplacements
$router->get('/api/spaces/{spaceId}/locations', LocationApiController::class, 'index');
$router->post('/api/spaces/{spaceId}/locations', LocationApiController::class, 'store');
$router->get('/api/spaces/{spaceId}/locations/{id}', LocationApiController::class, 'show');
$router->put('/api/spaces/{spaceId}/locations/{id}', LocationApiController::class, 'update');
$router->delete('/api/spaces/{spaceId}/locations/{id}', LocationApiController::class, 'destroy');

// Produits
$router->get('/api/spaces/{spaceId}/products', ProductApiController::class, 'index');
$router->post('/api/spaces/{spaceId}/products', ProductApiController::class, 'store');
$router->get('/api/spaces/{spaceId}/products/{id}', ProductApiController::class, 'show');
$router->put('/api/spaces/{spaceId}/products/{id}', ProductApiController::class, 'update');
$router->delete('/api/spaces/{spaceId}/products/{id}', ProductApiController::class, 'destroy');

// Inventaire (routes statiques AVANT les routes paramétrées)
$router->get('/api/spaces/{spaceId}/inventory', InventoryApiController::class, 'index');
$router->post('/api/spaces/{spaceId}/inventory', InventoryApiController::class, 'store');
$router->get('/api/spaces/{spaceId}/inventory/expired', InventoryApiController::class, 'expired');
$router->get('/api/spaces/{spaceId}/inventory/expiring', InventoryApiController::class, 'expiring');
$router->get('/api/spaces/{spaceId}/inventory/casse', InventoryApiController::class, 'casse');
$router->get('/api/spaces/{spaceId}/inventory/{id}', InventoryApiController::class, 'show');
$router->put('/api/spaces/{spaceId}/inventory/{id}', InventoryApiController::class, 'update');
$router->delete('/api/spaces/{spaceId}/inventory/{id}', InventoryApiController::class, 'destroy');
$router->post('/api/spaces/{spaceId}/inventory/{id}/decrease', InventoryApiController::class, 'decrease');
$router->post('/api/spaces/{spaceId}/inventory/{id}/casse', InventoryApiController::class, 'markCasse');
$router->post('/api/spaces/{spaceId}/inventory/{id}/uncasse', InventoryApiController::class, 'unmarkCasse');

// Dispatcher la requête
$router->dispatch();
