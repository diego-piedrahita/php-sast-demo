<?php
// Configuracion insegura: credenciales y secretos embebidos en el codigo fuente (mala practica intencional)
define('DB_HOST', 'localhost');
define('DB_USER', 'root');
define('DB_PASS', 'admin123');
define('DB_NAME', 'php_sast_demo');
define('APP_SECRET', 'supersecret_api_key_2024');

$conn = mysqli_connect(DB_HOST, DB_USER, DB_PASS, DB_NAME);

if (!$conn) {
    // Divulgacion de informacion sensible: expone detalle interno de la conexion
    die('Connection failed: ' . mysqli_connect_error());
}

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
