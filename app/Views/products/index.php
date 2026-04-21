<?php
$canManage = in_array($role, ['gestionnaire_produits', 'gestionnaire_global', 'administrateur'], true);
?>

<div class="page-header">
    <div>
        <h1><i class="bi bi-box-seam"></i> Produits</h1>
        <p class="page-description"><?= e($space['name']) ?></p>
    </div>
    <div class="btn-group">
        <a href="/spaces/<?= (int)$space['id'] ?>" class="btn btn-outline btn-sm"><i class="bi bi-arrow-left"></i> Retour</a>
        <?php if ($canManage): ?>
            <a href="/spaces/<?= (int)$space['id'] ?>/products/create" class="btn btn-primary btn-sm"><i class="bi bi-plus-lg"></i> Nouveau</a>
        <?php endif; ?>
    </div>
</div>

<?php if (empty($products)): ?>
    <div class="empty-state">
        <div class="empty-icon"><i class="bi bi-box-seam"></i></div>
        <p>Aucun produit pour le moment.</p>
        <?php if ($canManage): ?>
            <a href="/spaces/<?= (int)$space['id'] ?>/products/create" class="btn btn-primary">Ajouter un produit</a>
        <?php endif; ?>
    </div>
<?php else: ?>
    <div class="card">
        <div class="search-bar">
            <i class="bi bi-search search-bar-icon"></i>
            <input type="text"
                   class="search-input js-search-input"
                   data-search-for="products-tbody"
                   placeholder="Rechercher un produit…">
        </div>
        <div class="table-responsive">
            <table class="table">
                <thead>
                    <tr>
                        <th>Nom</th>
                        <th>Catégories</th>
                        <?php if ($canManage): ?>
                            <th class="text-right">Actions</th>
                        <?php endif; ?>
                    </tr>
                </thead>
                <tbody id="products-tbody">
                    <?php foreach ($products as $p): ?>
                        <tr>
                            <td><strong><?= e($p['name']) ?></strong></td>
                            <td>
                                <?php if ($p['category_names']): ?>
                                    <?php foreach (explode(', ', $p['category_names']) as $cat): ?>
                                        <span class="badge badge-secondary"><?= e($cat) ?></span>
                                    <?php endforeach; ?>
                                <?php else: ?>
                                    <span class="text-muted">—</span>
                                <?php endif; ?>
                            </td>
                            </td>
                            <?php if ($canManage): ?>
                                <td class="text-right">
                                    <div class="btn-group">
                                        <a href="/spaces/<?= (int)$space['id'] ?>/products/<?= (int)$p['id'] ?>/edit" class="btn btn-sm btn-outline"><i class="bi bi-pencil"></i></a>
                                        <form method="POST" action="/spaces/<?= (int)$space['id'] ?>/products/<?= (int)$p['id'] ?>/delete" onsubmit="return confirm('Supprimer ce produit ?')">
                                            <?= csrf_field() ?>
                                            <button type="submit" class="btn btn-sm btn-outline-danger"><i class="bi bi-trash"></i></button>
                                        </form>
                                    </div>
                                </td>
                            <?php endif; ?>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
            <p class="search-no-results" id="products-tbody-no-results"><i class="bi bi-search"></i> Aucun produit ne correspond à votre recherche.</p>
        </div>
    </div>
<?php endif; ?>
