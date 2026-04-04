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
        <div class="table-responsive">
            <table class="table">
                <thead>
                    <tr>
                        <th>Nom</th>
                        <th>Catégories</th>
                        <th>Date de stock</th>
                        <th>Date limite</th>
                        <th>Statut</th>
                        <?php if ($canManage): ?>
                            <th class="text-right">Actions</th>
                        <?php endif; ?>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($products as $p): ?>
                        <?php
                        $expiryStatus = '';
                        $expiryBadge = '';
                        if ($p['expiry_date']) {
                            $today = new DateTime();
                            $expiry = new DateTime($p['expiry_date']);
                            $diff = $today->diff($expiry);
                            if ($expiry < $today) {
                                $expiryStatus = 'Périmé';
                                $expiryBadge = 'badge-danger';
                            } elseif ($diff->days <= 7) {
                                $expiryStatus = 'Expire bientôt';
                                $expiryBadge = 'badge-warning';
                            } else {
                                $expiryStatus = 'OK';
                                $expiryBadge = 'badge-success';
                            }
                        }
                        ?>
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
                            <td><?= e(date('d/m/Y', strtotime($p['stock_date']))) ?></td>
                            <td>
                                <?php if ($p['expiry_date']): ?>
                                    <?= e(date('d/m/Y', strtotime($p['expiry_date']))) ?>
                                <?php else: ?>
                                    <span class="text-muted">—</span>
                                <?php endif; ?>
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
        </div>
    </div>
<?php endif; ?>
