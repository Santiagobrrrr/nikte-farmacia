<?php
$allowed_roles = ['administradora'];
require_once __DIR__ . '/../../includes/role_check.php';
require_once __DIR__ . '/../../config/config.php';
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../includes/session.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: ' . BASE_URL . '/modules/compras/index.php');
    exit;
}

$fechaCompra = trim($_POST['fecha_compra'] ?? '');
$idProveedor = isset($_POST['id_proveedor']) ? (int) $_POST['id_proveedor'] : 0;
$idProducto = isset($_POST['id_producto']) ? (int) $_POST['id_producto'] : 0;
$codigoLote = trim($_POST['codigo_lote'] ?? '');
$fechaVencimiento = trim($_POST['fecha_vencimiento'] ?? '');
$costoUnitario = trim($_POST['costo_unitario'] ?? '');
$cantidad = trim($_POST['cantidad'] ?? '');
$idUsuario = (int) ($_SESSION['usuario_id'] ?? 0);

if (
    $fechaCompra === '' ||
    $idProveedor <= 0 ||
    $idProducto <= 0 ||
    $codigoLote === '' ||
    $fechaVencimiento === '' ||
    $costoUnitario === '' ||
    $cantidad === '' ||
    $idUsuario <= 0
) {
    $_SESSION['compra_error'] = 'Debes completar todos los campos obligatorios.';
    header('Location: ' . BASE_URL . '/modules/compras/form.php');
    exit;
}

if (!is_numeric($costoUnitario) || (float) $costoUnitario < 0) {
    $_SESSION['compra_error'] = 'El costo unitario no es válido.';
    header('Location: ' . BASE_URL . '/modules/compras/form.php');
    exit;
}

if (!is_numeric($cantidad) || (int) $cantidad <= 0) {
    $_SESSION['compra_error'] = 'La cantidad debe ser mayor que cero.';
    header('Location: ' . BASE_URL . '/modules/compras/form.php');
    exit;
}

if ($fechaVencimiento < $fechaCompra) {
    $_SESSION['compra_error'] = 'La fecha de vencimiento no puede ser anterior a la fecha de compra.';
    header('Location: ' . BASE_URL . '/modules/compras/form.php');
    exit;
}

try {
    $pdo = getPDO();
    $pdo->beginTransaction();

    $stmt = $pdo->prepare("SELECT id_proveedor FROM proveedor WHERE id_proveedor = :id_proveedor LIMIT 1");
    $stmt->execute(['id_proveedor' => $idProveedor]);
    if (!$stmt->fetch()) {
        throw new Exception('Proveedor no válido.');
    }

    $stmt = $pdo->prepare("SELECT id_producto FROM producto WHERE id_producto = :id_producto LIMIT 1");
    $stmt->execute(['id_producto' => $idProducto]);
    if (!$stmt->fetch()) {
        throw new Exception('Producto no válido.');
    }

    $subtotal = (float) $costoUnitario * (int) $cantidad;

    $sqlCompra = "INSERT INTO compra (
                    fecha_compra,
                    id_proveedor,
                    id_usuario,
                    total_compra
                 ) VALUES (
                    :fecha_compra,
                    :id_proveedor,
                    :id_usuario,
                    :total_compra
                 )";

    $stmt = $pdo->prepare($sqlCompra);
    $stmt->execute([
        'fecha_compra' => $fechaCompra,
        'id_proveedor' => $idProveedor,
        'id_usuario' => $idUsuario,
        'total_compra' => $subtotal,
    ]);

    $idCompra = (int) $pdo->lastInsertId();

    $sqlLote = "INSERT INTO lote (
                    codigo_lote,
                    id_producto,
                    fecha_vencimiento,
                    costo_unitario,
                    cantidad_actual,
                    fecha_ingreso
                ) VALUES (
                    :codigo_lote,
                    :id_producto,
                    :fecha_vencimiento,
                    :costo_unitario,
                    :cantidad_actual,
                    :fecha_ingreso
                )";

    $stmt = $pdo->prepare($sqlLote);
    $stmt->execute([
        'codigo_lote' => $codigoLote,
        'id_producto' => $idProducto,
        'fecha_vencimiento' => $fechaVencimiento,
        'costo_unitario' => (float) $costoUnitario,
        'cantidad_actual' => (int) $cantidad,
        'fecha_ingreso' => $fechaCompra,
    ]);

    $idLote = (int) $pdo->lastInsertId();

    $sqlDetalle = "INSERT INTO detallecompra (
                    id_compra,
                    id_producto,
                    id_lote,
                    cantidad,
                    costo_unitario,
                    subtotal
                  ) VALUES (
                    :id_compra,
                    :id_producto,
                    :id_lote,
                    :cantidad,
                    :costo_unitario,
                    :subtotal
                  )";

    $stmt = $pdo->prepare($sqlDetalle);
    $stmt->execute([
        'id_compra' => $idCompra,
        'id_producto' => $idProducto,
        'id_lote' => $idLote,
        'cantidad' => (int) $cantidad,
        'costo_unitario' => (float) $costoUnitario,
        'subtotal' => $subtotal,
    ]);

    $pdo->commit();

    $_SESSION['compra_success'] = 'Compra registrada correctamente.';
    header('Location: ' . BASE_URL . '/modules/compras/index.php');
    exit;

} catch (Throwable $e) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }

    $_SESSION['compra_error'] = 'No se pudo registrar la compra. Verifica que el código de lote no esté repetido.';
    header('Location: ' . BASE_URL . '/modules/compras/form.php');
    exit;
}