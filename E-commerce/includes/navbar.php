<?php 
if (session_status() === PHP_SESSION_NONE) session_start();
// Make sure BASE_URL is available
if (!defined('BASE_URL')) {
    require_once __DIR__ . '/../config/database.php';
}
?>

<nav class="navbar">
    <div class="nav-brand">
        <a href="<?php echo BASE_URL; ?>index.php">🛒 ShopGreen</a>
    </div>
    <div class="nav-links">
        <a href="<?php echo BASE_URL; ?>index.php">Home</a>
        <?php if (isset($_SESSION['user_id'])): ?>
            <a href="<?php echo BASE_URL; ?>cart.php">
                Cart <span class="cart-count" id="cart-count">0</span>
            </a>
            <a href="<?php echo BASE_URL; ?>orders.php">My Orders</a>
            <?php if (isset($_SESSION['role']) && $_SESSION['role'] === 'admin'): ?>
                <a href="<?php echo BASE_URL; ?>admin/dashboard.php">Admin</a>
            <?php endif; ?>
            <a href="<?php echo BASE_URL; ?>actions/logout.php">Logout</a>
        <?php else: ?>
            <a href="<?php echo BASE_URL; ?>login.php">Login</a>
            <a href="<?php echo BASE_URL; ?>register.php" class="btn-nav">Register</a>
        <?php endif; ?>
    </div>
</nav>