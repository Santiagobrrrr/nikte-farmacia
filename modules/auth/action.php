<?php
require_once __DIR__ . '/../../config/config.php';
require_once __DIR__ . '/../../includes/session.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: ' . BASE_URL . '/modules/auth/login.php');
    exit;
}

$usuario = trim($_POST['usuario'] ?? '');
$contrasena = trim($_POST['contrasena'] ?? '');

if ($usuario === '' || $contrasena === '') {
    $_SESSION['error'] = 'Debes completar todos los campos.';
    header('Location: ' . BASE_URL . '/modules/auth/login.php');
    exit;
}

if ($usuario === 'admin' && $contrasena === '1234') {
    $_SESSION['usuario_id'] = 1;
    $_SESSION['nombre_usuario'] = 'Admin Nikte';
    $_SESSION['rol_nombre'] = 'administradora';

    header('Location: ' . BASE_URL . '/modules/dashboard/index.php');
    exit;
}

if ($usuario === 'vendedora' && $contrasena === '1234') {
    $_SESSION['usuario_id'] = 2;
    $_SESSION['nombre_usuario'] = 'Vendedora Nikte';
    $_SESSION['rol_nombre'] = 'vendedora';

    header('Location: ' . BASE_URL . '/modules/dashboard/index.php');
    exit;
}

$_SESSION['error'] = 'Credenciales incorrectas.';
header('Location: ' . BASE_URL . '/modules/auth/login.php');
exit;