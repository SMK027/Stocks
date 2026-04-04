<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/tom-select@2.4.3/dist/css/tom-select.min.css">
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
                        <select id="category_select" name="categories[]" multiple placeholder="Rechercher une catégorie...">
                            <?php foreach ($categories as $cat): ?>
                                <option value="<?= (int)$cat['id'] ?>">
                                    <?= e($cat['name']) ?><?= $cat['max_consumption_days'] ? ' (' . (int)$cat['max_consumption_days'] . ' j)' : '' ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
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

<script src="https://cdn.jsdelivr.net/npm/tom-select@2.4.3/dist/js/tom-select.complete.min.js"></script>
<script>
document.addEventListener('DOMContentLoaded', function () {
    const categoryDays = {
        <?php foreach ($categories as $cat): ?>
        <?= (int)$cat['id'] ?>: <?= (int)($cat['max_consumption_days'] ?? 0) ?>,
        <?php endforeach; ?>
    };

    const stockDate  = document.getElementById('stock_date');
    const expiryDate = document.getElementById('expiry_date');
    const expiryHint = document.getElementById('expiry_hint');
    const selectEl   = document.getElementById('category_select');

    if (!selectEl) return;

    const ts = new TomSelect('#category_select', {
        plugins: ['remove_button'],
        placeholder: 'Rechercher une catégorie…',
        create: false,
        onChange: updateExpiryDate
    });

    function updateExpiryDate() {
        const selected = ts.getValue();
        let minDays = null;
        selected.forEach(function (v) {
            const days = categoryDays[parseInt(v)];
            if (days && days > 0 && (minDays === null || days < minDays)) minDays = days;
        });
        if (minDays !== null && stockDate.value) {
            const d = new Date(stockDate.value);
            d.setDate(d.getDate() + minDays);
            const suggested = d.toISOString().split('T')[0];
            if (!expiryDate.value || expiryDate.dataset.autoSet === 'true') {
                expiryDate.value = suggested;
                expiryDate.dataset.autoSet = 'true';
            }
            expiryHint.textContent = 'Suggestion : ' + minDays + ' jours (' + suggested + ')';
        } else {
            expiryHint.textContent = '';
        }
    }

    stockDate.addEventListener('change', updateExpiryDate);
    expiryDate.addEventListener('input', function () { this.dataset.autoSet = 'false'; });
});
</script>
