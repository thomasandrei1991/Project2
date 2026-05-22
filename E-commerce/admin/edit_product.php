<?php
session_start();
require_once __DIR__ . '/../config/database.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header("Location: " . BASE_URL . "login.php");
    exit;
}

$id = (int)($_GET['id'] ?? 0);
if ($id <= 0) {
    header("Location: " . BASE_URL . "admin/products.php");
    exit;
}

$stmt = mysqli_prepare($conn, 
    "SELECT * FROM products WHERE id = ?");
mysqli_stmt_bind_param($stmt, 'i', $id);
mysqli_stmt_execute($stmt);
$product = mysqli_fetch_assoc(mysqli_stmt_get_result($stmt));
mysqli_stmt_close($stmt);

if (!$product) {
    header("Location: " . BASE_URL . "admin/products.php");
    exit;
}

$categories = mysqli_fetch_all(
    mysqli_query($conn, "SELECT * FROM categories ORDER BY name"),
    MYSQLI_ASSOC);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Edit Product — ShopGreen</title>
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
                <h1>Edit Product</h1>
                <a href="<?php echo BASE_URL; ?>admin/products.php"
                   class="btn btn-outline">← Back</a>
            </div>

            <?php if (isset($_GET['success'])): ?>
                <div class="alert alert-success">
                    Product updated successfully!</div>
            <?php endif; ?>

            <div class="admin-card">
                <form method="POST"
                      action="<?php echo BASE_URL; 
                          ?>admin/actions/save_product.php"
                      enctype="multipart/form-data">
                    <input type="hidden" name="id" 
                           value="<?php echo $product['id']; ?>">

                    <div class="form-row">
                        <div class="form-group">
                            <label>Product Name *</label>
                            <input type="text" name="name"
                                value="<?php echo htmlspecialchars(
                                    $product['name']); ?>" required>
                        </div>
                        <div class="form-group">
                            <label>Category *</label>
                            <select name="category_id" required>
                                <?php foreach ($categories as $cat): ?>
                                    <option value="<?php echo $cat['id']; ?>"
                                        <?php echo $cat['id'] == 
                                            $product['category_id'] 
                                            ? 'selected' : ''; ?>>
                                        <?php echo htmlspecialchars(
                                            $cat['name']); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    </div>

                    <div class="form-group">
                        <label>Description</label>
                        <textarea name="description" rows="3">
<?php echo htmlspecialchars($product['description'] ?? ''); ?></textarea>
                    </div>

                    <div class="form-row">
                        <div class="form-group">
                            <label>Price (₱) *</label>
                            <input type="number" name="price"
                                step="0.01" min="0"
                                value="<?php echo $product['price']; ?>"
                                required>
                        </div>
                        <div class="form-group">
                            <label>Stock *</label>
                            <input type="number" name="stocks"
                                min="0"
                                value="<?php echo $product['stocks']; ?>"
                                required>
                        </div>
                    </div>

                    <div class="form-group">
                        <label>Current Image</label><br>
                        <?php if (!empty($product['image'])): ?>
                            <img src="<?php echo BASE_URL; 
                                ?>assets/images/<?php echo htmlspecialchars(
                                $product['image']); ?>"
                                style="height:100px; border-radius:8px;
                                       margin-bottom:8px; display:block;">
                        <?php else: ?>
                            <p style="color:var(--gray-text); 
                                      font-size:0.9rem;">
                                No image uploaded</p>
                        <?php endif; ?>
                        <label>New Image (optional)</label>
                        <input type="file" name="image" 
                               accept="image/*">
                    </div>

                    <button type="submit" class="btn btn-primary">
                        Update Product
                    </button>
                </form>
            </div>
        </main>
    </div>

    <?php include __DIR__ . '/../includes/footer.php'; ?>
</body>
</html>