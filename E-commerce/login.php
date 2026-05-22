<?php
session_start();
require_once __DIR__ . "/config/database.php"; // ← load FIRST so BASE_URL is defined

if (isset($_SESSION['user_id'])) {
    header("Location: " . BASE_URL . "index.php");
    exit;
}
$error = "";
// ... rest of your code

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    require_once __DIR__ . "/config/database.php";

    $email    = trim($_POST['email']);
    $password = $_POST['password'];

    if (empty($email) || empty($password)) {
        $error = "Email and password are required.";
    } else {
        $stmt = mysqli_prepare($conn, 
            "SELECT id, fullname, password, role FROM users WHERE email = ?");
        mysqli_stmt_bind_param($stmt, 's', $email);
        mysqli_stmt_execute($stmt);
        $result = mysqli_stmt_get_result($stmt);
        $user   = mysqli_fetch_assoc($result);
        mysqli_stmt_close($stmt);

        if ($user && password_verify($password, $user['password'])) {
            $_SESSION['user_id']  = $user['id'];
            $_SESSION['fullname'] = $user['fullname'];
            $_SESSION['role']     = $user['role'];

            // BEFORE: header("Location: index.php");
            // AFTER:
            if ($user['role'] === 'admin') {
                header("Location: " . BASE_URL . "admin/dashboard.php");
            } else {
                header("Location: " . BASE_URL . "index.php");
            }
            exit;
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login — ShopGreen</title>
    <link rel="stylesheet" href="<?php echo BASE_URL; ?>assets/css/style.css">
    <link rel="stylesheet" href="<?php echo BASE_URL; ?>assets/css/auth.css">
</head>
<body>
    <?php include 'includes/navbar.php'; ?>

    <div class="container">
        <div class="auth-wrapper">        <!-- ← needs this wrapper -->
            <div class="auth-card">       <!-- ← and this card -->
                <div class="auth-header">
                    <h2>Welcome Back</h2>
                    <p>Login to your ShopGreen account</p>
                </div>

                <?php if ($error): ?>
                    <div class="alert alert-danger"><?php echo $error; ?></div>
                <?php endif; ?>

                <form method="POST" action="">
                    <div class="form-group">
                        <label>Email Address</label>
                        <input type="email" name="email"
                            value="<?php echo htmlspecialchars($_POST['email'] ?? ''); ?>"
                            placeholder="juan@email.com" required>
                    </div>
                    <div class="form-group">
                        <label>Password</label>
                        <div class="input-eye">
                            <input type="password" name="password"
                                id="password" placeholder="Your password" required>
                            <span class="eye-toggle" 
                                  onclick="togglePass('password')">👁</span>
                        </div>
                    </div>
                    <button type="submit" class="btn btn-primary btn-full">
                        Login
                    </button>
                </form>

                <div class="auth-footer">
                    Don't have an account? 
                    <a href="<?php echo BASE_URL; ?>register.php">Register</a>
                </div>

            </div>  <!-- end auth-card -->
        </div>      <!-- end auth-wrapper -->
    </div>

    <?php include 'includes/footer.php'; ?>
    <script>
        function togglePass(id) {
            const input = document.getElementById(id);
            input.type = input.type === 'password' ? 'text' : 'password';
        }
    </script>
</body>
</html>