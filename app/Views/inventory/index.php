<?php
$canManage = in_array($role, ['gestionnaire_inventaires', 'gestionnaire_global', 'administrateur'], true);
?>

<div class="page-header">
    <div>
        <h1><i class="bi bi-clipboard-data"></i> Inventaire</h1>
        <p class="page-description"><?= e($space['name']) ?></p>
    </div>
    <div class="btn-group">
        <a href="/spaces/<?= (int)$space['id'] ?>" class="btn btn-outline btn-sm"><i class="bi bi-arrow-left"></i> Retour</a>
        <?php if ($canManage): ?>
            <a href="/spaces/<?= (int)$space['id'] ?>/inventory/create" class="btn btn-primary btn-sm"><i class="bi bi-plus-lg"></i> Ajouter</a>
        <?php endif; ?>
    </div>
</div>

<?php if (!empty($expired)): ?>
    <div class="alert alert-danger" data-auto-dismiss>
        <span><i class="bi bi-exclamation-triangle"></i> <strong><?= count($expired) ?></strong> produit(s) périmé(s) dans cet espace !</span>
        <button class="alert-close">&times;</button>
    </div>
<?php endif; ?>

<?php if (!empty($expiringSoon)): ?>
    <div class="alert alert-warning" data-auto-dismiss>
        <span><i class="bi bi-clock"></i> <strong><?= count($expiringSoon) ?></strong> produit(s) expirant dans les 7 prochains jours.</span>
        <button class="alert-close">&times;</button>
    </div>
<?php endif; ?>

<?php if (empty($items)): ?>
    <div class="empty-state">
        <div class="empty-icon"><i class="bi bi-clipboard-data"></i></div>
        <p>L'inventaire est vide pour le moment.</p>
        <?php if ($canManage): ?>
            <a href="/spaces/<?= (int)$space['id'] ?>/inventory/create" class="btn btn-primary">Ajouter une entrée</a>
        <?php endif; ?>
    </div>
<?php else: ?>
    <div class="card">
        <div class="table-responsive">
            <table class="table">
                <thead>
                    <tr>
                        <th>Produit</th>
                        <th>Emplacement</th>
                        <th>Quantité</th>
                        <th>Date de stock</th>
                        <th>Date limite</th>
                        <th>Statut</th>
                        <?php if ($canManage): ?>
                            <th class="text-right">Actions</th>
                        <?php endif; ?>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($items as $item): ?>
                        <?php
                        $expiryBadge = '';
                        $expiryStatus = '';
                        if ($item['expiry_date']) {
                            $today = new DateTime();
                            $expiry = new DateTime($item['expiry_date']);
                            $diff = $today->diff($expiry);
                            if ($expiry < $today) {
                                $expiryStatus = 'Périmé';
                                $expiryBadge = 'badge-danger';
                            } elseif ($diff->days <= 7) {
                                $expiryStatus = $diff->days . 'j restants';
                                $expiryBadge = 'badge-warning';
                            } else {
                                $expiryStatus = 'OK';
                                $expiryBadge = 'badge-success';
                            }
                        }
                        ?>
                        <tr>
                            <td><strong><?= e($item['product_name']) ?></strong></td>
                            <td><span class="badge badge-info"><?= e($item['location_name']) ?></span></td>
                            <td><strong><?= (int)$item['quantity'] ?></strong></td>
                            <td class="text-small"><?= e(date('d/m/Y', strtotime($item['stock_date']))) ?></td>
                            <td class="text-small">
                                <?= $item['expiry_date'] ? e(date('d/m/Y', strtotime($item['expiry_date']))) : '—' ?>
                            </td>
                            <td>
                                <?php if ($expiryBadge): ?>
                                    <span class="badge <?= $expiryBadge ?>"><?= e($expiryStatus) ?></span>
                                <?php else: ?>
                                    <span class="text-muted">—</span>
                                <?php endif; ?>
                            </td>
                            <?php if ($canManage): ?>
                                <td class="text-right">
                                    <div class="btn-group">
                                        <a href="/spaces/<?= (int)$space['id'] ?>/inventory/<?= (int)$item['id'] ?>/edit" class="btn btn-sm btn-outline"><i class="bi bi-pencil"></i></a>
                                        <form method="POST" action="/spaces/<?= (int)$space['id'] ?>/inventory/<?= (int)$item['id'] ?>/delete" onsubmit="return confirm('Supprimer cette entrée ?')">
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
