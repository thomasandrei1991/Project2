<?php
    session_start();
    require_once __DIR__ . '/../../config/database.php';

    if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
        header("Location: " . BASE_URL . "login.php");
        exit;
    }

    $id = (int)($_POST['id'] ?? 0);
    $name = trim($_POST['name'] ?? '');
    $category_id = (int)($_POST['category_id'] ?? 0);
    $description = trim($_POST['description'] ?? '');
    $price = (float)($_POST['price'] ?? 0);
    $stocks = (int)($_POST['stocks'] ?? 0);
    $imageName = null;

    // ── Handle image upload ──
    if (!empty($_FILES['image']['name']) && $_FILES['image']['error'] === 0) {
        $ext = strtolower(pathinfo($_FILES['image']['name'], PATHINFO_EXTENSION));
        $allowed = ['jpg', 'jpeg', 'png', 'gif', 'webp'];
        if (in_array($ext, $allowed)) {
            $newName   = time() . '_' . bin2hex(random_bytes(6)) . '.' . $ext;
            $uploadDir = __DIR__ . '/../../assets/images/';
            if (!is_dir($uploadDir)) mkdir($uploadDir, 0755, true);

            if (move_uploaded_file($_FILES['image']['tmp_name'], $uploadDir . $newName)) {
                $imageName = $newName;
            }
        }
    }

    if ($id > 0) {
        // ── UPDATE ──

        // Fetch current image
        $s = mysqli_prepare($conn, "SELECT image FROM products WHERE id = ?");
        mysqli_stmt_bind_param($s, 'i', $id);
        mysqli_stmt_execute($s);
        $existing = mysqli_fetch_assoc(mysqli_stmt_get_result($s));
        mysqli_stmt_close($s);

        if (!$imageName) {
            // Keep existing image
            $imageName = $existing['image'];
        } else {
            // Delete old image file
            if (!empty($existing['image'])) {
                $old = __DIR__ . '/../../assets/images/' . $existing['image'];
                if (file_exists($old)) unlink($old);
            }
        }

        $stmt = mysqli_prepare($conn, "UPDATE products SET name = ?, category_id = ?, description = ?, price = ?, stocks = ?, image = ? WHERE id = ?");
        mysqli_stmt_bind_param($stmt, 'sisdisi', $name, $category_id, $description, $price, $stocks, $imageName, $id);
        mysqli_stmt_execute($stmt);
        mysqli_stmt_close($stmt);

        header("Location: " . BASE_URL . "admin/edit_product.php?id=$id&success=1");

    } else {
        $stmt = mysqli_prepare($conn, "INSERT INTO products(name, category_id, description, price, stocks, image) VALUES (?, ?, ?, ?, ?, ?)");
        mysqli_stmt_bind_param($stmt, 'sisdis', $name, $category_id, $description, $price, $stocks, $imageName);
        mysqli_stmt_execute($stmt);
        mysqli_stmt_close($stmt);
        header("Location: " . BASE_URL . "admin/products.php?success=added");
    }
    exit;
?>