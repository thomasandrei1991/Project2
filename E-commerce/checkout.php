<?php
ob_start();
session_start();
require_once __DIR__ . "/config/database.php";

if (!isset($_SESSION['user_id'])) {
    header("Location: " . BASE_URL . "login.php");
    exit;
}

$user_id = (int)$_SESSION['user_id'];

// Fetch cart items
$stmt = mysqli_prepare($conn,
    "SELECT c.id AS cart_id, c.quantity,
            p.id AS product_id, p.name, 
            p.price, p.image, p.stocks
     FROM cart c
     JOIN products p ON c.product_id = p.id
     WHERE c.user_id = ?
     ORDER BY c.id DESC");
mysqli_stmt_bind_param($stmt, 'i', $user_id);
mysqli_stmt_execute($stmt);
$cartItems = mysqli_fetch_all(
    mysqli_stmt_get_result($stmt), MYSQLI_ASSOC);
mysqli_stmt_close($stmt);

if (empty($cartItems)) {
    header("Location: " . BASE_URL . "cart.php");
    exit;
}

// Calculate totals
$subtotal = 0;
foreach ($cartItems as $item) {
    $subtotal += $item['price'] * $item['quantity'];
}
$shipping = $subtotal >= 1000 ? 0 : 80;
$total    = $subtotal + $shipping;

// Fetch user details
$ustmt = mysqli_prepare($conn,
    "SELECT fullname, email FROM users WHERE id = ?");
mysqli_stmt_bind_param($ustmt, 'i', $user_id);
mysqli_stmt_execute($ustmt);
$user = mysqli_fetch_assoc(mysqli_stmt_get_result($ustmt));
mysqli_stmt_close($ustmt);

$error = "";

// ── Handle POST before ANY HTML output ──
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $fullname = trim($_POST['fullname']);
    $email    = trim($_POST['email']);
    $phone    = trim($_POST['phone']);
    $address  = trim($_POST['address']);
    $city     = trim($_POST['city']);
    $zip      = trim($_POST['zip'] ?? '');
    $payment  = $_POST['payment'] ?? 'cod';

    if (empty($fullname) || empty($address) ||
        empty($city)     || empty($phone)) {
        $error = "Please fill in all required fields.";
    } else {
        $full_address = "$address, $city" . ($zip ? " $zip" : "");

        mysqli_begin_transaction($conn);

        try {
            // Check stock
            foreach ($cartItems as $item) {
                $scheck = mysqli_prepare($conn,
                    "SELECT stocks FROM products WHERE id = ?");
                mysqli_stmt_bind_param($scheck, 'i',
                    $item['product_id']);
                mysqli_stmt_execute($scheck);
                $srow = mysqli_fetch_assoc(
                    mysqli_stmt_get_result($scheck));
                mysqli_stmt_close($scheck);

                if ($srow['stocks'] < $item['quantity']) {
                    throw new Exception(
                        "Sorry, \"{$item['name']}\" only has " .
                        "{$srow['stocks']} left in stock.");
                }
            }

            // Insert order
            $ostmt = mysqli_prepare($conn,
                "INSERT INTO orders 
                    (user_id, total, status, address)
                 VALUES (?, ?, 'pending', ?)");
            mysqli_stmt_bind_param($ostmt, 'ids',
                $user_id, $total, $full_address);
            mysqli_stmt_execute($ostmt);
            $order_id = mysqli_insert_id($conn);
            mysqli_stmt_close($ostmt);

            // Insert order items + deduct stock
            foreach ($cartItems as $item) {
                $istmt = mysqli_prepare($conn,
                    "INSERT INTO order_items
                        (order_id, product_id, quantity, price)
                     VALUES (?, ?, ?, ?)");
                mysqli_stmt_bind_param($istmt, 'iiid',
                    $order_id,
                    $item['product_id'],
                    $item['quantity'],
                    $item['price']);
                mysqli_stmt_execute($istmt);
                mysqli_stmt_close($istmt);

                $dstmt = mysqli_prepare($conn,
                    "UPDATE products
                     SET stocks = stocks - ?
                     WHERE id = ?");
                mysqli_stmt_bind_param($dstmt, 'ii',
                    $item['quantity'],
                    $item['product_id']);
                mysqli_stmt_execute($dstmt);
                mysqli_stmt_close($dstmt);
            }

            // Clear cart
            $clstmt = mysqli_prepare($conn,
                "DELETE FROM cart WHERE user_id = ?");
            mysqli_stmt_bind_param($clstmt, 'i', $user_id);
            mysqli_stmt_execute($clstmt);
            mysqli_stmt_close($clstmt);

            mysqli_commit($conn);

            // Clean buffer then redirect
            ob_end_clean();
            header("Location: " . BASE_URL . "order_success.php?id=" . $order_id);
            exit;

        } catch (Exception $e) {
            mysqli_rollback($conn);
            $error = $e->getMessage();
        }
    }
}
// ── Only reach here if GET request or validation failed ──
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Checkout — ShopGreen</title>
    <link rel="stylesheet"
          href="<?php echo BASE_URL; ?>assets/css/style.css">
    <link rel="stylesheet"
          href="<?php echo BASE_URL; ?>assets/css/checkout.css">
</head>
<body>
    <?php include 'includes/navbar.php'; ?>

    <div class="container">
        <h1 class="page-title">🧾 Checkout</h1>

        <?php if ($error): ?>
            <div class="alert alert-danger">
                <?php echo $error; ?>
            </div>
        <?php endif; ?>

        <form method="POST" action="">
        <div class="checkout-layout">

            <div class="checkout-left">
                <div class="checkout-card">
                    <h2>📍 Delivery Information</h2>

                    <div class="form-row">
                        <div class="form-group">
                            <label>Full Name
                                <span class="req">*</span>
                            </label>
                            <input type="text" name="fullname"
                                value="<?php echo htmlspecialchars(
                                    $_POST['fullname']
                                    ?? $user['fullname']); ?>"
                                required>
                        </div>
                        <div class="form-group">
                            <label>Email
                                <span class="req">*</span>
                            </label>
                            <input type="email" name="email"
                                value="<?php echo htmlspecialchars(
                                    $_POST['email']
                                    ?? $user['email']); ?>"
                                required>
                        </div>
                    </div>

                    <div class="form-group">
                        <label>Phone Number
                            <span class="req">*</span>
                        </label>
                        <input type="text" name="phone"
                            value="<?php echo htmlspecialchars(
                                $_POST['phone'] ?? ''); ?>"
                            placeholder="09XX XXX XXXX" required>
                    </div>

                    <div class="form-group">
                        <label>Street Address
                            <span class="req">*</span>
                        </label>
                        <input type="text" name="address"
                            value="<?php echo htmlspecialchars(
                                $_POST['address'] ?? ''); ?>"
                            placeholder="House No., Street, Barangay"
                            required>
                    </div>

                    <div class="form-row">
                        <div class="form-group">
                            <label>City / Municipality
                                <span class="req">*</span>
                            </label>
                            <input type="text" name="city"
                                value="<?php echo htmlspecialchars(
                                    $_POST['city'] ?? ''); ?>"
                                placeholder="Quezon City" required>
                        </div>
                        <div class="form-group">
                            <label>ZIP Code</label>
                            <input type="text" name="zip"
                                value="<?php echo htmlspecialchars(
                                    $_POST['zip'] ?? ''); ?>"
                                placeholder="1100">
                        </div>
                    </div>
                </div>

                <div class="checkout-card">
                    <h2>💳 Payment Method</h2>
                    <div class="payment-options">

                        <label class="payment-option">
                            <input type="radio" name="payment"
                                   value="cod" checked>
                            <div class="payment-box">
                                <span class="pay-icon">🚚</span>
                                <div>
                                    <strong>Cash on Delivery</strong>
                                    <p>Pay when your order arrives</p>
                                </div>
                            </div>
                        </label>

                        <label class="payment-option">
                            <input type="radio" name="payment"
                                   value="gcash">
                            <div class="payment-box">
                                <span class="pay-icon">📱</span>
                                <div>
                                    <strong>GCash</strong>
                                    <p>Pay via GCash mobile wallet</p>
                                </div>
                            </div>
                        </label>

                        <label class="payment-option">
                            <input type="radio" name="payment"
                                   value="bank">
                            <div class="payment-box">
                                <span class="pay-icon">🏦</span>
                                <div>
                                    <strong>Bank Transfer</strong>
                                    <p>Transfer to our bank account</p>
                                </div>
                            </div>
                        </label>

                    </div>
                </div>
            </div>

            <div class="checkout-right">
                <div class="checkout-card summary-card">
                    <h2>🛒 Order Summary</h2>

                    <div class="checkout-items">
                        <?php foreach ($cartItems as $item): ?>
                            <div class="checkout-item">
                                <div class="ci-img">
                                    <?php if (!empty($item['image'])): ?>
                                        <img src="<?php echo BASE_URL;
                                            ?>assets/images/<?php
                                            echo htmlspecialchars(
                                                $item['image']); ?>"
                                            alt="">
                                    <?php else: ?>
                                        <div class="no-img">🛍️</div>
                                    <?php endif; ?>
                                    <span class="ci-qty">
                                        <?php echo $item['quantity']; ?>
                                    </span>
                                </div>
                                <div class="ci-name">
                                    <?php echo htmlspecialchars(
                                        $item['name']); ?>
                                </div>
                                <div class="ci-price">
                                    ₱<?php echo number_format(
                                        $item['price'] * $item['quantity'],
                                        2); ?>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>

                    <div class="summary-divider"></div>

                    <div class="summary-row">
                        <span>Subtotal</span>
                        <span>₱<?php echo number_format(
                            $subtotal, 2); ?></span>
                    </div>
                    <div class="summary-row">
                        <span>Shipping</span>
                        <span>
                            <?php if ($shipping === 0): ?>
                                <span class="free-ship">FREE</span>
                            <?php else: ?>
                                ₱<?php echo number_format(
                                    $shipping, 2); ?>
                            <?php endif; ?>
                        </span>
                    </div>

                    <div class="summary-divider"></div>

                    <div class="summary-row summary-total">
                        <span>Total</span>
                        <span>₱<?php echo number_format(
                            $total, 2); ?></span>
                    </div>

                    <button type="submit"
                            class="btn btn-primary btn-full
                                   place-order-btn">
                        Place Order →
                    </button>

                    <a href="<?php echo BASE_URL; ?>cart.php"
                       class="btn btn-outline btn-full"
                       style="margin-top:10px; text-align:center;">
                        ← Back to Cart
                    </a>

                    <div class="secure-note">
                        🔒 Secured & encrypted checkout
                    </div>
                </div>
            </div>

        </div>
        </form>
    </div>

    <?php include 'includes/footer.php'; ?>
</body>
</html>