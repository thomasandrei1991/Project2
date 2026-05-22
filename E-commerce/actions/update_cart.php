<?php
session_start();
require_once __DIR__ . '/../config/database.php';
header('Content-Type: application/json');

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false]);
    exit;
}

$user_id  = (int)$_SESSION['user_id'];
$cart_id  = (int)($_POST['cart_id']  ?? 0);
$quantity = (int)($_POST['quantity'] ?? 1);

if ($cart_id <= 0 || $quantity < 1) {
    echo json_encode(['success' => false]);
    exit;
}

// Make sure cart item belongs to this user
$stmt = mysqli_prepare($conn,
    "UPDATE cart SET quantity = ?
     WHERE id = ? AND user_id = ?");
mysqli_stmt_bind_param($stmt, 'iii', $quantity, $cart_id, $user_id);
mysqli_stmt_execute($stmt);
mysqli_stmt_close($stmt);

echo json_encode(array_merge(
    ['success' => true],
    getCartTotals($conn, $user_id)
));

function getCartTotals($conn, $user_id) {
    $stmt = mysqli_prepare($conn,
        "SELECT SUM(c.quantity * p.price) AS subtotal,
                SUM(c.quantity)           AS cart_count
         FROM cart c
         JOIN products p ON c.product_id = p.id
         WHERE c.user_id = ?");
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