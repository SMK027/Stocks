<div class="auth-container">
    <div class="card">
        <div class="card-body">
            <h2><i class="bi bi-plus-lg"></i> Créer un espace</h2>
            <form method="POST" action="/spaces/create">
                <?= csrf_field() ?>
                <div class="form-group">
                    <label class="form-label" for="name">Nom de l'espace *</label>
                    <input type="text" id="name" name="name" class="form-control" required maxlength="100" placeholder="Ex : Cuisine, Entrepôt...">
                </div>
                <div class="form-group">
                    <label class="form-label" for="description">Description</label>
                    <textarea id="description" name="description" class="form-control" rows="3" placeholder="Description optionnelle"></textarea>
                </div>
                <button type="submit" class="btn btn-primary btn-block">Créer l'espace</button>
            </form>
        </div>
    </div>
</div>
