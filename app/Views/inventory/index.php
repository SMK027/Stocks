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
    <?php
    $expiredIds   = array_column($expired   ?? [], 'id');
    $expiringIds  = array_column($expiringSoon ?? [], 'id');
    $countAll      = count($items);
    $countExpired  = count($expiredIds);
    $countExpiring = count($expiringIds);
    $countOk       = count(array_filter($items, fn($i) => $i['expiry_date'] && !in_array($i['id'], $expiredIds) && !in_array($i['id'], $expiringIds)));
    ?>
    <div class="card">
        <div class="search-bar">
            <i class="bi bi-search search-bar-icon"></i>
            <input type="text"
                   class="search-input js-search-input"
                   data-search-for="inventory-tbody"
                   placeholder="Rechercher un produit, emplacement…">
        </div>
        <div class="search-filters">
            <span class="search-filters-label">Statut&nbsp;:</span>
            <button class="filter-btn filter-btn-all active js-status-filter" data-status="all" data-search-for="inventory-tbody">
                Tous <span class="filter-count"><?= $countAll ?></span>
            </button>
            <button class="filter-btn filter-btn-danger js-status-filter" data-status="expired" data-search-for="inventory-tbody">
                Périmés <span class="filter-count"><?= $countExpired ?></span>
            </button>
            <button class="filter-btn filter-btn-warning js-status-filter" data-status="expiring" data-search-for="inventory-tbody">
                Bientôt périmés <span class="filter-count"><?= $countExpiring ?></span>
            </button>
            <button class="filter-btn filter-btn-success js-status-filter" data-status="ok" data-search-for="inventory-tbody">
                OK <span class="filter-count"><?= $countOk ?></span>
            </button>
        </div>
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
                <tbody id="inventory-tbody">
                    <?php foreach ($items as $item): ?>
                        <?php
                        $expiryBadge = '';
                        $expiryStatus = '';
                        $rowStatus = 'none';
                        if ($item['expiry_date']) {
                            $today = new DateTime();
                            $expiry = new DateTime($item['expiry_date']);
                            $diff = $today->diff($expiry);
                            if ($expiry < $today) {
                                $expiryStatus = 'Périmé';
                                $expiryBadge = 'badge-danger';
                                $rowStatus = 'expired';
                            } elseif ($diff->days <= 7) {
                                $expiryStatus = $diff->days . 'j restants';
                                $expiryBadge = 'badge-warning';
                                $rowStatus = 'expiring';
                            } else {
                                $expiryStatus = 'OK';
                                $expiryBadge = 'badge-success';
                                $rowStatus = 'ok';
                            }
                        }
                        ?>
                        <tr data-status="<?= $rowStatus ?>">
                            <td data-label="Produit"><strong><?= e($item['product_name']) ?></strong></td>
                            <td data-label="Emplacement"><span class="badge badge-info"><?= e($item['location_name']) ?></span></td>
                            <td data-label="Qté"><strong><?= (int)$item['quantity'] ?></strong></td>
                            <td data-label="Stocké le" class="text-small"><?= e(date('d/m/Y', strtotime($item['stock_date']))) ?></td>
                            <td data-label="Expire le" class="text-small">
                                <?= $item['expiry_date'] ? e(date('d/m/Y', strtotime($item['expiry_date']))) : '—' ?>
                            </td>
                            <td data-label="Statut">
                                <?php if ($expiryBadge): ?>
                                    <span class="badge <?= $expiryBadge ?>"><?= e($expiryStatus) ?></span>
                                <?php else: ?>
                                    <span class="text-muted">—</span>
                                <?php endif; ?>
                            </td>
                            <?php if ($canManage): ?>
                                <td class="text-right">
                                    <div class="btn-group">
                                        <button type="button"
                                            class="btn btn-sm btn-outline-danger js-decrease-btn"
                                            title="Diminuer le stock"
                                            data-id="<?= (int)$item['id'] ?>"
                                            data-name="<?= e($item['product_name']) ?>"
                                            data-qty="<?= (int)$item['quantity'] ?>"
                                            data-action="/spaces/<?= (int)$space['id'] ?>/inventory/<?= (int)$item['id'] ?>/decrease">
                                            <i class="bi bi-dash-lg"></i>
                                        </button>
                                        <a href="/spaces/<?= (int)$space['id'] ?>/inventory/<?= (int)$item['id'] ?>/edit" class="btn btn-sm btn-outline"><i class="bi bi-pencil"></i></a>
                                        <form method="POST" action="/spaces/<?= (int)$space['id'] ?>/inventory/<?= (int)$item['id'] ?>/casse" onsubmit="return confirm('Mettre ce produit en casse ?')">
                                            <?= csrf_field() ?>
                                            <button type="submit" class="btn btn-sm btn-outline-warning" title="Mettre en casse"><i class="bi bi-x-circle"></i></button>
                                        </form>
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
            <p class="search-no-results" id="inventory-tbody-no-results"><i class="bi bi-search"></i> Aucune entrée ne correspond à votre recherche.</p>
        </div>
    </div>
<?php endif; ?>

<?php if (!empty($casseItems)): ?>
    <div class="card" style="margin-top: 2rem;">
        <div class="card-header" style="cursor: pointer;" onclick="document.getElementById('casse-section').classList.toggle('hidden')">
            <h3 style="margin: 0; font-size: 1.1rem;">
                <i class="bi bi-x-circle"></i> Produits en casse
                <span class="badge badge-secondary"><?= count($casseItems) ?></span>
                <i class="bi bi-chevron-down" style="float: right; margin-top: 2px;"></i>
            </h3>
        </div>
        <div id="casse-section" class="hidden">
            <div class="table-responsive">
                <table class="table">
                    <thead>
                        <tr>
                            <th>Produit</th>
                            <th>Emplacement</th>
                            <th>Quantité</th>
                            <th>Date de stock</th>
                            <th>Date limite</th>
                            <?php if ($canManage): ?>
                                <th class="text-right">Actions</th>
                            <?php endif; ?>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($casseItems as $cItem): ?>
                            <tr>
                                <td data-label="Produit"><strong><?= e($cItem['product_name']) ?></strong></td>
                                <td data-label="Emplacement"><span class="badge badge-info"><?= e($cItem['location_name']) ?></span></td>
                                <td data-label="Qté"><strong><?= (int)$cItem['quantity'] ?></strong></td>
                                <td data-label="Stocké le" class="text-small"><?= e(date('d/m/Y', strtotime($cItem['stock_date']))) ?></td>
                                <td data-label="Expire le" class="text-small">
                                    <?= $cItem['expiry_date'] ? e(date('d/m/Y', strtotime($cItem['expiry_date']))) : '—' ?>
                                </td>
                                <?php if ($canManage): ?>
                                    <td class="text-right">
                                        <div class="btn-group">
                                            <form method="POST" action="/spaces/<?= (int)$space['id'] ?>/inventory/<?= (int)$cItem['id'] ?>/uncasse" onsubmit="return confirm('Remettre ce produit en service ?')">
                                                <?= csrf_field() ?>
                                                <button type="submit" class="btn btn-sm btn-outline-success" title="Remettre en service"><i class="bi bi-arrow-counterclockwise"></i></button>
                                            </form>
                                            <form method="POST" action="/spaces/<?= (int)$space['id'] ?>/inventory/<?= (int)$cItem['id'] ?>/delete" onsubmit="return confirm('Supprimer définitivement ?')">
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
    </div>
<?php endif; ?>

<!-- Modal : diminuer le stock -->
<?php if ($canManage): ?>
<div class="modal-overlay" id="decrease-modal">
    <div class="modal">
        <h3><i class="bi bi-dash-circle"></i> Retirer du stock</h3>
        <p id="decrease-modal-desc" class="text-muted"></p>
        <form method="POST" id="decrease-form">
            <?= csrf_field() ?>
            <div class="form-group">
                <label class="form-label" for="decrease_by">Quantité à retirer *</label>
                <input type="number" id="decrease_by" name="decrease_by"
                       class="form-control" min="1" required>
                <span class="form-hint" id="decrease-hint"></span>
            </div>
            <div class="btn-group" style="justify-content:flex-end;">
                <button type="button" class="btn btn-outline" id="decrease-cancel">Annuler</button>
                <button type="submit" class="btn btn-primary">Confirmer</button>
            </div>
        </form>
    </div>
</div>
<script>
(function () {
    const overlay   = document.getElementById('decrease-modal');
    const form      = document.getElementById('decrease-form');
    const desc      = document.getElementById('decrease-modal-desc');
    const input     = document.getElementById('decrease_by');
    const hint      = document.getElementById('decrease-hint');
    const cancelBtn = document.getElementById('decrease-cancel');

    document.querySelectorAll('.js-decrease-btn').forEach(function (btn) {
        btn.addEventListener('click', function () {
            const qty    = parseInt(this.dataset.qty, 10);
            const name   = this.dataset.name;
            const action = this.dataset.action;

            desc.textContent = name + ' \u2014 stock actuel\u00a0: ' + qty;
            input.max   = qty;
            input.value = '';
            hint.textContent = 'Valeur comprise entre 1 et ' + qty + '.';
            hint.style.color = '';
            form.action = action;

            overlay.classList.add('active');
            setTimeout(function () { input.focus(); }, 50);
        });
    });

    form.addEventListener('submit', function (e) {
        const val = parseInt(input.value, 10);
        const max = parseInt(input.max, 10);
        if (isNaN(val) || val < 1 || val > max) {
            e.preventDefault();
            hint.textContent = 'Valeur invalide\u00a0: saisissez un nombre entre 1 et ' + max + '.';
            hint.style.color = 'var(--danger)';
            input.focus();
        }
    });

    function closeModal() {
        overlay.classList.remove('active');
        hint.style.color = '';
    }

    cancelBtn.addEventListener('click', closeModal);
    overlay.addEventListener('click', function (e) {
        if (e.target === overlay) closeModal();
    });
    document.addEventListener('keydown', function (e) {
        if (e.key === 'Escape' && overlay.classList.contains('active')) closeModal();
    });
})();
</script>
<?php endif; ?>
