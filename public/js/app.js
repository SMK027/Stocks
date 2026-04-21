/**
 * ToolBox — JavaScript utilitaire
 */
document.addEventListener('DOMContentLoaded', function () {

    /* ----- Navbar toggle (mobile) ----- */
    const navToggle = document.querySelector('.navbar-toggle');
    const navMenu = document.querySelector('.navbar-menu');
    if (navToggle && navMenu) {
        navToggle.addEventListener('click', function () {
            const isOpen = navMenu.classList.toggle('open');
            navToggle.classList.toggle('active', isOpen);
            navToggle.setAttribute('aria-expanded', isOpen);
        });
        // Fermer le menu au clic en dehors
        document.addEventListener('click', function (e) {
            if (!navToggle.contains(e.target) && !navMenu.contains(e.target)) {
                navMenu.classList.remove('open');
                navToggle.classList.remove('active');
                navToggle.setAttribute('aria-expanded', 'false');
            }
        });
        // Fermer le menu sur la touche Escape
        document.addEventListener('keydown', function (e) {
            if (e.key === 'Escape' && navMenu.classList.contains('open')) {
                navMenu.classList.remove('open');
                navToggle.classList.remove('active');
                navToggle.setAttribute('aria-expanded', 'false');
                navToggle.focus();
            }
        });
        // Fermer le menu quand un lien est cliqué
        navMenu.querySelectorAll('a').forEach(function (link) {
            link.addEventListener('click', function () {
                navMenu.classList.remove('open');
                navToggle.classList.remove('active');
                navToggle.setAttribute('aria-expanded', 'false');
            });
        });
    }

    /* ----- Sidebar toggle (tablette) ----- */
    const sidebarToggle = document.querySelector('.sidebar-toggle');
    const sidebar = document.querySelector('.sidebar');
    if (sidebarToggle && sidebar) {
        sidebarToggle.addEventListener('click', function () {
            sidebar.classList.toggle('open');
        });
    }

    /* ----- Password visibility toggle ----- */
    document.querySelectorAll('.btn-toggle-password').forEach(function (btn) {
        btn.addEventListener('click', function () {
            const input = this.closest('.password-wrapper').querySelector('input');
            const icon = this.querySelector('i');
            if (input.type === 'password') {
                input.type = 'text';
                icon.className = 'bi bi-eye-slash';
                this.classList.add('active');
            } else {
                input.type = 'password';
                icon.className = 'bi bi-eye';
                this.classList.remove('active');
            }
        });
    });

    /* ----- Fermer les alertes ----- */
    document.querySelectorAll('.alert-close').forEach(function (btn) {
        btn.addEventListener('click', function () {
            const alert = this.closest('.alert');
            alert.style.opacity = '0';
            alert.style.transform = 'translateY(-10px)';
            setTimeout(function () { alert.remove(); }, 200);
        });
    });

    /* ----- Auto-dismiss des alertes (5s) ----- */
    document.querySelectorAll('.alert[data-auto-dismiss]').forEach(function (alert) {
        setTimeout(function () {
            const closeBtn = alert.querySelector('.alert-close');
            if (closeBtn) closeBtn.click();
        }, 5000);
    });

    /* ----- Modal de confirmation ----- */
    document.querySelectorAll('[data-confirm]').forEach(function (trigger) {
        trigger.addEventListener('click', function (e) {
            e.preventDefault();
            const message = this.dataset.confirm || 'Êtes-vous sûr ?';
            const overlay = document.getElementById('confirm-modal');
            if (overlay) {
                overlay.querySelector('.modal-message').textContent = message;
                overlay.dataset.action = this.href || this.dataset.action || '';
                overlay.classList.add('active');
            }
        });
    });

    const confirmModal = document.getElementById('confirm-modal');
    if (confirmModal) {
        confirmModal.querySelector('.btn-confirm')?.addEventListener('click', function () {
            const action = confirmModal.dataset.action;
            if (action) window.location.href = action;
            confirmModal.classList.remove('active');
        });
        confirmModal.querySelector('.btn-cancel')?.addEventListener('click', function () {
            confirmModal.classList.remove('active');
        });
        confirmModal.addEventListener('click', function (e) {
            if (e.target === this) this.classList.remove('active');
        });
    }

    /* ----- Toast notifications ----- */
    window.showToast = function (message, type) {
        type = type || 'info';
        let container = document.querySelector('.toast-container');
        if (!container) {
            container = document.createElement('div');
            container.className = 'toast-container';
            document.body.appendChild(container);
        }
        const toast = document.createElement('div');
        toast.className = 'toast toast-' + type;
        toast.textContent = message;
        container.appendChild(toast);
        setTimeout(function () {
            toast.style.opacity = '0';
            toast.style.transform = 'translateX(20px)';
            setTimeout(function () { toast.remove(); }, 300);
        }, 4000);
    };

    /* ----- Filtrage dynamique (tables & card-grid) ----- */
    function normalizeText(str) {
        return str.toLowerCase()
            .normalize('NFD')
            .replace(/[\u0300-\u036f]/g, '');
    }

    function applyFilters(targetId) {
        const container = document.getElementById(targetId);
        if (!container) return;

        const searchInput = document.querySelector('.js-search-input[data-search-for="' + targetId + '"]');
        const query = searchInput ? normalizeText(searchInput.value.trim()) : '';

        const activeStatusBtn = document.querySelector('.js-status-filter.active[data-search-for="' + targetId + '"]');
        const statusFilter = activeStatusBtn ? activeStatusBtn.dataset.status : 'all';

        let visibleCount = 0;

        if (container.tagName === 'TBODY') {
            container.querySelectorAll('tr').forEach(function (row) {
                const text = normalizeText(row.textContent);
                const rowStatus = row.dataset.status || 'none';
                const matchesText = !query || text.includes(query);
                const matchesStatus = statusFilter === 'all' || rowStatus === statusFilter;
                const show = matchesText && matchesStatus;
                row.style.display = show ? '' : 'none';
                if (show) visibleCount++;
            });
        } else {
            container.querySelectorAll('.card-link').forEach(function (card) {
                const text = normalizeText(card.textContent);
                const show = !query || text.includes(query);
                card.style.display = show ? '' : 'none';
                if (show) visibleCount++;
            });
        }

        const noResults = document.getElementById(targetId + '-no-results');
        if (noResults) noResults.style.display = visibleCount === 0 ? 'block' : 'none';
    }

    document.querySelectorAll('.js-search-input').forEach(function (input) {
        input.addEventListener('input', function () {
            applyFilters(this.dataset.searchFor);
        });
    });

    document.querySelectorAll('.js-status-filter').forEach(function (btn) {
        btn.addEventListener('click', function () {
            const targetId = this.dataset.searchFor;
            document.querySelectorAll('.js-status-filter[data-search-for="' + targetId + '"]').forEach(function (b) {
                b.classList.remove('active');
            });
            this.classList.add('active');
            applyFilters(targetId);
        });
    });
});
