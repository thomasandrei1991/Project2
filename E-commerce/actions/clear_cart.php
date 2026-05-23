<?php
    session_start();
    require_once __DIR__ . '/../config/database.php';
    header('Content-Type: application/json');

    if (!isset($_SESSION['user_id'])) {
        echo json_encode(['success' => false]);
        exit;
    }

    $user_id = (int)$_SESSION['user_id'];

    $stmt = mysqli_prepare($conn, "DELETE FROM cart WHERE user_id = ?");
    mysqli_stmt_bind_param($stmt, 'i', $user_id);
    mysqli_stmt_execute($stmt);
    mysqli_stmt_close($stmt);

    echo json_encode([
        'success'    => true,
        'subtotal'   => 0,
        'shipping'   => 0,
        'total'      => 0,
        'cart_count' => 0
    ]);
?>