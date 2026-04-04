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
                <div class="form-group" id="password-confirm-email-group" style="display: none;">
                    <label for="password_confirm_email" class="form-label">Mot de passe <span class="text-danger">*</span></label>
                    <div class="password-wrapper">
                        <input type="password" id="password_confirm_email" name="password_confirm_email" class="form-control"
                               placeholder="Saisissez votre mot de passe pour confirmer">
                        <button type="button" class="btn-toggle-password" data-target="password_confirm_email" title="Afficher">
                            <i class="bi bi-eye"></i>
                        </button>
                    </div>
                    <span class="form-hint">Requis pour modifier l'adresse email</span>
                </div>
                <script>
                (function() {
                    var emailInput = document.getElementById('email');
                    var originalEmail = <?= json_encode($user['email']) ?>;
                    var group = document.getElementById('password-confirm-email-group');
                    var pwdInput = document.getElementById('password_confirm_email');
                    emailInput.addEventListener('input', function() {
                        if (emailInput.value !== originalEmail) {
                            group.style.display = '';
                            pwdInput.required = true;
                        } else {
                            group.style.display = 'none';
                            pwdInput.required = false;
                            pwdInput.value = '';
                        }
                    });
                })();
                </script>
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

<!-- Notifications -->
<div class="card" style="margin-top: 2rem;">
    <div class="card-body">
        <h3><i class="bi bi-bell"></i> Notifications par email</h3>
        <form method="POST" action="/profile">
            <?= csrf_field() ?>
            <!-- Champs cachés pour conserver les valeurs actuelles -->
            <input type="hidden" name="firstname" value="<?= e($user['firstname'] ?? '') ?>">
            <input type="hidden" name="lastname"  value="<?= e($user['lastname']  ?? '') ?>">
            <input type="hidden" name="username"  value="<?= e($user['username']) ?>">
            <input type="hidden" name="email"     value="<?= e($user['email']) ?>">
            <div class="form-group">
                <label class="toggle-label">
                    <input type="checkbox" name="daily_digest" value="1"
                           <?= !empty($user['daily_digest']) ? 'checked' : '' ?>>
                    <span class="toggle-track">
                        <span class="toggle-thumb"></span>
                    </span>
                    <span>Recevoir un récapitulatif journalier par email</span>
                </label>
                <span class="form-hint">Chaque matin, un résumé des produits périmés et proches de leur date limite sera envoyé à <strong><?= e($user['email']) ?></strong>.</span>
            </div>
            <div class="form-group">
                <button type="submit" class="btn btn-primary btn-sm">
                    <i class="bi bi-check-lg"></i> Enregistrer
                </button>
            </div>
        </form>
        <?php if (!empty($appDebug)): ?>
            <hr style="margin: 1.25rem 0;">
            <form method="POST" action="/profile/send-digest-test" onsubmit="return confirm('Envoyer un email de test maintenant ?')">
                <?= csrf_field() ?>
                <div class="form-group" style="margin-bottom: 0;">
                    <button type="submit" class="btn btn-sm" style="background:var(--warning);color:var(--dark);border-color:var(--warning);">
                        <i class="bi bi-bug"></i> [DEBUG] Forcer l'envoi du récapitulatif
                    </button>
                    <span class="form-hint">Envoie immédiatement l'email à votre adresse.</span>
                </div>
            </form>
        <?php endif; ?>
    </div>
</div>
