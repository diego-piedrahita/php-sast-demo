<?php
require_once __DIR__ . '/config/config.php';

// Vulnerable: destruye la sesion sin invalidar la cookie ni regenerar el ID
session_destroy();
header('Location: login.php');
exit;
