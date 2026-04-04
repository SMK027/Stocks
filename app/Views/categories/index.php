<?php
$canManage = in_array($role, ['gestionnaire_produits', 'gestionnaire_global', 'administrateur'], true);
?>

<div class="page-header">
    <div>
        <h1><i class="bi bi-tags"></i> Catégories</h1>
        <p class="page-description"><?= e($space['name']) ?></p>
    </div>
    <div class="btn-group">
        <a href="/spaces/<?= (int)$space['id'] ?>" class="btn btn-outline btn-sm"><i class="bi bi-arrow-left"></i> Retour</a>
        <?php if ($canManage): ?>
            <a href="/spaces/<?= (int)$space['id'] ?>/categories/create" class="btn btn-primary btn-sm"><i class="bi bi-plus-lg"></i> Nouvelle</a>
        <?php endif; ?>
    </div>
</div>

<?php if (empty($categories)): ?>
    <div class="empty-state">
        <div class="empty-icon"><i class="bi bi-tags"></i></div>
        <p>Aucune catégorie pour le moment.</p>
        <?php if ($canManage): ?>
            <a href="/spaces/<?= (int)$space['id'] ?>/categories/create" class="btn btn-primary">Créer une catégorie</a>
        <?php endif; ?>
    </div>
<?php else: ?>
    <div class="card">
        <div class="table-responsive">
            <table class="table">
                <thead>
                    <tr>
                        <th>Nom</th>
                        <th>Description</th>
                        <th>Durée max. (jours)</th>
                        <?php if ($canManage): ?>
                            <th class="text-right">Actions</th>
                        <?php endif; ?>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($categories as $cat): ?>
                        <tr>
                            <td><strong><?= e($cat['name']) ?></strong></td>
                            <td class="text-muted"><?= e($cat['description'] ?? '—') ?></td>
                            <td>
                                <?php if ($cat['max_consumption_days']): ?>
                                    <span class="badge badge-info"><?= (int)$cat['max_consumption_days'] ?> j</span>
                                <?php else: ?>
                                    <span class="text-muted">—</span>
                                <?php endif; ?>
                            </td>
                            <?php if ($canManage): ?>
                                <td class="text-right">
                                    <div class="btn-group">
                                        <a href="/spaces/<?= (int)$space['id'] ?>/categories/<?= (int)$cat['id'] ?>/edit" class="btn btn-sm btn-outline"><i class="bi bi-pencil"></i></a>
                                        <form method="POST" action="/spaces/<?= (int)$space['id'] ?>/categories/<?= (int)$cat['id'] ?>/delete" onsubmit="return confirm('Supprimer cette catégorie ?')">
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
        </div>
    </div>
<?php endif; ?>
