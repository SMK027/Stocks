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
    const selectEl = document.getElementById('category_select');
    if (!selectEl) return;
    new TomSelect('#category_select', {
        plugins: ['remove_button'],
        placeholder: 'Rechercher une catégorie…',
        create: false
    });
});
</script>
