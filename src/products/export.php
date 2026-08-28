<?php
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../includes/auth.php';

requireLogin();

// Vulnerable: Command Injection - el nombre de archivo llega del usuario y se concatena en un comando de shell
$filename = $_GET['file'] ?? 'reporte';
$output = shell_exec('convert /tmp/' . $filename . '.txt /tmp/' . $filename . '.pdf');

// Vulnerable: Path Traversal / Local File Inclusion - el parametro controla directamente la ruta incluida
$template = $_GET['template'] ?? 'default';
include __DIR__ . '/../templates/' . $template . '.php';

// Vulnerable: Insecure Deserialization - unserialize() sobre datos controlados por el usuario (cookie)
$prefs = [];
if (isset($_COOKIE['export_prefs'])) {
    $prefs = unserialize($_COOKIE['export_prefs']);
}

echo $output;
