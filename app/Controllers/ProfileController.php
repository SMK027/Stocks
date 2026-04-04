<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Controller;
use App\Core\Session;
use App\Core\Mailer;
use App\Models\User;
use App\Models\InventoryItem;

class ProfileController extends Controller
{
    private User $userModel;
    private InventoryItem $inventoryModel;

    public function __construct()
    {
        $this->userModel      = new User();
        $this->inventoryModel = new InventoryItem();
    }

    /**
     * Affiche le formulaire de modification du profil.
     */
    public function edit(): void
    {
        $this->requireAuth();

        $user = $this->userModel->find($this->getCurrentUserId());
        if (!$user) {
            $this->setFlash('danger', 'Utilisateur introuvable.');
            $this->redirect('/');
            return;
        }

        $this->render('profile/edit', [
            'title'    => 'Mon profil',
            'user'     => $user,
            'appDebug' => getenv('APP_DEBUG') === 'true',
        ]);
    }

    /**
     * Traitement de la mise à jour du profil.
     */
    public function update(): void
    {
        $this->requireAuth();
        $this->validateCSRF();

        $userId = $this->getCurrentUserId();
        $user = $this->userModel->find($userId);

        $data = $this->getPostData(['firstname', 'lastname', 'email', 'username', 'password_confirm_email']);
        // La checkbox non cochée n'est pas envoyée — normalisation
        $data['daily_digest'] = isset($_POST['daily_digest']) && $_POST['daily_digest'] === '1' ? 1 : 0;

        // Validation email
        if (empty($data['email'])) {
            $this->setFlash('danger', 'L\'adresse email est requise.');
            $this->redirect('/profile');
            return;
        }

        if (!filter_var($data['email'], FILTER_VALIDATE_EMAIL)) {
            $this->setFlash('danger', 'L\'adresse email n\'est pas valide.');
            $this->redirect('/profile');
            return;
        }

        // Validation username
        if (empty($data['username'])) {
            $this->setFlash('danger', 'Le nom d\'utilisateur est requis.');
            $this->redirect('/profile');
            return;
        }

        // Vérifier unicité email si changé
        if ($data['email'] !== $user['email']) {
            // Exiger le mot de passe pour changer l'email
            if (empty($data['password_confirm_email'])) {
                $this->setFlash('danger', 'Le mot de passe est requis pour modifier l\'adresse email.');
                $this->redirect('/profile');
                return;
            }

            if (!password_verify($data['password_confirm_email'], $user['password'])) {
                $this->setFlash('danger', 'Le mot de passe est incorrect.');
                $this->redirect('/profile');
                return;
            }

            $existing = $this->userModel->findByEmail($data['email']);
            if ($existing) {
                $this->setFlash('danger', 'Cette adresse email est déjà utilisée.');
                $this->redirect('/profile');
                return;
            }
        }

        // Vérifier unicité username si changé
        if ($data['username'] !== $user['username']) {
            $existing = $this->userModel->findByUsername($data['username']);
            if ($existing) {
                $this->setFlash('danger', 'Ce nom d\'utilisateur est déjà pris.');
                $this->redirect('/profile');
                return;
            }
        }

        $this->userModel->updateProfile($userId, $data);

        // Mettre à jour la session
        Session::set('username', $data['username']);

        $this->setFlash('success', 'Profil mis à jour avec succès.');
        $this->redirect('/profile');
    }

    /**
     * [DEBUG] Force l'envoi du récapitulatif journalier à l'utilisateur connecté.
     * Uniquement disponible si APP_DEBUG=true.
     */
    public function sendDigestTest(): void
    {
        $this->requireAuth();
        $this->validateCSRF();

        if (getenv('APP_DEBUG') !== 'true') {
            $this->setFlash('danger', 'Accès refusé.');
            $this->redirect('/profile');
            return;
        }

        $userId = $this->getCurrentUserId();
        $user   = $this->userModel->find($userId);

        if (!$user) {
            $this->setFlash('danger', 'Utilisateur introuvable.');
            $this->redirect('/profile');
            return;
        }

        $expiredItems = $this->inventoryModel->findExpiredForUser($userId);
        $soonItems    = $this->inventoryModel->findExpiringSoonForUser($userId, 7);

        $today  = date('d/m/Y');
        $prenom = $user['firstname'] ?: $user['username'];
        $appUrl = rtrim(getenv('APP_URL') ?: 'http://localhost:8085', '/');

        $body = '<!DOCTYPE html><html lang="fr"><body style="font-family:sans-serif;color:#333;max-width:600px;margin:auto;">';
        $body .= '<p style="background:#fff8e6;padding:8px 12px;border-radius:4px;font-size:0.85rem;color:#7a5c00;">';
        $body .= '&#128295; Ceci est un <strong>email de test</strong> (mode débogage actif).</p>';
        $body .= '<h2 style="color:#3a86ff;">Récapitulatif du ' . $today . '</h2>';
        $body .= '<p>Bonjour ' . htmlspecialchars($prenom, ENT_QUOTES) . ',</p>';
        $body .= '<p>Voici votre résumé quotidien de l\'\u00e9tat de vos stocks.</p>';

        if (!empty($expiredItems)) {
            $body .= '<h3 style="color:#ef476f;">⚠️ Produits périmés (' . count($expiredItems) . ')</h3>';
            $body .= '<table width="100%" cellpadding="6" cellspacing="0" style="border-collapse:collapse;">';
            $body .= '<tr style="background:#ffeef2;text-align:left;"><th>Produit</th><th>Espace</th><th>Emplacement</th><th>Qté</th><th>Périmé le</th></tr>';
            foreach ($expiredItems as $item) {
                $body .= '<tr style="border-bottom:1px solid #eee;">';
                $body .= '<td>' . htmlspecialchars($item['product_name'], ENT_QUOTES) . '</td>';
                $body .= '<td>' . htmlspecialchars($item['space_name'], ENT_QUOTES) . '</td>';
                $body .= '<td>' . htmlspecialchars($item['location_name'], ENT_QUOTES) . '</td>';
                $body .= '<td>' . (int)$item['quantity'] . '</td>';
                $body .= '<td>' . date('d/m/Y', strtotime($item['expiry_date'])) . '</td>';
                $body .= '</tr>';
            }
            $body .= '</table>';
        }

        if (!empty($soonItems)) {
            $body .= '<h3 style="color:#e09f1b;margin-top:1.5rem;">🕐 Expire dans les 7 jours (' . count($soonItems) . ')</h3>';
            $body .= '<table width="100%" cellpadding="6" cellspacing="0" style="border-collapse:collapse;">';
            $body .= '<tr style="background:#fff8e6;text-align:left;"><th>Produit</th><th>Espace</th><th>Emplacement</th><th>Qté</th><th>Expire le</th></tr>';
            foreach ($soonItems as $item) {
                $body .= '<tr style="border-bottom:1px solid #eee;">';
                $body .= '<td>' . htmlspecialchars($item['product_name'], ENT_QUOTES) . '</td>';
                $body .= '<td>' . htmlspecialchars($item['space_name'], ENT_QUOTES) . '</td>';
                $body .= '<td>' . htmlspecialchars($item['location_name'], ENT_QUOTES) . '</td>';
                $body .= '<td>' . (int)$item['quantity'] . '</td>';
                $body .= '<td>' . date('d/m/Y', strtotime($item['expiry_date'])) . '</td>';
                $body .= '</tr>';
            }
            $body .= '</table>';
        }

        if (empty($expiredItems) && empty($soonItems)) {
            $body .= '<p>Aucun produit périmé ni proche de la date limite — tout est OK !</p>';
        }

        $body .= '<p style="margin-top:1.5rem;">';
        $body .= '<a href="' . htmlspecialchars($appUrl . '/dashboard', ENT_QUOTES) . '" ';
        $body .= 'style="display:inline-block;padding:10px 20px;background:#3a86ff;color:#fff;text-decoration:none;border-radius:6px;">';
        $body .= 'Voir le tableau de bord</a></p>';
        $body .= '</body></html>';

        try {
            Mailer::send($user['email'], $prenom, '[TEST] Récapitulatif stocks du ' . $today, $body);
            $this->setFlash('success', 'Email de test envoyé à ' . $user['email'] . '.');
        } catch (\Exception $e) {
            $this->setFlash('danger', 'Erreur d\'envoi : ' . $e->getMessage());
        }

        $this->redirect('/profile');
    }

    /**
     * Traitement du changement de mot de passe.
     */
    public function updatePassword(): void
    {
        $this->requireAuth();
        $this->validateCSRF();

        $userId = $this->getCurrentUserId();
        $user = $this->userModel->find($userId);

        $data = $this->getPostData(['current_password', 'new_password', 'new_password_confirm']);

        if (empty($data['current_password']) || empty($data['new_password']) || empty($data['new_password_confirm'])) {
            $this->setFlash('danger', 'Tous les champs du mot de passe sont requis.');
            $this->redirect('/profile');
            return;
        }

        // Vérifier le mot de passe actuel
        if (!password_verify($data['current_password'], $user['password'])) {
            $this->setFlash('danger', 'Le mot de passe actuel est incorrect.');
            $this->redirect('/profile');
            return;
        }

        if ($data['new_password'] !== $data['new_password_confirm']) {
            $this->setFlash('danger', 'Les nouveaux mots de passe ne correspondent pas.');
            $this->redirect('/profile');
            return;
        }

        if (strlen($data['new_password']) < 8) {
            $this->setFlash('danger', 'Le nouveau mot de passe doit contenir au moins 8 caractères.');
            $this->redirect('/profile');
            return;
        }

        $this->userModel->updatePassword($userId, $data['new_password']);

        $this->setFlash('success', 'Mot de passe modifié avec succès.');
        $this->redirect('/profile');
    }
}
