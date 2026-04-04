<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Controller;
use App\Core\Mailer;
use App\Models\User;
use App\Models\PasswordReset;

class PasswordResetController extends Controller
{
    private User $userModel;
    private PasswordReset $resetModel;

    public function __construct()
    {
        $this->userModel  = new User();
        $this->resetModel = new PasswordReset();
    }

    /**
     * Formulaire de demande de réinitialisation (saisie email).
     */
    public function requestForm(): void
    {
        $this->render('auth/password-request', ['title' => 'Mot de passe oublié']);
    }

    /**
     * Traitement de la demande : génère le token et envoie l'email.
     */
    public function request(): void
    {
        $this->validateCSRF();
        $data = $this->getPostData(['email']);

        if (empty($data['email']) || !filter_var($data['email'], FILTER_VALIDATE_EMAIL)) {
            $this->setFlash('danger', 'Adresse email invalide.');
            $this->redirect('/password-reset');
            return;
        }

        // Toujours afficher le même message pour éviter l'énumération d'utilisateurs
        $user = $this->userModel->findByEmail($data['email']);
        if ($user) {
            // Nettoyage des tokens expirés au passage
            $this->resetModel->deleteExpired();

            $token  = $this->resetModel->createToken($data['email']);
            $appUrl = rtrim(getenv('APP_URL') ?: 'http://localhost:8085', '/');
            $link   = $appUrl . '/password-reset/' . urlencode($token);

            $subject = 'Réinitialisation de votre mot de passe';
            $body    = '
<p>Bonjour,</p>
<p>Vous avez demandé à réinitialiser le mot de passe de votre compte.</p>
<p>Cliquez sur le lien ci-dessous pour choisir un nouveau mot de passe :</p>
<p><a href="' . htmlspecialchars($link, ENT_QUOTES) . '" style="color:#3a86ff;">
    ' . htmlspecialchars($link, ENT_QUOTES) . '
</a></p>
<p>Ce lien est valable pendant <strong>1 heure</strong>.</p>
<p>Si vous n\'avez pas fait cette demande, ignorez simplement ce message.</p>
';

            try {
                Mailer::send($data['email'], $user['username'] ?? '', $subject, $body);
            } catch (\Exception $e) {
                if (getenv('APP_DEBUG') === 'true') {
                    error_log('[PasswordReset] Erreur envoi email : ' . $e->getMessage());
                }
            }
        }

        $this->setFlash('success', 'Si cette adresse email correspond à un compte, vous recevrez un email de réinitialisation sous peu.');
        $this->redirect('/login');
    }

    /**
     * Formulaire de saisie du nouveau mot de passe.
     */
    public function resetForm(string $token): void
    {
        $reset = $this->resetModel->findValidByToken($token);
        if (!$reset) {
            $this->setFlash('danger', 'Ce lien de réinitialisation est invalide ou a expiré.');
            $this->redirect('/password-reset');
            return;
        }

        $this->render('auth/password-reset', [
            'title' => 'Nouveau mot de passe',
            'token' => $token,
        ]);
    }

    /**
     * Traitement du nouveau mot de passe.
     */
    public function reset(string $token): void
    {
        $this->validateCSRF();

        $reset = $this->resetModel->findValidByToken($token);
        if (!$reset) {
            $this->setFlash('danger', 'Ce lien de réinitialisation est invalide ou a expiré.');
            $this->redirect('/password-reset');
            return;
        }

        $data = $this->getPostData(['new_password', 'new_password_confirm']);

        if (empty($data['new_password']) || empty($data['new_password_confirm'])) {
            $this->setFlash('danger', 'Tous les champs sont requis.');
            $this->redirect('/password-reset/' . urlencode($token));
            return;
        }

        if ($data['new_password'] !== $data['new_password_confirm']) {
            $this->setFlash('danger', 'Les mots de passe ne correspondent pas.');
            $this->redirect('/password-reset/' . urlencode($token));
            return;
        }

        if (strlen($data['new_password']) < 8) {
            $this->setFlash('danger', 'Le mot de passe doit contenir au moins 8 caractères.');
            $this->redirect('/password-reset/' . urlencode($token));
            return;
        }

        $user = $this->userModel->findByEmail($reset['email']);
        if (!$user) {
            $this->setFlash('danger', 'Utilisateur introuvable.');
            $this->redirect('/password-reset');
            return;
        }

        $this->userModel->updatePassword((int)$user['id'], $data['new_password']);
        $this->resetModel->deleteByToken($token); // Token à usage unique

        $this->setFlash('success', 'Mot de passe réinitialisé avec succès. Vous pouvez maintenant vous connecter.');
        $this->redirect('/login');
    }
}
