<?php
$canManageProducts = in_array($role, ['gestionnaire_produits', 'gestionnaire_global', 'administrateur'], true);
$canManageInventory = in_array($role, ['gestionnaire_inventaires', 'gestionnaire_global', 'administrateur'], true);
$isAdmin = $role === 'administrateur';
?>

<div class="page-header">
    <div>
        <h1><i class="bi bi-grid"></i> <?= e($space['name']) ?></h1>
        <?php if ($space['description']): ?>
            <p class="page-description"><?= e($space['description']) ?></p>
        <?php endif; ?>
        <span class="badge badge-primary mt-1"><?= e(space_role_label($role)) ?></span>
    </div>
    <div class="btn-group">
        <a href="/spaces" class="btn btn-outline btn-sm"><i class="bi bi-arrow-left"></i> Retour</a>
        <?php if ($isAdmin): ?>
            <a href="/spaces/<?= (int)$space['id'] ?>/edit" class="btn btn-sm btn-outline"><i class="bi bi-pencil"></i> Modifier</a>
        <?php endif; ?>
    </div>
</div>

<div class="card-grid">
    <a href="/spaces/<?= (int)$space['id'] ?>/products" class="card-link">
        <div class="card">
            <div class="card-body text-center">
                <div style="font-size:2rem;color:var(--primary);margin-bottom:0.5rem;"><i class="bi bi-box-seam"></i></div>
                <h3>Produits</h3>
                <p class="text-muted text-small">Gérer les produits et catégories</p>
            </div>
        </div>
    </a>

    <a href="/spaces/<?= (int)$space['id'] ?>/categories" class="card-link">
        <div class="card">
            <div class="card-body text-center">
                <div style="font-size:2rem;color:var(--secondary);margin-bottom:0.5rem;"><i class="bi bi-tags"></i></div>
                <h3>Catégories</h3>
                <p class="text-muted text-small">Organiser par catégories</p>
            </div>
        </div>
    </a>

    <a href="/spaces/<?= (int)$space['id'] ?>/locations" class="card-link">
        <div class="card">
            <div class="card-body text-center">
                <div style="font-size:2rem;color:var(--info);margin-bottom:0.5rem;"><i class="bi bi-geo-alt"></i></div>
                <h3>Emplacements</h3>
                <p class="text-muted text-small">Définir les lieux de stockage</p>
            </div>
        </div>
    </a>

    <a href="/spaces/<?= (int)$space['id'] ?>/inventory" class="card-link">
        <div class="card">
            <div class="card-body text-center">
                <div style="font-size:2rem;color:var(--success);margin-bottom:0.5rem;"><i class="bi bi-clipboard-data"></i></div>
                <h3>Inventaire</h3>
                <p class="text-muted text-small">Consulter et gérer le stock</p>
            </div>
        </div>
    </a>

    <?php if ($isAdmin): ?>
    <a href="/spaces/<?= (int)$space['id'] ?>/members" class="card-link">
        <div class="card">
            <div class="card-body text-center">
                <div style="font-size:2rem;color:var(--warning);margin-bottom:0.5rem;"><i class="bi bi-people"></i></div>
                <h3>Membres</h3>
                <p class="text-muted text-small">Gérer les accès et rôles</p>
            </div>
        </div>
    </a>
    <?php endif; ?>
</div>
