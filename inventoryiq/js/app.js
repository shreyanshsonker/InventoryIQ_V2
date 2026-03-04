/**
 * InventoryIQ v2.0 — Minimal Vanilla JS
 * AI Rules §1.2 — JS is enhancement only. Pages must work without JS.
 */

/* ============================================================
   Confirmation Modal
   ============================================================ */
function showConfirmModal(title, message, onConfirm) {
    var backdrop = document.getElementById('confirm-modal');
    if (!backdrop) return;

    document.getElementById('modal-title').textContent = title;
    document.getElementById('modal-message').textContent = message;
    backdrop.classList.add('show');

    var confirmBtn = document.getElementById('modal-confirm-btn');
    var cancelBtn = document.getElementById('modal-cancel-btn');

    var newConfirm = confirmBtn.cloneNode(true);
    confirmBtn.parentNode.replaceChild(newConfirm, confirmBtn);
    newConfirm.id = 'modal-confirm-btn';

    newConfirm.addEventListener('click', function () {
        backdrop.classList.remove('show');
        if (typeof onConfirm === 'function') onConfirm();
    });

    cancelBtn.addEventListener('click', function () {
        backdrop.classList.remove('show');
    });

    backdrop.addEventListener('click', function (e) {
        if (e.target === backdrop) backdrop.classList.remove('show');
    });
}

function confirmDelete(formId, itemName) {
    showConfirmModal(
        'Confirm Deletion',
        'Are you sure you want to delete "' + itemName + '"? This action cannot be undone.',
        function () {
            document.getElementById(formId).submit();
        }
    );
    return false;
}

/* ============================================================
   Toast Notifications
   ============================================================ */
function showToast(type, message) {
    var container = document.getElementById('toast-container');
    if (!container) return;

    var iconMap = {
        success: 'check-circle',
        error: 'x-circle',
        warning: 'alert-triangle',
        info: 'info'
    };

    var toast = document.createElement('div');
    toast.className = 'toast toast-' + type;
    toast.innerHTML = '<i data-lucide="' + (iconMap[type] || 'info') + '" class="toast-icon"></i>' +
        '<span>' + message + '</span>';

    container.appendChild(toast);

    if (typeof lucide !== 'undefined') {
        lucide.createIcons({ nodes: [toast] });
    }

    setTimeout(function () {
        toast.style.animation = 'toastSlideOut 300ms cubic-bezier(0.34, 1.56, 0.64, 1) forwards';
        setTimeout(function () {
            if (toast.parentNode) toast.parentNode.removeChild(toast);
        }, 300);
    }, 4000);
}

/* ============================================================
   Light / Dark Mode Toggle
   ============================================================ */
function toggleDarkMode() {
    document.body.classList.toggle('light-mode');
    var isLight = document.body.classList.contains('light-mode');
    try {
        localStorage.setItem('iqTheme', isLight ? 'light' : 'dark');
    } catch (e) { }

    /* Re-init Lucide so sun/moon icons update */
    if (typeof lucide !== 'undefined') {
        lucide.createIcons();
    }
}

(function () {
    try {
        if (localStorage.getItem('iqTheme') === 'light') {
            document.body.classList.add('light-mode');
        }
    } catch (e) { }
})();

/* ============================================================
   Avatar Dropdown Toggle
   ============================================================ */
function toggleAvatarDropdown() {
    var dropdown = document.getElementById('avatar-dropdown');
    if (!dropdown) return;
    dropdown.classList.toggle('open');

    /* Re-init Lucide for dropdown icons */
    if (typeof lucide !== 'undefined') {
        lucide.createIcons();
    }
}

/* Close dropdown when clicking outside */
document.addEventListener('click', function (e) {
    var wrap = document.getElementById('avatar-dropdown-wrap');
    var dropdown = document.getElementById('avatar-dropdown');
    if (wrap && dropdown && !wrap.contains(e.target)) {
        dropdown.classList.remove('open');
    }
});

/* ============================================================
   3D Card Tilt Effect
   ============================================================ */
function initTiltEffect() {
    var cards = document.querySelectorAll('.tilt-card');
    cards.forEach(function (card) {
        card.addEventListener('mousemove', function (e) {
            var rect = card.getBoundingClientRect();
            var x = e.clientX - rect.left;
            var y = e.clientY - rect.top;
            var centerX = rect.width / 2;
            var centerY = rect.height / 2;
            var rotateX = ((y - centerY) / centerY) * -8;
            var rotateY = ((x - centerX) / centerX) * 8;
            card.style.transform = 'perspective(1000px) rotateX(' + rotateX + 'deg) rotateY(' + rotateY + 'deg) translateY(-4px)';
        });

        card.addEventListener('mouseleave', function () {
            card.style.transform = 'perspective(1000px) rotateX(0deg) rotateY(0deg) translateY(0)';
        });
    });
}

/* ============================================================
   Stat Number Count-Up Animation
   ============================================================ */
function animateCountUp() {
    var counters = document.querySelectorAll('.count-up');
    counters.forEach(function (counter) {
        var target = parseInt(counter.getAttribute('data-target'), 10);
        if (isNaN(target)) return;

        var duration = 1200;
        var start = 0;
        var startTime = null;

        function step(timestamp) {
            if (!startTime) startTime = timestamp;
            var progress = Math.min((timestamp - startTime) / duration, 1);
            var ease = 1 - Math.pow(1 - progress, 3);
            counter.textContent = Math.floor(ease * target).toLocaleString();
            if (progress < 1) {
                requestAnimationFrame(step);
            } else {
                counter.textContent = target.toLocaleString();
            }
        }

        requestAnimationFrame(step);
    });
}

/* ============================================================
   Password Toggle
   ============================================================ */
function togglePasswordVisibility(inputId, iconElement) {
    var input = document.getElementById(inputId);
    if (!input) return;

    if (input.type === 'password') {
        input.type = 'text';
        if (iconElement) iconElement.setAttribute('data-lucide', 'eye-off');
    } else {
        input.type = 'password';
        if (iconElement) iconElement.setAttribute('data-lucide', 'eye');
    }

    if (typeof lucide !== 'undefined') {
        lucide.createIcons();
    }
}

/* ============================================================
   Init on DOM Ready
   ============================================================ */
document.addEventListener('DOMContentLoaded', function () {
    /* Initialize Lucide icons */
    if (typeof lucide !== 'undefined') {
        lucide.createIcons();
    }

    /* Initialize tilt effect */
    initTiltEffect();

    /* Count-up animation */
    animateCountUp();

    /* Auto-show toasts from PHP session flash */
    var flashToasts = document.querySelectorAll('[data-toast]');
    flashToasts.forEach(function (el) {
        showToast(el.getAttribute('data-toast-type') || 'info', el.getAttribute('data-toast'));
        el.parentNode.removeChild(el);
    });
});
