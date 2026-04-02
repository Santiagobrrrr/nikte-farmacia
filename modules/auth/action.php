<?php
require_once __DIR__ . '/../../config/config.php';
require_once __DIR__ . '/../../config/database.php';
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

try {
    $pdo = getPDO();

    $sql = "
        SELECT 
            u.id_usuario,
            u.nombre,
            u.usuario,
            u.contrasena,
            r.nombre_rol
        FROM usuario u
        INNER JOIN rol r ON r.id_rol = u.id_rol
        WHERE u.usuario = :usuario
        LIMIT 1
    ";

    $stmt = $pdo->prepare($sql);
    $stmt->execute(['usuario' => $usuario]);

    $user = $stmt->fetch();

    if ($user && password_verify($contrasena, $user['contrasena'])) {
        $_SESSION['usuario_id'] = $user['id_usuario'];
        $_SESSION['nombre_usuario'] = $user['nombre'];
        $_SESSION['rol_nombre'] = $user['nombre_rol'];

        header('Location: ' . BASE_URL . '/modules/dashboard/index.php');
        exit;
    }

    $_SESSION['error'] = 'Credenciales incorrectas.';
    header('Location: ' . BASE_URL . '/modules/auth/login.php');
    exit;

} catch (Throwable $e) {
    $_SESSION['error'] = 'Error al iniciar sesión.';
    header('Location: ' . BASE_URL . '/modules/auth/login.php');
    exit;
}