<div class="page-header">
    <div>
        <h1><i class="bi bi-people"></i> Membres — <?= e($space['name']) ?></h1>
        <p class="page-description">Gérer les accès et les rôles des utilisateurs</p>
    </div>
    <a href="/spaces/<?= (int)$space['id'] ?>" class="btn btn-outline btn-sm"><i class="bi bi-arrow-left"></i> Retour</a>
</div>

<div class="card mb-3">
    <div class="card-header">
        <h3>Ajouter un membre</h3>
    </div>
    <div class="card-body">
        <form method="POST" action="/spaces/<?= (int)$space['id'] ?>/members/add">
            <?= csrf_field() ?>
            <div class="form-row">
                <div class="form-group">
                    <label class="form-label" for="email">Email de l'utilisateur</label>
                    <input type="email" id="email" name="email" class="form-control" required placeholder="utilisateur@email.com">
                </div>
                <div class="form-group">
                    <label class="form-label" for="role">Rôle</label>
                    <select id="role" name="role" class="form-control">
                        <option value="membre">Membre (lecture seule)</option>
                        <option value="gestionnaire_produits">Gestionnaire produits</option>
                        <option value="gestionnaire_inventaires">Gestionnaire inventaires</option>
                        <option value="gestionnaire_global">Gestionnaire global</option>
                        <option value="administrateur">Administrateur</option>
                    </select>
                </div>
            </div>
            <button type="submit" class="btn btn-primary"><i class="bi bi-person-plus"></i> Ajouter</button>
        </form>
    </div>
</div>

<div class="card">
    <div class="card-header">
        <h3>Membres actuels (<?= count($members) ?>)</h3>
    </div>
    <div class="table-responsive">
        <table class="table">
            <thead>
                <tr>
                    <th>Utilisateur</th>
                    <th>Email</th>
                    <th>Rôle</th>
                    <th>Depuis</th>
                    <th class="text-right">Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($members as $member): ?>
                    <tr>
                        <td><strong><?= e($member['username']) ?></strong></td>
                        <td><?= e($member['email']) ?></td>
                        <td>
                            <form method="POST" action="/spaces/<?= (int)$space['id'] ?>/members/<?= (int)$member['id'] ?>/update" class="d-flex gap-1">
                                <?= csrf_field() ?>
                                <select name="role" class="form-control" style="width:auto;min-width:180px;">
                                    <?php
                                    $roles = ['membre', 'gestionnaire_produits', 'gestionnaire_inventaires', 'gestionnaire_global', 'administrateur'];
                                    foreach ($roles as $r):
                                    ?>
                                        <option value="<?= $r ?>" <?= $member['role'] === $r ? 'selected' : '' ?>><?= e($r) ?></option>
                                    <?php endforeach; ?>
                                </select>
                                <button type="submit" class="btn btn-sm btn-outline" title="Modifier le rôle"><i class="bi bi-check-lg"></i></button>
                            </form>
                        </td>
                        <td class="text-small text-muted"><?= e(date('d/m/Y', strtotime($member['joined_at']))) ?></td>
                        <td class="text-right">
                            <?php if ((int)$member['id'] !== current_user_id()): ?>
                                <form method="POST" action="/spaces/<?= (int)$space['id'] ?>/members/<?= (int)$member['id'] ?>/remove" onsubmit="return confirm('Retirer ce membre ?')">
                                    <?= csrf_field() ?>
                                    <button type="submit" class="btn btn-sm btn-outline-danger"><i class="bi bi-person-dash"></i></button>
                                </form>
                            <?php endif; ?>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>
