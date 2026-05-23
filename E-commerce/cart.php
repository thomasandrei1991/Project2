<?php
    session_start();
    if (!isset($_SESSION['user_id'])) {
        header("Location: login.php");
        exit;
    }
    require_once __DIR__ . "/config/database.php";

    $user_id = (int)$_SESSION['user_id'];

    // Fetch cart items with product details
    $stmt = mysqli_prepare($conn,
        "SELECT c.id AS cart_id, c.quantity,
                p.id AS product_id, p.name, p.price, p.image, p.stocks
        FROM cart c
        JOIN products p ON c.product_id = p.id
        WHERE c.user_id = ?
        ORDER BY c.id DESC");
    mysqli_stmt_bind_param($stmt, 'i', $user_id);
    mysqli_stmt_execute($stmt);
    $cartItems = mysqli_fetch_all(mysqli_stmt_get_result($stmt), MYSQLI_ASSOC);
    mysqli_stmt_close($stmt);

    // Calculate totals
    $subtotal = 0;
    foreach ($cartItems as $item) {
        $subtotal += $item['price'] * $item['quantity'];
    }
    $shipping = $subtotal > 0 ? ($subtotal >= 1000 ? 0 : 80) : 0;
    $total    = $subtotal + $shipping;
?>
<!DOCTYPE html>
<html lang="en">
    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <title>My Cart — ShopGreen</title>
        <!-- Every page: index.php, register.php, cart.php, product.php, etc. -->
        <link rel="stylesheet" href="<?php echo BASE_URL; ?>assets/css/style.css">
        <link rel="stylesheet" href="<?php echo BASE_URL; ?>assets/css/shop.css">
        <link rel="stylesheet" href="<?php echo BASE_URL; ?>assets/css/cart.css">
    </head>
    <body>
        <?php include 'includes/navbar.php'; ?>

        <div class="container">
            <h1 class="page-title">🛒 My Cart
                <span class="item-count">
                    <?php echo count($cartItems); ?> item(s)
                </span>
            </h1>

            <?php if (isset($_GET['updated'])): ?>
                <div class="alert alert-success">Cart updated successfully.</div>
            <?php endif; ?>

            <?php if (empty($cartItems)): ?>
                <div class="empty-state">
                    <div class="empty-icon">🛒</div>
                    <h3>Your cart is empty</h3>
                    <p>Looks like you haven't added anything yet</p>
                    <a href="index.php" class="btn btn-primary">Start Shopping</a>
                </div>
            <?php else: ?>

                <div class="cart-layout">

                    <!-- Cart Items -->
                    <div class="cart-items">
                        <?php foreach ($cartItems as $item): ?>
                            <div class="cart-row" id="row-<?php echo $item['cart_id']; ?>">

                                <!-- Product Image -->
                                <div class="cart-img">
                                    <?php if (!empty($item['image'])): ?>
                                        <img src="assets/images/<?php
                                            echo htmlspecialchars($item['image']); ?>"
                                            alt="<?php
                                            echo htmlspecialchars($item['name']); ?>">
                                    <?php else: ?>
                                        <div class="no-img">🛍️</div>
                                    <?php endif; ?>
                                </div>

                                <!-- Product Info -->
                                <div class="cart-details">
                                    <h3>
                                        <a href="product.php?id=<?php
                                            echo $item['product_id']; ?>">
                                            <?php echo htmlspecialchars($item['name']); ?>
                                        </a>
                                    </h3>
                                    <p class="unit-price">
                                        ₱<?php echo number_format($item['price'], 2); ?>
                                        each
                                    </p>
                                </div>

                                <!-- Quantity Control -->
                                <div class="cart-qty">
                                    <button class="qty-btn"
                                        onclick="updateQty(
                                            <?php echo $item['cart_id']; ?>,
                                            <?php echo $item['quantity'] - 1; ?>,
                                            <?php echo $item['stocks']; ?>)">
                                        −
                                    </button>
                                    <span class="qty-display"
                                        id="qty-<?php echo $item['cart_id']; ?>">
                                        <?php echo $item['quantity']; ?>
                                    </span>
                                    <button class="qty-btn"
                                        onclick="updateQty(
                                            <?php echo $item['cart_id']; ?>,
                                            <?php echo $item['quantity'] + 1; ?>,
                                            <?php echo $item['stocks']; ?>)">
                                        +
                                    </button>
                                </div>

                                <!-- Item Total -->
                                <div class="cart-item-total"
                                    id="total-<?php echo $item['cart_id']; ?>">
                                    ₱<?php echo number_format(
                                        $item['price'] * $item['quantity'], 2); ?>
                                </div>

                                <!-- Remove -->
                                <button class="btn-remove"
                                    data-cart-id="<?php echo $item['cart_id']; ?>"
                                    data-price="<?php echo $item['price']; ?>"
                                    data-qty="<?php echo $item['quantity']; ?>"
                                    title="Remove item"
                                    onclick="removeItem(
                                        <?php echo $item['cart_id']; ?>,
                                        <?php echo $item['price']; ?>,
                                        <?php echo $item['quantity']; ?>)">
                                    ✕
                                </button>

                            </div>
                        <?php endforeach; ?>

                        <div class="cart-actions">
                            <a href="index.php" class="btn btn-outline">
                                ← Continue Shopping
                            </a>
                            <button onclick="clearCart()" class="btn-clear">
                                🗑 Clear Cart
                            </button>
                        </div>
                    </div>

                    <!-- Order Summary -->
                    <div class="cart-summary">
                        <h2>Order Summary</h2>

                        <div class="summary-row">
                            <span>Subtotal</span>
                            <span id="summary-subtotal">
                                ₱<?php echo number_format($subtotal, 2); ?>
                            </span>
                        </div>
                        <div class="summary-row">
                            <span>Shipping</span>
                            <span id="summary-shipping">
                                <?php if ($shipping === 0 && $subtotal > 0): ?>
                                    <span class="free-ship">FREE</span>
                                <?php elseif ($subtotal === 0): ?>
                                    ₱0.00
                                <?php else: ?>
                                    ₱<?php echo number_format($shipping, 2); ?>
                                <?php endif; ?>
                            </span>
                        </div>

                        <?php if ($subtotal > 0 && $subtotal < 1000): ?>
                            <div class="free-ship-notice">
                                Add ₱<?php echo number_format(1000 - $subtotal, 2); ?>
                                more for FREE shipping!
                            </div>
                        <?php endif; ?>

                        <div class="summary-divider"></div>

                        <div class="summary-row summary-total">
                            <span>Total</span>
                            <span id="summary-total">
                                ₱<?php echo number_format($total, 2); ?>
                            </span>
                        </div>

                        <a href="checkout.php" class="btn btn-primary btn-full checkout-btn">
                            Proceed to Checkout →
                        </a>

                        <div class="secure-note">
                            🔒 Secure checkout
                        </div>
                    </div>

                </div>
            <?php endif; ?>
        </div>

        <?php include 'includes/footer.php'; ?>

        <script>
            const BASE_URL = '<?php echo BASE_URL; ?>';

            const prices = {
                <?php foreach ($cartItems as $item): ?>
                    <?php echo $item['cart_id']; ?>: <?php echo $item['price']; ?>,
                <?php endforeach; ?>
            };

            async function updateQty(cartId, newQty, maxStock) {
                if (newQty < 1) {
                    if (confirm('Remove this item from your cart?')) {
                        const qty = parseInt(
                            document.getElementById('qty-' + cartId).textContent);
                        removeItem(cartId, prices[cartId], qty);
                    }
                    return;
                }

                if (newQty > maxStock) {
                    alert('Only ' + maxStock + ' items in stock.');
                    return;
                }

                const res  = await fetch(BASE_URL + 'actions/update_cart.php', {
                    method: 'POST',
                    headers: {'Content-Type': 'application/x-www-form-urlencoded'},
                    body: `cart_id=${cartId}&quantity=${newQty}`
                });
                const data = await res.json();

                if (data.success) {
                    document.getElementById('qty-' + cartId).textContent = newQty;

                    const itemTotal = prices[cartId] * newQty;
                    document.getElementById('total-' + cartId).textContent =
                        '₱' + itemTotal.toFixed(2)
                            .replace(/\B(?=(\d{3})+(?!\d))/g, ',');

                    const row     = document.getElementById('row-' + cartId);
                    const buttons = row.querySelectorAll('.qty-btn');
                    buttons[0].setAttribute('onclick',
                        `updateQty(${cartId}, ${newQty - 1}, ${maxStock})`);
                    buttons[1].setAttribute('onclick',
                        `updateQty(${cartId}, ${newQty + 1}, ${maxStock})`);

                    refreshSummary(data.subtotal, data.shipping, data.total);

                    const badge = document.getElementById('cart-count');
                    if (badge) badge.textContent = data.cart_count;
                }
            }

            async function removeItem(cartId, price, qty) {
                const res  = await fetch(BASE_URL + 'actions/remove_cart.php', {
                    method: 'POST',
                    headers: {'Content-Type': 'application/x-www-form-urlencoded'},
                    body: `cart_id=${cartId}`
                });
                const data = await res.json();

                if (data.success) {
                    const row            = document.getElementById('row-' + cartId);
                    row.style.opacity    = '0';
                    row.style.transition = 'opacity 0.3s';

                    setTimeout(() => {
                        row.remove();
                        refreshSummary(data.subtotal, data.shipping, data.total);

                        const badge = document.getElementById('cart-count');
                        if (badge) badge.textContent = data.cart_count;

                        const rows = document.querySelectorAll('.cart-row');
                        if (rows.length === 0) location.reload();
                    }, 300);
                }
            }

            async function clearCart() {
                if (!confirm('Remove all items from your cart?')) return;

                const res  = await fetch(BASE_URL + 'actions/clear_cart.php', {
                    method: 'POST'
                });
                const data = await res.json();
                if (data.success) location.reload();
            }

            function refreshSummary(subtotal, shipping, total) {
                document.getElementById('summary-subtotal').textContent =
                    '₱' + parseFloat(subtotal).toFixed(2)
                        .replace(/\B(?=(\d{3})+(?!\d))/g, ',');

                const shipEl     = document.getElementById('summary-shipping');
                shipEl.innerHTML = shipping == 0
                    ? '<span class="free-ship">FREE</span>'
                    : '₱' + parseFloat(shipping).toFixed(2)
                        .replace(/\B(?=(\d{3})+(?!\d))/g, ',');

                document.getElementById('summary-total').textContent =
                    '₱' + parseFloat(total).toFixed(2)
                        .replace(/\B(?=(\d{3})+(?!\d))/g, ',');
            }
        </script>



    </body>
</html>