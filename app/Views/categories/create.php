<div class="auth-container">
    <div class="card">
        <div class="card-body">
            <h2><i class="bi bi-plus-lg"></i> Nouvelle catégorie</h2>
            <p class="text-muted text-small mb-2"><?= e($space['name']) ?></p>
            <form method="POST" action="/spaces/<?= (int)$space['id'] ?>/categories/create">
                <?= csrf_field() ?>
                <div class="form-group">
                    <label class="form-label" for="name">Nom *</label>
                    <input type="text" id="name" name="name" class="form-control" required maxlength="100" placeholder="Ex : Fruits, Conserves...">
                </div>
                <div class="form-group">
                    <label class="form-label" for="description">Description</label>
                    <textarea id="description" name="description" class="form-control" rows="3"></textarea>
                </div>
                <div class="form-group">
                    <label class="form-label" for="max_consumption_days">Durée max. de consommation (jours)</label>
                    <input type="number" id="max_consumption_days" name="max_consumption_days" class="form-control" min="1" placeholder="Ex : 30">
                    <span class="form-hint">Sera utilisée pour prédéterminer la date limite de consommation des produits.</span>
                </div>
                <div class="btn-group">
                    <button type="submit" class="btn btn-primary">Créer</button>
                    <a href="/spaces/<?= (int)$space['id'] ?>/categories" class="btn btn-outline">Annuler</a>
                </div>
            </form>
        </div>
    </div>
</div>
