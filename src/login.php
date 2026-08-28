<?php
require_once __DIR__ . '/config/config.php';

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = $_POST['username'];
    $password = $_POST['password'];

    // Vulnerable: concatenacion directa de entrada del usuario (SQL Injection)
    $query = "SELECT * FROM users WHERE username = '$username' AND password = '" . md5($password) . "'";
    $result = mysqli_query($conn, $query);

    if ($result && mysqli_num_rows($result) > 0) {
        $user = mysqli_fetch_assoc($result);
        // Vulnerable: no se regenera el ID de sesion tras autenticar (session fixation)
        $_SESSION['user_id'] = $user['id'];
        $_SESSION['username'] = $user['username'];
        $_SESSION['is_admin'] = $user['is_admin'];
        header('Location: dashboard.php');
        exit;
    } else {
        // Divulgacion de informacion sensible del motor de base de datos
        $error = 'Login failed: ' . mysqli_error($conn);
    }
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Ingresar - PHP SAST Demo</title>
    <link rel="stylesheet" href="assets/css/style.css">
</head>
<body>
    <div class="container">
        <h1>Iniciar sesion</h1>
        <?php if ($error): ?>
            <!-- Vulnerable: el mensaje de error se imprime sin escapar -->
            <div class="alert"><?php echo $error; ?></div>
        <?php endif; ?>
        <form method="POST" action="login.php">
            <label>Usuario</label>
            <input type="text" name="username" required>
            <label>Contrasena</label>
            <input type="password" name="password" required>
            <button type="submit">Ingresar</button>
        </form>
        <p>No tienes cuenta? <a href="register.php">Registrate aqui</a></p>
    </div>
</body>
</html>
