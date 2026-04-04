<div class="auth-container">
    <div class="card">
        <div class="card-body">
            <h2><i class="bi bi-pencil"></i> Modifier la catégorie</h2>
            <p class="text-muted text-small mb-2"><?= e($space['name']) ?></p>
            <form method="POST" action="/spaces/<?= (int)$space['id'] ?>/categories/<?= (int)$category['id'] ?>/edit">
                <?= csrf_field() ?>
                <div class="form-group">
                    <label class="form-label" for="name">Nom *</label>
                    <input type="text" id="name" name="name" class="form-control" required maxlength="100" value="<?= e($category['name']) ?>">
                </div>
                <div class="form-group">
                    <label class="form-label" for="description">Description</label>
                    <textarea id="description" name="description" class="form-control" rows="3"><?= e($category['description'] ?? '') ?></textarea>
                </div>
                <div class="form-group">
                    <label class="form-label" for="max_consumption_days">Durée max. de consommation (jours)</label>
                    <input type="number" id="max_consumption_days" name="max_consumption_days" class="form-control" min="1" value="<?= e($category['max_consumption_days'] ?? '') ?>">
                </div>
                <div class="btn-group">
                    <button type="submit" class="btn btn-primary">Enregistrer</button>
                    <a href="/spaces/<?= (int)$space['id'] ?>/categories" class="btn btn-outline">Annuler</a>
                </div>
            </form>
        </div>
    </div>
</div>
