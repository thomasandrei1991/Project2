<?php
    session_start();
    require_once __DIR__ . '/../config/database.php';
    if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
        header("Location: " . BASE_URL . "login.php");
        exit;
    }
    $success = $_GET['success'] ?? '';
    $error = $_GET['error']   ?? '';
    // Fetch all products with category
    $products = mysqli_fetch_all(mysqli_query($conn, "SELECT p.*, c.name AS category_name FROM products p LEFT JOIN categories c ON p.category_id = c.id ORDER BY p.created_at DESC"), MYSQLI_ASSOC);
    // Fetch categories for the add form
    $categories = mysqli_fetch_all(mysqli_query($conn, "SELECT * FROM categories ORDER BY name"), MYSQLI_ASSOC);
?>
<!DOCTYPE html>
<html lang="en">
    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <title>Manage Products — ShopGreen</title>
        <link rel="stylesheet" href="<?php echo BASE_URL; ?>assets/css/style.css">
        <link rel="stylesheet" href="<?php echo BASE_URL; ?>assets/css/admin.css">
    </head>
    <body>
        <?php include __DIR__ . '/../includes/admin_navbar.php'; ?>
        <div class="admin-layout">
            <?php include __DIR__ . '/../includes/admin_sidebar.php'; ?>
            <main class="admin-main">
                <div class="admin-header">
                    <h1>Manage Products</h1>
                    <button class="btn btn-primary" onclick="toggleForm()">+ Add Product</button>
                </div>
                <?php if ($success === 'added'): ?>
                    <div class="alert alert-success">Product added successfully!</div>
                <?php elseif ($success === 'updated'): ?>
                    <div class="alert alert-success">Product updated successfully!</div>
                <?php elseif ($success === 'deleted'): ?>
                    <div class="alert alert-success">Product deleted.</div>
                <?php endif; ?>
                <!-- Add Product Form -->
                <div class="admin-card" id="add-form" 
                    style="display:none; margin-bottom:24px;">
                    <div class="card-header">
                        <h2>Add New Product</h2>
                        <button onclick="toggleForm()" class="btn-icon">✕</button>
                    </div>
                    <form method="POST"action="<?php echo BASE_URL; ?> admin/actions/save_product.php" enctype="multipart/form-data">
                        <div class="form-row">
                            <div class="form-group">
                                <label>Product Name *</label>
                                <input type="text" name="name" placeholder="e.g. Wireless Earbuds" required>
                            </div>
                            <div class="form-group">
                                <label>Category *</label>
                                <select name="category_id" required>
                                    <option value="">Select category</option>
                                    <?php foreach ($categories as $cat): ?>
                                        <option value="<?php echo $cat['id']; ?>">
                                            <?php echo htmlspecialchars($cat['name']); ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                        </div>
                        <div class="form-group">
                            <label>Description</label>
                            <textarea name="description" rows="3" placeholder="Product description..."></textarea>
                        </div>
                        <div class="form-row">
                            <div class="form-group">
                                <label>Price (₱) *</label>
                                <input type="number" name="price" step="0.01" min="0" placeholder="0.00" required>
                            </div>
                            <div class="form-group">
                                <label>Stock Quantity *</label>
                                <input type="number" name="stocks" min="0" placeholder="0" required>
                            </div>
                            <div class="form-group">
                                <label>Product Image</label>
                                <input type="file" name="image" accept="image/*">
                            </div>
                        </div>
                        <button type="submit" class="btn btn-primary">Save Product</button>
                    </form>
                </div>
                <!-- Products Table -->
                <div class="admin-card">
                    <table class="admin-table">
                        <thead>
                            <tr>
                                <th>Image</th>
                                <th>Name</th>
                                <th>Category</th>
                                <th>Price</th>
                                <th>Stock</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (empty($products)): ?>
                                <tr>
                                    <td colspan="6" class="empty-msg">No products yet. Add one above!</td>
                                </tr>
                            <?php else: ?>
                                <?php foreach ($products as $p): ?>
                                    <tr>
                                        <td>
                                            <?php if (!empty($p['image'])): ?>
                                                <img src="<?php echo BASE_URL; ?>assets/images/<?php echo htmlspecialchars($p['image']); ?>" class="table-thumb" alt="">
                                            <?php else: ?>
                                                <div class="thumb-placeholder">🛍️</div>
                                            <?php endif; ?>
                                        </td>
                                        <td>
                                            <strong><?php echo htmlspecialchars($p['name']); ?></strong>
                                        </td>
                                        <td><?php echo htmlspecialchars($p['category_name'] ?? 'Uncategorized'); ?></td>
                                        <td>₱<?php echo number_format($p['price'], 2); ?></td>
                                        <td><span class="badge <?php echo $p['stocks'] == 0 ? 'badge-cancelled' : ($p['stocks'] <= 5 ? 'badge-pending' : 'badge-completed'); ?>"><?php echo $p['stocks']; ?></span></td>
                                        <td class="action-btns">
                                            <a href="<?php echo BASE_URL; ?>admin/edit_product.php?id=<?php echo $p['id']; ?>" class="btn-action edit">Edit</a>
                                            <a href="<?php echo BASE_URL; ?>admin/actions/delete_product.php?id=<?php echo $p['id']; ?>" class="btn-action delete" onclick="return confirm('Delete this product?')">Delete</a>
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
        <script>
            function toggleForm() {
                const f = document.getElementById('add-form');
                f.style.display = f.style.display === 'none' ? 'block' : 'none';
            }
        </script>
    </body>
</html>