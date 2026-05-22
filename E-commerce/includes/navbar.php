<?php
if (session_status() === PHP_SESSION_NONE) session_start();
if (!defined('BASE_URL')) {
    require_once __DIR__ . '/../config/database.php';
}

// Cart count
$cartCount = 0;
if (isset($_SESSION['user_id'])) {
    global $conn;
    if ($conn) {
        $uid   = (int)$_SESSION['user_id'];
        $cstmt = mysqli_prepare($conn,
            "SELECT SUM(quantity) AS t FROM cart WHERE user_id=?");
        mysqli_stmt_bind_param($cstmt, 'i', $uid);
        mysqli_stmt_execute($cstmt);
        $crow      = mysqli_fetch_assoc(mysqli_stmt_get_result($cstmt));
        $cartCount = (int)($crow['t'] ?? 0);
        mysqli_stmt_close($cstmt);
    }
}
?>
<nav class="navbar">
    <div class="nav-brand">
        <a href="<?php echo BASE_URL; ?>index.php">🌿 ShopGreen</a>
    </div>

    <button class="nav-toggle" 
            onclick="document.querySelector('.nav-links')
                .classList.toggle('open')"
            aria-label="Toggle menu">☰</button>

    <div class="nav-links">
        <a href="<?php echo BASE_URL; ?>index.php">Home</a>
        <?php if (isset($_SESSION['user_id'])): ?>
            <a href="<?php echo BASE_URL; ?>cart.php">
                🛒 Cart
                <span class="cart-count" id="cart-count">
                    <?php echo $cartCount; ?>
                </span>
            </a>
            <a href="<?php echo BASE_URL; ?>orders.php">My Orders</a>
            <?php if (isset($_SESSION['role']) && 
                $_SESSION['role'] === 'admin'): ?>
                <a href="<?php echo BASE_URL; ?>admin/dashboard.php">
                    Admin Panel
                </a>
            <?php endif; ?>
            <a href="<?php echo BASE_URL; ?>actions/logout.php">
                Logout
            </a>
        <?php else: ?>
            <a href="<?php echo BASE_URL; ?>login.php">Login</a>
            <a href="<?php echo BASE_URL; ?>register.php" 
               class="btn-nav">Register</a>
        <?php endif; ?>
    </div>
</nav>