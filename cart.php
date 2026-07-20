<?php

session_start();
include 'partials/header.php';
?>

<h1 class="mb-4">Your Shopping Cart</h1>

<div id="cart-container">
    <div class="text-center py-5">
        <div class="spinner-border text-success" role="status">
            <span class="visually-hidden">Loading...</span>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', loadCart);

function loadCart() {
    fetch('cart_actions.php?action=get')
        .then(response => response.json())
        .then(cart => {
            const container = document.getElementById('cart-container');
            if (cart.length === 0) {
                container.innerHTML = `
                    <div class="alert alert-info">
                        Your cart is empty. <a href="index.php" class="alert-link">Continue shopping</a>.
                    </div>
                `;
                updateCartCountDisplay();
                return;
            }

            let html = `
                <div class="table-responsive">
                    <table class="table align-middle">
                        <thead>
                            <tr>
                                <th>Product</th>
                                <th>Price</th>
                                <th>Quantity</th>
                                <th>Total</th>
                                <th></th>
                            </tr>
                        </thead>
                        <tbody>
            `;
            let subtotal = 0;
            cart.forEach(item => {
                const itemTotal = item.price * item.quantity;
                subtotal += itemTotal;
                html += `
                    <tr>
                        <td>${item.name}</td>
                        <td>₹${parseFloat(item.price).toFixed(2)}</td>
                        <td style="width: 120px;">
                            <input type="number" min="1" value="${item.quantity}" class="form-control form-control-sm" 
                                   onchange="updateCart(${item.id}, this.value)">
                        </td>
                        <td>₹${itemTotal.toFixed(2)}</td>
                        <td>
                            <button class="btn btn-sm btn-outline-danger" onclick="removeFromCart(${item.id})">
                                Remove
                            </button>
                        </td>
                    </tr>
                `;
            });
            html += `
                        </tbody>
                    </table>
                </div>
                <div class="d-flex justify-content-between align-items-center mt-4">
                    <h4>Total: ₹${subtotal.toFixed(2)}</h4>
                    <div>
                        <a href="index.php" class="btn btn-outline-secondary me-2">Continue Shopping</a>
                        <a href="new_checkout.php" class="btn btn-success">Proceed to Checkout</a>
                    </div>
                </div>
            `;
            container.innerHTML = html;
            updateCartCountDisplay();
        })
        .catch(error => {
            document.getElementById('cart-container').innerHTML = '<div class="alert alert-danger">Error loading cart.</div>';
        });
}

function updateCart(productId, quantity) {
    fetch(`cart_actions.php?action=update&id=${productId}&qty=${quantity}`)
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                loadCart();
            }
        });
}

function removeFromCart(productId) {
    if (confirm('Remove this item from cart?')) {
        fetch(`cart_actions.php?action=remove&id=${productId}`)
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    loadCart();
                }
            });
    }
}

function updateCartCountDisplay() {
    fetch('cart_actions.php?action=get')
        .then(res => res.json())
        .then(cart => {
            const count = cart.reduce((sum, item) => sum + item.quantity, 0);
            const badge = document.getElementById('cart-count');
            if (badge) badge.textContent = count;
        });
}
</script>

<?php include 'partials/footer.php'; ?>