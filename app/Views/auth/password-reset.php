<div class="auth-container">
    <div class="card">
        <div class="card-body">
            <h2><i class="bi bi-shield-lock"></i> Nouveau mot de passe</h2>
            <form method="POST" action="/password-reset/<?= e($token) ?>">
                <?= csrf_field() ?>
                <div class="form-group">
                    <label for="new_password" class="form-label">Mot de passe <span class="text-danger">*</span></label>
                    <div class="password-wrapper">
                        <input type="password" id="new_password" name="new_password" class="form-control"
                               required minlength="8" placeholder="Nouveau mot de passe" autofocus>
                        <button type="button" class="btn-toggle-password" data-target="new_password" title="Afficher">
                            <i class="bi bi-eye"></i>
                        </button>
                    </div>
                    <span class="form-hint">8 caractères minimum</span>
                </div>
                <div class="form-group">
                    <label for="new_password_confirm" class="form-label">Confirmer le mot de passe <span class="text-danger">*</span></label>
                    <div class="password-wrapper">
                        <input type="password" id="new_password_confirm" name="new_password_confirm" class="form-control"
                               required minlength="8" placeholder="Confirmer le mot de passe">
                        <button type="button" class="btn-toggle-password" data-target="new_password_confirm" title="Afficher">
                            <i class="bi bi-eye"></i>
                        </button>
                    </div>
                </div>
                <div class="form-group">
                    <button type="submit" class="btn btn-primary btn-block">
                        <i class="bi bi-check-lg"></i> Enregistrer le mot de passe
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>
