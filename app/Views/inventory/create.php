<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/tom-select@2.4.3/dist/css/tom-select.min.css">
<div class="auth-container" style="max-width:520px;">
    <div class="card">
        <div class="card-body">
            <h2><i class="bi bi-plus-lg"></i> Ajouter à l'inventaire</h2>
            <p class="text-muted text-small mb-2"><?= e($space['name']) ?></p>

            <?php if (empty($products) || empty($locations)): ?>
                <div class="alert alert-warning">
                    <?php if (empty($products)): ?>
                        <p>Vous devez d'abord <a href="/spaces/<?= (int)$space['id'] ?>/products/create">créer des produits</a>.</p>
                    <?php endif; ?>
                    <?php if (empty($locations)): ?>
                        <p>Vous devez d'abord <a href="/spaces/<?= (int)$space['id'] ?>/locations/create">créer des emplacements</a>.</p>
                    <?php endif; ?>
                </div>
            <?php else: ?>
                <form method="POST" action="/spaces/<?= (int)$space['id'] ?>/inventory/create">
                    <?= csrf_field() ?>
                    <div class="form-group">
                        <label class="form-label" for="product_id">Produit *</label>
                        <select id="product_id" name="product_id" required>
                            <option value="">— Sélectionner un produit —</option>
                            <?php foreach ($products as $p): ?>
                                <option value="<?= (int)$p['id'] ?>"><?= e($p['name']) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="form-group">
                        <label class="form-label" for="location_id">Emplacement *</label>
                        <select id="location_id" name="location_id" required>
                            <option value="">— Sélectionner un emplacement —</option>
                            <?php foreach ($locations as $loc): ?>
                                <option value="<?= (int)$loc['id'] ?>"><?= e($loc['name']) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="form-group">
                        <label class="form-label" for="quantity">Quantité *</label>
                        <input type="number" id="quantity" name="quantity" class="form-control" required min="0" value="1">
                    </div>
                    <div class="btn-group">
                        <button type="submit" class="btn btn-primary">Ajouter</button>
                        <a href="/spaces/<?= (int)$space['id'] ?>/inventory" class="btn btn-outline">Annuler</a>
                    </div>
                </form>
            <?php endif; ?>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/tom-select@2.4.3/dist/js/tom-select.complete.min.js"></script>
<script>
document.addEventListener('DOMContentLoaded', function () {
    new TomSelect('#product_id',  { create: false, placeholder: 'Rechercher un produit…' });
    new TomSelect('#location_id', { create: false, placeholder: 'Rechercher un emplacement…' });
});
</script>
