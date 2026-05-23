<?php
    session_start();
    require_once __DIR__ . '/../../config/database.php';

    if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
        header("Location: " . BASE_URL . "login.php");
        exit;
    }

    $id = (int)($_GET['id'] ?? 0);
    if ($id <= 0) {
        header("Location: " . BASE_URL . "admin/products.php");
        exit;
    }

    // Delete image file first
    $stmt = mysqli_prepare($conn, "SELECT image FROM products WHERE id = ?");
    mysqli_stmt_bind_param($stmt, 'i', $id);
    mysqli_stmt_execute($stmt);
    $row = mysqli_fetch_assoc(mysqli_stmt_get_result($stmt));
    mysqli_stmt_close($stmt);

    if (!empty($row['image'])) {
        $path = __DIR__ . '/../../assets/images/' . $row['image'];
        if (file_exists($path)) unlink($path);
    }

    $stmt = mysqli_prepare($conn, "DELETE FROM products WHERE id = ?");
    mysqli_stmt_bind_param($stmt, 'i', $id);
    mysqli_stmt_execute($stmt);
    mysqli_stmt_close($stmt);

    header("Location: " . BASE_URL . "admin/products.php?success=deleted");
    exit;
    
?>