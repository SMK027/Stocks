<div class="page-header">
    <h1><i class="bi bi-person-circle"></i> Mon profil</h1>
</div>

<div class="grid-2">
    <!-- Informations personnelles -->
    <div class="card">
        <div class="card-body">
            <h3><i class="bi bi-pencil-square"></i> Informations personnelles</h3>
            <form method="POST" action="/profile">
                <?= csrf_field() ?>
                <div class="form-row">
                    <div class="form-group">
                        <label for="firstname" class="form-label">Prénom</label>
                        <input type="text" id="firstname" name="firstname" class="form-control"
                               value="<?= e($user['firstname'] ?? '') ?>"
                               placeholder="Votre prénom" maxlength="100">
                    </div>
                    <div class="form-group">
                        <label for="lastname" class="form-label">Nom</label>
                        <input type="text" id="lastname" name="lastname" class="form-control"
                               value="<?= e($user['lastname'] ?? '') ?>"
                               placeholder="Votre nom" maxlength="100">
                    </div>
                </div>
                <div class="form-group">
                    <label for="username" class="form-label">Nom d'utilisateur <span class="text-danger">*</span></label>
                    <input type="text" id="username" name="username" class="form-control"
                           value="<?= e($user['username']) ?>" required
                           minlength="3" maxlength="50" pattern="[a-zA-Z0-9_-]+">
                    <span class="form-hint">3 à 50 caractères (lettres, chiffres, tirets, underscores)</span>
                </div>
                <div class="form-group">
                    <label for="email" class="form-label">Adresse email <span class="text-danger">*</span></label>
                    <input type="email" id="email" name="email" class="form-control"
                           value="<?= e($user['email']) ?>" required>
                </div>
                <div class="form-group">
                    <button type="submit" class="btn btn-primary">
                        <i class="bi bi-check-lg"></i> Enregistrer
                    </button>
                </div>
            </form>
        </div>
    </div>

    <!-- Changement de mot de passe -->
    <div class="card">
        <div class="card-body">
            <h3><i class="bi bi-shield-lock"></i> Changer le mot de passe</h3>
            <form method="POST" action="/profile/password">
                <?= csrf_field() ?>
                <div class="form-group">
                    <label for="current_password" class="form-label">Mot de passe actuel <span class="text-danger">*</span></label>
                    <div class="password-wrapper">
                        <input type="password" id="current_password" name="current_password" class="form-control"
                               required placeholder="Votre mot de passe actuel">
                        <button type="button" class="btn-toggle-password" data-target="current_password" title="Afficher">
                            <i class="bi bi-eye"></i>
                        </button>
                    </div>
                </div>
                <div class="form-group">
                    <label for="new_password" class="form-label">Nouveau mot de passe <span class="text-danger">*</span></label>
                    <div class="password-wrapper">
                        <input type="password" id="new_password" name="new_password" class="form-control"
                               required minlength="8" placeholder="Nouveau mot de passe">
                        <button type="button" class="btn-toggle-password" data-target="new_password" title="Afficher">
                            <i class="bi bi-eye"></i>
                        </button>
                    </div>
                    <span class="form-hint">8 caractères minimum</span>
                </div>
                <div class="form-group">
                    <label for="new_password_confirm" class="form-label">Confirmer le nouveau mot de passe <span class="text-danger">*</span></label>
                    <div class="password-wrapper">
                        <input type="password" id="new_password_confirm" name="new_password_confirm" class="form-control"
                               required minlength="8" placeholder="Confirmer le mot de passe">
                        <button type="button" class="btn-toggle-password" data-target="new_password_confirm" title="Afficher">
                            <i class="bi bi-eye"></i>
                        </button>
                    </div>
                </div>
                <div class="form-group">
                    <button type="submit" class="btn btn-primary">
                        <i class="bi bi-key"></i> Changer le mot de passe
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>
