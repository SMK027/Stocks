<div class="auth-container">
    <div class="card">
        <div class="card-body">
            <h2><i class="bi bi-pencil"></i> Modifier l'emplacement</h2>
            <p class="text-muted text-small mb-2"><?= e($space['name']) ?></p>
            <form method="POST" action="/spaces/<?= (int)$space['id'] ?>/locations/<?= (int)$location['id'] ?>/edit">
                <?= csrf_field() ?>
                <div class="form-group">
                    <label class="form-label" for="name">Nom *</label>
                    <input type="text" id="name" name="name" class="form-control" required maxlength="100" value="<?= e($location['name']) ?>">
                </div>
                <div class="form-group">
                    <label class="form-label" for="description">Description</label>
                    <textarea id="description" name="description" class="form-control" rows="3"><?= e($location['description'] ?? '') ?></textarea>
                </div>
                <div class="btn-group">
                    <button type="submit" class="btn btn-primary">Enregistrer</button>
                    <a href="/spaces/<?= (int)$space['id'] ?>/locations" class="btn btn-outline">Annuler</a>
                </div>
            </form>
        </div>
    </div>
</div>
