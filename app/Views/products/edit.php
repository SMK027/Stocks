<div class="auth-container" style="max-width:520px;">
    <div class="card">
        <div class="card-body">
            <h2><i class="bi bi-pencil"></i> Modifier le produit</h2>
            <p class="text-muted text-small mb-2"><?= e($space['name']) ?></p>
            <form method="POST" action="/spaces/<?= (int)$space['id'] ?>/products/<?= (int)$product['id'] ?>/edit">
                <?= csrf_field() ?>
                <div class="form-group">
                    <label class="form-label" for="name">Nom du produit *</label>
                    <input type="text" id="name" name="name" class="form-control" required maxlength="150" value="<?= e($product['name']) ?>">
                </div>

                <div class="form-group">
                    <label class="form-label">Catégories</label>
                    <?php if (empty($categories)): ?>
                        <p class="text-muted text-small">Aucune catégorie disponible.</p>
                    <?php else: ?>
                        <div class="checkbox-group">
                            <?php foreach ($categories as $cat): ?>
                                <div class="form-check">
                                    <input type="checkbox" name="categories[]" value="<?= (int)$cat['id'] ?>" id="cat_<?= (int)$cat['id'] ?>"
                                           <?= in_array($cat['id'], $product['category_ids'] ?? []) ? 'checked' : '' ?>>
                                    <label for="cat_<?= (int)$cat['id'] ?>"><?= e($cat['name']) ?></label>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>
                </div>

                <div class="form-row">
                    <div class="form-group">
                        <label class="form-label" for="stock_date">Date de mise en stock *</label>
                        <input type="date" id="stock_date" name="stock_date" class="form-control" required value="<?= e($product['stock_date']) ?>">
                    </div>
                    <div class="form-group">
                        <label class="form-label" for="expiry_date">Date limite de consommation</label>
                        <input type="date" id="expiry_date" name="expiry_date" class="form-control" value="<?= e($product['expiry_date'] ?? '') ?>">
                    </div>
                </div>

                <div class="btn-group">
                    <button type="submit" class="btn btn-primary">Enregistrer</button>
                    <a href="/spaces/<?= (int)$space['id'] ?>/products" class="btn btn-outline">Annuler</a>
                </div>
            </form>
        </div>
    </div>
</div>
