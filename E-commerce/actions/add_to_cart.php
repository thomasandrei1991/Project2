<?php
    session_start();
    require_once __DIR__ . '/../config/database.php';
    header('Content-Type: application/json');

    if (!isset($_SESSION['user_id'])) {
        echo json_encode(['success' => false, 'message' => 'Not logged in']);
        exit;
    }

    $user_id    = (int)$_SESSION['user_id'];
    $product_id = (int)($_POST['product_id'] ?? 0);
    $quantity   = max(1, (int)($_POST['quantity'] ?? 1));

    if ($product_id <= 0) {
        echo json_encode(['success' => false, 'message' => 'Invalid product']);
        exit;
    }

    // Check if already in cart
    $stmt = mysqli_prepare($conn, "SELECT id, quantity FROM cart WHERE user_id = ? AND product_id = ?");
    mysqli_stmt_bind_param($stmt, 'ii', $user_id, $product_id);
    mysqli_stmt_execute($stmt);
    $existing = mysqli_fetch_assoc(mysqli_stmt_get_result($stmt));
    mysqli_stmt_close($stmt);

    if ($existing) {
        $newQty = $existing['quantity'] + $quantity;
        $stmt   = mysqli_prepare($conn, "UPDATE cart SET quantity = ? WHERE id = ?");
        mysqli_stmt_bind_param($stmt, 'ii', $newQty, $existing['id']);
        mysqli_stmt_execute($stmt);
        mysqli_stmt_close($stmt);
    } else {
        $stmt = mysqli_prepare($conn, "INSERT INTO cart (user_id, product_id, quantity) VALUES (?, ?, ?)");
        mysqli_stmt_bind_param($stmt, 'iii', $user_id, $product_id, $quantity);
        mysqli_stmt_execute($stmt);
        mysqli_stmt_close($stmt);
    }

    // Get updated cart count
    $cstmt = mysqli_prepare($conn, "SELECT SUM(quantity) AS total FROM cart WHERE user_id = ?");
    mysqli_stmt_bind_param($cstmt, 'i', $user_id);
    mysqli_stmt_execute($cstmt);
    $crow  = mysqli_fetch_assoc(mysqli_stmt_get_result($cstmt));
    mysqli_stmt_close($cstmt);

    echo json_encode([
        'success'    => true,
        'cart_count' => (int)($crow['total'] ?? 0)
    ]);
    exit;
?>