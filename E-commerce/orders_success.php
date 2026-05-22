<?php
session_start();
require_once __DIR__ . "/config/database.php";

if (!isset($_SESSION['user_id'])) {
    header("Location: " . BASE_URL . "login.php");
    exit;
}

$order_id = (int)($_GET['id'] ?? 0);
$user_id  = (int)$_SESSION['user_id'];

if ($order_id <= 0) {
    header("Location: " . BASE_URL . "index.php");
    exit;
}

// Fetch order
$stmt = mysqli_prepare($conn,
    "SELECT * FROM orders WHERE id = ? AND user_id = ?");
mysqli_stmt_bind_param($stmt, 'ii', $order_id, $user_id);
mysqli_stmt_execute($stmt);
$order = mysqli_fetch_assoc(mysqli_stmt_get_result($stmt));
mysqli_stmt_close($stmt);

if (!$order) {
    header("Location: " . BASE_URL . "index.php");
    exit;
}

// Fetch order items
$istmt = mysqli_prepare($conn,
    "SELECT oi.*, p.name, p.image
     FROM order_items oi
     JOIN products p ON oi.product_id = p.id
     WHERE oi.order_id = ?");
mysqli_stmt_bind_param($istmt, 'i', $order_id);
mysqli_stmt_execute($istmt);
$items = mysqli_fetch_all(mysqli_stmt_get_result($istmt), MYSQLI_ASSOC);
mysqli_stmt_close($istmt);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Order Placed! — ShopGreen</title>
    <link rel="stylesheet" 
          href="<?php echo BASE_URL; ?>assets/css/style.css">
    <link rel="stylesheet" 
          href="<?php echo BASE_URL; ?>assets/css/checkout.css">
</head>
<body>
    <?php include 'includes/navbar.php'; ?>

    <div class="container">
        <div class="success-wrapper">

            <!-- Success Banner -->
            <div class="success-banner">
                <div class="success-icon">✅</div>
                <h1>Order Placed Successfully!</h1>
                <p>Thank you, <strong>
                    <?php echo htmlspecialchars($_SESSION['fullname']); ?>
                    </strong>! Your order has been received.</p>
                <div class="order-number">
                    Order #<?php echo str_pad($order_id, 5, '0', STR_PAD_LEFT); ?>
                </div>
            </div>

            <!-- Order Details -->
            <div class="success-grid">

                <!-- Items -->
                <div class="checkout-card">
                    <h2>📦 Items Ordered</h2>
                    <div class="checkout-items">
                        <?php foreach ($items as $item): ?>
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
                    <div class="summary-row summary-total">
                        <span>Total Paid</span>
                        <span>₱<?php echo number_format(
                            $order['total'], 2); ?></span>
                    </div>
                </div>

                <!-- Delivery Info -->
                <div class="checkout-card">
                    <h2>📍 Delivery Details</h2>
                    <div class="delivery-detail-row">
                        <span class="dd-label">Address</span>
                        <span class="dd-value">
                            <?php echo htmlspecialchars(
                                $order['address'] ?? 'N/A'); ?>
                        </span>
                    </div>
                    <div class="delivery-detail-row">
                        <span class="dd-label">Status</span>
                        <span class="order-status 
                            status-<?php echo $order['status']; ?>">
                            <?php echo ucfirst($order['status']); ?>
                        </span>
                    </div>
                    <div class="delivery-detail-row">
                        <span class="dd-label">Date</span>
                        <span class="dd-value">
                            <?php echo date('F d, Y h:i A',
                                strtotime($order['created_at'])); ?>
                        </span>
                    </div>

                    <div class="success-actions">
                        <a href="<?php echo BASE_URL; ?>orders.php"
                           class="btn btn-primary btn-full">
                            View My Orders
                        </a>
                        <a href="<?php echo BASE_URL; ?>index.php"
                           class="btn btn-outline btn-full">
                            Continue Shopping
                        </a>
                    </div>
                </div>

            </div>
        </div>
    </div>

    <?php include 'includes/footer.php'; ?>
</body>
</html>