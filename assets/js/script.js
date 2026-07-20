/*****************************************************
 * Grocify - Frontend JavaScript
 * Cart Management + Location Selector + Toast
 *****************************************************/

document.addEventListener('DOMContentLoaded', function () {
    updateCartCount();

    // Legacy add-to-cart buttons (product.php etc)
    document.querySelectorAll('.add-to-cart').forEach(button => {
        button.addEventListener('click', function () {
            addToCart(this.dataset.id, this);
        });
    });

    // Location: Load saved
    const savedLocation = localStorage.getItem('grocify_location');
    if (savedLocation) updateLocationDisplay(savedLocation);

    // Location: Dropdown
    document.querySelectorAll('.location-option').forEach(item => {
        item.addEventListener('click', function (e) {
            e.preventDefault();
            const loc = this.dataset.location;
            updateLocationDisplay(loc);
            saveLocation(loc);
        });
    });

    // Location: Modal form
    const locationForm = document.getElementById('location-form');
    if (locationForm) {
        locationForm.addEventListener('submit', function (e) {
            e.preventDefault();
            const address = document.getElementById('address').value.trim();
            if (address) {
                const shortLoc = address.length > 20 ? address.substring(0, 17) + '...' : address;
                updateLocationDisplay(shortLoc);
                saveLocation(address);
                const modal = bootstrap.Modal.getInstance(document.getElementById('locationModal'));
                if (modal) modal.hide();
            }
        });
    }
});

/* ========== CART ========== */

function addToCart(productId, btn) {
    fetch(`cart_actions.php?action=add&id=${productId}`)
        .then(r => r.json())
        .then(data => {
            if (data.success) {
                updateCartCount();
                showToast('🛒 Added to cart!', 'success');
                if (btn) {
                    btn.textContent = '✓ Added';
                    btn.disabled = true;
                    setTimeout(() => { btn.textContent = 'Add to Cart'; btn.disabled = false; }, 1800);
                }
            } else {
                showToast('❌ Could not add item.', 'danger');
            }
        })
        .catch(() => showToast('❌ Network error.', 'danger'));
}

function updateCartCount() {
    fetch('cart_actions.php?action=get')
        .then(r => r.json())
        .then(cart => {
            const total = cart.reduce((s, i) => s + i.quantity, 0);
            const badge = document.getElementById('cart-count');
            if (badge) badge.textContent = total;
        })
        .catch(() => {});
}

/* ========== TOAST ========== */

function showToast(msg, type) {
    // Create toast container if missing
    let container = document.getElementById('grocify-toast-container');
    if (!container) {
        container = document.createElement('div');
        container.id = 'grocify-toast-container';
        container.className = 'position-fixed bottom-0 end-0 p-3';
        container.style.zIndex = '9999';
        document.body.appendChild(container);
    }

    const id = 'toast-' + Date.now();
    const bg = type === 'success' ? 'bg-success' : 'bg-danger';
    container.insertAdjacentHTML('beforeend', `
        <div id="${id}" class="toast align-items-center text-white ${bg} border-0 mb-2 show" role="alert">
            <div class="d-flex">
                <div class="toast-body fw-semibold">${msg}</div>
                <button type="button" class="btn-close btn-close-white me-2 m-auto" data-bs-dismiss="toast"></button>
            </div>
        </div>
    `);
    const el = document.getElementById(id);
    new bootstrap.Toast(el, { delay: 2500 }).show();
    el.addEventListener('hidden.bs.toast', () => el.remove());
}

/* ========== LOCATION ========== */

function updateLocationDisplay(text) {
    const span = document.getElementById('selected-location-text');
    if (span) span.textContent = text;
}

function saveLocation(location) {
    localStorage.setItem('grocify_location', location);
    fetch('save_location.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
        body: 'location=' + encodeURIComponent(location)
    }).catch(() => {});
}


// ===== WISHLIST =====
document.addEventListener('click', function(e) {
    const btn = e.target.closest('.wishlist-btn');
    if (!btn) return;
    const pid = btn.dataset.productId;
    if (!pid) return;
    e.preventDefault();
    fetch('/grocify/wishlist_action.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
        body: 'product_id=' + pid + '&action=toggle'
    }).then(r => r.json()).then(data => {
        if (data.redirect) {
            window.location.href = '/grocify/' + data.redirect;
            return;
        }
        btn.textContent = data.wishlisted ? '❤️' : '🤍';
        btn.title = data.wishlisted ? 'Remove from Wishlist' : 'Add to Wishlist';
        // Tiny toast
        const toast = document.createElement('div');
        toast.className = 'position-fixed bottom-0 end-0 m-3 alert alert-success py-2 px-3 small shadow';
        toast.style.zIndex = 9999;
        toast.textContent = data.msg;
        document.body.appendChild(toast);
        setTimeout(() => toast.remove(), 2500);
    }).catch(() => {});
});
