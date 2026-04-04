<?php
$canManage = in_array($role, ['gestionnaire_produits', 'gestionnaire_global', 'administrateur'], true);
?>

<div class="page-header">
    <div>
        <h1><i class="bi bi-geo-alt"></i> Emplacements</h1>
        <p class="page-description"><?= e($space['name']) ?></p>
    </div>
    <div class="btn-group">
        <a href="/spaces/<?= (int)$space['id'] ?>" class="btn btn-outline btn-sm"><i class="bi bi-arrow-left"></i> Retour</a>
        <?php if ($canManage): ?>
            <a href="/spaces/<?= (int)$space['id'] ?>/locations/create" class="btn btn-primary btn-sm"><i class="bi bi-plus-lg"></i> Nouveau</a>
        <?php endif; ?>
    </div>
</div>

<?php if (empty($locations)): ?>
    <div class="empty-state">
        <div class="empty-icon"><i class="bi bi-geo-alt"></i></div>
        <p>Aucun emplacement pour le moment.</p>
        <?php if ($canManage): ?>
            <a href="/spaces/<?= (int)$space['id'] ?>/locations/create" class="btn btn-primary">Créer un emplacement</a>
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
                        <?php if ($canManage): ?>
                            <th class="text-right">Actions</th>
                        <?php endif; ?>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($locations as $loc): ?>
                        <tr>
                            <td><strong><?= e($loc['name']) ?></strong></td>
                            <td class="text-muted"><?= e($loc['description'] ?? '—') ?></td>
                            <?php if ($canManage): ?>
                                <td class="text-right">
                                    <div class="btn-group">
                                        <a href="/spaces/<?= (int)$space['id'] ?>/locations/<?= (int)$loc['id'] ?>/edit" class="btn btn-sm btn-outline"><i class="bi bi-pencil"></i></a>
                                        <form method="POST" action="/spaces/<?= (int)$space['id'] ?>/locations/<?= (int)$loc['id'] ?>/delete" onsubmit="return confirm('Supprimer cet emplacement ?')">
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
