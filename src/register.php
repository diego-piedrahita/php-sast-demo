<?php
require_once __DIR__ . '/config/config.php';

$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = $_POST['username'];
    $password = $_POST['password'];

    // Vulnerable: sin validacion/whitelist de entrada y concatenacion directa (SQL Injection)
    $checkQuery = "SELECT id FROM users WHERE username = '$username'";
    $checkResult = mysqli_query($conn, $checkQuery);

    if ($checkResult && mysqli_num_rows($checkResult) > 0) {
        $error = 'El usuario ya existe';
    } else {
        // Vulnerable: hash debil (MD5) para contrasenas en lugar de password_hash()
        $hashedPassword = md5($password);
        $insertQuery = "INSERT INTO users (username, password, is_admin) VALUES ('$username', '$hashedPassword', 0)";

        if (mysqli_query($conn, $insertQuery)) {
            // Vulnerable: XSS reflejado al mostrar el username sin escapar
            $success = 'Bienvenido, ' . $username . '! Registro exitoso.';
        } else {
            $error = 'Registration failed: ' . mysqli_error($conn);
        }
    }
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Registro - PHP SAST Demo</title>
    <link rel="stylesheet" href="assets/css/style.css">
</head>
<body>
    <div class="container">
        <h1>Crear cuenta</h1>
        <?php if ($error): ?>
            <div class="alert"><?php echo $error; ?></div>
        <?php endif; ?>
        <?php if ($success): ?>
            <div class="alert alert-success"><?php echo $success; ?></div>
        <?php endif; ?>
        <form method="POST" action="register.php">
            <label>Usuario</label>
            <input type="text" name="username" required>
            <label>Contrasena</label>
            <input type="password" name="password" required>
            <button type="submit">Registrarse</button>
        </form>
        <p>Ya tienes cuenta? <a href="login.php">Ingresa aqui</a></p>
    </div>
</body>
</html>
