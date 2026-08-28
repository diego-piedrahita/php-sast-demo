<?php
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../includes/auth.php';

requireLogin();

// Vulnerable: parametro de la URL concatenado directamente en la consulta (SQL Injection + IDOR)
$id = $_GET['id'];
$query = "SELECT * FROM products WHERE id = $id";
$result = mysqli_query($conn, $query);
$product = $result ? mysqli_fetch_assoc($result) : null;
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Detalle de producto - PHP SAST Demo</title>
    <link rel="stylesheet" href="../assets/css/style.css">
</head>
<body>
    <?php include __DIR__ . '/../includes/header.php'; ?>
    <div class="container">
        <?php if ($product): ?>
            <!-- Vulnerable: XSS almacenado, salida sin escapar -->
            <h1><?php echo $product['name']; ?></h1>
            <p><?php echo $product['description']; ?></p>
            <?php if (!empty($product['image_url'])): ?>
                <img src="<?php echo $product['image_url']; ?>" alt="producto" width="250">
            <?php endif; ?>
            <p class="price">$<?php echo $product['price']; ?></p>
        <?php else: ?>
            <!-- Vulnerable: expone el error SQL directamente al usuario -->
            <p class="alert">Producto no encontrado: <?php echo mysqli_error($conn); ?></p>
        <?php endif; ?>
        <a href="list.php">Volver</a>
    </div>
    <?php include __DIR__ . '/../includes/footer.php'; ?>
</body>
</html>
