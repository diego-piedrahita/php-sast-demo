<?php
require_once __DIR__ . '/config/config.php';
require_once __DIR__ . '/includes/auth.php';

requireLogin();
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Dashboard - PHP SAST Demo</title>
    <link rel="stylesheet" href="assets/css/style.css">
</head>
<body>
    <?php include __DIR__ . '/includes/header.php'; ?>
    <div class="container">
        <h1>Bienvenido al panel</h1>
        <p><a href="products/list.php">Ver productos</a> | <a href="products/add.php">Agregar producto</a></p>
    </div>
    <?php include __DIR__ . '/includes/footer.php'; ?>
</body>
</html>
