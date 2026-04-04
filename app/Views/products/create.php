<div class="auth-container" style="max-width:520px;">
    <div class="card">
        <div class="card-body">
            <h2><i class="bi bi-plus-lg"></i> Nouveau produit</h2>
            <p class="text-muted text-small mb-2"><?= e($space['name']) ?></p>
            <form method="POST" action="/spaces/<?= (int)$space['id'] ?>/products/create" id="productForm">
                <?= csrf_field() ?>
                <div class="form-group">
                    <label class="form-label" for="name">Nom du produit *</label>
                    <input type="text" id="name" name="name" class="form-control" required maxlength="150" placeholder="Ex : Lait, Farine...">
                </div>

                <div class="form-group">
                    <label class="form-label">Catégories</label>
                    <?php if (empty($categories)): ?>
                        <p class="text-muted text-small">Aucune catégorie. <a href="/spaces/<?= (int)$space['id'] ?>/categories/create">Créer une catégorie</a></p>
                    <?php else: ?>
                        <div class="checkbox-group">
                            <?php foreach ($categories as $cat): ?>
                                <div class="form-check">
                                    <input type="checkbox" name="categories[]" value="<?= (int)$cat['id'] ?>" id="cat_<?= (int)$cat['id'] ?>"
                                           class="category-checkbox"
                                           data-max-days="<?= e($cat['max_consumption_days'] ?? '') ?>">
                                    <label for="cat_<?= (int)$cat['id'] ?>"><?= e($cat['name']) ?>
                                        <?php if ($cat['max_consumption_days']): ?>
                                            <span class="text-muted text-small">(<?= (int)$cat['max_consumption_days'] ?> j)</span>
                                        <?php endif; ?>
                                    </label>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>
                </div>

                <div class="form-row">
                    <div class="form-group">
                        <label class="form-label" for="stock_date">Date de mise en stock *</label>
                        <input type="date" id="stock_date" name="stock_date" class="form-control" required value="<?= date('Y-m-d') ?>">
                    </div>
                    <div class="form-group">
                        <label class="form-label" for="expiry_date">Date limite de consommation</label>
                        <input type="date" id="expiry_date" name="expiry_date" class="form-control">
                        <span class="form-hint" id="expiry_hint"></span>
                    </div>
                </div>

                <div class="btn-group">
                    <button type="submit" class="btn btn-primary">Créer</button>
                    <a href="/spaces/<?= (int)$space['id'] ?>/products" class="btn btn-outline">Annuler</a>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const checkboxes = document.querySelectorAll('.category-checkbox');
    const stockDate = document.getElementById('stock_date');
    const expiryDate = document.getElementById('expiry_date');
    const expiryHint = document.getElementById('expiry_hint');

    function updateExpiryDate() {
        let minDays = null;
        checkboxes.forEach(function(cb) {
            if (cb.checked && cb.dataset.maxDays) {
                const days = parseInt(cb.dataset.maxDays);
                if (minDays === null || days < minDays) minDays = days;
            }
        });

        if (minDays !== null && stockDate.value) {
            const date = new Date(stockDate.value);
            date.setDate(date.getDate() + minDays);
            const suggested = date.toISOString().split('T')[0];
            if (!expiryDate.value || expiryDate.dataset.autoSet === 'true') {
                expiryDate.value = suggested;
                expiryDate.dataset.autoSet = 'true';
            }
            expiryHint.textContent = 'Suggestion : ' + minDays + ' jours (' + suggested + ')';
        } else {
            expiryHint.textContent = '';
        }
    }

    checkboxes.forEach(function(cb) { cb.addEventListener('change', updateExpiryDate); });
    stockDate.addEventListener('change', updateExpiryDate);
    expiryDate.addEventListener('input', function() { this.dataset.autoSet = 'false'; });
});
</script>
