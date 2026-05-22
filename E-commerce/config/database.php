<?php
    $host     = "localhost";
    $username = "root";
    $password = "";
    $database = "ecommerce_db";

    $conn = mysqli_connect($host, $username, $password, $database);

    if (!$conn) {
        die("Connection failed: " . mysqli_connect_error());
    }
    define('BASE_URL', '/Project2/E-commerce/');
?>