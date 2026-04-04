<div class="auth-container">
    <div class="card">
        <div class="card-body">
            <h2><i class="bi bi-pencil"></i> Modifier l'espace</h2>
            <form method="POST" action="/spaces/<?= (int)$space['id'] ?>/edit">
                <?= csrf_field() ?>
                <div class="form-group">
                    <label class="form-label" for="name">Nom *</label>
                    <input type="text" id="name" name="name" class="form-control" required maxlength="100" value="<?= e($space['name']) ?>">
                </div>
                <div class="form-group">
                    <label class="form-label" for="description">Description</label>
                    <textarea id="description" name="description" class="form-control" rows="3"><?= e($space['description'] ?? '') ?></textarea>
                </div>
                <div class="btn-group">
                    <button type="submit" class="btn btn-primary">Enregistrer</button>
                    <a href="/spaces/<?= (int)$space['id'] ?>" class="btn btn-outline">Annuler</a>
                </div>
            </form>

            <hr style="margin: 2rem 0;">
            <h3 class="text-danger">Zone dangereuse</h3>
            <form method="POST" action="/spaces/<?= (int)$space['id'] ?>/delete" onsubmit="return confirm('Supprimer cet espace et toutes ses données ?')">
                <?= csrf_field() ?>
                <button type="submit" class="btn btn-danger"><i class="bi bi-trash"></i> Supprimer l'espace</button>
            </form>
        </div>
    </div>
</div>
