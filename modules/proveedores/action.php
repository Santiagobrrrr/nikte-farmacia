<?php
$allowed_roles = ['administradora'];
require_once __DIR__ . '/../../includes/role_check.php';
require_once __DIR__ . '/../../config/config.php';
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../includes/session.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: ' . BASE_URL . '/modules/proveedores/index.php');
    exit;
}

$idProveedor = isset($_POST['id_proveedor']) ? (int) $_POST['id_proveedor'] : 0;
$nombre = trim($_POST['nombre'] ?? '');
$telefono = trim($_POST['telefono'] ?? '');
$direccion = trim($_POST['direccion'] ?? '');

$redirect = $idProveedor > 0
    ? BASE_URL . '/modules/proveedores/form.php?id=' . $idProveedor
    : BASE_URL . '/modules/proveedores/form.php';

if ($nombre === '') {
    $_SESSION['proveedor_error'] = 'El nombre del proveedor es obligatorio.';
    header('Location: ' . $redirect);
    exit;
}

try {
    $pdo = getPDO();

    if ($idProveedor > 0) {
        $sql = "UPDATE proveedor
                SET nombre = :nombre,
                    telefono = :telefono,
                    direccion = :direccion
                WHERE id_proveedor = :id_proveedor";

        $stmt = $pdo->prepare($sql);
        $stmt->execute([
            'nombre' => $nombre,
            'telefono' => $telefono !== '' ? $telefono : null,
            'direccion' => $direccion !== '' ? $direccion : null,
            'id_proveedor' => $idProveedor,
        ]);

        $_SESSION['proveedor_success'] = 'Proveedor actualizado correctamente.';
    } else {
        $sql = "INSERT INTO proveedor (nombre, telefono, direccion)
                VALUES (:nombre, :telefono, :direccion)";

        $stmt = $pdo->prepare($sql);
        $stmt->execute([
            'nombre' => $nombre,
            'telefono' => $telefono !== '' ? $telefono : null,
            'direccion' => $direccion !== '' ? $direccion : null,
        ]);

        $_SESSION['proveedor_success'] = 'Proveedor guardado correctamente.';
    }

    header('Location: ' . BASE_URL . '/modules/proveedores/index.php');
    exit;

} catch (Throwable $e) {
    $_SESSION['proveedor_error'] = 'No se pudo guardar la información del proveedor.';
    header('Location: ' . $redirect);
    exit;
}