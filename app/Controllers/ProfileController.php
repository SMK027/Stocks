<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Controller;
use App\Core\Session;
use App\Models\User;

class ProfileController extends Controller
{
    private User $userModel;

    public function __construct()
    {
        $this->userModel = new User();
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
            'title' => 'Mon profil',
            'user' => $user,
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
