<?php 
    if (session_status() === PHP_SESSION_NONE) session_start();
    if (!defined('BASE_URL')) {
        require_once __DIR__ . '/../config/database.php';
    } 
?>
<nav class="navbar">
    <div class="nav-brand"><a href="<?php echo BASE_URL; ?>admin/dashboard.php">🛒 ShopGreen <span class="admin-badge">Admin</span></a></div>
    <div class="nav-links">
        <a href="<?php echo BASE_URL; ?>index.php">View Store</a>
        <a href="<?php echo BASE_URL; ?>actions/logout.php">Logout</a>
    </div>
</nav>
