<?php
session_start();
require_once __DIR__ . "/config/database.php";

$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
if ($id <= 0) { header("Location: index.php"); exit; }

$stmt = mysqli_prepare($conn,
    "SELECT p.*, c.name AS category_name 
     FROM products p
     LEFT JOIN categories c ON p.category_id = c.id
     WHERE p.id = ?");
mysqli_stmt_bind_param($stmt, 'i', $id);
mysqli_stmt_execute($stmt);
$product = mysqli_fetch_assoc(mysqli_stmt_get_result($stmt));
mysqli_stmt_close($stmt);

if (!$product) { header("Location: index.php"); exit; }

// Related products (same category)
$relStmt = mysqli_prepare($conn,
    "SELECT * FROM products 
     WHERE category_id = ? AND id != ? AND stocks > 0 
     LIMIT 4");
mysqli_stmt_bind_param($relStmt, 'ii', $product['category_id'], $id);
mysqli_stmt_execute($relStmt);
$related = mysqli_fetch_all(mysqli_stmt_get_result($relStmt), MYSQLI_ASSOC);
mysqli_stmt_close($relStmt);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($product['name']); ?> — ShopGreen</title>
    <!-- Every page: index.php, register.php, cart.php, product.php, etc. -->
    <link rel="stylesheet" href="<?php echo BASE_URL; ?>assets/css/style.css">
    <link rel="stylesheet" href="<?php echo BASE_URL; ?>assets/css/shop.css">
    <link rel="stylesheet" href="<?php echo BASE_URL; ?>assets/css/cart.css">
</head>
<body>
    <?php include 'includes/navbar.php'; ?>

    <div class="container">

        <!-- Breadcrumb -->
        <div class="breadcrumb">
            <a href="index.php">Home</a> /
            <a href="index.php?category=<?php echo $product['category_id']; ?>">
                <?php echo htmlspecialchars($product['category_name'] ?? 'Products'); ?>
            </a> /
            <span><?php echo htmlspecialchars($product['name']); ?></span>
        </div>

        <!-- Product Detail -->
        <div class="product-detail">
            <div class="detail-image">
                <?php if (!empty($product['image'])): ?>
                    <img src="assets/images/<?php 
                        echo htmlspecialchars($product['image']); ?>"
                        alt="<?php echo htmlspecialchars($product['name']); ?>">
                <?php else: ?>
                    <div class="no-img-large">🛍️</div>
                <?php endif; ?>
            </div>

            <div class="detail-info">
                <span class="product-category">
                    <?php echo htmlspecialchars(
                        $product['category_name'] ?? 'Uncategorized'); ?>
                </span>
                <h1><?php echo htmlspecialchars($product['name']); ?></h1>
                <div class="detail-price">
                    ₱<?php echo number_format($product['price'], 2); ?>
                </div>
                <p class="detail-desc">
                    <?php echo nl2br(htmlspecialchars(
                        $product['description'] ?? 'No description available.')); ?>
                </p>

                <div class="stock-info">
                    <?php if ($product['stocks'] > 0): ?>
                        <span class="in-stock">✓ In Stock 
                            (<?php echo $product['stocks']; ?> left)</span>
                    <?php else: ?>
                        <span class="out-stock">✕ Out of Stock</span>
                    <?php endif; ?>
                </div>

                <?php if ($product['stocks'] > 0): ?>
                    <?php if (isset($_SESSION['user_id'])): ?>
                        <form method="POST" action="actions/add_to_cart.php" 
                              class="detail-add-form">
                            <input type="hidden" name="product_id" 
                                   value="<?php echo $product['id']; ?>">
                            <div class="qty-row">
                                <label>Quantity</label>
                                <div class="qty-control">
                                    <button type="button" 
                                            onclick="changeQty(-1)">−</button>
                                    <input type="number" name="quantity" 
                                           id="qty" value="1" 
                                           min="1" 
                                           max="<?php echo $product['stocks']; ?>">
                                    <button type="button" 
                                            onclick="changeQty(1)">+</button>
                                </div>
                            </div>
                            <div class="detail-actions">
                                <button type="submit" class="btn btn-primary btn-full">
                                    🛒 Add to Cart
                                </button>
                                <a href="index.php" class="btn btn-outline btn-full">
                                    ← Continue Shopping
                                </a>
                            </div>
                        </form>
                    <?php else: ?>
                        <a href="login.php" class="btn btn-primary btn-full">
                            Login to Add to Cart
                        </a>
                    <?php endif; ?>
                <?php endif; ?>
            </div>
        </div>

        <!-- Related Products -->
        <?php if (!empty($related)): ?>
            <div class="related-section">
                <h2>Related Products</h2>
                <div class="product-grid">
                    <?php foreach ($related as $rel): ?>
                        <div class="product-card">
                            <a href="product.php?id=<?php echo $rel['id']; ?>">
                                <div class="product-img-wrap">
                                    <?php if (!empty($rel['image'])): ?>
                                        <img src="assets/images/<?php 
                                            echo htmlspecialchars($rel['image']); ?>"
                                            alt="<?php 
                                            echo htmlspecialchars($rel['name']); ?>">
                                    <?php else: ?>
                                        <div class="no-img">🛍️</div>
                                    <?php endif; ?>
                                </div>
                            </a>
                            <div class="product-info">
                                <h3 class="product-name">
                                    <a href="product.php?id=<?php echo $rel['id']; ?>">
                                        <?php echo htmlspecialchars($rel['name']); ?>
                                    </a>
                                </h3>
                                <div class="product-footer">
                                    <span class="product-price">
                                        ₱<?php echo number_format($rel['price'], 2); ?>
                                    </span>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>
        <?php endif; ?>

    </div>

    <?php include 'includes/footer.php'; ?>
    <script>
        function changeQty(delta) {
            const input = document.getElementById('qty');
            const max   = parseInt(input.max);
            let val     = parseInt(input.value) + delta;
            if (val < 1)   val = 1;
            if (val > max) val = max;
            input.value = val;
        }
    </script>
</body>
</html>