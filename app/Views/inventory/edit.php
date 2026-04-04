<div class="auth-container" style="max-width:520px;">
    <div class="card">
        <div class="card-body">
            <h2><i class="bi bi-pencil"></i> Modifier l'entrée</h2>
            <p class="text-muted text-small mb-2"><?= e($space['name']) ?></p>
            <form method="POST" action="/spaces/<?= (int)$space['id'] ?>/inventory/<?= (int)$item['id'] ?>/edit">
                <?= csrf_field() ?>
                <div class="form-group">
                    <label class="form-label" for="product_id">Produit *</label>
                    <select id="product_id" name="product_id" class="form-control" required>
                        <?php foreach ($products as $p): ?>
                            <option value="<?= (int)$p['id'] ?>" <?= (int)$p['id'] === (int)$item['product_id'] ? 'selected' : '' ?>><?= e($p['name']) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="form-group">
                    <label class="form-label" for="location_id">Emplacement *</label>
                    <select id="location_id" name="location_id" class="form-control" required>
                        <?php foreach ($locations as $loc): ?>
                            <option value="<?= (int)$loc['id'] ?>" <?= (int)$loc['id'] === (int)$item['location_id'] ? 'selected' : '' ?>><?= e($loc['name']) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="form-group">
                    <label class="form-label" for="quantity">Quantité *</label>
                    <input type="number" id="quantity" name="quantity" class="form-control" required min="0" value="<?= (int)$item['quantity'] ?>">
                </div>
                <div class="btn-group">
                    <button type="submit" class="btn btn-primary">Enregistrer</button>
                    <a href="/spaces/<?= (int)$space['id'] ?>/inventory" class="btn btn-outline">Annuler</a>
                </div>
            </form>
        </div>
    </div>
</div>
