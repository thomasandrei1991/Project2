<?php
    declare(strict_types=1);

    session_start();
    require_once __DIR__ . '/../config/database.php';
    header('Content-Type: application/json');

    /**
     * @psalm-suppress UndefinedVariable
     * @var mysqli $conn
     */
    /** @var int $user_id */

    if (!isset($_SESSION['user_id'])) {
        echo json_encode(['success' => false]);
        exit;
    }

    $user_id  = (int)$_SESSION['user_id'];
    $cart_id  = (int)($_POST['cart_id'] ?? 0);
    $quantity = (int)($_POST['quantity'] ?? 1);

    // Optional stock validation (best-effort)
    // If the cart row no longer exists or stock is missing, we'll fall back to the current quantity checks.
    $stockStmt = mysqli_prepare($conn, "SELECT p.stocks FROM cart c JOIN products p ON c.product_id = p.id WHERE c.id = ? AND c.user_id = ?");
    mysqli_stmt_bind_param($stockStmt, 'ii', $cart_id, $user_id);
    mysqli_stmt_execute($stockStmt);
    $stockRow = mysqli_fetch_assoc(mysqli_stmt_get_result($stockStmt));
    mysqli_stmt_close($stockStmt);

    if ($quantity < 1) {
        echo json_encode(['success' => false]);
        exit;
    }

    if ($stockRow && isset($stockRow['stocks']) && $quantity > (int)$stockRow['stocks']) {
        $quantity = (int)$stockRow['stocks'];

        // If user tried to reduce below 1 after stock cap, treat as invalid.
        if ($quantity < 1) {
            echo json_encode(['success' => false]);
            exit;
        }
    }


    if ($cart_id <= 0 || $quantity < 1) {
        echo json_encode(['success' => false]);
        exit;
    }

    // Make sure cart item belongs to this user
    $stmt = mysqli_prepare($conn, "UPDATE cart SET quantity = ? WHERE id = ? AND user_id = ?");
    mysqli_stmt_bind_param($stmt, 'iii', $quantity, $cart_id, $user_id);
    mysqli_stmt_execute($stmt);
    mysqli_stmt_close($stmt);

    echo json_encode(array_merge(
        ['success' => true],
        getCartTotals($conn, $user_id)
    ));

    function getCartTotals(mysqli $conn, int $user_id) {
        $stmt = mysqli_prepare($conn, "SELECT SUM(c.quantity * p.price) AS subtotal, SUM(c.quantity) AS cart_count FROM cart c JOIN products p ON c.product_id = p.id WHERE c.user_id = ?");
        mysqli_stmt_bind_param($stmt, 'i', $user_id);
        mysqli_stmt_execute($stmt);
        $row      = mysqli_fetch_assoc(mysqli_stmt_get_result($stmt));
        mysqli_stmt_close($stmt);
        $subtotal = (float)($row['subtotal']   ?? 0);
        $shipping = $subtotal >= 1000 ? 0 : ($subtotal > 0 ? 80 : 0);

        return [
            'subtotal'   => $subtotal,
            'shipping'   => $shipping,
            'total'      => $subtotal + $shipping,
            'cart_count' => (int)($row['cart_count'] ?? 0)
        ];
    }
?>