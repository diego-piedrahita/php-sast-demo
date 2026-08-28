<header>
    <nav class="navbar">
        <a href="dashboard.php" class="brand">PHP SAST Demo</a>
        <div class="nav-links">
            <a href="products/list.php">Productos</a>
            <?php if (isset($_SESSION['username'])): ?>
                <!-- Vulnerable: el username se imprime sin escapar (XSS reflejado/almacenado) -->
                <span>Hola, <?php echo $_SESSION['username']; ?></span>
                <a href="logout.php">Salir</a>
            <?php else: ?>
                <a href="login.php">Ingresar</a>
                <a href="register.php">Registrarse</a>
            <?php endif; ?>
        </div>
    </nav>
</header>
