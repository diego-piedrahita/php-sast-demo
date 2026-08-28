<?php
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../includes/auth.php';

requireLogin();

// Vulnerable: sin sentencias preparadas (aunque aqui no hay entrada de usuario directa)
$query = "SELECT id, name, description, image_url, price FROM products ORDER BY created_at DESC";
$result = mysqli_query($conn, $query);
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Productos - PHP SAST Demo</title>
    <link rel="stylesheet" href="../assets/css/style.css">
</head>
<body>
    <?php include __DIR__ . '/../includes/header.php'; ?>
    <div class="container">
        <h1>Productos</h1>
        <a href="add.php" class="btn">Agregar producto</a>
        <div class="product-grid">
            <?php while ($row = mysqli_fetch_assoc($result)): ?>
                <div class="product-card">
                    <!-- Vulnerable: XSS almacenado, los campos se imprimen sin htmlspecialchars -->
                    <h3><?php echo $row['name']; ?></h3>
                    <p><?php echo $row['description']; ?></p>
                    <?php if (!empty($row['image_url'])): ?>
                        <img src="<?php echo $row['image_url']; ?>" alt="producto" width="150">
                    <?php endif; ?>
                    <p class="price">$<?php echo $row['price']; ?></p>
                    <a href="view.php?id=<?php echo $row['id']; ?>">Ver detalle</a>
                </div>
            <?php endwhile; ?>
        </div>
    </div>
    <?php include __DIR__ . '/../includes/footer.php'; ?>
</body>
</html>
