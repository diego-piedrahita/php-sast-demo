<?php
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../includes/auth.php';

requireLogin();

$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = $_POST['name'];
    $description = $_POST['description'];
    $imageUrl = $_POST['image_url'];
    $price = $_POST['price'];

    // Vulnerable: concatenacion directa de entrada del usuario (SQL Injection)
    $query = "INSERT INTO products (name, description, image_url, price, created_by) "
        . "VALUES ('$name', '$description', '$imageUrl', $price, " . $_SESSION['user_id'] . ")";

    if (mysqli_query($conn, $query)) {
        $success = 'Producto agregado correctamente';
    } else {
        $error = 'Error al guardar: ' . mysqli_error($conn);
    }
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Agregar producto - PHP SAST Demo</title>
    <link rel="stylesheet" href="../assets/css/style.css">
</head>
<body>
    <?php include __DIR__ . '/../includes/header.php'; ?>
    <div class="container">
        <h1>Agregar producto</h1>
        <?php if ($error): ?>
            <div class="alert"><?php echo $error; ?></div>
        <?php endif; ?>
        <?php if ($success): ?>
            <div class="alert alert-success"><?php echo $success; ?></div>
        <?php endif; ?>
        <!-- Vulnerable: sin token CSRF en el formulario -->
        <form method="POST" action="add.php">
            <label>Nombre</label>
            <input type="text" name="name" required>
            <label>Descripcion</label>
            <textarea name="description"></textarea>
            <label>URL de imagen</label>
            <input type="text" name="image_url">
            <label>Precio</label>
            <input type="text" name="price" required>
            <button type="submit">Guardar</button>
        </form>
    </div>
    <?php include __DIR__ . '/../includes/footer.php'; ?>
</body>
</html>
