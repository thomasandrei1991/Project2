<?php
    session_start();
    require_once __DIR__ . '/../config/database.php';
    if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
        header("Location: " . BASE_URL . "login.php");
        exit;
    }
    $orders = mysqli_fetch_all(mysqli_query($conn, "SELECT o.*, u.fullname, u.email, COUNT(oi.id) AS item_count, SUM(oi.quantity) AS total_qty FROM orders o JOIN users u ON o.user_id = u.id LEFT JOIN order_items oi ON o.id = oi.order_id GROUP BY o.id ORDER BY o.created_at DESC"), MYSQLI_ASSOC);
?>
<!DOCTYPE html>
<html lang="en">
    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <title>Manage Orders — ShopGreen</title>
        <link rel="stylesheet" href="<?php echo BASE_URL; ?>assets/css/style.css">
        <link rel="stylesheet" href="<?php echo BASE_URL; ?>assets/css/admin.css">
    </head>
    <body>
        <?php include __DIR__ . '/../includes/admin_navbar.php'; ?>
        <div class="admin-layout">
            <?php include __DIR__ . '/../includes/admin_sidebar.php'; ?>
            <main class="admin-main">
                <div class="admin-header">
                    <h1>Manage Orders</h1>
                </div>

                <?php if (isset($_GET['updated'])): ?>
                    <div class="alert alert-success">Order status updated.</div>
                <?php endif; ?>
                <div class="admin-card">
                    <table class="admin-table">
                        <thead>
                            <tr>
                                <th>Order</th>
                                <th>Customer</th>
                                <th>Items</th>
                                <th>Total</th>
                                <th>Date</th>
                                <th>Status</th>
                                <th>Action</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (empty($orders)): ?>
                                <tr>
                                    <td colspan="7" class="empty-msg">
                                        No orders yet.
                                    </td>
                                </tr>
                            <?php else: ?>
                                <?php foreach ($orders as $o): ?>
                                    <tr>
                                        <td>
                                            <strong>#<?php echo str_pad($o['id'],5,'0',STR_PAD_LEFT); ?></strong>
                                        </td>
                                        <td>
                                            <?php echo htmlspecialchars($o['fullname']); ?><br>
                                            <small><?php echo htmlspecialchars($o['email']); ?></small>
                                        </td>
                                        <td><?php echo $o['total_qty']; ?>item(s)</td>
                                        <td>₱<?php echo number_format($o['total'], 2); ?></td>
                                        <td><?php echo date('M d, Y', strtotime($o['created_at'])); ?></td>
                                        <td><span class="badge badge-<?php echo $o['status'];?>"><?php echo ucfirst($o['status']); ?></span></td>
                                        <td>
                                            <form method="POST" action="<?php echo BASE_URL; ?>admin/actions/update_order_status.php">
                                                <input type="hidden" name="order_id" value="<?php echo $o['id']; ?>">
                                                <select name="status" onchange="this.form.submit()" class="status-select">
                                                    <?php foreach (['pending','processing','completed','cancelled'] as $s): ?>
                                                        <option value="<?php echo $s; ?>"
                                                            <?php echo $o['status'] === $s ? 'selected' : ''; ?>>
                                                            <?php echo ucfirst($s); ?>
                                                        </option>
                                                    <?php endforeach; ?>
                                                </select>
                                            </form>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </main>
        </div>
        <?php include __DIR__ . '/../includes/footer.php'; ?>
    </body>
</html>