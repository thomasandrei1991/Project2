<?php
session_start();
require_once __DIR__ . '/../config/database.php';
header('Content-Type: application/json');

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false]);
    exit;
}

$user_id = (int)$_SESSION['user_id'];
$cart_id = (int)($_POST['cart_id'] ?? 0);

if ($cart_id <= 0) {
    echo json_encode(['success' => false]);
    exit;
}

$stmt = mysqli_prepare($conn,
    "DELETE FROM cart WHERE id = ? AND user_id = ?");
mysqli_stmt_bind_param($stmt, 'ii', $cart_id, $user_id);
mysqli_stmt_execute($stmt);
mysqli_stmt_close($stmt);

require_once __DIR__ . '/update_cart.php';