<div class="page-header">
    <h1><i class="bi bi-speedometer2"></i> Tableau de bord</h1>
</div>

<!-- Statistiques rapides -->
<div class="dashboard-stats">
    <div class="stat-card stat-danger">
        <div class="stat-icon"><i class="bi bi-exclamation-triangle-fill"></i></div>
        <div class="stat-info">
            <span class="stat-number"><?= count($expired) ?></span>
            <span class="stat-label">Périmé<?= count($expired) > 1 ? 's' : '' ?></span>
        </div>
    </div>
    <div class="stat-card stat-warning">
        <div class="stat-icon"><i class="bi bi-clock-history"></i></div>
        <div class="stat-info">
            <span class="stat-number"><?= count($expiringSoon) ?></span>
            <span class="stat-label">Expire bientôt</span>
        </div>
    </div>
</div>

<!-- Produits périmés -->
<?php if (!empty($expired)): ?>
<div class="card dashboard-section">
    <div class="card-body">
        <h3 class="text-danger"><i class="bi bi-exclamation-triangle-fill"></i> Produits périmés</h3>
        <div class="table-responsive">
            <table class="table">
                <thead>
                    <tr>
                        <th>Produit</th>
                        <th>Espace</th>
                        <th>Emplacement</th>
                        <th>Qté</th>
                        <th>Péremption</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($expired as $item): ?>
                    <tr>
                        <td data-label="Produit"><strong><?= e($item['product_name']) ?></strong></td>
                        <td data-label="Espace">
                            <a href="/spaces/<?= (int)$item['sid'] ?>/inventory"><?= e($item['space_name']) ?></a>
                        </td>
                        <td data-label="Emplacement"><?= e($item['location_name']) ?></td>
                        <td data-label="Qté"><?= (int)$item['quantity'] ?></td>
                        <td data-label="Péremption">
                            <span class="badge badge-danger"><?= e($item['expiry_date']) ?></span>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>
<?php endif; ?>

<!-- Produits à consommer prochainement -->
<div class="card dashboard-section">
    <div class="card-body">
        <h3 class="text-warning-dark"><i class="bi bi-clock-history"></i> À consommer prochainement (14 jours)</h3>
        <?php if (empty($expiringSoon)): ?>
            <p class="text-muted">Aucun produit n'expire dans les 14 prochains jours.</p>
        <?php else: ?>
        <div class="table-responsive">
            <table class="table">
                <thead>
                    <tr>
                        <th>Produit</th>
                        <th>Espace</th>
                        <th>Emplacement</th>
                        <th>Qté</th>
                        <th>Péremption</th>
                        <th>Jours restants</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($expiringSoon as $item):
                        $daysLeft = (int)((strtotime($item['expiry_date']) - time()) / 86400);
                        $urgencyClass = $daysLeft <= 3 ? 'badge-danger' : ($daysLeft <= 7 ? 'badge-warning' : 'badge-success');
                    ?>
                    <tr>
                        <td data-label="Produit"><strong><?= e($item['product_name']) ?></strong></td>
                        <td data-label="Espace">
                            <a href="/spaces/<?= (int)$item['sid'] ?>/inventory"><?= e($item['space_name']) ?></a>
                        </td>
                        <td data-label="Emplacement"><?= e($item['location_name']) ?></td>
                        <td data-label="Qté"><?= (int)$item['quantity'] ?></td>
                        <td data-label="Péremption"><?= e($item['expiry_date']) ?></td>
                        <td data-label="Jours restants">
                            <span class="badge <?= $urgencyClass ?>"><?= $daysLeft ?> jour<?= $daysLeft > 1 ? 's' : '' ?></span>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
        <?php endif; ?>
    </div>
</div>

<!-- Calendrier des péremptions -->
<div class="card dashboard-section">
    <div class="card-body">
        <h3><i class="bi bi-calendar3"></i> Calendrier des péremptions</h3>
        <div class="calendar-legend">
            <span class="legend-item"><span class="legend-dot legend-dot-success"></span> OK</span>
            <span class="legend-item"><span class="legend-dot legend-dot-warning"></span> Expire bientôt</span>
            <span class="legend-item"><span class="legend-dot legend-dot-danger"></span> Périmé</span>
        </div>
        <div id="calendar"></div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/fullcalendar@6.1.15/index.global.min.js"></script>
<script>
document.addEventListener('DOMContentLoaded', function() {
    var calendarEl = document.getElementById('calendar');
    if (!calendarEl) return;

    var events = <?= $calendarEventsJson ?>;

    var calendar = new FullCalendar.Calendar(calendarEl, {
        locale: 'fr',
        initialView: window.innerWidth < 768 ? 'listMonth' : 'dayGridMonth',
        headerToolbar: {
            left: 'prev,next today',
            center: 'title',
            right: window.innerWidth < 768 ? 'listMonth' : 'dayGridMonth,listMonth'
        },
        buttonText: {
            today: "Aujourd'hui",
            month: 'Mois',
            list: 'Liste'
        },
        events: events,
        height: 'auto',
        eventDisplay: 'block',
        eventDidMount: function(info) {
            var props = info.event.extendedProps;
            info.el.title = info.event.title
                + '\nEspace : ' + props.space
                + '\nEmplacement : ' + props.location
                + '\nQuantité : ' + props.quantity
                + '\nEn stock depuis : ' + props.stockDate
                + '\nPéremption : ' + props.expiryDate;
        },
        windowResize: function(view) {
            if (window.innerWidth < 768) {
                calendar.changeView('listMonth');
                calendar.setOption('headerToolbar', {
                    left: 'prev,next today',
                    center: 'title',
                    right: 'listMonth'
                });
            } else {
                calendar.changeView('dayGridMonth');
                calendar.setOption('headerToolbar', {
                    left: 'prev,next today',
                    center: 'title',
                    right: 'dayGridMonth,listMonth'
                });
            }
        }
    });

    calendar.render();
});
</script>
