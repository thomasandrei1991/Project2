<?php
    session_start();
    session_unset();
    session_destroy();

    // Load BASE_URL for the redirect
    require_once __DIR__ . '/../config/database.php';
    header("Location: " . BASE_URL . "login.php");
    exit;
?>