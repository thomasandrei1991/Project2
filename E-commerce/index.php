<?php
    session_start();
    require_once __DIR__ . "/config/database.php";

    // Fetch categories
    $catResult  = mysqli_query($conn, "SELECT * FROM categories ORDER BY name");
    $categories = mysqli_fetch_all($catResult, MYSQLI_ASSOC);

    // Filters
    $search      = isset($_GET['search'])   ? trim($_GET['search'])   : '';
    $category_id = isset($_GET['category']) ? (int)$_GET['category'] : 0;
    $sort        = isset($_GET['sort'])     ? $_GET['sort']           : 'newest';

    // Build query
    $where  = "WHERE p.stocks > 0";
    $params = [];
    $types  = '';

    if ($search !== '') {
        $where   .= " AND (p.name LIKE ? OR p.description LIKE ?)";
        $like     = "%$search%";
        $params[] = $like;
        $params[] = $like;
        $types   .= 'ss';
    }

    if ($category_id > 0) {
        $where   .= " AND p.category_id = ?";
        $params[] = $category_id;
        $types   .= 'i';
    }

    $orderBy = match($sort) {
        'price_asc'  => "p.price ASC",
        'price_desc' => "p.price DESC",
        'name'       => "p.name ASC",
        default      => "p.created_at DESC"
    };

    $sql  = "SELECT p.*, c.name AS category_name FROM products p LEFT JOIN categories c ON p.category_id = c.id $where ORDER BY $orderBy";
    $stmt = mysqli_prepare($conn, $sql);

    if (!empty($params)) {
        mysqli_stmt_bind_param($stmt, $types, ...$params);
    }

    mysqli_stmt_execute($stmt);
    $result   = mysqli_stmt_get_result($stmt);
    $products = mysqli_fetch_all($result, MYSQLI_ASSOC);
    mysqli_stmt_close($stmt);

    // Cart count for navbar badge
    $cartCount = 0;
    if (isset($_SESSION['user_id'])) {
        $uid  = $_SESSION['user_id'];
        $cstmt = mysqli_prepare($conn, 
            "SELECT SUM(quantity) AS total FROM cart WHERE user_id = ?");
        mysqli_stmt_bind_param($cstmt, 'i', $uid);
        mysqli_stmt_execute($cstmt);
        $crow      = mysqli_fetch_assoc(mysqli_stmt_get_result($cstmt));
        $cartCount = $crow['total'] ?? 0;
        mysqli_stmt_close($cstmt);
    }
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>ShopGreen — Home</title>
    <!-- Every page: index.php, register.php, cart.php, product.php, etc. -->
    <!-- Every page: index.php, register.php, cart.php, product.php, etc. -->
    <link rel="stylesheet" href="<?php echo BASE_URL; ?>assets/css/style.css">
    <link rel="stylesheet" href="<?php echo BASE_URL; ?>assets/css/shop.css">
</head>
<body>
    <?php include 'includes/navbar.php'; ?>

    <!-- Hero Banner -->
    <div class="hero">
        <div class="hero-content">
            <h1>Fresh Deals, Every Day 🌿</h1>
            <p>Shop the best products across all categories</p>
            <form method="GET" action="index.php" class="hero-search">
                <input type="text" name="search" value="<?php echo htmlspecialchars($search); ?>" placeholder="Search for products...">
                <button type="submit" class="btn btn-primary">Search</button>
            </form>
        </div>
    </div>

    <div class="container">

        <!-- Filter Bar -->
        <div class="filter-bar">
            <div class="filter-categories">
                <a href="index.php" class="cat-pill <?php echo $category_id === 0 ? 'active' : ''; ?>">All</a>
                <?php foreach ($categories as $cat): ?>
                    <a href="index.php?category=<?php echo $cat['id'];
                        echo $search ? '&search='.urlencode($search) : ''; ?>"
                        class="cat-pill <?php echo $category_id === (int)$cat['id'] ? 'active' : ''; ?>">
                        <?php echo htmlspecialchars($cat['name']); ?>
                    </a>
                <?php endforeach; ?>
            </div>
            <div class="filter-sort">
                <select onchange="window.location=this.value">
                    <?php
                    $base = "index.php?sort=";
                    if ($search)      $base .= "&search=".urlencode($search);
                    if ($category_id) $base .= "&category=$category_id";
                    $sorts = [
                        'newest'     => 'Newest',
                        'price_asc'  => 'Price: Low to High',
                        'price_desc' => 'Price: High to Low',
                        'name'       => 'Name A–Z'
                    ];
                    foreach ($sorts as $val => $label):
                        $selected = $sort === $val ? 'selected' : '';
                    ?>
                        <option value="index.php?sort=<?php echo $val;
                            echo $search ? '&search='.urlencode($search) : '';
                            echo $category_id ? '&category='.$category_id : ''; ?>"
                            <?php echo $selected; ?>>
                            <?php echo $label; ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
        </div>

        <!-- Results info -->
        <div class="results-info">
            <?php if ($search): ?>
                <p>Showing results for "<strong><?php 
                    echo htmlspecialchars($search); ?></strong>"
                   — <?php echo count($products); ?> item(s) found
                   <a href="index.php" class="clear-search">✕ Clear</a>
                </p>
            <?php else: ?>
                <p><?php echo count($products); ?> product(s) available</p>
            <?php endif; ?>
        </div>

        <!-- Product Grid -->
        <?php if (empty($products)): ?>
            <div class="empty-state">
                <div class="empty-icon">🔍</div>
                <h3>No products found</h3>
                <p>Try a different search or category</p>
                <a href="index.php" class="btn btn-outline">Browse All</a>
            </div>
        <?php else: ?>
            <div class="product-grid">
                <?php foreach ($products as $product): ?>
                    <div class="product-card">
                        <a href="product.php?id=<?php echo $product['id']; ?>">
                            <div class="product-img-wrap">
                                <?php if (!empty($product['image'])): ?>
                                    <img src="assets/images/<?php 
                                        echo htmlspecialchars($product['image']); ?>"
                                        alt="<?php echo htmlspecialchars($product['name']); ?>">
                                <?php else: ?>
                                    <div class="no-img">🛍️</div>
                                <?php endif; ?>
                            </div>
                        </a>
                        <div class="product-info">
                            <span class="product-category">
                                <?php echo htmlspecialchars(
                                    $product['category_name'] ?? 'Uncategorized'); ?>
                            </span>
                            <h3 class="product-name">
                                <a href="product.php?id=<?php echo $product['id']; ?>">
                                    <?php echo htmlspecialchars($product['name']); ?>
                                </a>
                            </h3>
                            <div class="product-footer">
                                <span class="product-price">
                                    ₱<?php echo number_format($product['price'], 2); ?>
                                </span>
                                <?php if (isset($_SESSION['user_id'])): ?>
                                    <form method="POST" 
                                          action="actions/add_to_cart.php"
                                          class="quick-add-form">
                                        <input type="hidden" 
                                               name="product_id" 
                                               value="<?php echo $product['id']; ?>">
                                        <input type="hidden" name="quantity" value="1">
                                        <button type="submit" class="btn-cart"
                                            title="Add to Cart">+🛒</button>
                                    </form>
                                <?php else: ?>
                                    <a href="login.php" class="btn-cart" 
                                       title="Login to add to cart">+🛒</a>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>

    <?php include 'includes/footer.php'; ?>

    <script>
        // Update cart badge on page load
        document.addEventListener('DOMContentLoaded', () => {
            const badge = document.getElementById('cart-count');
            if (badge) badge.textContent = '<?php echo $cartCount; ?>';
        });

        // Quick-add AJAX so page doesn't reload
        document.querySelectorAll('.quick-add-form').forEach(form => {
            form.addEventListener('submit', async (e) => {
                e.preventDefault();
                const res  = await fetch(form.action, {
                    method: 'POST',
                    body: new FormData(form)
                });
                const data = await res.json();
                if (data.success) {
                    const badge = document.getElementById('cart-count');
                    if (badge) badge.textContent = data.cart_count;
                    const btn = form.querySelector('.btn-cart');
                    btn.textContent = '✓';
                    setTimeout(() => btn.textContent = '+🛒', 1200);
                }
            });
        });
    </script>
</body>
</html>