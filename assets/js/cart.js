document.addEventListener('DOMContentLoaded', function() {
    loadCart();
});

function loadCart() {
    fetch('cart_actions.php?action=get')
        .then(response => response.json())
        .then(cart => {
            let html = '';
            let total = 0;
            if (cart.length === 0) {
                html = '<p>Your cart is empty.</p>';
            } else {
                html = '<table class="table"><thead><tr><th>Product</th><th>Price</th><th>Quantity</th><th>Total</th><th></th></tr></thead><tbody>';
                cart.forEach(item => {
                    total += item.price * item.quantity;
                    html += `<tr>
                        <td>${item.name}</td>
                        <td>$${item.price}</td>
                        <td><input type="number" min="1" value="${item.quantity}" onchange="updateCart(${item.id}, this.value)"></td>
                        <td>$${(item.price * item.quantity).toFixed(2)}</td>
                        <td><button class="btn btn-sm btn-danger" onclick="removeFromCart(${item.id})">Remove</button></td>
                    </tr>`;
                });
                html += `</tbody></table><h3>Total: $${total.toFixed(2)}</h3><a href="checkout.php" class="btn btn-success">Proceed to Checkout</a>`;
            }
            document.getElementById('cart-items').innerHTML = html;
        });
}

function updateCart(productId, quantity) {
    fetch(`cart_actions.php?action=update&id=${productId}&qty=${quantity}`)
        .then(() => loadCart());
}

function removeFromCart(productId) {
    fetch(`cart_actions.php?action=remove&id=${productId}`)
        .then(() => loadCart());
}