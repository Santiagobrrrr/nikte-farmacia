<?php
require_once __DIR__ . '/../../config/config.php';
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../includes/session.php';

$allowed_roles = ['administradora'];
require_once __DIR__ . '/../../includes/role_check.php';

$idProducto = isset($_GET['id']) ? (int) $_GET['id'] : 0;
$accion = $_GET['accion'] ?? '';

if ($idProducto <= 0 || !in_array($accion, ['activar', 'desactivar'], true)) {
    $_SESSION['producto_error'] = 'Acción no válida.';
    header('Location: ' . BASE_URL . '/modules/productos/index.php');
    exit;
}

$nuevoEstado = $accion === 'activar' ? 1 : 0;

try {
    $pdo = getPDO();

    $stmt = $pdo->prepare("
        UPDATE producto
        SET activo = :activo
        WHERE id_producto = :id_producto
    ");

    $stmt->execute([
        'activo' => $nuevoEstado,
        'id_producto' => $idProducto,
    ]);

    if ($stmt->rowCount() === 0) {
        $_SESSION['producto_error'] = 'No se encontró el producto.';
    } else {
        $_SESSION['producto_success'] = $nuevoEstado === 1
            ? 'Producto activado correctamente.'
            : 'Producto desactivado correctamente.';
    }

} catch (Throwable $e) {
    $_SESSION['producto_error'] = 'No se pudo cambiar el estado del producto.';
}

header('Location: ' . BASE_URL . '/modules/productos/index.php');
exit;