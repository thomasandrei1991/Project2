<?php
session_start();
require_once __DIR__ . '/../config/database.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header("Location: " . BASE_URL . "login.php");
    exit;
}

// Stats
$stats = [];

$r = mysqli_query($conn, "SELECT COUNT(*) AS c FROM products");
$stats['products'] = mysqli_fetch_assoc($r)['c'];

$r = mysqli_query($conn, "SELECT COUNT(*) AS c FROM users WHERE role='user'");
$stats['users'] = mysqli_fetch_assoc($r)['c'];

$r = mysqli_query($conn, "SELECT COUNT(*) AS c FROM orders");
$stats['orders'] = mysqli_fetch_assoc($r)['c'];

$r = mysqli_query($conn, 
    "SELECT COALESCE(SUM(total),0) AS c FROM orders 
     WHERE status != 'cancelled'");
$stats['revenue'] = mysqli_fetch_assoc($r)['c'];

// Recent orders
$recentOrders = mysqli_fetch_all(mysqli_query($conn,
    "SELECT o.*, u.fullname 
     FROM orders o 
     JOIN users u ON o.user_id = u.id 
     ORDER BY o.created_at DESC LIMIT 5"),
    MYSQLI_ASSOC);

// Low stock products
$lowStock = mysqli_fetch_all(mysqli_query($conn,
    "SELECT * FROM products 
     WHERE stocks <= 5 
     ORDER BY stocks ASC LIMIT 5"),
    MYSQLI_ASSOC);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Dashboard — ShopGreen</title>
    <link rel="stylesheet" 
          href="<?php echo BASE_URL; ?>assets/css/style.css">
    <link rel="stylesheet" 
          href="<?php echo BASE_URL; ?>assets/css/admin.css">
</head>
<body>
    <?php include __DIR__ . '/../includes/admin_navbar.php'; ?>

    <div class="admin-layout">
        <?php include __DIR__ . '/../includes/admin_sidebar.php'; ?>

        <main class="admin-main">
            <div class="admin-header">
                <h1>Dashboard</h1>
                <span class="admin-date">
                    <?php echo date('F d, Y'); ?>
                </span>
            </div>

            <!-- Stat Cards -->
            <div class="stat-cards">
                <div class="stat-card green">
                    <div class="stat-icon">📦</div>
                    <div class="stat-info">
                        <h3><?php echo $stats['products']; ?></h3>
                        <p>Total Products</p>
                    </div>
                </div>
                <div class="stat-card blue">
                    <div class="stat-icon">👥</div>
                    <div class="stat-info">
                        <h3><?php echo $stats['users']; ?></h3>
                        <p>Customers</p>
                    </div>
                </div>
                <div class="stat-card orange">
                    <div class="stat-icon">🛒</div>
                    <div class="stat-info">
                        <h3><?php echo $stats['orders']; ?></h3>
                        <p>Total Orders</p>
                    </div>
                </div>
                <div class="stat-card teal">
                    <div class="stat-icon">💰</div>
                    <div class="stat-info">
                        <h3>₱<?php echo number_format(
                            $stats['revenue'], 2); ?></h3>
                        <p>Total Revenue</p>
                    </div>
                </div>
            </div>

            <div class="dashboard-grid">

                <!-- Recent Orders -->
                <div class="admin-card">
                    <div class="card-header">
                        <h2>Recent Orders</h2>
                        <a href="<?php echo BASE_URL; ?>admin/orders.php"
                           class="view-all">View all →</a>
                    </div>
                    <?php if (empty($recentOrders)): ?>
                        <p class="empty-msg">No orders yet.</p>
                    <?php else: ?>
                        <table class="admin-table">
                            <thead>
                                <tr>
                                    <th>Order</th>
                                    <th>Customer</th>
                                    <th>Total</th>
                                    <th>Status</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($recentOrders as $o): ?>
                                    <tr>
                                        <td>#<?php echo str_pad(
                                            $o['id'],5,'0',STR_PAD_LEFT); ?>
                                        </td>
                                        <td><?php echo htmlspecialchars(
                                            $o['fullname']); ?></td>
                                        <td>₱<?php echo number_format(
                                            $o['total'], 2); ?></td>
                                        <td>
                                            <span class="badge 
                                                badge-<?php echo $o['status'];?>">
                                                <?php echo ucfirst(
                                                    $o['status']); ?>
                                            </span>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    <?php endif; ?>
                </div>

                <!-- Low Stock -->
                <div class="admin-card">
                    <div class="card-header">
                        <h2>Low Stock Alert</h2>
                        <a href="<?php echo BASE_URL; ?>admin/products.php"
                           class="view-all">Manage →</a>
                    </div>
                    <?php if (empty($lowStock)): ?>
                        <p class="empty-msg">All products well stocked.</p>
                    <?php else: ?>
                        <table class="admin-table">
                            <thead>
                                <tr>
                                    <th>Product</th>
                                    <th>Stock</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($lowStock as $p): ?>
                                    <tr>
                                        <td><?php echo htmlspecialchars(
                                            $p['name']); ?></td>
                                        <td>
                                            <span class="badge badge-<?php 
                                                echo $p['stocks'] == 0 
                                                    ? 'cancelled' 
                                                    : 'pending'; ?>">
                                                <?php echo $p['stocks']; ?> left
                                            </span>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    <?php endif; ?>
                </div>

            </div>
        </main>
    </div>

    <?php include __DIR__ . '/../includes/footer.php'; ?>
</body>
</html>