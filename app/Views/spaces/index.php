<div class="page-header">
    <div>
        <h1><i class="bi bi-grid"></i> Mes espaces</h1>
        <p class="page-description">Gérez vos différents espaces d'inventaire</p>
    </div>
    <a href="/spaces/create" class="btn btn-primary"><i class="bi bi-plus-lg"></i> Nouvel espace</a>
</div>

<?php if (empty($spaces)): ?>
    <div class="empty-state">
        <div class="empty-icon"><i class="bi bi-grid"></i></div>
        <p>Vous n'avez aucun espace pour le moment.</p>
        <a href="/spaces/create" class="btn btn-primary">Créer mon premier espace</a>
    </div>
<?php else: ?>
    <div class="search-bar-card">
        <i class="bi bi-search search-bar-icon"></i>
        <input type="text"
               class="search-input js-search-input"
               data-search-for="spaces-grid"
               placeholder="Rechercher un espace…">
    </div>
    <div class="card-grid" id="spaces-grid">
        <?php foreach ($spaces as $s): ?>
            <a href="/spaces/<?= (int)$s['id'] ?>" class="card-link">
                <div class="card">
                    <div class="card-body">
                        <h3><?= e($s['name']) ?></h3>
                        <?php if ($s['description']): ?>
                            <p class="text-muted text-small"><?= e($s['description']) ?></p>
                        <?php endif; ?>
                        <span class="badge badge-primary"><?= e(space_role_label($s['role'])) ?></span>
                    </div>
                </div>
            </a>
        <?php endforeach; ?>
    </div>
    <p class="search-no-results" id="spaces-grid-no-results"><i class="bi bi-search"></i> Aucun espace ne correspond à votre recherche.</p>
<?php endif; ?>
