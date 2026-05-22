<?php
session_start();
require_once __DIR__ . '/../../config/database.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header("Location: " . BASE_URL . "login.php");
    exit;
}

$order_id = (int)$_POST['order_id'];
$status   = $_POST['status'];
$allowed  = ['pending','processing','completed','cancelled'];

if ($order_id > 0 && in_array($status, $allowed)) {
    $stmt = mysqli_prepare($conn,
        "UPDATE orders SET status = ? WHERE id = ?");
    mysqli_stmt_bind_param($stmt, 'si', $status, $order_id);
    mysqli_stmt_execute($stmt);
    mysqli_stmt_close($stmt);
}

header("Location: " . BASE_URL . "admin/orders.php?updated=1");
exit;