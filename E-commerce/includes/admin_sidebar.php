<?php
    $current = basename($_SERVER['PHP_SELF']);
?>
<aside class="admin-sidebar">
    <ul>
        <li><a href="<?php echo BASE_URL; ?>admin/dashboard.php" class="<?php echo $current === 'dashboard.php' ? 'active' : ''; ?>">📊 Dashboard</a></li>
        <li><a href="<?php echo BASE_URL; ?>admin/products.php" class="<?php echo $current === 'products.php' ? 'active' : ''; ?>">📦 Products</a></li>
        <li><a href="<?php echo BASE_URL; ?>admin/orders.php" class="<?php echo $current === 'orders.php' ? 'active' : ''; ?>">🛒 Orders</a></li>
    </ul>
</aside>