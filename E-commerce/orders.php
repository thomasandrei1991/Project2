<?php
session_start();
require_once __DIR__ . "/config/database.php";

if (!isset($_SESSION['user_id'])) {
    header("Location: " . BASE_URL . "login.php");
    exit;
}

$user_id = (int)$_SESSION['user_id'];

$stmt = mysqli_prepare($conn,
    "SELECT o.*,
            COUNT(oi.id)     AS item_count,
            SUM(oi.quantity) AS total_qty
     FROM orders o
     LEFT JOIN order_items oi ON o.id = oi.order_id
     WHERE o.user_id = ?
     GROUP BY o.id
     ORDER BY o.created_at DESC");
mysqli_stmt_bind_param($stmt, 'i', $user_id);
mysqli_stmt_execute($stmt);
$orders = mysqli_fetch_all(mysqli_stmt_get_result($stmt), MYSQLI_ASSOC);
mysqli_stmt_close($stmt);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Orders — ShopGreen</title>
    <link rel="stylesheet" 
          href="<?php echo BASE_URL; ?>assets/css/style.css">
    <link rel="stylesheet" 
          href="<?php echo BASE_URL; ?>assets/css/orders.css">
</head>
<body>
    <?php include 'includes/navbar.php'; ?>

    <div class="container">
        <h1 class="page-title">📦 My Orders
            <span class="item-count">
                <?php echo count($orders); ?> order(s)
            </span>
        </h1>

        <?php if (empty($orders)): ?>
            <div class="empty-state">
                <div class="empty-icon">📦</div>
                <h3>No orders yet</h3>
                <p>You haven't placed any orders yet</p>
                <a href="<?php echo BASE_URL; ?>index.php"
                   class="btn btn-primary">Start Shopping</a>
            </div>
        <?php else: ?>
            <div class="orders-list">
                <?php foreach ($orders as $order): ?>
                    <div class="order-card">
                        <div class="order-header">
                            <div class="order-meta">
                                <span class="order-id">
                                    Order #<?php echo str_pad(
                                        $order['id'], 5, '0', STR_PAD_LEFT); ?>
                                </span>
                                <span class="order-date">
                                    <?php echo date('M d, Y h:i A',
                                        strtotime($order['created_at'])); ?>
                                </span>
                            </div>
                            <span class="order-status 
                                status-<?php echo $order['status']; ?>">
                                <?php echo ucfirst($order['status']); ?>
                            </span>
                        </div>

                        <div class="order-body">
                            <div class="order-info-row">
                                <div class="order-stat">
                                    <span class="stat-label">Items</span>
                                    <span class="stat-value">
                                        <?php echo $order['total_qty']; ?> 
                                        item(s)
                                    </span>
                                </div>
                                <div class="order-stat">
                                    <span class="stat-label">
                                        Delivery Address
                                    </span>
                                    <span class="stat-value">
                                        <?php echo $order['address']
                                            ? htmlspecialchars($order['address'])
                                            : 'N/A'; ?>
                                    </span>
                                </div>
                                <div class="order-stat">
                                    <span class="stat-label">Total</span>
                                    <span class="stat-value order-total">
                                        ₱<?php echo number_format(
                                            $order['total'], 2); ?>
                                    </span>
                                </div>
                            </div>
                        </div>

                        <div class="order-footer">
                            <a href="<?php echo BASE_URL; 
                                ?>order_detail.php?id=<?php 
                                echo $order['id']; ?>"
                               class="btn btn-outline btn-sm">
                                View Details
                            </a>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>

    <?php include 'includes/footer.php'; ?>
</body>
</html>