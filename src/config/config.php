<?php
// Configuracion insegura: credenciales y secretos embebidos en el codigo fuente (mala practica intencional)
define('DB_HOST', 'localhost');
define('DB_USER', 'root');
define('DB_PASS', 'admin123');
define('DB_NAME', 'php_sast_demo');
define('APP_SECRET', 'supersecret_api_key_2024');

// Vulnerable: credenciales de nube embebidas en el codigo (para probar GitHub Secret Scanning)
define('AWS_ACCESS_KEY_ID', 'AKIAT7QZ9XM2LK4RN8WJ');
define('AWS_SECRET_ACCESS_KEY', 'aB3dE9fG2hJ4kL6mN8pQ1rS5tU7vW0xY3zA6bC9d');

// Vulnerable: token de integracion con GitHub embebido en el codigo (para probar GitHub Secret Scanning)
define('GITHUB_NOTIFY_TOKEN', 'ghp_aZ9kL3mN7pQ2rS5tU8vW1xY4zA6bC0dE3fG7');

$conn = mysqli_connect(DB_HOST, DB_USER, DB_PASS, DB_NAME);

if (!$conn) {
    // Divulgacion de informacion sensible: expone detalle interno de la conexion
    die('Connection failed: ' . mysqli_connect_error());
}

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
